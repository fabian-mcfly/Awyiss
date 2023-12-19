<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Model\Behavior\AccessBehavior;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Pages Model
 *
 * @property \Awyiss\Model\Table\PageRolesTable&\Cake\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Cake\ORM\Association\BelongsTo $PageTemplates
 * @property \Awyiss\Model\Table\PagesTable&\Cake\ORM\Association\BelongsTo $Duplicate
 * @property \Awyiss\Model\Table\PagesTable&\Cake\ORM\Association\BelongsTo $Parent
 * @property \Awyiss\Model\Table\PagesTable&\Cake\ORM\Association\HasMany $Children
 *
 * @method Page newDefaultEntity(array $aa_additionalData = [])
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getDirectChildren(EntityInterface $ao_entity)
 * @method Page getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class PagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'nested' => [
			'children' => [
				'associationName' => 'Children',
			],
			'parent' => [
				'associationName' => 'Parent',
			],
			'relatedColumns' => ['language_shortcode', 'page_role_id'],
		],
		'systemOrder' => [
			'relatedColumns' => ['language_shortcode', 'page_role_id', 'parent_id'],
		],
	];
	/**
	 * Integer identifier of the used page role.
	 *
	 * @var NULL|int
	 */
	protected ?int $pageRoleId = PAGEROLE_PAGE;
	public const TABLE = 'pages';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nested', $this->getConfig('nested', []));

		$this->setTable(static::TABLE);
		$this->setPrimaryKey('id');

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['type' => 'frontend'],
			'foreignKey' => 'language_shortcode',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('PageRoles', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('PageTemplates', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Duplicate', [
			'bindingKey' => 'duplicate_of',
			'className' => 'Pages',
			'foreignKey' => 'id',
		]);

		$this->belongsTo('Parent', [
			'className' => 'Pages',
			'foreignKey' => 'parent_id',
		]);

		$this->hasMany('Children', [
			'className' => 'Pages',
			'foreignKey' => 'parent_id',
		]);


		/** @var AccessBehavior $lo_accessBehavior */
		$lo_accessBehavior = $this->getBehavior('Access');

		if ( ! $lo_accessBehavior->getConfig('Model.buildRules')) {
			//Set a default callable for the `Model.buildRules`-event
			$lo_accessBehavior->setConfig('Model.buildRules', function(Page $ao_entity, array $aa_options, AccessBehavior $ao_behavior, ?bool $ab_accessible): ?bool {
				if ( ! $ab_accessible) {
					return FALSE;
				}

				//Make sure the `page_role`-value of the entity equals the page role set for this model
				return $ao_entity->page_role_id === $this->getPageRoleId();
			});
		}

		/*if ( ! $lo_accessBehavior->getConfig('beforeFind')) {
			//Set a default callable for the `Model.beforeFind`-event
			$lo_accessBehavior->setConfig('Model.beforeFind', function(EventInterface $ao_event, Query $ao_subject, array $aa_options, AccessBehavior $ao_behavior, ?bool $ab_accessible): ?bool {
				if ( ! $ab_accessible) {
					return FALSE;
				}

				//Add a where-condition that limits all results to the page role set for this model
				$ao_subject->where(['page_role_id' => $this->getPageRoleId()]);

				return TRUE;
			});
		}*/
	}


	/**
	 * @return int
	 * @noinspection PhpUnused
	 */
	public function getPageRoleId (): int {
		return $this->pageRoleId;
	}


	/**
	 * @param NULL|int $ai_pageRoleId
	 *
	 * @return \Awyiss\Model\Table\PagesTable
	 */
	public function setPageRoleId (?int $ai_pageRoleId = NULL): static {
		$this->pageRoleId = $ai_pageRoleId;

		return $this;
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('slug')->maxLength('slug', 255);

		$ao_validator->scalar('language_shortcode')
			->requirePresence('language_shortcode', 'create')
			->minLength('shortcode', 2, __('validation::not_exact_length'))
			->maxLength('shortcode', 2, __('validation::not_exact_length'))
			->notEmptyString('language_shortcode');

		$ao_validator->scalar('title')->requirePresence('title', 'create')->maxLength('title', 255)->notEmptyString('title');

		$ao_validator->scalar('redirect_link')->maxLength('redirect_link', 255)->allowEmptyString('redirect_link');

		$ao_validator->dateTime('eventdate_start')->allowEmptyDateTime('eventdate_start');

		$ao_validator->dateTime('eventdate_end')->allowEmptyDateTime('eventdate_end');

		$ao_validator->dateTime('publishdate_start')->allowEmptyDateTime('publishdate_start');

		$ao_validator->dateTime('publishdate_end')->allowEmptyDateTime('publishdate_end');

		$ao_validator->scalar('meta_title')->maxLength('meta_title', 100)->allowEmptyString('meta_title');

		$ao_validator->scalar('meta_description')->allowEmptyString('meta_description');

		$ao_validator->boolean('robots_index')->notEmptyString('robots_index');

		$ao_validator->boolean('robots_follow')->notEmptyString('robots_follow');

		$ao_validator->integer('page_role_id')->notEmptyString('page_role_id');

		$ao_validator->integer('page_template_id')->notEmptyString('page_template_id');

		$ao_validator->integer('duplicate_of')->allowEmptyString('duplicate_of');

		$ao_validator->integer('parent_id')->allowEmptyString('parent_id');

		$ao_validator->integer('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('parents_active')->notEmptyString('parents_active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['language_shortcode'], 'Languages', ['access' => ['skip' => TRUE]]), ['errorField' => 'language_shortcode']);

		$ao_rules->add($ao_rules->existsIn(['page_role_id'], 'PageRoles', ['access' => ['skip' => TRUE]]), ['errorField' => 'page_role_id']);

		$ao_rules->add($ao_rules->existsIn(['page_template_id'], 'PageTemplates', ['access' => ['skip' => TRUE]]), ['errorField' => 'page_template_id']);

		$ao_rules->add($ao_rules->existsIn(['duplicate_of'], 'Duplicate', ['access' => ['skip' => TRUE]]), ['errorField' => 'duplicate_of']);

		$ao_rules->add($ao_rules->existsIn(['parent_id'], 'Parent', ['access' => ['skip' => TRUE], 'skipPageRoleCheck' => TRUE]), ['errorField' => 'parent_id']);

		return $ao_rules;
	}


	/**
	 * Add a where-condition that limits all results to the page role set for this model
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query $ao_query
	 * @param \ArrayObject $ao_options
	 *
	 * @return void
	 */
	public function beforeFind (EventInterface $ao_event, Query $ao_query, ArrayObject $ao_options): void {
		if ($ao_event->isStopped()) {
			return;
		}

		if ( ! $ao_options->offsetExists('skipPageRoleCheck') || ! $ao_options->offsetGet('skipPageRoleCheck')) {
			$ao_query->where(['page_role_id' => $this->getPageRoleId()]);
		}
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param Page $ao_entity
	 * @param \ArrayObject $ao_options
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		parent::beforeSave($ao_event, $ao_entity, $ao_options);

		if ($ao_event->isStopped()) {
			return;
		}

		$ls_field = $this->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->slug)) {
			//Make sure the slug is set. Use the title if it's empty.
			$ao_entity->set('slug', $ao_entity->title);
		}

		if ( ! $ao_entity->isDirty('slug')) {
			/*
			 * If the slug is not dirty, mark it as such.
			 * This forces the correct pre-slug when the slug itself has not changed but the parent page or language has.
			*/
			$ao_entity->set('slug', $ao_entity->slug . '/');
		}

		$ls_preSlug = '';
		/** @var Page $lo_parentPage */
		if ( ! empty($ao_entity->parent_id) && $lo_parentPage = $this->get($ao_entity->parent_id, ['access' => ['skip' => TRUE], 'skipPageRoleCheck' => TRUE])) {
			//If there's a parent page, add its slug the one of the current page
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';
		}

		$ls_slug = $ls_preSlug . $ao_entity->slug;


		$ls_originalSlug = $ao_entity->getOriginal('slug');
		//When the slug has changed
		if ($ls_slug != $ls_originalSlug) {
			$ls_field = $this->getAlias() . '.slug';

			$la_conditions = [
				$ls_field => $ls_slug,
				'language_shortcode' => $ao_entity->language_shortcode,
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
			 * 	"Pages.slug" => "new/slug/of/the/current/page"
			 * 	"language_shortcode" => "de"
			 * 	"NOT" => [
			 * 		"Pages.id" => 1234
			 * 	]
			 * ]
			 * ```
			 *
			 */

			$li_i = 1;
			$ls_suffix = '';

			//As long as a page with the same slug exists, append an increasing number to the slug and try again
			while ($this->exists($la_conditions, ['access' => ['skip' => TRUE], 'skipPageRoleCheck' => TRUE])) {
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

		$ao_entity->set('slug', $ls_slug, ['setter' => FALSE]);
		if ($ls_slug === $ls_originalSlug) {
			$ao_entity->setDirty('slug', FALSE);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param Page $ao_entity
	 * @param \ArrayObject $ao_options
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		parent::afterSaveCommit($ao_event, $ao_entity, $ao_options);

		if ($ao_event->isStopped()) {
			return;
		}

		if ($ao_entity->slug != $ao_entity->getOriginal('slug')) {
			$lo_query = $this->query();

			/**
			 * UPDATE pages SET slug = (CONCAT('newslug', substr(slug, '8'))) WHERE slug LIKE 'oldslug/%'
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$lo_query->update()->set('slug', $lo_query->newExpr($lo_query->func()->concat([
				$ao_entity->slug,
				$lo_query->func()->substr([
					'slug' => 'identifier',
					mb_strlen($ao_entity->getOriginal('slug')) + 1,
				], [
					NULL,
					'integer',
				]),
			])))->where(function(QueryExpression $ao_expression/*, Query $ao_query*/) use ($ao_entity) {
				return $ao_expression->like('slug', $ao_entity->getOriginal('slug') . '/%');
			})->execute();
		}
	}


	/**
	 * Creates a threaded list of pages from a query, adding the `level`-property to each page and returns
	 * a collection
	 *
	 * @param \Cake\ORM\Query $ao_query
	 *
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function listNested (Query $ao_query): CollectionInterface {
		$lo_pages = $ao_query->find('threaded')->all()->listNested();

		/** @var Page $lo_page */
		foreach ($lo_pages AS $lo_page) {
			$lo_page->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_page->level = $lo_pages->getDepth();
		}

		return $lo_pages;
	}
}
