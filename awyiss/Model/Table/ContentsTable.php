<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\AwyissColumnSystem;
use Awyiss\Utility\Inflector;
use Awyiss\Validation\Validator;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator as BaseValidator;
use Exception;
use RuntimeException;
use ScssPhp\ScssPhp\Exception\SassException;
use SplFileInfo;


/**
 * Contents Model
 *
 * @property \Awyiss\Model\Table\ContentAreasTable&\Awyiss\ORM\Association\BelongsTo $ContentAreas
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ContentTemplates
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $Pages
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\BelongsTo $ParentContents
 * @property \Awyiss\Model\Table\SurveysTable&\Awyiss\ORM\Association\BelongsTo $Surveys
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $ChildContents
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $DuplicatingContents
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfContents
 * @method \Awyiss\Model\Entity\Content newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\Content getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class ContentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'contents';


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
	protected array $search = [
		'blocklistedColumns' => ['page_id'],
	];
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

		$this->belongsTo('ContentTemplates');

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

		$this->belongsTo('Forms');

		$this->belongsTo('Surveys');
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
	 * Finds the most recent content for each page.
	 * Used to determine the last changed content and
	 * therefore the last changed page.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findLatestForPages(SelectQuery $query): SelectQuery {
		/**
		 * SELECT Contents.page_id AS Contents__page_id, Contents.id AS Contents__id, Contents.changed_on AS Contents__changed_on, Contents.created_on AS Contents__created_on
		 * FROM contents Contents
		 * INNER JOIN (SELECT latest.page_id AS latest_page_id, (MAX(COALESCE(latest.changed_on, latest.created_on))) AS latest_date FROM Contents as latest GROUP BY latest.page_id) latest
		 * ON (Contents.page_id = latest.latest_page_id AND COALESCE(Contents.changed_on, Contents.created_on) = latest.latest_date)
		 * WHERE (Contents.deleted = 0)
		 * GROUP BY Contents.page_id
		 * ORDER BY changed_on DESC, created_on DESC, system_order ASC;
		 *
		 * @noinspection GrazieInspection
		 */
		$lo_subquery = $this->find()->select([
			'latest_page_id' => 'page_id',
			'latest_date' => $this->find()->func()->max(
				new FunctionExpression('COALESCE', ['changed_on' => 'literal', 'created_on' => 'literal'])
			),
		])->groupBy('page_id')->applyOptions([
			'attributes' => [
				'skip' => true,
			],
		]);

		return $query->select([
			'page_id',
			'id',
			'changed_on',
			'created_on',
		])->innerJoin(
			['latest' => $lo_subquery],
			function (QueryExpression $exp/*, SelectQuery $q*/) {
				return $exp->eq('Contents.page_id', new IdentifierExpression('latest_page_id'))->eq(
					new FunctionExpression('COALESCE', [
						'Contents.changed_on' => 'literal',
						'Contents.created_on' => 'literal',
					]),
					new IdentifierExpression('latest_date')
				);
			}
		)->where(['Contents.deleted' => 0])->groupBy('Contents.page_id')->orderBy([
			'Contents.changed_on' => 'DESC',
			'Contents.created_on' => 'DESC',
		])->applyOptions([
			'attributes' => [
				'skip' => true,
			],
		]);
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
	 * @inheritDoc
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
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('subtitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
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


		$validator->add('columnLast', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('columnRtl', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('cssClass', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('css', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
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


		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('surveyId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
	 * @noinspection DuplicatedCode
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): BaseRulesChecker {
		$rules->add(function (Content $entity/*, array $options*/): bool {
			/**
			 * Retrieve the page and the assigned page template.
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
				$entity->setError('pageId', [
					'validPageId' => __df($this->getI18nDomain(), 'validation', 'error_valid_page_id'),
				]);

				return false;
			}


			/*
			 * Retrieve the content template of the current entity
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
				//Content Template not found
				$entity->setError('content_template_id', [
					'validContentTemplateId' => __df($this->getI18nDomain(), 'validation', 'error_valid_content_template_id'),
				]);

				return false;
			}


			//Content Area not found in the content template
			if (!in_array($entity->contentAreaId, array_column($lo_contentTemplate->contentAreas, 'id'))) {
				$entity->setError('content_area_id', [
					'validContentAreaId' => __df($this->getI18nDomain(), 'validation', 'error_valid_content_area_id'),
				]);

				return false;
			}


			// Make sure that all children of the current entity can be moved to the target content area as well
			if (!$this->childrenCanBeMoved($entity, $lo_page->pageTemplateId)) {
				$entity->setError('content_area_id', [
					'validContentAreaIdForChildren' => __df($this->getI18nDomain(), 'validation', 'error_valid_content_area_id_for_children'),
				]);

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


		$rules->add($rules->existsIn(['formId'], 'Forms', ['allowNullableNulls' => true]), 'validFormId', ['errorField' => 'formId']);


		$rules->add($rules->existsIn(['surveyId'], 'Surveys', ['allowNullableNulls' => true]), 'validSurveyId', ['errorField' => 'surveyId']);


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


		$rules->add(
			function (Content $entity) {
				if (empty($entity->css) || !$entity->isDirty('css')) {
					return true;
				}

				// Replace any Windows line endings with Unix line endings
				$entity->css = str_replace("\r\n", "\n", $entity->css);

				if ($entity->hasOriginal('css') && $entity->getOriginal('css') === $entity->css) {
					return true;
				}

				// If there's an @import rule, the SCSS is invalid
				if (str_contains($entity->css, '@import')) {
					return false;
				}

				// compileScss requires a \SplFileInfo instance and the file needs to have the `.scss` extension
				$ls_tempFile = tempnam(sys_get_temp_dir(), 'awyiss_scss_');
				rename($ls_tempFile, $ls_tempFile . '.scss');
				$ls_tempFile .= '.scss';
				file_put_contents($ls_tempFile, '#Content { ' . $entity->css . ' }');
				$lo_tempFile = new SplFileInfo($ls_tempFile);

				/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $ls_compilerClass */
				$ls_compilerClass = App::className('ScssCompiler', 'Utility/Design');
				try {
					/** @var \Awyiss\Middleware\DesignMiddleware $lo_designMiddleware */
					$lo_designMiddleware = Router::getRequest()->getAttribute('design');
					$ls_css = $ls_compilerClass::compileScss($lo_tempFile, ROOT . DS . CUSTOM_DIR . DS . 'asset' . DS, $lo_designMiddleware?->getDesignVariables() ?? [], true);
				}
				catch (Exception | SassException) {
					$ls_css = false;
				}

				unlink($lo_tempFile->getRealPath());

				return $ls_css !== false;
			},
			'validCss',
			[
				'errorField' => 'css',
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
		if ($entity->isNew()) {
			return true;
		}

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
			'mediaAssignments',
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
							'active' => [
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
				'robots_follow',
				'robots_index',
			],
			skipPageRoleCheck: true,
			translate: ['skip' => true],
		);


		return $lo_page;
	}


	/**
	 * Sets this table to run the access check of the 'Pages'-association with a specific page role.
	 *
	 * @param \Awyiss\Model\Entity\PageRole|\Awyiss\Model\Enum\PageRoleEnumInterface $pageRole
	 * @param bool $initializePages
	 * @return void
	 * @throws \Exception
	 */
	public function forPageRole(PageRole|PageRoleEnumInterface $pageRole, bool $initializePages = true): void {
		if ($pageRole instanceof PageRole) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
			/** @noinspection PhpVariableNamingConventionInspection */
			$pageRole = $ls_pageRoleEnum::tryFromName($pageRole->identifier);
		}

		if ($initializePages) {
			if (!$this->hasAssociation($pageRole->tableAlias())) {
				$this->belongsTo($pageRole->tableAlias(), [
					'bindingKey' => 'id',
					/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
					'finder' => 'forCurrentLanguage',
					'foreignKey' => 'page_id',
					'propertyName' => 'page',
				]);
			}

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
		$la_contentAttributes = array_column($la_contentAttributes, null, 'identifier');

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
	 * @param \Awyiss\Validation\Validator $validator
	 * @param array $contentAttributes
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
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
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param array $contentAttributes
	 * @param \Awyiss\Validation\Validator|null $attributesValidator
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
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return string|bool
	 * @noinspection DuplicatedCode
	 */
	protected function checkValidDuplicateRules(Content $entity): string|bool {
		// Get all children of the current entity
		$la_nestedChildren = $entity->getNestedChildren()->toArray();
		$la_duplicatedContentIds = array_column($la_nestedChildren, 'duplicateOf');

		// Neither the current entity nor any of its nested children are duplicates?
		if (empty($entity->duplicateOf) && !$la_duplicatedContentIds) {
			return true;
		}

		if ($entity->duplicateOf) {
			// Disallow self-duplicating contents
			if (!$entity->isNew() && $entity->id === $entity->duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_self_duplicating');
			}

			// Prevent a content (current) from duplicating another one (target),
			// if the (current) content is already duplicated by a content (third).
			if ($entity->id && $this->exists(['duplicate_of' => $entity->id])) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_duplicating_duplicated');
			}

			/** @var \Awyiss\Model\Entity\Content $lo_duplicateOf */
			$lo_duplicateOf = $this->findById($entity->duplicateOf)->first();

			// Disallow duplicating contents that do not exist
			if (!$lo_duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_valid_duplicate_of');
			}

			// Prevents a content (current) from duplicating another content (target),
			// if the (target) content is already duplicating another content (third).
			if ($lo_duplicateOf->duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_duplicating_duplicating');
			}

			// Disallow duplicating contents that are on the same page
			if ($lo_duplicateOf->pageId === $entity->pageId) {
				return __df($this->getI18nDomain(), 'validation', 'error_duplicate_not_on_same_page');
			}
		}

		// No nested children to check? Rule is valid.
		if (!$la_duplicatedContentIds) {
			return true;
		}

		// Find all contents that are duplicated by nested children of the current entity
		$la_duplicatedContents = $this->find()->where(['id IN' => $la_duplicatedContentIds])->all()->indexBy('id')->toArray();

		/** @var \Awyiss\Model\Entity\Content $lo_duplicatedContent */
		foreach ($la_duplicatedContents as $lo_duplicatedContent) {
			/**
			 * If any of the nested children of the current entity
			 * is duplicating another content that is on the same page,
			 * return an error message.
			 *
			 * This prevents moving a content to a page if it
			 * would result in a content and its duplicated content
			 * being on the same page.
			 */
			if ($lo_duplicatedContent->pageId === $entity->pageId) {
				return __df($this->getI18nDomain(), 'validation', 'error_children_not_duplicating_contents_on_same_page');
			}
		}

		return true;
	}


	/**
	 * Get possible field values for the search form.
	 *
	 * @param string $column
	 * @param string|null $type
	 * @return array|null
	 * @throws \ReflectionException
	 */
	public function getPossibleFieldValues(string $column, ?string $type = null): ?array {
		if ($column === 'content_template_id') {
			return $this->getAssociation('ContentTemplates')->find('list', valueField: 'label')->toArray();
		}

		if ($column === 'duplicate_of') {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return $this->find('threaded')->find('mediaAssignments', useMediaEntity: true)->all()->listNested()->printer('label', 'id', '- ')->toArray();
		}

		if ($column === 'form_id') {
			return $this->getAssociation('Forms')->find('list', valueField: 'label')->toArray();
		}

		if ($column === 'survey_id') {
			return $this->getAssociation('Surveys')->find('list', valueField: 'label')->toArray();
		}

		return $this->getBehavior('Search')->getPossibleFieldValues($column, $type);
	}
}
