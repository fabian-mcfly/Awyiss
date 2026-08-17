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
	 * @var array The column indents
	 */
	protected array $columnIndents;
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
	 * @var string
	 */
	protected string $forScope;
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
		'blocklistedColumns' => ['pageId'],
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
			'foreignKey' => 'contentAreaId',
			'joinType' => 'INNER',
			'propertyName' => 'contentArea',
		]);

		$this->belongsTo('ContentTemplates', [
			'foreignKey' => 'contentTemplateId',
			'propertyName' => 'contentTemplate',
		]);

		$this->hasMany('DuplicatingContents', [
			'bindingKey' => 'duplicateOf',
			'className' => 'Contents',
			'foreignKey' => 'id',
			'propertyName' => 'duplicatingContents',
		]);

		$this->belongsTo('DuplicateOfContents', [
			'bindingKey' => 'id',
			'className' => 'Contents',
			'foreignKey' => 'duplicateOf',
			'propertyName' => 'duplicateOfContent',
		]);

		$this->belongsTo('Forms', [
			'foreignKey' => 'formId',
		]);

		$this->belongsTo('Surveys', [
			'foreignKey' => 'surveyId',
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
	 * Finds the most recent content for each page.
	 * Used to determine the last changed content and
	 * therefore the last changed page.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findLatestForPages(SelectQuery $query): SelectQuery {
		/**
		 * SELECT Contents.pageId AS Contents__pageId, Contents.id AS Contents__id, Contents.changedOn AS Contents__changedOn, Contents.createdOn AS Contents__createdOn
		 * FROM contents Contents
		 * INNER JOIN (SELECT latest.pageId AS latestPageId, (MAX(COALESCE(latest.changedOn, latest.createdOn))) AS latestDate FROM Contents as latest GROUP BY latest.pageId) latest
		 * ON (Contents.pageId = latest.latestPageId AND COALESCE(Contents.changedOn, Contents.createdOn) = latest.latestDate)
		 * WHERE (Contents.deleted = 0)
		 * GROUP BY Contents.pageId
		 * ORDER BY changedOn DESC, createdOn DESC, systemOrder ASC;
		 *
		 * @noinspection GrazieInspection
		 */
		$subquery = $this
			->find()
			->select([
				'latestPageId' => 'pageId',
				'latestDate' => $this
					->find()
					->func()
					->max(
						new FunctionExpression('COALESCE', [
							'Contents.changedOn' => 'literal',
							'Contents.createdOn' => 'literal',
						])
					),
			])
			->groupBy('pageId')
			->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			])
		;

		return $query
			->select([
				'pageId',
				'id',
				'changedOn',
				'createdOn',
			])
			->innerJoin(
				['latest' => $subquery],
				function (QueryExpression $exp/*, SelectQuery $q*/) {
					return $exp
						->eq('Contents.pageId', new IdentifierExpression('latestPageId'))
						->eq(
							new FunctionExpression('COALESCE', [
								'Contents.changedOn' => 'literal',
								'Contents.createdOn' => 'literal',
							]),
							new IdentifierExpression('latestDate')
						)
					;
				}
			)
			->where(['Contents.deleted' => 0])
			->groupBy('Contents.pageId')
			->orderBy([
				'Contents.changedOn' => 'DESC',
				'Contents.createdOn' => 'DESC',
			])
			->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			])
		;
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
		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $className */
		$className = $this->columnSystem['className'];
		$className::setMaxDenominator($this->columnSystem['maxColumns']);

		$this->columnWidths = $className::getColumnWidths();
		$this->columnIndents = $className::getColumnIndents();
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
				/** @var \Awyiss\Model\Entity\Page $page */
				$page = $this->{$this->getPageRole()->tableAlias()}->get($entity->pageId, contain: [
					'PageTemplates',
				]);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				$entity->setError('pageId', [
					'validPageId' => __df($this->getI18nDomain(), 'Validation', 'error_valid_page_id'),
				]);

				return false;
			}


			/*
			 * Retrieve the content template of the current entity
			 * This works as an existsIn-like rule
			 */
			try {
				/** @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate */
				$contentTemplate = $this->ContentTemplates->get(
					$entity->contentTemplateId,
					contain: [
						'ContentAreas' => [
							'queryBuilder' => function (SelectQuery $query) use ($page) {
								return $query->where(['ContentTemplateContentAreas.pageTemplateId' => $page->pageTemplateId]);
							},
						],
						'ContentTemplateElements',
					],
				);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				//Content Template not found
				$entity->setError('contentTemplateId', [
					'validContentTemplateId' => __df($this->getI18nDomain(), 'Validation', 'error_valid_content_template_id'),
				]);

				return false;
			}


			//Content Area not found in the content template
			if (!in_array($entity->contentAreaId, array_column($contentTemplate->contentAreas, 'id'))) {
				$entity->setError('contentAreaId', [
					'validContentAreaId' => __df($this->getI18nDomain(), 'Validation', 'error_valid_content_area_id'),
				]);

				return false;
			}


			// Make sure that all children of the current entity can be moved to the target content area as well
			if (!$this->childrenCanBeMoved($entity, $page->pageTemplateId)) {
				$entity->setError('contentAreaId', [
					'validContentAreaIdForChildren' => __df(
						$this->getI18nDomain(),
						'Validation',
						'error_valid_content_area_id_for_children'
					),
				]);

				return false;
			}


			/** @var \Awyiss\Validation\Validator $validator */
			$validator = new $this->_validatorClass();
			$validator->setI18nDomain($this->getI18nDomain());

			$data = $entity->extract();
			if (!empty($entity->attributes)) {
				/** @var \Awyiss\Validation\Validator $attributesValidator */
				$attributesValidator = new $this->_validatorClass();
				$attributesValidator->setI18nDomain($this->getI18nDomain());

				$data['attributes'] = $entity->attributes->extract();
			}

			$this->validateInputFields($entity, $validator, $attributesValidator ?? null, $contentTemplate);

			//Validate the entity using the
			$errors = $validator->validate($data, $entity->isNew());

			if ($this->hasAttributes() && !empty($errors['attributes'])) {
				$entity->attributes->setErrors($errors['attributes']);
			}

			$entity->setErrors($errors);


			return empty($errors);
		}, 'validContentArea');


		$rules->add($rules->existsIn(['formId'], 'Forms', ['allowNullableNulls' => true]), 'validFormId', ['errorField' => 'formId']);


		$rules->add(
			$rules->existsIn(['surveyId'], 'Surveys', ['allowNullableNulls' => true]),
			'validSurveyId',
			['errorField' => 'surveyId']
		);


		$rules->add(function (Content $entity): bool {
			/** @var \Awyiss\Utility\Content\ColumnInterface $width */
			$width = $entity->column['width'];
			/** @var \Awyiss\Utility\Content\ColumnInterface $indent */
			$indent = $entity->column['indent'];

			$totalWidth = $width->getPercentage() + ($indent?->getPercentage() ?? 0);

			if ($totalWidth > 1) {
				return false;
			}

			return true;
		}, 'validWidthIndentCombination', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_width_indent_combination'),
		]);


		$rules->add(
			function (Content $entity) {
				$valid = $this->checkValidDuplicateRules($entity);

				if ($valid !== true && !$entity->duplicateOf) {
					/**
					 * If the entity is not a duplicate of another content but
					 * the validation failed, set the error message to the general error field.
					 *
					 * Most likely, the entity itself does not have a duplicateOf field
					 */
					$entity->setError('_general', $valid);
				}

				return $valid;
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
				$tempFileName = tempnam(sys_get_temp_dir(), 'awyiss_scss_');
				rename($tempFileName, $tempFileName . '.scss');
				$tempFileName .= '.scss';
				file_put_contents($tempFileName, '#Content { ' . PHP_EOL . $entity->css . PHP_EOL . ' }');
				$tempFile = new SplFileInfo($tempFileName);

				ob_start();
				/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $compilerClass */
				$compilerClass = App::className('ScssCompiler', 'Utility/Design');
				try {
					/** @var \Awyiss\Middleware\DesignMiddleware $designMiddleware */
					$designMiddleware = Router::getRequest()->getAttribute('design');
					$css = $compilerClass::compileScss(
						$tempFile,
						ROOT . DS . CUSTOM_DIR . DS . 'asset' . DS,
						$designMiddleware?->getDesignVariables() ?? [],
						true
					);
				}
				catch (Exception | SassException) {
					$css = false;
				}
				ob_end_clean();

				unlink($tempFile->getRealPath());

				return $css !== false;
			},
			'validCss',
			[
				'errorField' => 'css',
			]
		);


		//Ensure that a content has no linked duplicating contents when deleting it.
		$rules->addDelete(
			function (Content $entity): string|bool {
				/** @var \Awyiss\Model\Table\ContentsTable $table */
				$table = FactoryLocator::get('Table')->get('Contents');

				if ($table->exists(['duplicateOf' => $entity->id])) {
					return false;
				}

				// Get all children of the current entity
				$nestedChildren = $entity->getNestedChildren()->toArray();
				$childrenContentIds = array_column($nestedChildren, 'id');

				if ($childrenContentIds) {
					$duplicatingContents = $table
						->find()
						->where(['duplicateOf IN' => $childrenContentIds])
						->count()
					;

					if ($duplicatingContents) {
						return __df($this->getI18nDomain(), 'Validation', 'error_no_duplicated_children');
					}
				}

				return true;
			},
			'noDuplicatingContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_no_duplicating_contents'),
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

		// Get all children of the current entity
		$children = $entity->getNestedChildren([
			'contain' => [
				'ContentTemplates' => [
					'ContentAreas' => [
						'queryBuilder' => function (SelectQuery $query) use ($entity, $pageTemplateId) {
							return $query->where([
								'ContentTemplateContentAreas.contentAreaId' => $entity->contentAreaId,
								'ContentTemplateContentAreas.pageTemplateId' => $pageTemplateId,
							]);
						},
					],
				],
			],
		]);

		/** @var \Awyiss\Model\Entity\Content $child */
		foreach ($children as $child) {
			if (!$child->contentTemplate?->contentAreas) {
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
		$tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $tableLocator->get('Pages');

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(
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
				'languageShortcode',
				'pageRoleId',
				'pageTemplateId',
				'robotsFollow',
				'robotsIndex',
			],
			skipPageRoleCheck: true,
			translate: ['skip' => true],
		);


		return $page;
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
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');
			$pageRole = $pageRoleEnum::tryFromName($pageRole->identifier);
		}

		if ($initializePages) {
			if (!$this->hasAssociation($pageRole->tableAlias())) {
				$this->belongsTo($pageRole->tableAlias(), [
					'bindingKey' => 'id',
					/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
					'finder' => 'forCurrentLanguage',
					'foreignKey' => 'pageId',
					'propertyName' => 'page',
				]);
			}

			/** @var \Awyiss\Model\Behavior\CategoriesBehavior $categoriesBehavior */
			$categoriesBehavior = $this->getBehavior('Categories');
			$categoriesBehavior->setConfig('associationName', $pageRole->tableAlias())->resetCategories();
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
	protected function validateInputFields(
		Content $entity,
		Validator $validator,
		?Validator $attributesValidator,
		ContentTemplate $contentTemplate
	): void {
		$contentAttributes = $this->ContentTemplates->getAvailableContentAttributes();
		$contentAttributes = array_column($contentAttributes, null, 'identifier');

		$this->validateAssignedElements($contentTemplate, $entity, $validator, $contentAttributes, $attributesValidator);

		$this->validateUnassignedElements($contentTemplate, $entity, $validator);

		if (isset($attributesValidator)) {
			$this->validateUnassignedAttributes($contentTemplate, $entity, $contentAttributes, $attributesValidator);

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
		$allowedKeyForDuplicating = $this->getAllowedKeyForDuplicating();

		//Traverse all elements that are available inside the content template
		foreach ($contentTemplate->contentTemplateElements as $contentTemplateElement) {
			if (!str_starts_with($contentTemplateElement->identifier, 'attributes.')) {
				// If the content is a duplicate of another content, only require those fields that are allowed for this content
				if ($entity->duplicateOf && !in_array($contentTemplateElement->identifier, $allowedKeyForDuplicating)) {
					continue;
				}

				if ($contentTemplateElement->required === true) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$validator->requirePresence($contentTemplateElement->identifier)->notEmptyString($contentTemplateElement->identifier);
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

			$identifier = substr($contentTemplateElement->identifier, 11);

			if ($entity->attributes->getError($identifier)) {
				continue;
			}

			if ($contentTemplateElement->required === true) {
				$attributesValidator->requirePresence($identifier);

				switch ($contentAttributes[ $identifier ]['inputType']) {
					case 'checkbox':
						$attributesValidator->add($identifier, [
							'checkboxChecked' => [
								'rule' => ['equalTo', true],
							],
						]);
						break;
					default:
						$attributesValidator->notEmptyString($identifier);
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
	protected function validateUnassignedAttributes(
		ContentTemplate $contentTemplate,
		Content $entity,
		array $contentAttributes,
		?Validator $attributesValidator
	): void {
		$attributes = array_keys($contentAttributes);

		foreach (
			array_diff(
				$attributes,
				$this->ContentTemplates->getAssignedContentAttributes($contentTemplate)
			) as $element
		) {
			if (!$entity->attributes->isDirty($element)) {
				continue;
			}

			$attributesValidator->add($element, 'isEmpty', [
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
			) as $element
		) {
			if ($entity->getError($element)) {
				continue;
			}

			if ($element === 'columnWidth') {
				$columnWidths = $this->getColumnWidths();

				$validator->add($element, [
					'equalTo' => [
						'rule' => ['equalTo', key($columnWidths)],
					],
				]);

				continue;
			}

			if ($element === 'columnLast') {
				$validator->add($element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			if ($element === 'columnRtl') {
				$validator->add($element, [
					'equalTo' => [
						'rule' => ['equalTo', false],
					],
				]);

				continue;
			}

			$validator->add($element, 'isEmpty', [
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
		$nestedChildren = $entity->getNestedChildren()->toArray();
		$duplicatedContentIds = array_column($nestedChildren, 'duplicateOf');

		// Neither the current entity nor any of its nested children are duplicates?
		if (empty($entity->duplicateOf) && !$duplicatedContentIds) {
			return true;
		}

		if ($entity->duplicateOf) {
			// Disallow self-duplicating contents
			if (!$entity->isNew() && $entity->id === $entity->duplicateOf) {
				return __df($this->getI18nDomain(), 'Validation', 'error_not_self_duplicating');
			}

			// Prevent a content (current) from duplicating another one (target),
			// if the (current) content is already duplicated by a content (third).
			if ($entity->id && $this->exists(['duplicateOf' => $entity->id])) {
				return __df($this->getI18nDomain(), 'Validation', 'error_not_duplicating_duplicated');
			}

			/** @var \Awyiss\Model\Entity\Content $duplicateOf */
			$duplicateOf = $this->findById($entity->duplicateOf)->first();

			// Disallow duplicating contents that do not exist
			if (!$duplicateOf) {
				return __df($this->getI18nDomain(), 'Validation', 'error_valid_duplicate_of');
			}

			// Prevents a content (current) from duplicating another content (target),
			// if the (target) content is already duplicating another content (third).
			if ($duplicateOf->duplicateOf) {
				return __df($this->getI18nDomain(), 'Validation', 'error_not_duplicating_duplicating');
			}

			// Disallow duplicating contents that are on the same page
			if ($duplicateOf->pageId === $entity->pageId) {
				return __df($this->getI18nDomain(), 'Validation', 'error_duplicate_not_on_same_page');
			}
		}

		// No nested children to check? Rule is valid.
		if (!$duplicatedContentIds) {
			return true;
		}

		// Find all contents that are duplicated by nested children of the current entity
		$duplicatedContents = $this
			->find()
			->where(['id IN' => $duplicatedContentIds])
			->all()
			->indexBy('id')
			->toArray()
		;

		/**
		 * If any of the nested children of the current entity
		 * is duplicating another content that is on the same page,
		 * return an error message.
		 *
		 * This prevents moving a content to a page if it
		 * would result in a content and its duplicated content
		 * being on the same page.
		 *
		 * @var \Awyiss\Model\Entity\Content $duplicatedContent
		 */
		if (array_any($duplicatedContents, fn($duplicatedContent) => $duplicatedContent->pageId === $entity->pageId)) {
			return __df($this->getI18nDomain(), 'Validation', 'error_children_not_duplicating_contents_on_same_page');
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
		if ($column === 'contentTemplateId') {
			return $this
				->getAssociation('ContentTemplates')
				->find('list', valueField: 'label')
				->toArray()
			;
		}

		if ($column === 'duplicateOf') {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return $this
				->find('threaded')
				->find('mediaAssignments', useMediaEntity: true)
				->all()
				->listNested()
				->printer(
					'label',
					'id',
					'- '
				)
				->toArray()
			;
		}

		if ($column === 'formId') {
			return $this
				->getAssociation('Forms')
				->find('list', valueField: 'label')
				->toArray()
			;
		}

		if ($column === 'surveyId') {
			return $this
				->getAssociation('Surveys')
				->find('list', valueField: 'label')
				->toArray()
			;
		}

		return $this->getBehavior('Search')->getPossibleFieldValues($column, $type);
	}
}
