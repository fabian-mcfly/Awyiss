<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Database\Type\PageRoleEnumInterface;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * Pages Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $DuplicatingPages
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfPages
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $ParentPages
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $ChildPages
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @method \Awyiss\Model\Entity\Page newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(EntityInterface $ao_entity, array $aa_options = [])
 * @method \Awyiss\Model\Entity\Page getParent(EntityInterface $ao_entity, array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class PagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'pages';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'finder' => 'forCurrentLanguage',
		'foreignKey' => 'parent_id',
	];
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'relatedColumns' => ['languageShortcode', 'pageRoleId'],
	];
	/**
	 * @var \Awyiss\Database\Type\PageRoleEnumInterface
	 */
	protected PageRoleEnumInterface $pageRole;
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['languageShortcode', 'pageRoleId', 'parentId'],
	];


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function initialize(array $aa_config): void {
		/** @var class-string<\Awyiss\Database\Type\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$la_parts = explode('\\', static::class);

		$this->pageRole = $ls_pageRoleEnum::tryFromName(substr(end($la_parts), 0, -5));

		parent::initialize($aa_config);

		$this->addBehavior('Nest', $this->nest);
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->buildPagesAssociations();

		$this->hasMany('Contents', [
			'cascadeCallbacks' => true,
			'className' => 'Contents',
			'dependent' => true,
			'foreignKey' => 'page_id',
		]);

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('PageRoles', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('PageTemplates', [
			'bindingKey' => [
				'id',
				'page_role_id',
			],
			'joinType' => 'INNER',
			'foreignKey' => [
				'page_template_id',
				'page_role_id',
			],
		]);
	}


	/**
	 * @return \Awyiss\Database\Type\PageRoleEnumInterface
	 */
	public function getPageRole(): PageRoleEnumInterface {
		return $this->pageRole;
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'languageShortcode',
			'slug',
			'title',
			'pageRoleId',
			'pageTemplateId',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('languageShortcode');
		$ao_validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'rule' => function ($as_shortcode) {
					return strlen($as_shortcode) == 2;
				},
			],
		]);


		$ao_validator->notEmptyString('slug');
		$ao_validator->add('slug', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('redirectLink', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
			'url' => ['rule' => ['url', true]],
		]);


		$ao_validator->add('metaTitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('metaDescription', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('robotsIndex', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('robotsFollow', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->notEmptyString('pageRoleId');
		$ao_validator->add('pageRoleId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('pageTemplateId');
		$ao_validator->add('pageTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('duplicateOf', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('parentsActive', [
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
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole->name));


		$ao_rules->add(
			$ao_rules->existsIn('languageShortcode', 'Languages'),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'pages', 'error_language_exists'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn('pageRoleId', 'PageRoles'),
			'validPageRole',
			[
				'errorField' => 'pageRoleId',
				'message' => __d($this->getI18nDomain(), 'error_valid_page_role'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn(['pageTemplateId', 'page_role_id'], 'PageTemplates'),
			'validPageTemplate',
			[
				'errorField' => 'pageTemplateId',
				'message' => __d($this->getI18nDomain(), 'error_valid_page_template'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn('duplicateOf', 'DuplicateOf' . $ls_pageRole),
			'validDuplicateOf',
			[
				'errorField' => 'duplicateOf',
				'message' => __d($this->getI18nDomain(), 'error_valid_duplicate_of'),
			]
		);


		//Ensure that a page has no linked duplicating pages when deleting it.
		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo(
				'DuplicatingPages',
				'_general',
				__d($this->getI18nDomain(), 'error_no_duplicating_pages')
			),
			'noDuplicatingPages'
		);


		$ao_rules->addDelete(function (Page $ao_page/*, array $aa_options = []*/): bool {
			$lo_children = $this->getNestedChildren($ao_page, [
				'finder' => [
					'all' => [
						'skipPageRoleCheck' => true,
					],
				],
				'skipFields' => [
					'pageRoleId',
				],
			]);

			if (!$lo_children) {
				return true;
			}

			$la_pageRoles = array_unique($lo_children->extract('pageRoleId')->toList(), SORT_REGULAR);
			$la_pageRoles = array_filter($la_pageRoles, fn (PageRoleEnumInterface $ae_pageRole) => $ae_pageRole != $ao_page->pageRoleId);

			return !$la_pageRoles;
		}, 'noNestedChildrenWithDifferentPageRole', [
			'errorField' => '_general',
			'message' => __d($this->getI18nDomain(), 'error_no_nested_children_with_different_page_role'),
		]);


		return $ao_rules;
	}


	/**
	 * Add a where-condition that limits all results to the page role set for this model
	 *
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options): void {
		if (!($ao_options['skipPageRoleCheck'] ?? false)) {
			$ao_query->where(['page_role_id' => $this->getPageRole()]);
		}
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ls_field = $this->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->slug)) {
			//Make sure the slug is set. Use the title if it's empty.
			$ao_entity->set('slug', $ao_entity->title);
		}

		if (
			!$ao_entity->isDirty('slug') &&
			!$ao_entity->isDirty('languageShortcode') &&
			!$ao_entity->isDirty('parentId')
		) {
			//If neither the slug, the language nor the parent id have changed, skip the slug logic
			return;
		}

		$ls_preSlug = '';
		if (!empty($ao_entity->parentId)) {
			/** @var \Awyiss\Model\Entity\Page $lo_parentPage */
			$lo_parentPage = $this->get($ao_entity->parentId, skipPageRoleCheck: true);
			//If there's a parent page, add its slug the one of the current page
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';
		}

		$la_parts = explode('/', $ao_entity->slug);
		$ls_slug = end($la_parts);
		$ls_slug = $ls_preSlug . $ls_slug;

		$ls_originalSlug = $ao_entity->hasOriginal('slug') ? $ao_entity->getOriginal('slug') : null;
		//When the slug has changed
		if ($ls_slug != $ls_originalSlug) {
			$ls_field = $this->getAlias() . '.slug';

			$la_conditions = [
				$ls_field => $ls_slug,
				'language_shortcode' => $ao_entity->languageShortcode,
			];

			$ls_primaryKey = $this->getPrimaryKey();
			$li_id = $ao_entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$this->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			/**
			 * `$la_conditions` holds an array of query conditions that are used to find pages with the same
			 * slug
			 *
			 * ```
			 * [
			 *    "Pages.slug" => "new/slug/of/the/current/page"
			 *    "language_shortcode" => "de"
			 *    "NOT" => [
			 *        "Pages.id" => 1234
			 *    ]
			 * ]
			 * ```
			 */

			$li_i = 1;
			$ls_suffix = '';

			//As long as a page with the same slug exists, append an increasing number to the slug and try again
			while ($this->exists($la_conditions, ['skipPageRoleCheck' => true])) {
				$li_i++;
				$ls_suffix = '-' . $li_i;

				if ($li_length && (mb_strlen($ls_slug . $ls_suffix) > $li_length)) {
					$ls_slug = mb_substr($ls_slug, 0, $li_length - mb_strlen($ls_suffix));
				}

				$la_conditions[ $ls_field ] = $ls_slug . $ls_suffix;
			}

			//Append the suffix, if it's not empty
			if ($ls_suffix) {
				$ls_slug .= $ls_suffix;
			}
		}

		$ao_entity->set('slug', $ls_slug, ['setter' => false]);
		if (!$ao_entity->isNew() && $ls_slug === $ls_originalSlug) {
			$ao_entity->setDirty('slug', false);
		}
	}


	/**
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ls_originalSlug = $ao_entity->hasOriginal('slug') ? $ao_entity->getOriginal('slug') : null;
		if ($ls_originalSlug && $ao_entity->slug != $ls_originalSlug) {
			$lo_query = $this->updateQuery();

			/**
			 * UPDATE pages SET slug = (CONCAT('newslug', substr(slug, '8'))) WHERE slug LIKE 'oldslug/%'
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$lo_query->update($this->getTable())->set('slug', $lo_query->newExpr($lo_query->func()->concat([
				$ao_entity->slug,
				$lo_query->func()->substr([
					'slug' => 'identifier',
					mb_strlen($ls_originalSlug) + 1,
				], [
					null,
					'integer',
				]),
			])))->where(function (QueryExpression $ao_expression/*, Query $ao_query*/) use ($ls_originalSlug) {
				return $ao_expression->like('slug', $ls_originalSlug . '/%');
			})->execute();
		}
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function beforeSoftDelete(): void {
		$this->Contents->disableCascadeCallbacks();
		$this->Contents->forPageRole($this->pageRole, false);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function beforeDelete(): void {
		$this->Contents->disableCascadeCallbacks();
		$this->Contents->forPageRole($this->pageRole, false);
	}


	/**
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function afterSoftDelete(): void {
		$this->Contents->enableCascadeCallbacks();
	}


	/**
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function afterDelete(): void {
		$this->Contents->enableCascadeCallbacks();
	}


	/**
	 * Creates a threaded list of pages from a query, adding the `level`-property to each page and returns
	 * a collection
	 *
	 * @param SelectQuery $ao_query
	 * @return CollectionInterface
	 */
	public function listNested(SelectQuery $ao_query): CollectionInterface {
		$lo_pages = $ao_query->find('threaded')->all()->listNested();

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			$lo_page->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_page->level = $lo_pages->getDepth();
		}


		return $lo_pages;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param \Awyiss\Model\Entity\Page|string|null $languageShortcode
	 * @param \Awyiss\Model\Entity\Page|null $entity
	 * @return \Cake\ORM\Query\SelectQuery
	 * @throws \Exception
	 */
	public function findForCurrentLanguage(SelectQuery $ao_query, Page|string|null $languageShortcode = null, ?Page $entity = null): SelectQuery {
		$ls_languageShortcode = $languageShortcode;

		if ($entity) {
			$ls_languageShortcode = $entity->languageShortcode;
		}

		return $ao_query->where([
			'language_shortcode' => $ls_languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode,
		]);
	}


	/**
	 * @return void
	 */
	protected function buildPagesAssociations(): void {
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole->name));

		$this->hasMany('Duplicating' . $ls_pageRole, [
			'bindingKey' => 'duplicate_of',
			'className' => $ls_pageRole,
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'id',
		]);

		$this->belongsTo('DuplicateOf' . $ls_pageRole, [
			'bindingKey' => 'id',
			'className' => $ls_pageRole,
			'foreignKey' => 'duplicate_of',
		]);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$this->getSchema()->setColumnType('page_role_id', EnumType::from($ls_pageRoleEnum));
	}
}
