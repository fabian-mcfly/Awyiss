<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\AwyissColumnSystem;
use Awyiss\Validation\Validator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\Log\LogTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator as BaseValidator;
use RuntimeException;


/**
 * Contents Model
 *
 * @property \Awyiss\Model\Table\ContentAreasTable&\Awyiss\ORM\Association\BelongsTo $ContentAreas
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ContentTemplates
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $Pages
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\BelongsTo $ParentContents
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $ChildContents
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $DuplicatingContents
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfContents
 * @method \Awyiss\Model\Entity\Content newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\Content getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 */
class ContentsTable extends Table {
	use LogTrait;


	/**
	 * @inheritDoc
	 */
	public const TABLE = 'contents';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'enabled' => true,
		'field' => 'pageId',
		'identifier' => 'page',
	];
	/**
	 * @var array The column system
	 */
	protected array $columnSystem = [
		'className' => AwyissColumnSystem::class,
		'maxColumns' => 5,
	];
	/**
	 * @var array The column widths
	 */
	protected array $columnWidths;
	/**
	 * @var array The column indents
	 */
	protected array $columnIndents;
	/**
	 * @var string
	 */
	private string $forScope;
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'enabled' => true,
		'relatedColumns' => ['pageId', 'contentAreaId'],
	];
	/**
	 * @var \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	protected PageRoleEnumInterface $pageRole;
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['pageId', 'contentAreaId', 'parentId'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		$this->customConfigProperties[] = 'columnSystem';

		parent::initialize($config);

		$this->initializeColumnSystem();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('ContentAreas', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('ContentTemplates', [
			'joinType' => 'INNER',
		]);

		$this->hasMany('DuplicatingContents', [
			'bindingKey' => 'duplicate_of',
			'className' => 'Contents',
			'foreignKey' => 'id',
		]);

		$this->belongsTo('DuplicateOfContents', [
			'bindingKey' => 'id',
			'className' => 'Contents',
			'foreignKey' => 'duplicate_of',
		]);
	}


	/**
	 * @return class-string<\Awyiss\Utility\Content\ColumnSystemInterface>
	 */
	public function getColumnSystemClass(): string {
		return $this->columnSystem['className'];
	}


	/**
	 * @return array
	 */
	public function getColumnWidths(): array {
		return $this->columnWidths;
	}


	/**
	 * @return array
	 */
	public function getColumnIndents(): array {
		return $this->columnIndents;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array $options
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findLatestForPages(SelectQuery $query): SelectQuery {
		return $query->select(['page_id', 'id', 'changed_on', 'created_on'])
		->orderBy(['changed_on' => 'DESC', 'created_on' => 'DESC'])
		->distinct(['page_id'])->groupBy('page_id');
	}


	/**
	 * Returns a list of keys that are allowed for a content
	 * when duplicating another content.
	 *
	 * All other keys will be taken from the duplicated content.
	 *
	 * @return array
	 */
	public function getAllowedKeyForDuplicating(): array {
		return [
			'active',
			'pageId',
			'contentAreaId',
			'parentId',
			'columnWidth',
			'columnIndent',
			'columnLast',
			'columnRtl',
			'duplicateOf',
			'systemOrder',
		];
	}


	/**
	 * @return void
	 */
	protected function initializeColumnSystem(): void {
		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $ls_className */
		$ls_className = $this->columnSystem['className'];
		$ls_className::setMaxDenominator($this->columnSystem['maxColumns']);

		$this->columnWidths = $ls_className::getColumnWidths();
		$this->columnIndents = $ls_className::getColumnIndents();
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Awyiss\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Awyiss\Validation\Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(BaseValidator $validator): BaseValidator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'pageId',
			'contentAreaId',
			'contentTemplateId',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('pageId');
		$validator->add('pageId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('subtitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('contentAreaId');
		$validator->add('contentAreaId', [
			'isScalar' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('contentTemplateId');
		$validator->add('contentTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('columnWidth', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->getColumnWidths()),
				],
			],
		]);


		$validator->add('columnIndent', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->getColumnIndents()),
				],
			],
		]);


		$validator->add('cssClass', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('duplicateOf', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('data', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
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
	 * @noinspection DuplicatedCode
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): BaseRulesChecker {
		$rules->add(function (Content $entity/*, array $options*/): bool {
			/**
			 * Retreive the page and the assigned page template.
			 *
			 * @see ContentsTable::forPageRole
			 */
			try {
				/** @var Page $lo_page */
				$lo_page = $this->{$this->getPageRole()->tableAlias()}->get($entity->pageId, contain: [
					'PageTemplates',
				]);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				$entity->setError('page_id', __df($this->getI18nDomain(), 'validation', 'error_valid_page_id'));

				return false;
			}


			/*
			 * Retreive tthe content template of the current entity
			 * This works as an existsIn-like rule
			 */
			try {
				/** @var ContentTemplate $lo_contentTemplate */
				$lo_contentTemplate = $this->ContentTemplates->get(
					$entity->contentTemplateId,
					contain: [
						'ContentAreas' => [
							'queryBuilder' => function (SelectQuery $query) use ($lo_page) {
								return $query->where(['ContentTemplateContentAreas.page_template_id' => $lo_page->pageTemplateId]);
							},
						],
						'ContentTemplateElements',
					],
				);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				//Content template not found
				$entity->setError('content_template_id', __df($this->getI18nDomain(), 'validation', 'error_valid_content_template_id'));

				return false;
			}


			//Content area not found in the content template
			if (!in_array($entity->contentAreaId, array_column($lo_contentTemplate->contentAreas, 'id'))) {
				$entity->setError('content_area_id', __df($this->getI18nDomain(), 'validation', 'error_valid_content_area_id'));

				return false;
			}


			// Make sure that all children of the current entity can be moved to the target content area as well
			if (!$this->childrenCanBeMoved($entity, $lo_page->pageTemplateId)) {
				$entity->setError('content_area_id', __df($this->getI18nDomain(), 'validation', 'error_valid_content_area_id_for_children'));

				return false;
			}


			/** @var \Awyiss\Validation\Validator $lo_validator */
			$lo_validator = new $this->_validatorClass();
			$lo_validator->setI18nDomain($this->getI18nDomain());

			$la_data = $entity->extract();
			if (!empty($entity->attributes)) {
				/** @var \Awyiss\Validation\Validator $lo_attributesValidator */
				$lo_attributesValidator = new $this->_validatorClass();
				$lo_attributesValidator->setI18nDomain($this->getI18nDomain());

				$la_data['attributes'] = $entity->attributes->extract();
			}

			$this->validateInputFields($entity, $lo_validator, $lo_attributesValidator ?? null, $lo_contentTemplate);

			//Validate the entity using the
			$la_errors = $lo_validator->validate($la_data, $entity->isNew());

			/** @noinspection PhpUndefinedMethodInspection */
			$la_errors = $this->getEntityClass()::mapFields($la_errors, true);

			if ($this->hasAttributes() && !empty($la_errors['attributes'])) {
				/** @noinspection PhpUndefinedMethodInspection */
				$la_errors['attributes'] = $this->getAttributesTable()->getEntityClass()::mapFields($la_errors['attributes'], true);
				$entity->attributes->setErrors($la_errors['attributes']);
			}

			$entity->setErrors($la_errors);


			return empty($la_errors);
		}, 'validContentArea');


		$rules->add(function (Content $entity): bool {
			/** @var \Awyiss\Utility\Content\ColumnInterface $lo_width */
			$lo_width = $entity->column['width'];
			/** @var \Awyiss\Utility\Content\ColumnInterface $lo_indent */
			$lo_indent = $entity->column['indent'];

			$lf_totalWidth = $lo_width->getPercentage() + ($lo_indent?->getPercentage() ?? 0);

			if ($lf_totalWidth > 1) {
				return false;
			}

			return true;
		}, 'validWidthIndentCombination', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_width_indent_combination'),
		]);


		$rules->add(
			function (Content $entity) {
				$lx_valid = $this->checkValidDuplicateRules($entity);

				if ($lx_valid !== true && !$entity->duplicateOf) {
					/**
					 * If the entity is not a duplicate of another content but
					 * the validation failed, set the error message to the general error field.
					 *
					 * Most likely, the entity itself does not have a duplicateOf field
					 */
					$entity->setError('_general', $lx_valid);
				}

				return $lx_valid;
			},
			'validDuplicateOf',
			[
				'errorField' => 'duplicateOf',
			]
		);


		//Ensure that a content has no linked duplicating contents when deleting it.
		$rules->addDelete(
			function (Content $entity): string|bool {
				/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
				$lo_table = FactoryLocator::get('Table')->get('Contents');

				if ($lo_table->exists(['duplicate_of' => $entity->id])) {
					return false;
				}

				// Get all children of the current entity
				$la_nestedChildren = $entity->getNestedChildren()->toArray();
				$la_childrenContentIds = array_column($la_nestedChildren, 'id');

				if ($la_childrenContentIds) {
					$li_duplicatingContents = $lo_table->find()->where(['duplicate_of IN' => $la_childrenContentIds])->count();

					if ($li_duplicatingContents) {
						return __df($this->getI18nDomain(), 'validation', 'error_no_duplicated_children');
					}
				}

				return true;
			},
			'noDuplicatingContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_duplicating_contents'),
			],
		);


		return $rules;
	}


	/**
	 * Checks if all children of the current entity can be moved to the target content area.
	 *
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param int $pageTemplateId
	 * @return bool
	 */
	protected function childrenCanBeMoved(Content $entity, int $pageTemplateId): bool {
		$li_contentAreaId = $entity->contentAreaId;
		$li_pageTemplateId = $pageTemplateId;

		// Get all children of the current entity
		$lo_children = $entity->getNestedChildren([
			'contain' => [
				'ContentTemplates' => [
					'ContentAreas' => [
						'queryBuilder' => function (SelectQuery $query) use ($li_contentAreaId, $li_pageTemplateId) {
							return $query->where([
								'ContentTemplateContentAreas.content_area_id' => $li_contentAreaId,
								'ContentTemplateContentAreas.page_template_id' => $li_pageTemplateId,
							]);
						},
					],
				],
			],
		]);

		/** @var \Awyiss\Model\Entity\Content $lo_child */
		foreach ($lo_children as $lo_child) {
			if (!$lo_child->contentTemplate?->contentAreas) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Groups the result of a query or the elements of a collection by their `contentAreaId`-value and returns
	 * a new collection
	 *
	 * @noinspection PhpUnused
	 */
	public function groupByContentArea(SelectQuery|CollectionInterface $data): CollectionInterface {
		$lo_data = is_a($data, SelectQuery::class) ? $data->all() : $data;

		foreach ($lo_data->groupBy('contentAreaId') as $ls_contentArea => $la_contents) {
			$lo_data->$ls_contentArea = new Collection($la_contents);
		}


		return $lo_data;
	}


	/**
	 * Groups the result of a query by their `contentAreaId`-value and returns a new collection with all
	 * contents nested and an added `level`-property.
	 *
	 * @param SelectQuery $query
	 * @return CollectionInterface
	 */
	public function nestedByContentArea(SelectQuery $query): CollectionInterface {
		return $query->find('threaded')->all()->groupBy('contentAreaId')->map(function (array $contents): CollectionInterface {
			$lo_contents = (new Collection($contents))->listNested();

			/** @var Content $lo_content */
			foreach ($lo_contents as $lo_content) {
				$lo_content->setVirtual(['level']);
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_content->level = $lo_contents->getDepth();
			}


			return $lo_contents;
		});
	}


	/**
	 * Return a Page-object with the page role and page template associations
	 *
	 * @param int $pageId
	 * @return Page
	 */
	public function getPage(int $pageId): Page {
		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var Table $lo_pages */
		$lo_pages = $lo_tableLocator->get('Pages');

		/** @var Page $lo_page */
		$lo_page = $lo_pages->get(
			$pageId,
			attributes: ['skip' => true],
			contain: [
				'PageTemplates' => [
					'finder' => [
						'all' => [
							'attributes' => ['skip' => true],
						],
					],
					'fields' => [
						'id',
						'title',
						'active',
					],
					'ContentAreas' => [
						'finder' => [
							'all' => [
								'attributes' => ['skip' => true],
							],
						],
						'fields' => [
							'id',
							'title',
							'active',
						],
					],
				],
			],
			fields: [
				'id',
				'title',
				'language_shortcode',
				'page_role_id',
				'page_template_id',
			],
			skipPageRoleCheck: true,
			translate: ['skip' => true],
		);


		return $lo_page;
	}


	/**
	 * Sets this table to run the access check of the 'Pages'-association with a specific page role.
	 *
	 * @param \Awyiss\Model\Enum\PageRoleEnumInterface $pageRole
	 * @param bool $initializePages
	 * @return void
	 * @throws \Exception
	 */
	public function forPageRole(PageRoleEnumInterface $pageRole, bool $initializePages = true): void {
		if ($initializePages) {
			$this->belongsTo($pageRole->tableAlias(), [
				'bindingKey' => 'id',
				'finder' => 'forCurrentLanguage',
				'foreignKey' => 'page_id',
				'joinType' => 'INNER',
				'propertyName' => 'page',
			]);

			/** @var \Awyiss\Model\Behavior\CategoriesBehavior $lo_behavior */
			$lo_behavior = $this->getBehavior('Categories');
			$lo_behavior->setConfig('associationName', $pageRole->tableAlias())->resetCategories();
		}

		$this->setPageRole($pageRole);
		$this->setForScope($pageRole->tableName());

		if ($this->getAlias() === 'Contents') {
			$this->ChildContents->forPageRole($pageRole, $initializePages);
			$this->ParentContents->forPageRole($pageRole, $initializePages);
		}
	}


	/**
	 * Returns the scope - the plural form of the page role identifier - that's set for the authorization behavior.
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getForScope(): string {
		if (!isset($this->forScope)) {
			throw new RuntimeException(sprintf('Cannot use `%s` without calling `forPageRole` first', static::class));
		}


		return $this->forScope;
	}


	/**
	 * Sets the scope the authorization behavior has to check.
	 *
	 * @param string $scope
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function setForScope(string $scope): void {
		$this->forScope = Inflector::underscore(Inflector::pluralize($scope));
	}


	/**
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	public function getPageRole(): PageRoleEnumInterface {
		return $this->pageRole;
	}


	/**
	 * @param \Awyiss\Model\Enum\PageRoleEnumInterface $pageRoleName
	 * @return ContentsTable
	 */
	protected function setPageRole(PageRoleEnumInterface $pageRoleName): static {
		$this->pageRole = $pageRoleName;


		return $this;
	}


	/**
	 * @return void
	 */
	public function disableCascadeCallbacks(): void {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->ChildContents->setDependent(false)->setCascadeCallbacks(false);
	}


	/**
	 * @return void
	 */
	public function enableCascadeCallbacks(): void {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->ChildContents->setDependent(true)->setCascadeCallbacks(true);
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @return void
	 */
	protected function validateInputFields(Content $entity, Validator $validator, ?Validator $attributesValidator, ContentTemplate $contentTemplate): void {
		$la_contentAttributes = $this->ContentTemplates->getAvailableContentAttributes();
		$la_contentAttributes = array_combine(
			array_column($la_contentAttributes, 'identifier'),
			$la_contentAttributes
		);

		$this->validateAssignedElements($contentTemplate, $entity, $validator, $la_contentAttributes, $attributesValidator);

		$this->validateUnassignedElements($contentTemplate, $entity, $validator);

		if (isset($attributesValidator)) {
			$this->validateUnassignedAttributes($contentTemplate, $entity, $la_contentAttributes, $attributesValidator);

			if ($attributesValidator->count()) {
				$validator->addNested('attributes', $attributesValidator);
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('data', 'json');
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param array $contentAttributes
	 * @param \Awyiss\Validation\Validator $attributesValidator
	 * @return void
	 */
	protected function validateUnassignedAttributes(ContentTemplate $contentTemplate, Content $entity, array $contentAttributes, ?Validator $attributesValidator): void {
		$la_attributes = array_keys($contentAttributes);

		foreach (
			array_diff(
				$la_attributes,
				$this->ContentTemplates->getAssignedContentAttributes($contentTemplate)
			) as $ls_element
		) {
			if (!$entity->attributes->isDirty($ls_element)) {
				continue;
			}

			$attributesValidator->add($ls_element, 'isEmpty', [
				'rule' => function (mixed $value): bool {
					return empty($value) && !in_array($value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function validateUnassignedElements(ContentTemplate $contentTemplate, Content $entity, Validator $validator): void {
		foreach (
			array_diff(
				array_keys($this->ContentTemplates->getAvailableContentElements()),
				array_column($contentTemplate->contentTemplateElements, 'identifier')
			) as $ls_element
		) {
			if ($entity->getError($ls_element)) {
				continue;
			}

			if ($ls_element === 'column_width') {
				$la_columnWidths = $this->getColumnWidths();

				$validator->add($ls_element, [
					'equalTo' => [
						'rule' => ['equalTo', key($la_columnWidths)],
					],
				]);

				continue;
			}

			if ($ls_element === 'column_last') {
				$validator->add($ls_element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			if ($ls_element === 'column_rtl') {
				$validator->add($ls_element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			$validator->add($ls_element, 'isEmpty', [
				'rule' => function (mixed $value): bool {
					return empty($value) && !in_array($value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \Awyiss\Validation\Validator $validator
	 * @param array $contentAttributes
	 * @param \Awyiss\Validation\Validator $attributesValidator
	 * @return void
	 */
	protected function validateAssignedElements(
		ContentTemplate $contentTemplate,
		Content $entity,
		Validator $validator,
		array $contentAttributes,
		?Validator $attributesValidator
	): void {
		$la_allowedKeyForDuplicating = $this->getAllowedKeyForDuplicating();

		//Traverse all elements that are available inside the content template
		foreach ($contentTemplate->contentTemplateElements as $lo_contentTemplateElement) {
			if (!str_starts_with($lo_contentTemplateElement->identifier, 'attributes.')) {
				// If the content is a duplicate of another content, only require those fields that are allowed for this content
				if ($entity->duplicateOf && !in_array($lo_contentTemplateElement->identifier, $la_allowedKeyForDuplicating)) {
					continue;
				}

				if ($lo_contentTemplateElement->required === true) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$validator->requirePresence($lo_contentTemplateElement->identifier)->notEmptyString($lo_contentTemplateElement->identifier);
					//TODO check if notEmptyString is enough. Some fields might need notEmpty*
				}

				continue;
			}

			/**
			 * If no validator for the attributes is set,
			 * or is duplicating another content,
			 * skip the validation of attributes
			 */
			if (!$attributesValidator || $entity->duplicateOf) {
				continue;
			}

			$ls_identifier = substr($lo_contentTemplateElement->identifier, 11);

			if ($entity->attributes->getError($ls_identifier)) {
				continue;
			}

			if ($lo_contentTemplateElement->required === true) {
				$attributesValidator->requirePresence($ls_identifier);

				switch ($contentAttributes[ $ls_identifier ]['inputType']) {
					case 'checkbox':
						$attributesValidator->add($ls_identifier, [
							'checkboxChecked' => [
								'rule' => ['equalTo', true],
							],
						]);
						break;
					default:
						$attributesValidator->notEmptyString($ls_identifier);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param array $options
	 * @param \Awyiss\ORM\RulesChecker $rules
	 * @return string|bool
	 */
	protected function checkValidDuplicateRules(Content $entity): string|bool {
		// Get all children of the current entity
		$la_nestedChildren = $entity->getNestedChildren()->toArray();
		$la_duplicatedContentIds = array_column($la_nestedChildren, 'duplicateOf');

		if (!$entity->duplicateOf && !$la_duplicatedContentIds) {
			return true;
		}

		if (!empty($entity->duplicateOf)) {
			// Disallow self-duplicating contents
			if (!$entity->isNew() && $entity->id === $entity->duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_self_duplicating');
			}

			/** @var \Awyiss\Model\Entity\Content $lo_duplicateOf */
			$lo_duplicateOf = $this->findById($entity->duplicateOf)->first();

			// Disallow duplicating contents that do not exist
			if (!$lo_duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_valid_duplicate_of');
			}

			// Disallow duplicating contents that are not on the same page
			if ($lo_duplicateOf->pageId === $entity->pageId) {
				return __df($this->getI18nDomain(), 'validation', 'error_duplicate_not_on_same_page');
			}

			// Disallow circular duplicating
			if (!$entity->isNew() && $lo_duplicateOf->duplicateOf === $entity->id) {
				return __df($this->getI18nDomain(), 'validation', 'error_circular_duplicating');
			}
		}

		if ($la_duplicatedContentIds) {
			$la_duplicatedContents = $this->find()->where(['id IN' => $la_duplicatedContentIds])->all()->indexBy('id')->toArray();

			/** @var \Awyiss\Model\Entity\Content $lo_duplicatedContent */
			foreach ($la_duplicatedContents as $lo_duplicatedContent) {
				if ($lo_duplicatedContent->pageId === $entity->pageId) {
					return __df($this->getI18nDomain(), 'validation', 'error_children_not_duplicating_contents_on_same_page');
				}
			}
		}

		return true;
	}
}
