<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Core\App;
use Awyiss\Model\Entity\MediaElement;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\ColumnSystem\BackendColumnSystem;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use ReflectionClass;


/**
 * MediaElements Model
 *
 * @method \Awyiss\Model\Entity\MediaElement newDefaultEntity(array $additionalData = [], array $options = [])
 * @property \Awyiss\Model\Table\MediaAssignmentsTable&\Awyiss\ORM\Association\HasMany $MediaAssignments
 * @property \Awyiss\Model\Table\MediaElementSelectorsTable&\Awyiss\ORM\Association\HasMany $MediaElementAssignments
 * @property \Awyiss\Model\Table\MediaElementSelectorsTable&\Awyiss\ORM\Association\HasMany $MediaElementSelectors
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class MediaElementsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'media_elements';


	/**
	 * @var array The available models for element assignment
	 */
	protected array $availableModels;
	/**
	 * @var array The column widths
	 */
	protected array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['internal'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->columnSpans = BackendColumnSystem::getColumnWidths();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('MediaAssignments', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'mediaElementId',
			'propertyName' => 'mediaAssignments',
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('MediaElementAssignments', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'mediaElementId',
			'propertyName' => 'mediaElementAssignments',
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('MediaElementSelectors', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'mediaElementId',
			'propertyName' => 'mediaElementSelectors',
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('columnSpan', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->columnSpans),
				],
			],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->isUnique(['identifier']),
			'identifierUnique',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('MediaAssignments', 'mediaAssignments'),
			'noLinkedMediaAssignments',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_linked_media_assignments'),
			]
		);

		$rules->addDelete(
			function (MediaElement $entity/*, array $options*/): bool {
				return $entity->id >= 10;
			},
			'notDefaultElementDeletion',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_not_default_element_deletion'),
			]
		);

		return $rules;
	}


	/**
	 * @return array
	 */
	public function getColumnSpans(): array {
		return $this->columnSpans;
	}


	/**
	 * Get the available models for element assignment
	 * Model level means that the model is assignable to an element
	 * Entity level means that the entity of the model is assignable to an element
	 *
	 * @param bool $includeEntities
	 * @param bool $allowGrouping
	 * @param bool $useCache
	 * @return array
	 * @throws \ReflectionException
	 */
	public function getAssignableModels(bool $includeEntities = false, bool $allowGrouping = true, bool $useCache = true): array {
		$withEntitiesKey = $includeEntities ? 'withEntities' : 'withoutEntities';
		$allowGroupingKey = $allowGrouping ? 'allowGrouping' : 'noGrouping';

		if ($useCache && isset($this->availableModels[ $withEntitiesKey ][ $allowGroupingKey ])) {
			return $this->availableModels[ $withEntitiesKey ][ $allowGroupingKey ];
		}

		$availableModels = [];

		$datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$datatables = $useCache
			? $datatablesTable
				->find('translations')
				->find('mediaAssignments')
				->find('mediaElementAssignments')
				->all()
			: $datatablesTable->findAllAndCache();
		$datatables = $datatables->indexBy('identifier')->toArray();

		/**
		 * @var \Cake\Collection\Collection $pageRoles
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$pageRoles = FactoryLocator::get('Table')
			->get('PageRoles')
			->findAllAndCache()
			->indexBy(fn($pageRole) => $pageRole->id)
		;
		$pageRoles = $pageRoles->map(fn($pageRole) => $pageRole->label)->toArray();

		$classes = App::classes('*', 'Model/Table', 'Table', null, null, ['GenericDatatablesTable']);

		/** @var class-string<\Awyiss\Model\Table> $className */
		foreach ($classes as $className) {
			/** @var string $tableName */
			$tableName = $className::TABLE;

			if (isset($availableModels[ $tableName ])) {
				continue;
			}

			$reflection = new ReflectionClass($className);

			$attributes = $reflection->getAttributes(MediaElementAssignable::class);

			if (!$attributes) {
				continue;
			}

			$attributeInstance = $attributes[0]->newInstance();

			$entityLevel = (bool)($attributeInstance->level & MediaElementAssignable::ENTITY_LEVEL);

			$entities = false;
			if ($includeEntities && $entityLevel) {
				$table = FactoryLocator::get('Table')->get(Inflector::camelize($tableName));
				$entities = $table
					->find()
					->all()
					->indexBy('id')
				;

				if ($allowGrouping && $tableName === PageTemplatesTable::TABLE) {
					$entities = $entities->groupBy(function (EntityInterface $entity) use ($pageRoles): string {
						/** @var \Awyiss\Model\Entity\PageTemplate $entity */
						return $pageRoles[ $entity->pageRoleId->value ];
					});

					$entities = $entities
						->map(
							fn(array $groupedEntities) => collection($groupedEntities)
								->indexBy('id')
								/** @var \Awyiss\Model\Entity $entity */
								->map(fn(EntityInterface $entity): string => $entity->label)
								->toArray()
						)
						->toArray()
					;

					uksort($entities, function (string $key1, string $key2) use ($pageRoles) {
						return array_search($key1, $pageRoles) <=> array_search($key2, $pageRoles);
					});
				}
				else {
					$entities = $entities->map(fn($entity) => $entity->label)->toArray();
				}
			}

			$camelizedTableName = Inflector::camelize($tableName);
			$availableModels[ $camelizedTableName ] = [
				'entityLevel' => $entityLevel,
				'modelLevel' => (bool)($attributeInstance->level & MediaElementAssignable::MODEL_LEVEL),
				'label' => isset($datatables[ $camelizedTableName ])
					? $datatables[ $camelizedTableName ]->label
					: __d($tableName, 'headline_overview'),
				'entities' => $entities,
			];
		}

		foreach ($datatables as $datatable) {
			if (!isset($availableModels[ $datatable->identifier ])) {
				$availableModels[ $datatable->identifier ] = [
					'entityLevel' => false,
					'modelLevel' => true,
					'label' => $datatable->label,
					'entities' => false,
				];
			}
		}

		uasort($availableModels, fn($a, $b) => strcasecmp($a['label'], $b['label']));

		$this->availableModels[ $withEntitiesKey ][ $allowGroupingKey ] = $availableModels;

		return $this->availableModels[ $withEntitiesKey ][ $allowGroupingKey ];
	}
}
