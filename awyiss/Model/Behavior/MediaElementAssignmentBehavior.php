<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Model\Table;
use Awyiss\Model\Table\DatatablesTable;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use ReflectionClass;


/**
 * This behavior automatically prefixes database columns with the name of their table.
 * This makes writing out the table name obsolete when using joins.
 */
class MediaElementAssignmentBehavior extends Behavior implements PropertyMarshalInterface {
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
			'mediaElementAssignments' => 'findMediaElementAssignments',
		],
		'referenceName' => '',
		'strategy' => 'subquery',
		'tableLocator' => null,
	];
	/**
	 * Instance of Table responsible for assignments.
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

		$this->setTableLocator($this->getConfig('tableLocator'));

		if (in_array($this->table()->getTable(), ['media_elements', 'media_element_assignments'])) {
			return;
		}

		if (Awyiss::getRealm() === Awyiss::REALM_BACKEND) {
			$this->setConfig('enabled', true);
		}
		else {
			return;
		}

		if ($this->table() instanceof DatatablesTable) {
			$this->setConfig('assignable', [
				'entityLevel' => true,
				'modelLevel' => false,
			]);

			$this->table()->hasMany('MediaElementAssignments', [
				'bindingKey' => 'identifier',
				'cascadeCallbacks' => true,
				'dependent' => true,
				'foreignKey' => 'scope',
				'propertyName' => 'mediaElementAssignments',
				'saveStrategy' => 'replace',
				'strategy' => $this->getConfig('strategy'),
			]);
		}
		else {
			$lo_reflection = new ReflectionClass($this->table());

			$la_attributes = $lo_reflection->getAttributes(MediaElementAssignable::class);

			if (!$la_attributes) {
				return;
			}

			$lo_attributeInstance = $la_attributes[0]->newInstance();

			$this->setConfig('assignable', [
				'entityLevel' => (bool)($lo_attributeInstance->level & MediaElementAssignable::ENTITY_LEVEL),
				'modelLevel' => (bool)($lo_attributeInstance->level & MediaElementAssignable::MODEL_LEVEL),
			]);


			$this->table()->hasMany('MediaElementAssignments', [
				'conditions' => [
					'MediaElementAssignments.scope' => $this->getScope($this->table()),
				],
				'cascadeCallbacks' => true,
				'dependent' => true,
				'foreignKey' => 'foreign_key',
				'propertyName' => 'mediaElementAssignments',
				'saveStrategy' => 'replace',
				'strategy' => $this->getConfig('strategy'),
			]);
		}

		$this->assignmentsTable = $this->getTableLocator()->get('MediaElementAssignments', ['allowFallbackClass' => false]);

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->table()->getEntityClass();

		$ls_entityClass::addFieldMapping('media_element_assignments', 'mediaElementAssignments');
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findMediaElementAssignments(SelectQuery $query): SelectQuery {
		if (!$this->getConfig('enabled') || !$this->getConfig('assignable.entityLevel')) {
			return $query;
		}

		return $query->contain([
			'MediaElementAssignments' => [
				'MediaElements' => [
					'MediaElementSelectors' => [
						'MediaSelectors',
					],
				],
			],
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
		if (!$this->getConfig('enabled') || ($options['mediaElementAssignments'] ?? true) === false) {
			return [];
		}

		$la_options = $options;
		unset($la_options['associated']);

		return [
			'media_element_assignments' => function (array $values, EntityInterface $entity) use ($la_options): array {
				/** @var array<string, \Awyiss\Model\Entity\MediaElementAssignment> $la_publicationData */
				$la_mediaElementAssignments = [];

				$la_errors = [];
				$lo_marshaller = $this->assignmentsTable->marshaller();

				foreach ($values as $la_data) {
					/** @var \Awyiss\Model\Entity\MediaElementAssignment|null $lo_entity */
					$lo_entity = null;
					if (!empty($la_data['id'])) {
						// Find the existing entity, if any, in `mediaElementAssignments`
						$lo_entity = array_filter($entity->mediaElementAssignments ?? [], fn(MediaElementAssignment $entity) => $entity->id === (int)$la_data['id'])[0] ?? null;
					}

					if (!$lo_entity) {
						$lo_entity = $this->assignmentsTable->newEmptyEntity();
					}

					$la_data['scope'] = $this->getConfig('referenceName');

					$lo_marshaller->merge($lo_entity, $la_data, $la_options);

					$la_dataErrors = $lo_entity->getErrors();
					if ($la_dataErrors) {
						$la_errors[] = $la_dataErrors;
					}

					if ($lo_entity->mediaElementId === 0) {
						continue;
					}

					$la_mediaElementAssignments[] = $lo_entity;
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($la_errors) {
					$entity->setErrors(['mediaElementAssignments' => $la_errors]);
				}

				$entity->setDirty('mediaElementAssignments');

				return $la_mediaElementAssignments;
			},
		];
	}
}
