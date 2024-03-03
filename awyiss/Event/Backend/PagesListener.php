<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\App;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table\PagesTable;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Event listeners for the Pages (and dynamically created page roles) scope of the backend
 */
class PagesListener implements EventListenerInterface {
	use EventListenerTrait;
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$la_events = [];

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_identifier = Inflector::camelize(Inflector::pluralize($le_pageRole->name));

			$la_events += [
				'Model.' . $ls_identifier . '.beforeFind' => 'beforeFind',
				'Model.' . $ls_identifier . '.beforeCopy' => 'beforeCopy',
				'Model.' . $ls_identifier . '.afterCopy' => 'afterCopy',
				'Model.' . $ls_identifier . '.beforeSave' => 'beforeSave',
				'Model.' . $ls_identifier . '.afterSave' => 'afterSave',
				'Model.' . $ls_identifier . '.beforeSoftDelete' => 'beforeSoftDelete',
				'Model.' . $ls_identifier . '.beforeDelete' => 'beforeDelete',
				'Model.' . $ls_identifier . '.afterSoftDelete' => 'afterSoftDelete',
				'Model.' . $ls_identifier . '.afterDelete' => 'afterDelete',
			];
		}


		return $la_events;
	}

	/**
	 * Add a where-condition that limits all results to the page role set for this model
	 *
	 * @param Event $ao_event
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(Event $ao_event, SelectQuery $ao_query, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		if (($ao_options['skipPageRoleCheck'] ?? false) === true) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

			$ls_prefixedColumn = $ao_query->getRepository()->getAlias() . '.page_role_id';

			/** @noinspection PhpUndefinedMethodInspection */
			$ao_query->orderByAsc($ao_query->newExpr($ao_query->func()->FIND_IN_SET([
				$ls_prefixedColumn => 'identifier',
				implode(',', array_map(function (PageRoleEnumInterface $ae_pageRole) {
					return $ae_pageRole->value;
				}, $ls_pageRoleEnum::cases())),
			])), true);
		}
		else {
			$ao_query->where(['page_role_id' => $lo_table->getPageRole()]);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 */
	public function beforeCopy(Event $ao_event, Page $ao_entity, ArrayObject $ao_options): void {
		if ($ao_options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		/** @var \Awyiss\Model\Entity\Page $lo_originalEntity */
		$lo_originalEntity = $ao_entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren([
			'forceEnable' => true,
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
			'skipFields' => [
				'pageRoleId',
			],
		]);

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childPages')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');

		/** @var \Awyiss\Model\Entity\Page $lo_childPage */
		foreach ($lo_children as $lo_childPage) {
			$la_primaryKeys = $lo_childPage->extract((array)$lo_table->getPrimaryKey());
			$lo_childPage->originalPrimaryKeys = $la_primaryKeys;

			$lo_childPage->unset((array)$lo_table->getPrimaryKey());
			$lo_childPage->setNew(true);

			$lo_childPage->set($ao_entity->extract($la_relatedColumns));
		}

		$ls_childrenPropertyName = 'child' . $lo_table->getAlias();
		$ls_childrenAssociationName = Inflector::camelize($ls_childrenPropertyName);
		$ao_entity->{$ls_childrenPropertyName} = $lo_nestedChildren;

		$lo_table->{$ls_childrenAssociationName}->getBehavior('Nest')->setConfig('buildRules', false);
		$lo_table->{$ls_childrenAssociationName}->getBehavior('Categories')->setConfig('buildRules', false);
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $ao_event, Page $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$ls_field = $lo_table->getSchema()->getColumn('slug');
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
			$lo_parentPage = $lo_table->get($ao_entity->parentId, skipPageRoleCheck: true);
			//If there's a parent page, add its slug the one of the current page
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';

			$ao_entity->parentsActive = $lo_parentPage->active && $lo_parentPage->parentsActive;
		}
		elseif ($ao_entity->parentsActive !== true) {
			$ao_entity->parentsActive = true;
		}

		$la_parts = explode('/', $ao_entity->slug);
		$ls_slug = end($la_parts);
		$ls_slug = $ls_preSlug . $ls_slug;

		$ls_originalSlug = $ao_entity->hasOriginal('slug') ? $ao_entity->getOriginal('slug') : null;
		//When the slug has changed
		if ($ao_entity->isNew() || $ls_slug != $ls_originalSlug) {
			$ls_field = $lo_table->getAlias() . '.slug';

			$la_conditions = [
				$ls_field => $ls_slug,
				'language_shortcode' => $ao_entity->languageShortcode,
			];

			$ls_primaryKey = $lo_table->getPrimaryKey();
			$li_id = $ao_entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$lo_table->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			/**
			 * `$la_conditions` holds an array of query conditions that are used to find pages with the same
			 * slug
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
			while ($lo_table->exists($la_conditions, ['skipPageRoleCheck' => true])) {
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
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	public function afterCopy(Event $ao_event, Page $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		/** @var \Awyiss\Model\Entity\Page $lo_originalEntity */
		$lo_originalEntity = $ao_entity->originalEntity;

		$lo_entries = $lo_table->Contents->find('threaded', nestingKey: 'childContents')->where(['page_id' => $lo_originalEntity->id])->all();

		$lo_listedEntries = $lo_entries->listNested('desc', 'childContents');
		/** @var \Awyiss\Model\Entity\Content $lo_content */
		foreach ($lo_listedEntries as $lo_content) {
			$lo_content->unset((array)$lo_table->getPrimaryKey());
			$lo_content->unset(['pageId']);
			$lo_content->setNew(true);

			$lo_content->pageId = $ao_entity->id;
		}

		$lo_table->Contents->saveMany($lo_entries->toList(), [
			'checkRules' => false,
		]);
	}


	/**
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, Page $ao_entity, ArrayObject $ao_options): void {
		foreach ($ao_entity->addMenuEntry ?? [] as $li_menuId) {
			$this->addMenuEntry($li_menuId, $ao_entity);
		}

		if ($ao_entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$ls_originalSlug = $ao_entity->hasOriginal('slug') ? $ao_entity->getOriginal('slug') : null;
		$lb_slugChanged = $ls_originalSlug && $ao_entity->slug !== $ls_originalSlug;

		$lb_originalActive = $ao_entity->hasOriginal('active') ? $ao_entity->getOriginal('active') : null;
		$lb_activeChanged = $lb_originalActive !== null && $ao_entity->active !== $lb_originalActive;

		$lb_originalParentsActive = $ao_entity->hasOriginal('parentsActive') ? $ao_entity->getOriginal('parentsActive') : null;
		$lb_parentsActiveChanged = $lb_originalParentsActive !== null && $ao_entity->parentsActive !== $lb_originalParentsActive;

		$ls_originalLanguage = $ao_entity->hasOriginal('languageShortcode') ? $ao_entity->getOriginal('languageShortcode') : null;
		$lb_languageChanged = $ls_originalLanguage && $ao_entity->languageShortcode !== $ls_originalLanguage;

		if ($lb_languageChanged || $lb_slugChanged) {
			$this->createHistoricalSlugs($lo_table, $ao_entity, $ls_originalSlug, $ls_originalLanguage);
		}

		if ($lb_slugChanged || $lb_activeChanged || $lb_parentsActiveChanged) {
			$this->updateDescendants(
				$lo_table,
				$ao_entity,
				$ls_originalLanguage,
				$lb_slugChanged,
				$ls_originalSlug,
				$lb_activeChanged,
				$lb_parentsActiveChanged
			);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeSoftDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->disableCascadeCallbacks();
		$lo_table->Contents->forPageRole($lo_table->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->disableCascadeCallbacks();
		$lo_table->Contents->forPageRole($lo_table->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function afterSoftDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function afterDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param int $ai_menuId
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @return void
	 */
	protected function addMenuEntry(int $ai_menuId, Page $ao_entity): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = $this->fetchTable('MenuEntries');
		$lo_menuEntry = $lo_table->newDefaultEntity([
			'languageShortcode' => $ao_entity->languageShortcode,
			'link' => $ao_entity->languageShortcode . '/' . $ao_entity->slug,
			'menuId' => $ai_menuId,
			'title' => $ao_entity->title,
		]);

		if (str_contains($ao_entity->slug, '/')) {
			$ls_testSlug = $ao_entity->languageShortcode . '/';
			$ls_testSlug .= substr($ao_entity->slug, 0, strrpos($ao_entity->slug, '/'));

			$lo_records = $lo_table->find()->where([
				'language_shortcode' => $ao_entity->languageShortcode,
				'link' => $ls_testSlug,
				'menu_id' => $ai_menuId,
			])->all();

			if ($lo_records->count()) {
				/** @var \Awyiss\Model\Entity\MenuEntry $lo_existingEntry */
				$lo_existingEntry = $lo_records->first();
				$lo_menuEntry->parentId = $lo_existingEntry->id;
			}
		}

		$lo_table->save($lo_menuEntry);
	}


	/**
	 * @param \Awyiss\Model\Table\PagesTable $ao_table
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param mixed $as_originalSlug
	 * @param mixed $as_originalLanguage
	 * @return void
	 */
	protected function createHistoricalSlugs(PagesTable $ao_table, Page $ao_entity, ?string $as_originalSlug, ?string $as_originalLanguage): void {
		$lo_records = $ao_table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $ao_expression) use ($as_originalSlug) {
			return $ao_expression->like('slug', $as_originalSlug . '/%');
		})->all();

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$li_userId = $this->getIdentity()?->id;
		$ld_now = new DateTime('now');

		$lo_query = $ao_table->SlugHistory->insertQuery()->insert(['slug', 'page_id', 'created_by', 'created_on']);
		$lo_query->values([
			'slug' => ($as_originalLanguage ?? $ao_entity->languageShortcode) . '/' . $as_originalSlug,
			'page_id' => $ao_entity->id,
			'created_by' => $li_userId,
			'created_on' => $ld_now,
		]);

		if (!$lo_records->count()) {
			return;
		}

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_records as $lo_page) {
			$lo_query->values([
				'slug' => $lo_page->languageShortcode . '/' . $lo_page->slug,
				'page_id' => $lo_page->id,
				'created_by' => $li_userId,
				'created_on' => $ld_now,
			]);
		}

		$lo_query->execute();
	}


	/**
	 * @param \Awyiss\Model\Table\PagesTable $ao_table
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param string|null $as_originalLanguage
	 * @param bool $ab_slugChanged
	 * @param string|null $as_originalSlug
	 * @param bool $ab_activeChanged
	 * @param bool $ab_parentsActiveChanged
	 * @return void
	 */
	protected function updateDescendants(
		PagesTable $ao_table,
		Page $ao_entity,
		?string $as_originalLanguage,
		bool $ab_slugChanged,
		?string $as_originalSlug,
		bool $ab_activeChanged,
		bool $ab_parentsActiveChanged
	): void {
		$lo_query = $ao_table->updateQuery()->update($ao_table->getTable())->where([
			'language_shortcode' => $as_originalLanguage ?? $ao_entity->languageShortcode,
		]);

		if ($ab_slugChanged) {
			/**
			 * UPDATE pages SET slug = (CONCAT('newslug', substr(slug, '8')))
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$lo_query->set('slug', $lo_query->newExpr($lo_query->func()->concat([
				$ao_entity->slug,
				$lo_query->func()->substr([
					'slug' => 'identifier',
					mb_strlen($as_originalSlug) + 1,
				], [
					null,
					'integer',
				]),
			])));
		}

		if ($ab_activeChanged || $ab_parentsActiveChanged) {
			$lb_parentsActive = $ao_entity->active && $ao_entity->parentsActive;

			if ($lb_parentsActive) {
				/**
				 * When updating all pages with the same slug (LIKE 'oldslug/%'), do not set the parents_active to true
				 * for pages that descendants of inactive sites.
				 */
				$lo_subPages = $ao_table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $ao_expression) use ($ao_entity, $as_originalSlug) {
					return $ao_expression->like('slug', ($as_originalSlug ?? $ao_entity->slug) . '/%');
				})->where(['active' => false])->all();

				foreach ($lo_subPages as $lo_subPage) {
					$lo_query->where(function (QueryExpression $ao_expression/*, Query $ao_query*/) use ($lo_subPage) {
						return $ao_expression->notLike('slug', $lo_subPage->slug . '/%');
					});
				}
			}

			$lo_query->set('parents_active', $lb_parentsActive);
		}


		/**
		 * WHERE slug LIKE 'oldslug/%'
		 */
		$lo_query->where(function (QueryExpression $ao_expression/*, Query $ao_query*/) use ($ao_entity, $as_originalSlug) {
			return $ao_expression->like('slug', ($as_originalSlug ?? $ao_entity->slug) . '/%');
		});

		$lo_query->execute();
	}
}
