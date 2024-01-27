<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Validation\Validator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\LogTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator as BaseValidator;
use RuntimeException;


/**
 * Contents Model
 *
 * @property ContentTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ContentTemplates
 * @property PagesTable&\Awyiss\ORM\Association\BelongsTo $Pages
 * @property ContentsTable&\Awyiss\ORM\Association\BelongsTo $ParentContents
 * @property ContentsTable&\Awyiss\ORM\Association\HasMany $ChildContents
 * @property ContentsTable&\Awyiss\ORM\Association\HasMany $DuplicateContents
 * @property ContentsTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfContents
 * @method Content newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @method CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [])
 * @method Content getParent(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [])
 * @method CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
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
		'fieldname' => 'pageId',
		'identifier' => 'page',
		'threaded' => true,
	];
	/**
	 * @var string
	 */
	private string $forScope;
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'relatedColumns' => ['pageId', 'contentAreaId'],
	];
	/**
	 * @var string
	 */
	private string $pageRoleName;
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['pageId', 'contentAreaId', 'parentId'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nest', $this->nest);
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

		$this->hasMany('DuplicateContents', [
			'bindingKey' => 'duplicate_of',
			'className' => 'Contents',
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'id',
		]);

		$this->belongsTo('DuplicateOfContents', [
			'bindingKey' => 'id',
			'className' => 'Contents',
			'foreignKey' => 'duplicate_of',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Awyiss\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Awyiss\Validation\Validator
	 */
	public function validationDefault(BaseValidator $ao_validator): BaseValidator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'pageId',
			'contentAreaId',
			'contentTemplateId',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('pageId');
		$ao_validator->add('pageId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$ao_validator->add('subtitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$ao_validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$ao_validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$ao_validator->notEmptyString('contentAreaId');
		$ao_validator->add('contentAreaId', [
			'isScalar' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('contentTemplateId');
		$ao_validator->add('contentTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('columnwidth', [
			'decimal' => ['rule' => ['decimal']],
			'maxLength' => ['rule' => ['maxLength', 4]],
		]);


		$ao_validator->add('cssClass', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$ao_validator->add('duplicateOf', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('data', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $aa_value): bool {
					return strlen(json_encode($aa_value)) <= 65535;
				},
			],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): BaseRulesChecker {
		$ao_rules->add(function (Content $ao_entity/*, array $aa_options*/): bool {
			/**
			 * Retreive the page and the assigned page template.
			 *
			 * @see ContentsTable::forPageRole
			 */
			try {
				/** @var Page $lo_page */
				$lo_page = $this->{$this->getPageRoleName()}->get($ao_entity->pageId, contain: [
					'PageTemplates',
				]);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				$ao_entity->setError('page_id', __d($this->getI18nDomain(), 'error_valid_page_id'));


				return false;
			}


			/*
			 * Retreive tthe content template of the current entity
			 * This works as an existsIn-like rule
			 */
			try {
				/** @var ContentTemplate $lo_contentTemplate */
				$lo_contentTemplate = $this->ContentTemplates->get(
					$ao_entity->contentTemplateId,
					contain: [
						'ContentAreas' => [
							'queryBuilder' => function (SelectQuery $ao_query) use ($lo_page) {
								return $ao_query->where(['ContentTemplateContentAreas.page_template_id' => $lo_page->pageTemplateId]);
							},
						],
						'ContentTemplateElements',
					],
				);
			}
			catch (RecordNotFoundException | InvalidPrimaryKeyException) {
				//Content template not found
				$ao_entity->setError('content_template_id', __d($this->getI18nDomain(), 'error_valid_content_template_id'));


				return false;
			}


			//Content area not found
			if (!in_array($ao_entity->contentAreaId, array_column($lo_contentTemplate->contentAreas, 'id'))) {
				$ao_entity->setError('content_area_id', __d($this->getI18nDomain(), 'error_valid_content_area_id'));


				return false;
			}


			/** @var \Awyiss\Validation\Validator $lo_validator */
			$lo_validator = new $this->_validatorClass();
			$lo_validator->setI18nDomain($this->getI18nDomain());

			$la_data = $this->validateInputFields($ao_entity, $lo_validator, $lo_contentTemplate);

			//Validate the entity using the
			$la_errors = $lo_validator->validate($la_data, $ao_entity->isNew());

			/** @noinspection PhpUndefinedMethodInspection */
			$la_errors = $this->getEntityClass()::mapFields($la_errors, true);

			if ($this->hasAttributes() && !empty($la_errors['attributes'])) {
				/** @noinspection PhpUndefinedMethodInspection */
				$la_errors['attributes'] = $this->{$this->getAttributesTable(true)}->getEntityClass()::mapFields($la_errors['attributes'], true);
				$ao_entity->attributes->setErrors($la_errors['attributes']);
			}

			$ao_entity->setErrors($la_errors);


			return empty($la_errors);
		}, 'validContentArea');


		$ao_rules->add(
			$ao_rules->existsIn(
				['duplicateOf'],
				'DuplicateOfContents'
			),
			'validDuplicateOf',
			[
				'errorField' => 'duplicateOf',
				'message' => __d($this->getI18nDomain(), 'error_valid_duplicate_of'),
			]
		);


		//Ensure that a content has no linked duplicating contents when deleting it.
		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo(
				'DuplicateContents',
				'_general',
				'Must have zero duplicating contents before deletion.'
			),
			'noDuplicatingContents'
		);


		return $ao_rules;
	}


	/**
	 * Groups the result of a query or the elements of a collection by their `contentAreaId`-value and returns
	 * a new collection
	 *
	 * @noinspection PhpUnused
	 */
	public function groupByContentArea(SelectQuery|CollectionInterface $ax_data): CollectionInterface {
		$lo_data = is_a($ax_data, SelectQuery::class) ? $ax_data->all() : $ax_data;

		foreach ($lo_data->groupBy('contentAreaId') as $ls_contentArea => $la_contents) {
			$lo_data->$ls_contentArea = new Collection($la_contents);
		}


		return $lo_data;
	}


	/**
	 * Groups the result of a query by their `contentAreaId`-value and returns a new collection with all
	 * contents nested and an added `level`-property.
	 *
	 * @param SelectQuery $ao_query
	 * @return CollectionInterface
	 */
	public function nestedByContentArea(SelectQuery $ao_query): CollectionInterface {
		return $ao_query->find('threaded')->all()->groupBy('contentAreaId')->map(function (array $aa_contents): CollectionInterface {
			$lo_contents = (new Collection($aa_contents))->listNested();

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
	 * Creates a threaded list of contents from a query, adding the `level`-property to each content and returns
	 * a collection
	 *
	 * @noinspection PhpUnused
	 */
	public function listNested(SelectQuery $ao_query): CollectionInterface {
		$lo_contents = $ao_query->find('threaded')->all()->listNested();

		/** @var Content $lo_content */
		foreach ($lo_contents as $lo_content) {
			$lo_content->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_content->level = $lo_contents->getDepth();
		}


		return $lo_contents;
	}


	/**
	 * Return a Page-object with the page role and page template associations
	 *
	 * @param int $ai_pageId
	 * @return Page
	 */
	public function getPage(int $ai_pageId): Page {
		try {
			$lo_tableLocator = FactoryLocator::get('Table');
			/** @var Table $lo_pages */
			$lo_pages = $lo_tableLocator->get('Pages');

			/** @var Page $lo_page */
			$lo_page = $lo_pages->get(
				$ai_pageId,
				contain: [
					'PageRoles' => [
						'fields' => [
							'identifier',
							'active',
						],
					],
					'PageTemplates' => [
						'fields' => [
							'id',
							'title',
							'active',
						],
						'ContentAreas' => [
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
					'language_shortcode',
					'page_role_id',
					'page_template_id',
				],
				skipPageRoleCheck: true,
			);
		}
		catch (ForbiddenException) {
			throw new ForbiddenException(sprintf('Access to page id `%s` is forbidden', $ai_pageId));
		}


		return $lo_page;
	}


	/**
	 * Sets this table to run the access check of the 'Pages'-association with a specific page role.
	 *
	 * @param string $as_identifier
	 * @param bool $ab_initializePages
	 * @return void
	 * @throws \Exception
	 */
	public function forPageRole(string $as_identifier, bool $ab_initializePages = true): void {
		$ls_singular = Inflector::singularize(Inflector::underscore($as_identifier));

		$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
		if (!defined($ls_constant)) {
			throw new RuntimeException(sprintf('Cannot use `%s` for page role `%s`', static::class, $as_identifier));
		}

		$ls_alias = Inflector::camelize(Inflector::tableize($as_identifier));
		if ($ab_initializePages) {
			$this->belongsTo($ls_alias, [
				'bindingKey' => 'id',
				'finder' => 'forCurrentLanguage',
				'foreignKey' => 'page_id',
				'joinType' => 'INNER',
				'propertyName' => 'page',
			]);

			/** @var \Awyiss\Model\Behavior\CategoriesBehavior $lo_behavior */
			$lo_behavior = $this->getBehavior('Categories');
			$lo_behavior->setConfig('associationName', $ls_alias)->resetCategories();
		}

		$this->setPageRoleName($ls_alias);
		$this->setForScope($as_identifier);
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
	 * @param string $as_scope
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function setForScope(string $as_scope): void {
		$this->forScope = Inflector::underscore(Inflector::pluralize($as_scope));
	}


	/**
	 * @return string
	 */
	public function getPageRoleName(): string {
		return $this->pageRoleName;
	}


	/**
	 * @param string $as_pageRoleName
	 * @return ContentsTable
	 */
	protected function setPageRoleName(string $as_pageRoleName): static {
		$this->pageRoleName = Inflector::camelize(Inflector::tableize($as_pageRoleName));


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
	 * @param Content $ao_entity
	 * @param Validator $ao_validator
	 * @param ContentTemplate $ao_contentTemplate
	 * @return array
	 */
	protected function validateInputFields(Content $ao_entity, Validator $ao_validator, ContentTemplate $ao_contentTemplate): array {
		$la_data = $ao_entity->extract();

		if (!empty($ao_entity->attributes)) {
			/** @var \Awyiss\Validation\Validator $lo_attributesValidator */
			$lo_attributesValidator = new $this->_validatorClass();
			$lo_attributesValidator->setI18nDomain($this->getI18nDomain());

			$la_data['attributes'] = $ao_entity->attributes->extract();
		}

		$la_contentAttributes = $this->ContentTemplates->getAvailableContentAttributes();
		$la_contentAttributes = array_combine(
			array_column($la_contentAttributes, 'identifier'),
			$la_contentAttributes
		);

		$this->validateAssignedElements($ao_contentTemplate, $ao_entity, $ao_validator, $la_contentAttributes, $lo_attributesValidator ?? null);

		$this->validateUnassignedElements($ao_contentTemplate, $ao_entity, $ao_validator);

		if (isset($lo_attributesValidator)) {
			$this->validateUnassignedAttributes($ao_contentTemplate, $ao_entity, $la_contentAttributes, $lo_attributesValidator);

			if ($lo_attributesValidator->count()) {
				$ao_validator->addNested('attributes', $lo_attributesValidator);
			}
		}


		return $la_data;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ao_schema->setColumnType('data', 'json');
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_contentTemplate
	 * @param \Awyiss\Model\Entity\Content $ao_entity
	 * @param array $aa_contentAttributes
	 * @param \Awyiss\Validation\Validator $ao_attributesValidator
	 * @return void
	 */
	protected function validateUnassignedAttributes(ContentTemplate $ao_contentTemplate, Content $ao_entity, array $aa_contentAttributes, ?Validator $ao_attributesValidator): void {
		$la_attributes = array_keys($aa_contentAttributes);

		foreach (
			array_diff(
				$la_attributes,
				$this->ContentTemplates->getAssignedContentAttributes($ao_contentTemplate)
			) as $ls_element
		) {
			if (!$ao_entity->attributes->isDirty($ls_element)) {
				continue;
			}

			$ao_attributesValidator->add($ls_element, 'isEmpty', [
				'rule' => function (mixed $ax_value): bool {
					return empty($ax_value) && !in_array($ax_value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_contentTemplate
	 * @param \Awyiss\Model\Entity\Content $ao_entity
	 * @param \Awyiss\Validation\Validator $ao_validator
	 * @return void
	 */
	protected function validateUnassignedElements(ContentTemplate $ao_contentTemplate, Content $ao_entity, Validator $ao_validator): void {
		foreach (
			array_diff(
				$this->ContentTemplates->getAvailableContentElements(),
				array_column($ao_contentTemplate->contentTemplateElements, 'identifier')
			) as $ls_element
		) {
			if ($ao_entity->getError($ls_element) || !$ao_entity->isDirty($ls_element)) {
				continue;
			}

			if ($ls_element === 'columnwidth') {
				$ao_validator->add($ls_element, [
					'isFullwidth' => [
						'rule' => ['equalTo', 1.0],
					],
				]);

				continue;
			}

			$ao_validator->add($ls_element, 'isEmpty', [
				'rule' => function (mixed $ax_value): bool {
					return empty($ax_value) && !in_array($ax_value, [false, '0', 0], true);
				},
			]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_contentTemplate
	 * @param \Awyiss\Model\Entity\Content $ao_entity
	 * @param \Awyiss\Validation\Validator $ao_validator
	 * @param array $aa_contentAttributes
	 * @param \Awyiss\Validation\Validator $ao_attributesValidator
	 * @return void
	 */
	protected function validateAssignedElements(
		ContentTemplate $ao_contentTemplate,
		Content $ao_entity,
		Validator $ao_validator,
		array $aa_contentAttributes,
		?Validator $ao_attributesValidator
	): void {
		//Traverse all elements that are available inside the content template
		foreach ($ao_contentTemplate->contentTemplateElements as $lo_contentTemplateElement) {
			if (!str_starts_with($lo_contentTemplateElement->identifier, 'attributes.')) {
				if ($lo_contentTemplateElement->required === true) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$ao_validator->requirePresence($lo_contentTemplateElement->identifier)->notEmptyString($lo_contentTemplateElement->identifier);
					//TODO check if notEmptyString is enouugh. Some fields might need notEmpty*
				}

				continue;
			}

			if (!$ao_attributesValidator) {
				continue;
			}

			$ls_identifier = substr($lo_contentTemplateElement->identifier, 11);

			if ($ao_entity->attributes->getError($ls_identifier)) {
				continue;
			}

			if ($lo_contentTemplateElement->required === true) {
				$ao_attributesValidator->requirePresence($ls_identifier);

				switch ($aa_contentAttributes[ $ls_identifier ]['inputType']) {
					case 'checkbox':
						$ao_attributesValidator->add($ls_identifier, [
							'checkboxChecked' => [
								'rule' => ['equalTo', true],
							],
						]);
						break;
					default:
						$ao_attributesValidator->notEmptyString($ls_identifier);
				}
			}
		}
	}
}
