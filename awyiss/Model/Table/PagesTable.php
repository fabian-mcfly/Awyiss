<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * Pages Model
 *
 * @property PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @property PagesTable&\Awyiss\ORM\Association\BelongsTo $Duplicate
 * @property PagesTable&\Awyiss\ORM\Association\BelongsTo $ParentPages
 * @property PagesTable&\Awyiss\ORM\Association\HasMany $ChildPages
 * @property ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @method Page newDefaultEntity(array $aa_additionalData = [])
 * @method CollectionInterface|null getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|null getChildren(EntityInterface $ao_entity)
 * @method Page getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|null getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class PagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'pages';
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'nest' => [
			'relatedColumns' => ['languageShortcode', 'pageRoleId'],
		],
		'systemOrder' => [
			'relatedColumns' => ['languageShortcode', 'pageRoleId', 'parentId'],
		],
	];
	protected string $pageRole = 'page';
	/**
	 * Integer identifier of the used page role.
	 *
	 * @var int
	 */
	protected int $pageRoleId;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function initialize(array $aa_config): void {
		$this->pageRoleId = constant('PAGEROLE_' . strtoupper($this->pageRole));
		$ls_alias = Inflector::pluralize($this->pageRole);

		if (!$this->getConfig('nest.alias')) {
			$this->setConfig('nest.alias', Inflector::camelize($ls_alias));
		}

		if (!$this->getConfig('attributes.foreignKey')) {
			$this->setConfig('attributes.foreignKey', 'page_id');
		}

		if (!$this->getConfig('attributes.sourceTable')) {
			$this->setConfig('attributes.sourceTable', $ls_alias);
		}

		parent::initialize($aa_config);

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
	 * @return int
	 * @noinspection PhpUnused
	 */
	public function getPageRoleId(): int {
		return $this->pageRoleId;
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
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
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
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole));


		$ao_rules->add(
			$ao_rules->existsIn(
				['languageShortcode'],
				'Languages',
			),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'pages', 'error_language_exists'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn(
				['pageRoleId'],
				'PageRoles',
			),
			'validPageRole',
			[
				'errorField' => 'pageRoleId',
				'message' => __d($this->getI18nDomain(), 'error_valid_page_role'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn(
				['pageTemplateId', 'pageRoleId'],
				'PageTemplates',
			),
			'validPageTemplate',
			[
				'errorField' => 'pageTemplateId',
				'message' => __d($this->getI18nDomain(), 'error_valid_page_template'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn(
				['duplicateOf'],
				'Duplicate' . $ls_pageRole,
			),
			'validDuplicateOf',
			[
				'errorField' => 'duplicateOf',
				'message' => __d($this->getI18nDomain(), 'error_valid_duplicate_of'),
			]
		);


		$ao_rules->add(function (Page $ao_entity, array $aa_options) use ($ao_rules, $ls_pageRole): bool {
			if (!$aa_options['checkRules']) {
				dd(__FILE__, __LINE__);
			}

			if (!$ao_entity->get('parentId')) {
				return true;
			}

			$lo_existsIn = $ao_rules->existsIn(
				['parentId', 'languageShortcode', 'pageRoleId'],
				'Parent' . $ls_pageRole,
				[
					'errorField' => 'parentId',
					'message' => __dfx($this->getI18nDomain(), 'validation', 'pages', 'error_valid_parent_id'),
					'skipPageRoleCheck' => true,
				],
			);


			return $lo_existsIn($ao_entity, $aa_options);
		}, 'validParentId');


		$ao_rules->addDelete(function (): void {
			//TODO: make sure no pages with other page_role_ids exists as subpages
			dd(__FILE__, __LINE__);
		});


		return $ao_rules;
	}


	/**
	 * Add a where-condition that limits all results to the page role set for this model
	 *
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @return void
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options): void {
		if ($ao_event->isStopped()) {
			return;
		}

		if (!($ao_options['skipPageRoleCheck'] ?? false)) {
			$ao_query->where(['page_role_id' => $this->getPageRoleId()]);
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
		if ($ao_event->isStopped()) {
			return;
		}

		$ls_field = $this->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->slug)) {
			//Make sure the slug is set. Use the title if it's empty.
			$ao_entity->set('slug', $ao_entity->title);
		}

		if (!$ao_entity->isDirty('slug')) {
			/*
			 * If the slug is not dirty, mark it as such.
			 * This forces the correct pre-slug when the slug itself has not changed but the parent page or language has.
			*/
			$ao_entity->set('slug', $ao_entity->slug . '/');
		}

		$ls_preSlug = '';
		if (!empty($ao_entity->parentId)) {
			/** @var Page $lo_parentPage */
			$lo_parentPage = $this->get($ao_entity->parentId, skipPageRoleCheck: true);
			//If there's a parent page, add its slug the one of the current page
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';
		}

		$ls_slug = $ls_preSlug . $ao_entity->slug;


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
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ($ao_event->isStopped()) {
			return;
		}

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
	 * Creates a threaded list of pages from a query, adding the `level`-property to each page and returns
	 * a collection
	 *
	 * @param SelectQuery $ao_query
	 * @return CollectionInterface
	 */
	public function listNested(SelectQuery $ao_query): CollectionInterface {
		$lo_pages = $ao_query->find('threaded')->all()->listNested();

		/** @var Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			$lo_page->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_page->level = $lo_pages->getDepth();
		}


		return $lo_pages;
	}


	/*
	 * @param Query $ao_query
	 *
	 * @return Query
	 * @throws \Exception
	 *
	public function findForCurrentLanguage (Query $ao_query): Query {
		return $ao_query->where([
			'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
		]);
	}*/


	/**
	 * @return void
	 */
	protected function buildPagesAssociations(): void {
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole));

		$this->hasMany('Duplicate' . $ls_pageRole, [
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

		if ($this->hasBehavior('Nest')) {
			$this->removeBehavior('Nest');
		}

		$this->addBehavior('Nest', $this->getConfig('nest', []));
	}
}
