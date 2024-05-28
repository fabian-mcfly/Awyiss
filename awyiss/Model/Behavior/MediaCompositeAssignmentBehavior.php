<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Annotation\MediaCompositeAssignable;
use Awyiss\Awyiss;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use ReflectionClass;


/**
 * This behavior automatically prefixes database columns with the name of their table.
 * This makes writing out the table name obsolete when using joins.
 */
class MediaCompositeAssignmentBehavior extends Behavior implements PropertyMarshalInterface {
	use LocatorAwareTrait;


	/**
	 * Default config
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'assignable' => [
			'entityLevel' => false,
			'modelLevel' => false,
		],
		'enabled' => false,
		'implementedFinders' => [
			'mediaCompositeAssignments' => 'findMediaCompositeAssignments',
		],
		'referenceName' => '',
		'strategy' => 'subquery',
		'tableLocator' => null,
	];
	/**
	 * Instance of Table responsible for dates
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
		$la_config = $config + [
			'referenceName' => $this->getScope($table),
			'tableLocator' => $table->associations()->getTableLocator(),
		];

		parent::__construct($table, $la_config);
	}


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->_tableLocator = $this->getConfig('tableLocator');

		if (in_array($this->table()->getTable(), ['media_composites', 'media_composite_assignments'])) {
			return;
		}

		if (Awyiss::getRealm() === Awyiss::REALM_BACKEND) {
			$this->setConfig('enabled', true);
		}
		else {
			return;
		}

		$lo_reflection = new ReflectionClass($this->table());

		$la_attributes = $lo_reflection->getAttributes(MediaCompositeAssignable::class);

		if (!$la_attributes) {
			return;
		}

		$lo_attributeInstance = $la_attributes[0]->newInstance();

		$this->setConfig('assignable', [
			'entityLevel' => (bool)($lo_attributeInstance->level & MediaCompositeAssignable::ENTITY_LEVEL),
			'modelLevel' => (bool)($lo_attributeInstance->level & MediaCompositeAssignable::MODEL_LEVEL),
		]);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->assignmentsTable = $this->getTableLocator()->get('MediaCompositeAssignments', ['allowFallbackClass' => false]);

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->_table->getEntityClass();

		$this->_table->hasMany('MediaCompositeAssignments', [
			'conditions' => [
				'MediaCompositeAssignments.scope' => $this->getScope($this->table()),
			],
			'cascadeDelete' => true,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => 'media_composite_assignments',
			'saveStrategy' => 'replace',
			'strategy' => $this->getConfig('strategy'),
		]);

		$ls_entityClass::addFieldMapping('media_composite_assignments', 'mediaCompositeAssignments');
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findMediaCompositeAssignments(SelectQuery $query): SelectQuery {
		if (!$this->getConfig('enabled') || !$this->getConfig('assignable.entityLevel')) {
			return $query;
		}

		return $query->contain([
			'MediaCompositeAssignments',
		]);
	}


	/**
	 * @param Table $table The table class to get a reference name for.
	 * @return string
	 */
	protected function getScope(Table $table): string {
		$ls_name = namespaceSplit($table::class);
		$ls_name = substr((string)end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $table->getTable() ?: $table->getAlias();
		}


		return Inflector::underscore($ls_name);
	}


	/**
	 * @param \Cake\ORM\Marshaller $marshaller
	 * @param array $map
	 * @param array $options
	 * @return array
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		if (!$this->getConfig('enabled') || ($options['mediaCompositeAssignments'] ?? true) === false) {
			return [];
		}

		$la_options = $options;
		unset($la_options['associated']);

		return [
			'media_composite_assignments' => function (array $values, EntityInterface $entity) use ($la_options): array {
				/**
				 * @var array<string, \Awyiss\Model\Entity\MediaCompositeAssignment> $la_publicationData
				 */
				$la_mediaCompositeAssignments = [];

				$la_errors = [];
				$lo_marshaller = $this->assignmentsTable->marshaller();

				foreach ($values as $la_data) {
					/** @var \Awyiss\Model\Entity\MediaCompositeAssignment $lo_entity */
					$lo_entity = $this->assignmentsTable->newEmptyEntity();

					$la_data['scope'] = $this->getConfig('referenceName');

					$lo_marshaller->merge($lo_entity, $la_data, $la_options);

					$la_dataErrors = $lo_entity->getErrors();
					if ($la_dataErrors) {
						$la_errors[] = $la_dataErrors;
					}

					if ($lo_entity->mediaCompositeId === 0) {
						continue;
					}

					$la_mediaCompositeAssignments[] = $lo_entity;
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($la_errors) {
					$entity->setErrors(['mediaCompositeAssignments' => $la_errors]);
				}

				$entity->setDirty('mediaCompositeAssignments');

				return $la_mediaCompositeAssignments;
			},
		];
	}
}
