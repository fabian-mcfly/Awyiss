<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Authentication\IdentityInterface;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Authorization\IdentityGroupPermissionInterface;
use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Model\Enum\CustomerGroupAccessType;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * Behavior for managing customer group access settings and assignments.
 * Controls frontend visibility/access per entity via customer groups (all_groups, hide_on_login, specific_groups).
 * Also manages customer group assignments for entities when access type is 'specific_groups'.
 */
class CustomerGroupAccessSettingBehavior extends Behavior implements PropertyMarshalInterface {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var array
	 */
	protected static array $pageRoles;


	/**
	 * Default config
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'enabled' => true,
		'implementedEvents' => [
			'beforeFind',
			'beforeSave',
		],
		'implementedFinders' => [
			'accessible' => 'findAccessible',
		],
		'implementedMethods' => [
			'getCustomerGroupAccessSettings' => 'getCustomerGroupAccessSettings',
		],
		'referenceName' => '',
		'skip' => false,
		'strategy' => 'select',
		'tableLocator' => null,
	];
	/**
	 * Instance of Table responsible for access settings
	 *
	 * @var \Awyiss\Model\Table
	 */
	protected Table $accessSettingsTable;
	/**
	 * Instance of Table responsible for customer group assignments
	 *
	 * @var \Awyiss\Model\Table
	 */
	protected Table $assignmentsTable;


	/**
	 * @inheritDoc
	 * @param Table $table
	 * @param array $config
	 */
	public function __construct(Table $table, array $config = []) {
		$config += [
			'referenceName' => $this->getScope($table),
			'tableLocator' => $table->associations()->getTableLocator(),
		];

		parent::__construct($table, $config);
	}


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->_tableLocator = $this->getConfig('tableLocator');
		$this->accessSettingsTable = $this->getTableLocator()->get('CustomerGroupAccessSettings', ['allowFallbackClass' => false]);
		$this->assignmentsTable = $this->getTableLocator()->get('CustomerGroupAssignments', ['allowFallbackClass' => false]);

