<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Behavior\AuthorizeBehavior;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\LogTrait;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Exception;
use RuntimeException;


/**
 * Contents Model
 *
 * @property ContentTemplatesTable&BelongsTo $ContentTemplates
 * @property PagesTable&BelongsTo $Pages
 * @property ContentsTable&BelongsTo $ParentContents
 * @property ContentsTable&HasMany $ChildContents
 * @property ContentsTable&HasMany $DuplicateContents
 * @property ContentsTable&BelongsTo $DuplicateOfContents
 *
 * @method Content newDefaultEntity(array $aa_additionalData = [])
 * @method CollectionInterface|NULL getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity)
 * @method Content getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
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
	protected array $_defaultConfig = [
		'nest' => [
			'relatedColumns' => ['pageId', 'contentAreaId'],
		],
		'systemOrder' => [
			'relatedColumns' => ['pageId', 'contentAreaId', 'parentId'],
		],
	];
	private string $forScope;
	private string $pageRoleName;


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nest', $this->getConfig('nest', []));

		$this->belongsTo('ContentAreas', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('ContentTemplates', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Pages', [
			'joinType' => 'INNER',
		]);

		$this->hasMany('DuplicateContents', [
			'bindingKey' => 'duplicate_of',
			'className' => 'Contents',
			'cascadeCallbacks' => TRUE,
			'dependent' => TRUE,
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
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return Validator
	 */
	public function validationDefault (Validator $ao_validator): Validator {
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
				'rule' => function(array $aa_value): bool {
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
	 *
	 * @return RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(function(Content $ao_entity/*, array $aa_options*/): bool {
			/**
			 * Retreive the page and the assigned page template.
			 * This ensures that the user has access to the scope (page role) for the page with `pageId`,
			 * since it uses the correct association, and therefore it's assigned access policy.
			 *
			 * For this to properly work, it's neccessary to set up the association for the right page resp. page role,
			 * using `forPageRole()`
			 *
			 * @see ContentsTable::forPageRole
			 */
			try {
				/** @var Page $lo_page */
				$lo_page = $this->{$this->getPageRoleName()}->get($ao_entity->pageId, contain: [
					'PageTemplates' => [
						'finder' => [
							'all' => [
								'authorize' => ['skip' => TRUE],
							],
						],
					],
				]);
			}
			catch (RecordNotFoundException|InvalidPrimaryKeyException|ForbiddenException) {
				$ao_entity->setError('page_id', __d($this->getI18nDomain(), 'error_valid_page_id'));

				return FALSE;
			}


			/*
			 * Retreive tthe content template of the current entity
			 * This works as an existsIn-like rule
			 */
			try {
				/** @var ContentTemplate $lo_contentTemplate */
				$lo_contentTemplate = $this->ContentTemplates->get($ao_entity->contentTemplateId,
					authorize: ['skip' => TRUE],
					contain: [
						'ContentTemplateContentAreas' => [
							'finder' => [
								'all' => [
									'authorize' => ['skip' => TRUE],
								],
							],
							'queryBuilder' => function(SelectQuery $ao_query) use ($lo_page) {
								return $ao_query->where(['ContentTemplateContentAreas.page_template_id' => $lo_page->pageTemplateId]);
							}
						],
						'ContentTemplateElements' => [
							'finder' => [
								'all' => [
									'authorize' => ['skip' => TRUE],
								],
							],
						],
					],
				);
			}
			catch (RecordNotFoundException|InvalidPrimaryKeyException) {
				//Content template not found
				$ao_entity->setError('content_template_id', __d($this->getI18nDomain(), 'error_valid_content_template_id'));

				return FALSE;
			}


			//Content area not found
			if ( ! in_array($ao_entity->contentAreaId, array_column($lo_contentTemplate->contentTemplateContentAreas, 'content_area_id'))) {
				$ao_entity->setError('content_area_id', __d($this->getI18nDomain(), 'error_valid_content_area_id'));

				return FALSE;
			}


			/** @var \Awyiss\Validation\Validator $lo_validator */
			$lo_validator = new $this->_validatorClass();
			$lo_validator->setI18nDomain($this->getI18nDomain());

			$la_data = $this->validateInputFields($ao_entity, $lo_validator, $lo_contentTemplate);

			//Validate the entity using the
			$la_errors = $lo_validator->validate($la_data, $ao_entity->isNew());

			/** @noinspection PhpUndefinedMethodInspection */
			$la_errors = $this->getEntityClass()::mapFields($la_errors, TRUE);

			if ($this->hasAttributes() && ! empty($la_errors['attributes'])) {
				/** @noinspection PhpUndefinedMethodInspection */
				$la_errors['attributes'] = $this->{$this->getAttributesTable(TRUE)}->getEntityClass()::mapFields($la_errors['attributes'], TRUE);
				$ao_entity->attributes->setErrors($la_errors['attributes']);
			}

			$ao_entity->setErrors($la_errors);

			return empty($la_errors);
		});


		$ao_rules->add(function(Content $ao_entity, array $aa_options) use ($ao_rules): bool {
			if (($aa_options['checkRules'] ?? TRUE) === FALSE) {
				dd(__FILE__, __LINE__);
			}

			if ( ! $ao_entity->get('parentId')) {
				return TRUE;
			}

			$lo_existsIn = $ao_rules->existsIn(['parentId', 'pageId', 'contentAreaId'], 'ParentContents',
				[
					'errorField' => 'parentId',
					'message' => __dfx($this->getI18nDomain(), 'validation', 'contents', 'error_valid_parent_id'),
				]
			);

			return $lo_existsIn($ao_entity, $aa_options);
		}, 'validParentId');


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
		$ao_rules->addDelete($ao_rules->isNotLinkedTo('DuplicateContents',
			'_general',
			'Must have zero duplicating contents before deletion.'));


		return $ao_rules;
	}


	/**
	 * Groups the result of a query or the elements of a collection by their `contentAreaId`-value and returns
	 * a new collection
	 *
	 * @noinspection PhpUnused
	 */
	public function groupByContentArea (SelectQuery|CollectionInterface $ax_data): CollectionInterface {
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
	 *
	 * @return CollectionInterface
	 */
	public function nestedByContentArea (SelectQuery $ao_query): CollectionInterface {
		return $ao_query->find('threaded')->all()->groupBy('contentAreaId')->map(function(array $aa_contents): CollectionInterface {
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
	public function listNested (SelectQuery $ao_query): CollectionInterface {
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
	 * TODO: move to somewhere safe so it's not usable by anyone
	 *
	 * @param int $ai_pageId
	 *
	 * @return Page
	 */
	public function getPage (int $ai_pageId): Page {
		try {
			$lo_tableLocator = FactoryLocator::get('Table');
			/** @var Table $lo_pages */
			$lo_pages = $lo_tableLocator->get('Pages');

			/** @var Page $lo_page */
			$lo_page = $lo_pages->get($ai_pageId,
				authorize: [
					//'failSilently' => FALSE,
					'skip' => TRUE,
				],
				contain: [
					'PageRoles' => [
						'fields' => [
							'identifier',
							'active',
						],
						'finder' => ['all' => ['authorize' => ['skip' => TRUE]]],
						/*'queryBuilder' => function(SelectQuery $ao_query): SelectQuery {
							$ao_query->applyOptions(['authorize' => ['skip' => TRUE]]);

							return $ao_query;
						},*/
					],
					'PageTemplates' => [
						'fields' => [
							'id',
							'title',
							'active',
						],
						'finder' => ['all' => ['authorize' => ['skip' => TRUE]]],
						/*'queryBuilder' => function(SelectQuery $ao_query): SelectQuery {
							$ao_query->applyOptions(['authorize' => ['skip' => TRUE]]);

							return $ao_query;
						},*/
						'ContentAreas' => [
							'fields' => [
								'id',
								'title',
								'active',
							],
							'finder' => ['all' => ['authorize' => ['skip' => TRUE]]],
						],
					],
				],
				fields: [
					'id',
					'language_shortcode',
					'page_role_id',
					'page_template_id',
				],
				skipPageRoleCheck: TRUE,
			);
		}
		catch (ForbiddenException) {
			throw new ForbiddenException(sprintf('Access to page id `%s` is forbidden', $ai_pageId));
		}

		return $lo_page;
	}


	/**
	 * Sets this table to use the 'Pages'-association for a specific page.
	 *
	 * @param int $ai_pageId
	 *
	 * @return Page
	 *
	 * @throws ForbiddenException
	 * @throws InvalidPrimaryKeyException
	 * @throws MissingTableClassException
	 * @throws RecordNotFoundException
	 * @throws Exception
	 * @throws RuntimeException
	 */
	/*public function forPage (Page $ao_page): Page {
		$this->forPageRole($ao_page->page_role->identifier);

		return $ao_page;
	}*/


	/**
	 * Sets this table to run the access check of the 'Pages'-association with a specific page role.
	 *
	 * @param string $as_identifier
	 * @param bool $ab_initializePages
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function forPageRole (string $as_identifier, bool $ab_initializePages = TRUE): void {
		//Remember the currently used foreign key for the Pages association
		//$ls_foreignKey = $this->Pages->getForeignKey();

		$ls_singular = Inflector::singularize(Inflector::underscore($as_identifier));

		$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
		if ( ! defined($ls_constant)) {
			throw new RuntimeException(sprintf('Cannot use `%s` for page role `%s`', static::class, $as_identifier));
		}

		$ls_alias = Inflector::camelize(Inflector::tableize($as_identifier));
		if ($ab_initializePages) {
			$this->belongsTo($ls_alias, [
				'bindingKey' => 'id',
				'foreignKey' => 'page_id',
				'joinType' => 'INNER',
				'propertyName' => 'page',
			]);
		}

		$this->setPageRoleName($ls_alias);
		$this->setForScope($as_identifier);
	}


	/**
	 * Returns the scope - the plural form of the page role identifier - that's set for the authorization behavior.
	 *
	 * @return string
	 *
	 * @noinspection PhpUnused
	 */
	public function getForScope (): string {
		if ( ! isset($this->forScope)) {
			throw new RuntimeException(sprintf('Cannot use `%s` without calling `forPageRole` first', static::class));
		}

		return $this->forScope;
	}


	/**
	 * Sets the scope the authorization behavior has to check.
	 *
	 * @param string $as_scope
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function setForScope (string $as_scope): void {
		if ($this->hasBehavior('Authorize')) {
			/** @var AuthorizeBehavior $lo_authorization */
			$lo_authorization = $this->getBehavior('Authorize');
			$lo_authorization->setScope($as_scope);

			if ($this->hasAttributes()) {
				/** @var AuthorizeBehavior $lo_authorization */
				$lo_authorization = $this->getAssociation('AttributesContents')->getBehavior('Authorize');
				$lo_authorization->setScope($as_scope);
			}

			//Also set the scope on the parent and children associations
			$this->ChildContents->getBehavior('Authorize')->setScope($as_scope);
			$this->ParentContents->getBehavior('Authorize')->setScope($as_scope);
			$this->DuplicateContents->getBehavior('Authorize')->setScope($as_scope);
			$this->DuplicateOfContents->getBehavior('Authorize')->setScope($as_scope);
		}

		$this->forScope = Inflector::underscore(Inflector::pluralize($as_scope));
	}


	/**
	 * @return string
	 */
	public function getPageRoleName (): string {
		return $this->pageRoleName;
	}


	/**
	 * @param string $as_pageRoleName
	 *
	 * @return ContentsTable
	 */
	protected function setPageRoleName (string $as_pageRoleName): static {
		$this->pageRoleName = Inflector::camelize(Inflector::tableize($as_pageRoleName));

		return $this;
	}


	/**
	 * @return void
	 */
	public function disableCascadeCallbacks (): void {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->ChildContents->setDependent(FALSE)->setCascadeCallbacks(FALSE);
	}


	/**
	 * @return void
	 */
	public function enableCascadeCallbacks (): void {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->ChildContents->setDependent(TRUE)->setCascadeCallbacks(TRUE);
	}


	/**
	 * @param Content $ao_entity
	 * @param Validator $ao_validator
	 * @param ContentTemplate $ao_contentTemplate
	 *
	 * @return array
	 */
	protected function validateInputFields (Content $ao_entity, Validator $ao_validator, ContentTemplate $ao_contentTemplate): array {
		$la_data = $ao_entity->extract();

		if ( ! empty($ao_entity->attributes)) {
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

		//Traverse all elements that are available inside the content template
		foreach ($ao_contentTemplate->contentTemplateElements as $lo_contentTemplateElement) {
			if ( ! str_starts_with($lo_contentTemplateElement->identifier, 'attributes.')) {
				if ($lo_contentTemplateElement->required === TRUE) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$ao_validator->requirePresence($lo_contentTemplateElement->identifier)
						->notEmptyString($lo_contentTemplateElement->identifier);
					//TODO check if notEmptyString is enouugh. Some fields might need notEmpty*
				}

				continue;
			}

			if (empty($ao_entity->attributes)) {
				continue;
			}

			$ls_identifier = substr($lo_contentTemplateElement->identifier, 11);

			if ($ao_entity->attributes->getError($ls_identifier)) {
				continue;
			}

			if ($lo_contentTemplateElement->required === TRUE) {
				/** @noinspection PhpUndefinedVariableInspection */
				$lo_attributesValidator->requirePresence($ls_identifier);

				switch ($la_contentAttributes[ $ls_identifier ]['inputType']) {
					case 'checkbox':
						$lo_attributesValidator->add($ls_identifier, [
							'checkboxChecked' => [
								'rule' => ['equalTo', TRUE],
							]
						]);
						break;
					default:
						$lo_attributesValidator->notEmptyString($ls_identifier);
				}
			}
		}

		foreach (
			array_diff(
				$this->ContentTemplates->getAvailableContentElements(),
				array_column($ao_contentTemplate->contentTemplateElements, 'identifier')
			) as $ls_element
		) {
			if ($ao_entity->getError($ls_element) || ! $ao_entity->isDirty($ls_element)) {
				continue;
			}

			if ($ls_element === 'columnwidth') {
				$ao_validator->add($ls_element, [
					'isFullwidth' => [
						'rule' => ['equalTo', 1.0],
					]
				]);

				continue;
			}

			$ao_validator->add($ls_element, 'isEmpty', [
				'rule' => function(mixed $ax_value): bool {
					return empty($ax_value) && ! in_array($ax_value, [FALSE, '0', 0], TRUE);
				},
			]);
		}

		if ( ! empty($ao_entity->attributes)) {
			$la_attributes = array_keys($la_contentAttributes);
			foreach (
				array_diff(
					$la_attributes,
					$this->ContentTemplates->getAssignedContentAttributes($ao_contentTemplate)
				) as $ls_element
			) {
				if ( ! $ao_entity->attributes->isDirty($ls_element)) {
					continue;
				}

				/** @noinspection PhpUndefinedVariableInspection */
				$lo_attributesValidator->add($ls_element, 'isEmpty', [
					'rule' => function(mixed $ax_value): bool {
						return empty($ax_value) && ! in_array($ax_value, [FALSE, '0', 0], TRUE);
					},
				]);
			}
		}

		/** @noinspection PhpUndefinedVariableInspection */
		if (isset($lo_attributesValidator) && $lo_attributesValidator->count()) {
			$ao_validator->addNested('attributes', $lo_attributesValidator);
		}

		return $la_data;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema (TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ao_schema->setColumnType('data', 'json');
	}
}
