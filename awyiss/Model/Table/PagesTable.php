<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Behavior\AccessBehavior;
use Awyiss\Model\Entity\Page;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
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
 * @method Page patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getDirectChildren(EntityInterface $ao_entity)
 * @method Page getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class PagesTable extends \Awyiss\Model\Table {
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
			'relatedColumns' => ['languages_shortcode', 'page_role_id'],
		],
		'systemOrder' => [
			'relatedColumns' => ['languages_shortcode', 'page_role_id', 'parent_id'],
		],
	];
	/**
	 * Integer identifier of the used page role.
	 *
	 * @var null|int
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
			$lo_accessBehavior->setConfig('Model.buildRules', function(Page $ao_entity, array $aa_options, AccessBehavior $ao_behavior, ?bool $ab_accessible): ?bool {
				if ( ! $ab_accessible) {
					return $ab_accessible;
				}

				return $ao_entity->page_role_id === $this->getPageRoleId();
			});
		}

		if ( ! $lo_accessBehavior->getConfig('beforeFind')) {
			$lo_accessBehavior->setConfig('Model.beforeFind', function(EventInterface $ao_event, Query $ao_subject, array $aa_options, AccessBehavior $ao_behavior, ?bool $ab_accessible): ?bool {
				if ( ! $ab_accessible) {
					return $ab_accessible;
				}

				$ao_subject->where(['page_role_id' => $this->getPageRoleId()]);

				/*$ao_subject->mapReduce(function(Page $ao_entity, int $ai_key, MapReduce $ao_mapReduce){
					if ($ao_entity->page_role_id === $this->getPageRoleId()) {
						$ao_mapReduce->emit($ao_entity);
					}
				});*/

				return TRUE;
			});
		}
	}


	/**
	 * @return int
	 * @noinspection PhpUnused
	 */
	public function getPageRoleId (): int {
		return $this->pageRoleId;
	}


	/**
	 * @param null|int $ai_pageRoleId
	 *
	 * @return \Awyiss\Model\Table\PagesTable
	 */
	public function setPageRoleId (?int $ai_pageRoleId = NULL): self {
		$this->pageRoleId = $ai_pageRoleId;

		return $this;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('slug')->maxLength('slug', 255);

		$ao_validator->scalar('languages_shortcode')
			->requirePresence('languages_shortcode', 'create')
			->minLength('shortcode', 2, __('validation::not_exact_length'))
			->maxLength('shortcode', 2, __('validation::not_exact_length'))
			->notEmptyString('languages_shortcode');

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
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		//$ao_rules->add($ao_rules->isUnique(['slug']), ['errorField' => 'slug']);

		/*$ao_rules->add(function(Page $ao_entity, array $aa_options) {
			return $ao_entity->page_role_id === $this->pageRoleId;
		}, [
			'errorField' => '_general',
			'message' => __('::cant_modify_page_role'),
		]);*/

		//TODO: check for the existence of language_shortcode

		$ao_rules->add($ao_rules->existsIn(['page_role_id'], 'PageRoles'), ['errorField' => 'page_role_id']);

		$ao_rules->add($ao_rules->existsIn(['page_template_id'], 'PageTemplates'), ['errorField' => 'page_template_id']);

		$ao_rules->add($ao_rules->existsIn(['duplicate_of'], 'Duplicate'), ['errorField' => 'duplicate_of']);

		$ao_rules->add($ao_rules->existsIn(['parent_id'], 'ParentPages'), ['errorField' => 'parent_id']);

		return $ao_rules;
	}


	/*public function afterMarshal (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_data, \ArrayObject $ao_options) {
		if (empty($ao_entity->slug) && !empty($ao_entity->title)) {
			$ao_entity->slug = $ao_entity->title;
		}
	}*/


	/*public function beforeFind (EventInterface $ao_event, Query $ao_query, \ArrayObject $ao_options, bool $ab_primary) {
		$ao_query->where(['page_role_id' => $this->pageRoleId]);
		dd($ao_query);
	}*/


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param Page $ao_entity
	 * @param \ArrayObject $ao_options
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		parent::beforeSave($ao_event, $ao_entity, $ao_options);

		if ($ao_event->isStopped()) {
			return;
		}

		$ls_field = $this->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->slug)) {
			$ao_entity->set('slug', $ao_entity->title);
		}
		else {
			$ls_originalPreSlug = '';
			if ( ! empty($ao_entity->getOriginal('parent_id'))) {
				/** @var Page $lo_originalParentPage */
				$lo_originalParentPage = $this->get($ao_entity->getOriginal('parent_id'));
				$ls_originalPreSlug = trim($lo_originalParentPage->slug, '/') . '/';
			}

			if ($ls_originalPreSlug) {
				$ao_entity->set('slug', substr($ao_entity->slug, strlen($ls_originalPreSlug)));
			}
		}

		$ls_preSlug = '';
		/** @var Page $lo_parentPage */
		if ( ! empty($ao_entity->parent_id) && $lo_parentPage = $this->get($ao_entity->parent_id)) {
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';
		}

		/*if ($li_length && (mb_strlen($ao_entity->slug) > $li_length)) {
			$ao_entity->set('slug', mb_substr($ao_entity->slug, 0, $li_length));
		}*/

		$ls_slug = $ls_preSlug . $ao_entity->slug;

		if ($ls_slug != $ao_entity->getOriginal('slug')) {
			$ls_field = $this->getAlias() . '.slug';
			$la_conditions = [
				$ls_field => $ls_slug,
				'languages_shortcode' => $ao_entity->languages_shortcode,
			];
			$ls_primaryKey = $this->getPrimaryKey();
			$li_id = $ao_entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$this->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			$li_i = 1;
			$ls_suffix = '';

			while ($this->exists($la_conditions)) {
				$li_i++;
				$ls_suffix = '-' . $li_i;

				if ($li_length && (mb_strlen($ls_slug . $ls_suffix) > $li_length)) {
					$ls_slug = mb_substr($ls_slug, 0, $li_length - mb_strlen($ls_suffix));
				}

				$la_conditions[ $ls_field ] = $ls_slug . $ls_suffix;
			}

			if ($ls_suffix) {
				$ls_slug .= $ls_suffix;
			}
		}

		$ao_entity->set('slug', $ls_slug, ['setter' => FALSE]);
		if ($ls_slug === $ao_entity->getOriginal('slug')) {
			$ao_entity->setDirty('slug', FALSE);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param Page $ao_entity
	 * @param \ArrayObject $ao_options
	 */
	public function afterSaveCommit (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		parent::afterSave($ao_event, $ao_entity, $ao_options);

		if ($ao_event->isStopped()) {
			return;
		}

		if ($ao_entity->slug != $ao_entity->getOriginal('slug')) {
			$lo_query = $this->query();
			/** @noinspection PhpUndefinedMethodInspection */
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
			//dd('UPDATE `' . $this->getAlias() . '` SET `slug` = CONCAT(\'' . $ao_entity->slug .'\', substr(`slug`, ' . mb_strlen($ao_entity->getOriginal('slug')) + 1 . ')) WHERE `slug` LIKE \'' . $ao_entity->getOriginal('slug') . '/%\'');
		}
	}


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
