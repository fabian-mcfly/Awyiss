<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\App;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;


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
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	public function beforeCopy(Event $event, Page $entity, ArrayObject $options): void {
		if (($options['_primary'] ?? false) !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		/**
		 * @var \Awyiss\Model\Entity\Page $lo_originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$lo_originalEntity = $entity->originalEntity;

		if (!$lo_originalEntity) {
			return;
		}

		if (($options['copyDescendantsWithDifferentPageRole'] ?? false) === true) {
			$lo_children = $lo_table->getNestedChildren($lo_originalEntity, [
				'forceEnable' => true,
				'finders' => [
					'all' => [
						'skipPageRoleCheck' => true,
					],
					'mediaAssignments' => ['formatResult' => false],
					'translations',
				],
			]);
		}
		else {
			$lo_children = $lo_table->getNestedChildren($lo_originalEntity, [
				'finders' => [
					'mediaAssignments' => ['formatResult' => false],
					'translations',
				],
			]);
		}

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childPages')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');
		// Remove all blocklisted columns from the related columns
		$la_blocklistedColumns = $lo_table->getBehavior('Nest')->getConfig('children.blocklistedColumns');
		if ($la_blocklistedColumns) {
			$la_relatedColumns = array_diff($la_relatedColumns, $la_blocklistedColumns);
		}

		$la_relatedColumnValues = $entity->extract($la_relatedColumns);

		/** @var \Awyiss\Model\Entity\Page $lo_childPage */
		foreach ($lo_children as $lo_childPage) {
			if (!isset($lo_childPage->originalEntity)) {
				/**
				 * Serialize and unserialize the entity to create a deep copy of it.
				 *
				 * @noinspection PhpUndefinedFieldInspection
				 */
				$lo_childPage->originalEntity = unserialize(serialize($lo_childPage));
				$lo_childPage->setVirtual(['originalEntity'], true);

				$lo_childPage->originalEntity->patch(
					$lo_childPage->extractOriginalChanged(
						$lo_childPage->getOriginalFields()
					)
				);

				$lo_childPage->originalEntity->clean();
			}

			$la_primaryKeys = $lo_childPage->extractOriginal((array)$lo_table->getPrimaryKey());
			if ($la_primaryKeys) {
				$lo_childPage->originalPrimaryKeyValues ??= $la_primaryKeys;
				$lo_childPage->unset((array)$lo_table->getPrimaryKey());
			}

			$lo_childPage->setNew(true);

			$lo_childPage->patch($la_relatedColumnValues);

			if ($lo_childPage->pageRoleId !== $entity->pageRoleId) {
				// Unset attributes as they cannot make their way into the copied entity since the page role is different
				$lo_childPage->unset('attributes');
			}
		}

		$ls_childrenPropertyName = 'child' . $lo_table->getAlias();
		$ls_childrenAssociationName = Inflector::camelize($ls_childrenPropertyName);
		$entity->{$ls_childrenPropertyName} = $lo_nestedChildren;

		$lo_table->{$ls_childrenAssociationName}->getBehavior('Nest')->setConfig('buildRules', false);
		$lo_table->{$ls_childrenAssociationName}->getBehavior('Categories')->setConfig('buildRules', false);
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Page $entity, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		$ls_field = $lo_table->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($entity->slug)) {
			//Make sure the slug is set. Use the title if it's empty.
			$entity->set('slug', $entity->title);
		}

		if (
			!$entity->isDirty('slug') &&
			!$entity->isDirty('languageShortcode') &&
			!$entity->isDirty('parentId')
		) {
			//If neither the slug, the language nor the parent id have changed, skip the slug logic
			return;
		}

		$ls_preSlug = '';
		if (!empty($entity->parentId)) {
			/** @var \Awyiss\Model\Entity\Page $lo_parentPage */
			$lo_parentPage = $lo_table->get($entity->parentId, skipPageRoleCheck: true);
			//If there's a parent page, add its slug the one of the current page
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';

			$entity->parentsActive = $lo_parentPage->active && $lo_parentPage->parentsActive;
		}
		elseif ($entity->parentsActive !== true) {
			$entity->parentsActive = true;
		}

		$la_parts = explode('/', $entity->slug);
		$ls_slug = end($la_parts);
		$ls_slug = $ls_preSlug . $ls_slug;
		$ls_languageShortcode = $entity->languageShortcode;

		$ls_originalSlug = $entity->hasOriginal('slug') ? $entity->getOriginal('slug') : $ls_slug;
		$ls_originalLanguageShortcode = $entity->hasOriginal('languageShortcode') ? $entity->getOriginal('languageShortcode') : $entity->languageShortcode;

		// When the slug or the language has changed, or if it's a new entity, ensure the slug is unique
		if (
			$entity->isNew() ||
			$ls_slug != $ls_originalSlug ||
			$ls_languageShortcode != $ls_originalLanguageShortcode
		) {
			$ls_field = $lo_table->getAlias() . '.slug';

			$la_conditions = [
				$ls_field => $ls_slug,
				'language_shortcode' => $entity->languageShortcode,
			];

			$ls_primaryKey = $lo_table->getPrimaryKey();
			$li_id = $entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$lo_table->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			/**
			 * `$la_conditions` holds an array of query conditions that are used to
			 * find pages with the same slug
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

			// As long as a page with the same slug exists, append an increasing number to the slug and try again
			while ($lo_table->exists($la_conditions, ['skipPageRoleCheck' => true])) {
				$li_i++;
				$ls_suffix = '-' . $li_i;

				if ($li_length && (mb_strlen($ls_slug . $ls_suffix) > $li_length)) {
					$ls_slug = mb_substr($ls_slug, 0, $li_length - mb_strlen($ls_suffix));
				}

				$la_conditions[ $ls_field ] = $ls_slug . $ls_suffix;
			}

			// Append the suffix, if it's not empty
			if ($ls_suffix) {
				$ls_slug .= $ls_suffix;
			}
		}

		$entity->set('slug', $ls_slug, ['setter' => false]);
		if (!$entity->isNew() && $ls_slug === $ls_originalSlug) {
			$entity->setDirty('slug', false);
		}
	}


	/**
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	public function afterCopy(Event $event, Page $entity, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		if (
			($options['_primary'] ?? false) === true &&
			($options['copyDescendantsWithDifferentPageRole'] ?? false) === true &&
			$entity->childPages
		) {
			$this->transferAttributes($entity);
		}

		/**
		 * @var \Awyiss\Model\Entity\Page $lo_originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$lo_originalEntity = $entity->originalEntity;

		/** @uses \Awyiss\Model\Table::findTranslations() */
		$lo_entries = $lo_table->Contents->find('threaded', nestingKey: 'childContents')
		->find('mediaAssignments', formatResult: false)
		->find('translations')
		->where(['page_id' => $lo_originalEntity->id])
		->all();

		$lo_listedEntries = $lo_entries->listNested('desc', 'childContents');
		/** @var \Awyiss\Model\Entity\Content $lo_content */
		foreach ($lo_listedEntries as $lo_content) {
			$lo_content->unset((array)$lo_table->getPrimaryKey());
			$lo_content->unset(['pageId']);
			$lo_content->setNew(true);

			$lo_content->pageId = $entity->id;
		}

		$lo_table->Contents->saveMany($lo_entries->toList(), [
			'checkRules' => false,
			'isCopy' => true,
			'_primary' => false,
		]);
	}


	/**
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param ArrayObject $options
	 * @noinspection DuplicatedCode, PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, Page $entity, ArrayObject $options): void {
		foreach ($entity->addMenuEntry ?? [] as $li_menuId) {
			$this->addMenuEntry($li_menuId, $entity);
		}

		if ($entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		$ls_originalSlug = $entity->hasOriginal('slug') ? $entity->getOriginal('slug') : null;
		$lb_slugChanged = $ls_originalSlug && $entity->slug !== $ls_originalSlug;

		$lb_originalActive = $entity->hasOriginal('active') ? $entity->getOriginal('active') : null;
		$lb_activeChanged = $lb_originalActive !== null && $entity->active !== $lb_originalActive;

		$lb_originalParentsActive = $entity->hasOriginal('parentsActive') ? $entity->getOriginal('parentsActive') : null;
		$lb_parentsActiveChanged = $lb_originalParentsActive !== null && $entity->parentsActive !== $lb_originalParentsActive;

		$ls_originalLanguage = $entity->hasOriginal('languageShortcode') ? $entity->getOriginal('languageShortcode') : null;
		$lb_languageChanged = $ls_originalLanguage && $entity->languageShortcode !== $ls_originalLanguage;

		if ($lb_languageChanged || $lb_slugChanged) {
			$this->createHistoricalSlugs($lo_table, $entity, $ls_originalLanguage, $ls_originalSlug);
			$this->updateMenuEntries($entity, $ls_originalLanguage, $ls_originalSlug);
		}

		if ($lb_slugChanged || $lb_activeChanged || $lb_parentsActiveChanged) {
			$this->updateDescendants(
				$lo_table,
				$entity,
				$ls_originalLanguage,
				$lb_slugChanged,
				$ls_originalSlug,
				$lb_activeChanged,
				$lb_parentsActiveChanged
			);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->Contents->disableCascadeCallbacks();
		$lo_table->Contents->forPageRole($lo_table->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->Contents->disableCascadeCallbacks();
		$lo_table->Contents->forPageRole($lo_table->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param int $menuId
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @return void
	 */
	protected function addMenuEntry(int $menuId, Page $entity): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = $this->fetchTable('MenuEntries');
		$lo_menuEntry = $lo_table->newDefaultEntity([
			'languageShortcode' => $entity->languageShortcode,
			'link' => $entity->languageShortcode . '/' . $entity->slug,
			'menuId' => $menuId,
			'title' => $entity->title,
		]);

		if (str_contains($entity->slug, '/')) {
			$ls_testSlug = $entity->languageShortcode . '/';
			$ls_testSlug .= substr($entity->slug, 0, strrpos($entity->slug, '/'));

			$lo_records = $lo_table->find()->where([
				'language_shortcode' => $entity->languageShortcode,
				'link' => $ls_testSlug,
				'menu_id' => $menuId,
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
	 * @param \Awyiss\Model\Table\PagesTable $table
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param mixed $originalLanguage
	 * @param mixed $originalSlug
	 * @return void
	 */
	protected function createHistoricalSlugs(PagesTable $table, Page $entity, ?string $originalLanguage, ?string $originalSlug): void {
		$ls_slug = $originalSlug ?? $entity->slug;
		$ls_languageShortcode = $originalLanguage ?? $entity->languageShortcode;

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$li_userId = $this->getIdentity()?->id;
		$ld_now = new DateTime('now');

		// Create a new historical slug entry for the original slug of the provided page
		$lo_query = $table->UrlHistory->insertQuery()->insert(['url', 'scope', 'foreign_key', 'status', 'created_by', 'created_on']);
		$lo_query->values([
			'url' => $ls_languageShortcode . '/' . $ls_slug,
			'scope' => 'pages',
			'foreign_key' => $entity->id,
			'status' => 308,
			'created_by' => $li_userId,
			'created_on' => $ld_now,
		]);

		// Find all pages whose slug starts with the original slug of the provided page
		$lo_records = $table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $expression) use ($ls_slug) {
			return $expression->like('slug', $ls_slug . '/%');
		})->all();

		if (!$lo_records->count()) {
			$lo_query->execute();

			return;
		}

		/**
		 * For each page that has a slug that starts with the original slug of the provided page,
		 * create a new historical slug entry.
		 *
		 * @var \Awyiss\Model\Entity\Page $lo_page
		 */
		foreach ($lo_records as $lo_page) {
			$lo_query->values([
				'url' => $lo_page->languageShortcode . '/' . $lo_page->slug,
				'scope' => 'pages',
				'foreign_key' => $lo_page->id,
				'status' => 308,
				'created_by' => $li_userId,
				'created_on' => $ld_now,
			]);
		}

		$lo_query->execute();
	}


	/**
	 * @param \Awyiss\Model\Table\PagesTable $table
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param string|null $originalLanguage
	 * @param bool $slugChanged
	 * @param string|null $originalSlug
	 * @param bool $activeChanged
	 * @param bool $parentsActiveChanged
	 * @return void
	 */
	protected function updateDescendants(
		PagesTable $table,
		Page $entity,
		?string $originalLanguage,
		bool $slugChanged,
		?string $originalSlug,
		bool $activeChanged,
		bool $parentsActiveChanged
	): void {
		$lo_query = $table->updateQuery()->where([
			'language_shortcode' => $originalLanguage ?? $entity->languageShortcode,
		]);

		if ($slugChanged) {
			/**
			 * UPDATE pages SET slug = (CONCAT('newslug', substr(slug, '8')))
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$lo_query->set('slug', $lo_query->newExpr($lo_query->func()->concat([
				$entity->slug,
				$lo_query->func()->substr([
					'slug' => 'identifier',
					mb_strlen($originalSlug) + 1,
				], [
					null,
					'integer',
				]),
			])));
		}

		$lo_entity = $entity;
		$ls_originalSlug = $originalSlug;
		$lo_subQuery = null;

		if ($activeChanged || $parentsActiveChanged) {
			$lb_parentsActive = $entity->active && $entity->parentsActive;

			if ($lb_parentsActive) {
				/**
				 * When updating all pages with the same slug (LIKE 'oldslug/%'),
				 * do not set the parents_active to true for pages that
				 * are descendants of inactive sites.
				 */
				$lo_subPages = $table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $expression) use ($lo_entity, $ls_originalSlug) {
					return $expression->like('slug', ($ls_originalSlug ?? $lo_entity->slug) . '/%');
				})->where(['active' => false])->all();

				$lo_subQuery = $slugChanged ? clone $lo_query : null;

				foreach ($lo_subPages as $lo_subPage) {
					($lo_subQuery ?? $lo_query)->where(function (QueryExpression $expression/*, Query $query*/) use ($lo_subPage) {
						return $expression->notLike('slug', $lo_subPage->slug . '/%');
					});
				}
			}

			($lo_subQuery ?? $lo_query)->set('parents_active', $lb_parentsActive);
		}

		/**
		 * WHERE slug LIKE 'oldslug/%'
		 */
		$lc_where = function (QueryExpression $expression/*, Query $query*/) use ($lo_entity, $ls_originalSlug) {
			return $expression->like('slug', ($ls_originalSlug ?? $lo_entity->slug) . '/%');
		};

		if ($lo_subQuery) {
			$lo_subQuery->where($lc_where)->execute();
		}

		$lo_query->where($lc_where)->execute();
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param string|null $originalLanguage
	 * @param string|null $originalSlug
	 * @return void
	 */
	protected function updateMenuEntries(Page $entity, ?string $originalLanguage, ?string $originalSlug): void {
		$lo_entity = $entity;
		$ls_slug = $originalSlug ?? $entity->slug;
		$ls_languageShortcode = $originalLanguage ?? $entity->languageShortcode;

		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = $this->fetchTable('MenuEntries');

		$lo_query = $lo_table->updateQuery();

		/**
		 * UPDATE menu_entries SET link = (CONCAT('de/newslug', substr(link, '12')))
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_query->set('link', $lo_query->newExpr($lo_query->func()->concat([
			$lo_entity->languageShortcode . '/' . $lo_entity->slug,
			$lo_query->func()->substr([
				'link' => 'identifier',
				mb_strlen($ls_slug) + 4,
			], [
				null,
				'integer',
			]),
		])));

		/**
		 * WHERE
		 * 	link LIKE 'xx/oldslug/%'
		 * OR
		 * 	link LIKE 'xx/oldslug#%'
		 * OR
		 * 	link = 'xx/oldslug'
		 * with xx being the old language
		 */
		$lo_query->where([
			'OR' => [
				function (QueryExpression $expression/*, Query $query*/) use ($lo_entity, $ls_languageShortcode, $ls_slug) {
					return $expression->like('link', $ls_languageShortcode . '/' . ($ls_slug ?? $lo_entity->slug) . '/%');
				},
				function (QueryExpression $expression/*, Query $query*/) use ($lo_entity, $ls_languageShortcode, $ls_slug) {
					return $expression->like('link', $ls_languageShortcode . '/' . ($ls_slug ?? $lo_entity->slug) . '#%');
				},
				[
					'link' => $ls_languageShortcode . '/' . ($ls_slug ?? $lo_entity->slug),
				],
			],
		]);

		$lo_query->execute();
	}


	/**
	 * Transfers attributes from the database to newly copied pages that have a different page role than their original entity.
	 *
	 * When copying a page with descendants that have a different page role than the original entity of the copied page,
	 * the attributes of those descendants will not be copied automatically, as the attributes belong to a different table.
	 *
	 * For example, if you copy a "Page" that has a child "News" page, the attributes of the "News" page will not be copied automatically,
	 * as the "AttributesNews" table is not associated with the "Pages" table.
	 *
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @return void
	 * @throws \Exception
	 */
	protected function transferAttributes(Page $entity): void {
		/**
		 * Filter child pages to only include those with different page roles and original entities
		 * This ensures we only process pages that need attribute transfer due to page role differences
		 */
		$la_children = collection($entity->childPages)->listNested('desc', 'childPages')->filter(function (Page $page) use ($entity): bool {
			return isset($page->originalEntity) && $page->pageRoleId !== $entity->pageRoleId;
		})->groupBy(function (Page $page) {
			// Group by page role ID value
			return $page->pageRoleId->value;
		})->toArray();

		// Early return if no child pages with different page roles are found
		if (!$la_children) {
			return;
		}

		// Process each group of pages by page role
		foreach ($la_children as $li_pageRoleId => $la_pages) {
			/** @var \Awyiss\Model\Enum\PageRole $le_pageRole */
			$le_pageRole = $la_pages[0]->pageRoleId;
			/** @var \Awyiss\Model\Table\PagesTable $lo_pageRoleTable */
			$lo_pageRoleTable = $this->fetchTable(Inflector::pluralize($le_pageRole->name));

			// Skip page roles that don't have attributes
			if (!$lo_pageRoleTable->hasAttributes()) {
				unset($la_children[ $li_pageRoleId ]);
				continue;
			}

			// Extract original page IDs
			$la_originalIds = array_map(function (Page $page) {
				return $page->originalEntity->id;
			}, $la_pages);

			// Skip if no valid original IDs are found
			if (!$la_originalIds) {
				unset($la_children[ $li_pageRoleId ]);
				continue;
			}

			// Retrieve all attributes for the original pages
			$lo_attributesTable = $this->fetchTable($lo_pageRoleTable->getAttributesTableName(true));
			$la_attributes = $lo_attributesTable->find()->where(['page_id IN' => $la_originalIds])->all()->indexBy('page_id')->toArray();

			// Skip if no attributes are found for any of the original pages
			if (!$la_attributes) {
				continue;
			}

			// Process each page and prepare its attributes for copying
			foreach ($la_pages as $lo_page) {
				$lo_originalPage = $lo_page->originalEntity;

				// Skip if no attribute for the page is found
				if (!isset($la_attributes[ $lo_originalPage->id ])) {
					continue;
				}

				/** @var \Awyiss\Model\Entity $lo_attribute */
				$lo_attribute = $la_attributes[ $lo_originalPage->id ];

				// Mark the attribute as new and unset its ID to prepare for insertion
				$lo_attribute->setNew(true);
				$lo_attribute->unset('id');
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_attribute->pageId = $lo_page->id;
			}

			// Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
			$lo_attributesTable->saveMany($la_attributes, [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			]);
		}
	}
}