		if (!isset(static::$pageRoles)) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');
			foreach ($pageRoleEnum::cases() as $pageRole) {
				static::$pageRoles[] = Inflector::pluralize(Inflector::underscore($pageRole->name));
			}
		}

		$this->setupAssociations(in_array($this->getConfig('referenceName'), static::$pageRoles, true));
	}


	/**
	 * Setup associations for customer group access settings and assignments.
	 *
	 * @param bool $forPages Whether the associations are being set up for pages.
	 * @return void
	 */
	protected function setupAssociations(bool $forPages = false): void {
		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $this->_table->getEntityClass();

		if ($forPages) {
			$accessSettingConditions['CustomerGroupAccessSettings.scope IN'] = static::$pageRoles;
			$assignmentConditions['CustomerGroupAssignments.scope IN'] = static::$pageRoles;
		}
		else {
			$accessSettingConditions['CustomerGroupAccessSettings.scope'] = $this->getConfig('referenceName');
			$assignmentConditions['CustomerGroupAssignments.scope'] = $this->getConfig('referenceName');
		}

		// Setup customer group access settings association
		$this->_table->hasOne('CustomerGroupAccessSettings', [
			'cascadeCallbacks' => true,
			'conditions' => $accessSettingConditions,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => 'customerGroupAccessSettings',
			'saveStrategy' => 'replace',
		]);

		// Setup customer group assignments association
		$this->_table->hasMany('CustomerGroupAssignments', [
			'cascadeCallbacks' => true,
			'conditions' => $assignmentConditions,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => 'customerGroupAssignments',
			'saveStrategy' => 'replace',
			'strategy' => $this->getConfig('strategy'),
		]);

		$entityClass::addFieldMapping('customer_group_access_settings', 'customerGroupAccessSettings');
		$entityClass::addFieldMapping('customer_group_assignments', 'customerGroupAssignments');
	}


	/**
	 * @param EventInterface $event
	 * @param SelectQuery $query
	 * @param ArrayObject $options
	 * @param bool $primary
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'customerGroupAccessSettings', []));
		// Skip if explicitly skipped
		if ($queryOptions['skip'] === true) {
			return;
		}

		if (
			$query->clause('select') &&
			!$query->isAutoFieldsEnabled() &&
			!in_array('id', $query->clause('select'), true) &&
			!in_array($query->aliasField('id'), $query->clause('select'), true)
		) {
			$query->select($query->aliasField('id'));
		}

		$query->contain([
			'CustomerGroupAccessSettings',
			'CustomerGroupAssignments' => [
				'CustomerGroups',
			],
		]);
	}


	/**
	 * Handle saving of access settings and customer group assignments before entity is saved.
	 * Only saves assignments when access type is 'specific_groups'.
	 *
	 * @param \Cake\Event\EventInterface $event The event
	 * @param \Cake\Datasource\EntityInterface $entity The entity
	 * @param \ArrayObject $options Save options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		// Skip if not enabled or explicitly skipped
		if (
			!$this->getConfig('enabled') ||
			($options['customerGroupAssignments']['skip'] ?? false) === true
		) {
			return;
		}

		// If no customer group assignments are set, skip the processing
		if (!$entity->has('customerGroupAssignments')) {
			return;
		}

		/**
		 * Get the access setting to check the access type
		 *
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$accessSetting = $entity->has('customerGroupAccessSettings') ? $entity->customerGroupAccessSettings : null;

		// Only save assignments if access type is 'specific_groups'
		if ($accessSetting?->accessType !== CustomerGroupAccessType::SpecificGroups) {
			// Clear assignments if not specific_groups
			$entity->set('customerGroupAssignments', []);

			return;
		}

		/**
		 * Make sure assignments in the wrong format are removed from the entity.
		 * This happens when no customer group was assigned but an assignment is part of the entity/patched data.
		 */
		$assignments = $entity->get('customerGroupAssignments') ?: [];
		foreach ($assignments as $key => $assignment) {
			if (!is_numeric($key) || !$assignment instanceof CustomerGroupAssignment) {
				unset($assignments[ $key ]);
			}
		}

		$entity->set('customerGroupAssignments', $assignments);

		if (($options['isCopy'] ?? false) === true) {
			// If the entity is a copy, we need to set the assignments as new
			foreach ($assignments as $assignment) {
				if (!$assignment instanceof CustomerGroupAssignment) {
					continue;
				}

				$assignment->unset('id');
				$assignment->setNew(true);
			}
		}
	}


	/**
	 * Add a formatter that will discard entities not accessible to the current customer.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Authentication\IdentityInterface|\Awyiss\Authorization\IdentityGroupPermissionInterface|null $identity The current identity
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findAccessible(SelectQuery $query, IdentityInterface|IdentityGroupPermissionInterface|null $identity = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}


		if (!$identity) {
			$identity = $this->getConfig('identity');
			$identity ??= Router::getRequest()?->getAttribute(Awyiss::getRealm() . 'Identity');
			$identity = $identity?->getOriginalData();
		}

		// Ensure we have the original Customer entity
		if ($identity instanceof IdentityInterface) {
			$identity = $identity->getOriginalData();
		}

		// Apply a mapReduce call that'll remove all entities from the query, except those that are re-added using the `emit()`-method
		$query->mapReduce(function (EntityInterface $entity, int $key, MapReduce $mapReduce) use ($identity): void {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			if ($entity->isAccessibleBy($identity)) {
				$mapReduce->emit($entity, $key);
			}
		});

		return $query;
	}


	/**
	 * Get the access setting for a specific entity
	 *
	 * @param string|int|null $entityId The entity ID
	 * @return \Awyiss\Model\Entity\CustomerGroupAccessSetting|null
	 */
	public function getCustomerGroupAccessSettings(int|string|null $entityId = null): ?CustomerGroupAccessSetting {
		return $this->accessSettingsTable
			->find()
			->where([
				'scope' => $this->getScope($this->table()),
				'foreign_key' => $entityId,
				'deleted' => false,
			])
			->first();
	}


	/**
	 * @param \Cake\ORM\Marshaller $marshaller
	 * @param array $map
	 * @param array $options
	 * @return array
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		$result = [];

		// Handle customer group access settings
		if ($this->getConfig('enabled') && ($options['customerGroupAccessSettings'] ?? true) !== false) {
			$result['customer_group_access_settings'] = function (array $values, EntityInterface $entity) {
				if (!$values) {
					return null;
				}

				/** @var \Awyiss\Model\Entity\CustomerGroupAccessSetting $accessSettings */
				$accessSettings = $entity->customerGroupAccessSettings ?? $this->accessSettingsTable->newDefaultEntity();

				$accessSettingData = [
					'scope' => $this->getConfig('referenceName'),
					'accessType' => $values['access_type'] ?? $values['accessType'] ?? null,
				];

				if (!$accessSettingData['accessType']) {
					if (!$accessSettings->isNew()) {
						$this->accessSettingsTable->delete($accessSettings);
					}

					return null;
				}

				$settingMarshaller = $this->accessSettingsTable->marshaller();
				$settingMarshaller->merge($accessSettings, $accessSettingData);

				$settingErrors = $accessSettings->getErrors();
				if ($settingErrors) {
					$entity->setErrors(['customerGroupAccessSettings' => $settingErrors]);
				}

				$isDirty = $accessSettings->isNew() || (
					$accessSettings->hasOriginal('accessType') &&
					$accessSettings->accessType !== $accessSettings->getOriginal('accessType')
				);

				$entity->setDirty('customerGroupAccessSettings', $isDirty);

				return $accessSettings;
			};
		}

		// Handle customer group assignments
		if (!$this->getConfig('enabled') || ($options['customerGroupAssignments'] ?? true) === false) {
			return $result;
		}

		unset($options['associated']);
		$options['fields'] = [
			'id',
			'customerGroupId',
			'scope',
			'foreignKey',
		];

		$result['customer_group_assignments'] = function (array $values, EntityInterface $entity) use ($options): array {
			/**
			 * @var array<\Awyiss\Model\Entity\CustomerGroupAssignment> $customerGroupAssignments
			 */
			$customerGroupAssignments = [];

			$errors = [];
			$marshaller = $this->assignmentsTable->marshaller();

			foreach ($values as $assignmentData) {
				// Handle both simple ID values and array data with id
				if (is_array($assignmentData)) {
					$customerGroupId = $assignmentData['customer_group_id'] ?? $assignmentData['customerGroupId'] ?? null;
					$assignmentId = $assignmentData['id'] ?? null;
				}
				else {
					$customerGroupId = $assignmentData;
					$assignmentId = null;
				}

				if (empty($customerGroupId)) {
					continue;
				}

				/** @var \Awyiss\Model\Entity\CustomerGroupAssignment $assignment */
				$assignment = $this->assignmentsTable->newDefaultEntity();


				if (!empty($assignmentId)) {
					$assignment->id = $assignmentId;
				}

				$data = [
					'customerGroupId' => (int)$customerGroupId,
					'scope' => $this->getConfig('referenceName'),
				];

				$marshaller->merge($assignment, $data, $options);

				$dataErrors = $assignment->getErrors();
				if ($dataErrors) {
					$errors[] = $dataErrors;
				}

				if ($assignment->id) {
					$assignment->unset('createdBy');
					$assignment->unset('createdOn');
				}

				$customerGroupAssignments[] = $assignment;
			}

			//Set errors into the root entity, so validation errors match the original form data position.
			if ($errors) {
				$entity->setErrors(['customerGroupAssignments' => $errors]);
			}

			$entity->setDirty('customerGroupAssignments');

			return $customerGroupAssignments;
		};

		return $result;
	}


	/**
	 * @param Table $table The table class to get a reference name for.
	 * @return string
	 */
	protected function getScope(Table $table): string {
		$name = namespaceSplit($table::class);
		$name = substr((string)end($name), 0, -5);

		if (empty($name)) {
			$name = $table->getTable() ?: $table->getAlias();
		}

		return Inflector::underscore($name);
	}
}
