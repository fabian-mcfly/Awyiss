<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\BootstrapColumnSystem;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
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
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MediaElementsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media_elements';


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
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->columnSpans = BootstrapColumnSystem::getColumnWidths();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('MediaAssignments', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('MediaElementAssignments', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('MediaElementSelectors', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
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
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('columSpan', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->columnSpans),
				],
			],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
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
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->isUnique(['identifier']),
			'identifierUnique',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('MediaAssignments', 'mediaAssignments'),
			'noLinkedMediaAssignments',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_media_assignments'),
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
	 * Model level means that the model is assignable to a element
	 * Entity level means that the entity of the model is assignable to a element
	 *
	 * @param bool $includeEntities
	 * @param bool $allowGrouping
	 * @return array
	 * @throws \ReflectionException
	 */
	public function getAssignableModels(bool $includeEntities = false, bool $allowGrouping = true): array {
		if (isset($this->availableModels)) {
			return $this->availableModels;
		}

		$this->availableModels = [];

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_datatables = FactoryLocator::get('Table')->get('Datatables')->findAllAndCache()->indexBy(fn($datatable) => $datatable->identifier)->toArray();

		/**
		 * @var \Cake\Collection\Collection $lo_pageRoles
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_pageRoles = FactoryLocator::get('Table')->get('PageRoles')->findAllAndCache()->indexBy(fn($pageRole) => $pageRole->id);
		$lo_pageRoles = $lo_pageRoles->map(fn($pageRole) => $pageRole->label);
		$la_pageRoles = $lo_pageRoles->toArray();

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Model\Table\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', '*Table.php',]),
			'\Awyiss\Model\Table\\' => implode(DS, [ROOT, APP_DIR, 'Model', 'Table', '*Table.php']),
		];

		//Traverse both namespaces
		foreach ($la_paths as $ls_namespace => $ls_path) {
			//Look for files with name "*Table.php"
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_tableName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);

				if ($ls_tableName === 'GenericDatatablesTable') {
					continue;
				}

				/** @var class-string<\Awyiss\Model\Table> $ls_tableClass */
				$ls_tableClass = $ls_namespace . $ls_tableName;
				/** @var string $ls_tableName */
				$ls_tableName = $ls_tableClass::TABLE;

				if (isset($this->availableModels[ $ls_tableName ])) {
					continue;
				}

				$lo_reflection = new ReflectionClass($ls_tableClass);

				$la_attributes = $lo_reflection->getAttributes(MediaElementAssignable::class);

				if (!$la_attributes) {
					continue;
				}

				$lo_attributeInstance = $la_attributes[0]->newInstance();

				$lb_entityLevel = (bool)($lo_attributeInstance->level & MediaElementAssignable::ENTITY_LEVEL);

				$la_entities = false;
				if ($includeEntities && $lb_entityLevel) {
					$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($ls_tableName));
					$lo_entities = $lo_table->find()->all()->indexBy('id');

					if ($allowGrouping && $ls_tableName === 'page_templates') {
						$lo_entities = $lo_entities->groupBy(function ($entity) use ($la_pageRoles) {
							return $la_pageRoles[ $entity->pageRoleId->value ];
						});

						$lo_entities = $lo_entities->map(fn ($entities) => collection($entities)->indexBy('id')->map(function ($entity) {
							return $entity->label;
						})->toArray());

						$la_entities = $lo_entities->toArray();

						uksort($la_entities, function ($key1, $key2) use ($la_pageRoles) {
							return array_search($key1, $la_pageRoles) <=> array_search($key2, $la_pageRoles);
						});
					}
					else {
						$lo_entities = $lo_entities->map(fn ($entity) => $entity->label);
						$la_entities = $lo_entities->toArray();
					}
				}

				$this->availableModels[ $ls_tableName ] = [
					'entityLevel' => $lb_entityLevel,
					'modelLevel' => (bool)($lo_attributeInstance->level & MediaElementAssignable::MODEL_LEVEL),
					'label' => isset($la_datatables[ $ls_tableName ]) ? $la_datatables[ $ls_tableName ]->label : __d($ls_tableName, 'headline_overview'),
					'entities' => $la_entities,
				];
			}
		}

		uasort($this->availableModels, fn($a, $b) => strcasecmp($a['label'], $b['label']));

		return $this->availableModels;
	}
}
