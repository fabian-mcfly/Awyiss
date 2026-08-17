<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Model\Table;
use Awyiss\Model\Table\DatatablesTable;
use Awyiss\Model\Table\MediaElementAssignmentsTable;
use Awyiss\Model\Table\MediaElementsTable;
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
	protected array $_defaultConfig = [ // phpcs:ignore
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

		$this->setTableLocator($this->getConfig('tableLocator'));

		if (
			in_array($this->table()->getTable(), [
				MediaElementsTable::TABLE,
				MediaElementAssignmentsTable::TABLE,
			])
		) {
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

			$this
				->table()
				->hasMany('MediaElementAssignments', [
					'bindingKey' => 'identifier',
					'cascadeCallbacks' => true,
					'dependent' => true,
					'foreignKey' => 'scope',
					'propertyName' => 'mediaElementAssignments',
					'saveStrategy' => 'replace',
					'strategy' => $this->getConfig('strategy'),
				])
			;
		}
		else {
			$reflection = new ReflectionClass($this->table());

			$attributes = $reflection->getAttributes(MediaElementAssignable::class);

			if (!$attributes) {
				return;
			}

			$attributeInstance = $attributes[0]->newInstance();

			$this->setConfig('assignable', [
				'entityLevel' => (bool)($attributeInstance->level & MediaElementAssignable::ENTITY_LEVEL),
				'modelLevel' => (bool)($attributeInstance->level & MediaElementAssignable::MODEL_LEVEL),
			]);


			$this
				->table()
				->hasMany('MediaElementAssignments', [
					'conditions' => [
						'MediaElementAssignments.scope' => $this->getScope($this->table()),
					],
					'cascadeCallbacks' => true,
					'dependent' => true,
					'foreignKey' => 'foreignKey',
					'propertyName' => 'mediaElementAssignments',
					'saveStrategy' => 'replace',
					'strategy' => $this->getConfig('strategy'),
				])
			;
		}

		$this->assignmentsTable = $this->getTableLocator()->get('MediaElementAssignments', ['allowFallbackClass' => false]);
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
		$name = namespaceSplit($table::class);
		$name = substr((string)end($name), 0, -5);

		if (empty($name)) {
			$name = $table->getTable() ?: $table->getAlias();
		}


		return Inflector::camelize($name);
	}


	/**
	 * @param \Cake\ORM\Marshaller $marshaller
	 * @param array $map
	 * @param array $options
	 * @return array
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		if (!$this->getConfig('enabled') || ($options['mediaElementAssignments'] ?? true) === false) {
			return [
				'mediaElementAssignments' => function (array $values): array {
					return $values;
				},
			];
		}

		unset($options['associated']);

		return [
			'mediaElementAssignments' => function (array $values, EntityInterface $entity) use ($options): array {
				$mediaElementAssignments = [];

				$errors = [];
				$marshaller = $this->assignmentsTable->marshaller();

				foreach ($values as $data) {
					/** @var \Awyiss\Model\Entity\MediaElementAssignment|null $mediaElementAssignment */
					$mediaElementAssignment = null;
					if (!empty($data['id'])) {
						// Find the existing entity, if any, in `mediaElementAssignments`
						$mediaElementAssignment = array_filter(
							$entity->mediaElementAssignments ?? [],
							fn(MediaElementAssignment $entity) => $entity->id === (int)$data['id']
						)[0] ?? null;
					}

					// If no existing entity was found, create a new one
					$mediaElementAssignment ??= $this->assignmentsTable->newEmptyEntity();

					$data['scope'] = $this->getConfig('referenceName');

					$marshaller->merge(
						$mediaElementAssignment,
						$data,
						[
							'fields' => [
								'id',
								'mediaElementId',
								'scope',
							],
						] + $options
					);

					$dataErrors = $mediaElementAssignment->getErrors();
					if ($dataErrors) {
						$errors[] = $dataErrors;
					}

					if ($mediaElementAssignment->mediaElementId === 0) {
						continue;
					}

					$mediaElementAssignments[] = $mediaElementAssignment;
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($errors) {
					$entity->setErrors(['mediaElementAssignments' => $errors]);
				}

				$entity->setDirty('mediaElementAssignments');

				return $mediaElementAssignments;
			},
		];
	}
}
