<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;


/**
 * Event listeners for the Pages (and dynamically created page roles) scope of the backend
 */
class PagesListener implements EventListenerInterface {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var array
	 */
	protected array $checkedTransactions = [];


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$events = [];

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$identifier = Inflector::camelize(Inflector::pluralize($pageRole->name));

			$events += [
				'Model.' . $identifier . '.beforeCopy' => 'beforeCopy',
				'Model.' . $identifier . '.afterCopy' => 'afterCopy',
				'Model.' . $identifier . '.beforeSave' => 'beforeSave',
				'Model.' . $identifier . '.afterSave' => 'afterSave',
				'Model.' . $identifier . '.afterSaveCommit' => 'afterSaveCommit',
				'Model.' . $identifier . '.beforeSoftDelete' => 'beforeSoftDelete',
				'Model.' . $identifier . '.beforeDelete' => 'beforeDelete',
				'Model.' . $identifier . '.afterSoftDelete' => 'afterSoftDelete',
				'Model.' . $identifier . '.afterDelete' => 'afterDelete',
			];
		}


		return $events;
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

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		/**
		 * @var \Awyiss\Model\Entity\Page $originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$originalEntity = $entity->originalEntity;

		if (!$originalEntity) {
			return;
		}

		if (($options['copyDescendantsWithDifferentPageRole'] ?? false) === true) {
			$children = $pagesTable->getNestedChildren($originalEntity, [
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
			$children = $pagesTable->getNestedChildren($originalEntity, [
				'finders' => [
					'mediaAssignments' => ['formatResult' => false],
					'translations',
				],
			]);
		}

		if (!$children?->count()) {
			return;
		}

		$nestedChildren = $children->nest('id', 'parentId', 'childPages')->toList();

		$relatedColumns = $pagesTable->getBehavior('Nest')->getConfig('relatedColumns');
		// Remove all blocklisted columns from the related columns
		$blocklistedColumns = $pagesTable->getBehavior('Nest')->getConfig('children.blocklistedColumns');
		if ($blocklistedColumns) {
			$relatedColumns = array_diff($relatedColumns, $blocklistedColumns);
		}

		$relatedColumnValues = $entity->extract($relatedColumns);

		/** @var \Awyiss\Model\Entity\Page $childPage */
		foreach ($children as $childPage) {
			$childPage->patch($relatedColumnValues);

			if ($childPage->pageRoleId !== $entity->pageRoleId) {
				// Unset attributes as they cannot make their way into the copied entity since the page role is different
				$childPage->unset('attributes');
			}
		}

		$childrenPropertyName = 'child' . $pagesTable->getAlias();
		$childrenAssociationName = Inflector::camelize($childrenPropertyName);
		$entity->{$childrenPropertyName} = $nestedChildren;

		$pagesTable->{$childrenAssociationName}->getBehavior('Nest')->setConfig('buildRules', false);
		$pagesTable->{$childrenAssociationName}->getBehavior('Categories')->setConfig('buildRules', false);
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
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		$field = $pagesTable->getSchema()->getColumn('slug');
		$length = $field ? $field['length'] : 0;

		if (empty($entity->slug)) {
			//Make sure the slug is set. Use the title if it's empty.
			$entity->set('slug', str_replace('/', '-', $entity->title));
		}

		if (
			!$entity->isDirty('slug')
			&& !$entity->isDirty('languageShortcode')
			&& !$entity->isDirty('parentId')
		) {
			//If neither the slug, the language nor the parent id have changed, skip the slug logic
			return;
		}

		$preSlug = '';
		if (!empty($entity->parentId)) {
			/** @var \Awyiss\Model\Entity\Page $parentPage */
			$parentPage = $pagesTable->get($entity->parentId, skipPageRoleCheck: true);
			//If there's a parent page, add its slug the one of the current page
			$preSlug = trim($parentPage->slug, '/') . '/';

			$entity->parentsActive = $parentPage->active && $parentPage->parentsActive;
		}
		elseif ($entity->parentsActive !== true) {
			$entity->parentsActive = true;
		}

		$parts = explode('/', $entity->slug);
		$slug = end($parts);
		$slug = $preSlug . $slug;
		$languageShortcode = $entity->languageShortcode;

		$originalSlug = $entity->hasOriginal('slug') ? $entity->getOriginal('slug') : $slug;
		$originalLanguageShortcode = $entity->hasOriginal('languageShortcode')
			? $entity->getOriginal('languageShortcode')
			: $entity->languageShortcode;

		// When the slug or the language has changed, or if it's a new entity, ensure the slug is unique
		if (
			$entity->isNew()
			|| $slug != $originalSlug
			|| $languageShortcode != $originalLanguageShortcode
		) {
			$field = $pagesTable->getAlias() . '.slug';

			$conditions = [
				$field => $slug,
				'languageShortcode' => $entity->languageShortcode,
			];

			$primaryKey = $pagesTable->getPrimaryKey();
			$id = $entity->get($primaryKey);
			if ($id) {
				$conditions['NOT'] = [$pagesTable->getAlias() . '.' . $primaryKey => $id];
			}

			/**
			 * `$conditions` holds an array of query conditions that are used to
			 * find pages with the same slug
			 * ```
			 * [
			 *    "Pages.slug" => "new/slug/of/the/current/page"
			 *    "languageShortcode" => "de"
			 *    "NOT" => [
			 *        "Pages.id" => 1234
			 *    ]
			 * ]
			 * ```
			 */

			$i = 1;
			$suffix = '';

			// As long as a page with the same slug exists, append an increasing number to the slug and try again
			while ($pagesTable->exists($conditions, ['skipPageRoleCheck' => true])) {
				$i++;
				$suffix = '-' . $i;

				if ($length && (mb_strlen($slug . $suffix) > $length)) {
					$slug = mb_substr($slug, 0, $length - mb_strlen($suffix));
				}

				$conditions[ $field ] = $slug . $suffix;
			}

			// Append the suffix, if it's not empty
			if ($suffix) {
				$slug .= $suffix;
			}
		}

		$entity->set('slug', $slug, ['setter' => false]);
		if (!$entity->isNew() && $slug === $originalSlug) {
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
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		if (
			($options['_primary'] ?? false) === true
			&& ($options['copyDescendantsWithDifferentPageRole'] ?? false) === true
			&& $entity->childPages
		) {
			$this->transferAttributes($entity);
		}

		/**
		 * @var \Awyiss\Model\Entity\Page $originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$originalEntity = $entity->originalEntity;

		/** @uses \Awyiss\Model\Table::findTranslations() */
		$entries = $pagesTable->Contents
			->find('threaded', nestingKey: 'childContents')
			->find('mediaAssignments', formatResult: false)
			->find('translations')
			->where(['pageId' => $originalEntity->id])
			->all()
		;

		$listedEntries = $entries->listNested('desc', 'childContents');
		/** @var \Awyiss\Model\Entity\Content $content */
		foreach ($listedEntries as $content) {
			$content->pageId = $entity->id;
		}

		$pagesTable->Contents->saveMany($entries->toList(), [
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
		$this->detectLanguageChange($entity, $options);

		foreach ($entity->addMenuEntry ?? [] as $menuId) {
			$this->addMenuEntry($menuId, $entity);
		}

		if ($entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		$originalSlug = $entity->hasOriginal('slug') ? $entity->getOriginal('slug') : null;
		$slugChanged = $originalSlug && $entity->slug !== $originalSlug;

		$originalActive = $entity->hasOriginal('active') ? $entity->getOriginal('active') : null;
		$activeChanged = $originalActive !== null && $entity->active !== $originalActive;

		$originalParentsActive = $entity->hasOriginal('parentsActive') ? $entity->getOriginal('parentsActive') : null;
		$parentsActiveChanged = $originalParentsActive !== null && $entity->parentsActive !== $originalParentsActive;

		$originalLanguage = $entity->hasOriginal('languageShortcode') ? $entity->getOriginal('languageShortcode') : null;
		$languageChanged = $originalLanguage && $entity->languageShortcode !== $originalLanguage;

		if ($languageChanged || $slugChanged) {
			$this->createHistoricalSlugs($pagesTable, $entity, $originalLanguage, $originalSlug);
			$this->updateMenuEntries($entity, $originalLanguage, $originalSlug);
		}

		if ($slugChanged || $activeChanged || $parentsActiveChanged) {
			$this->updateDescendants(
				$pagesTable,
				$entity,
				$originalLanguage,
				$slugChanged,
				$originalSlug,
				$activeChanged,
				$parentsActiveChanged
			);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Page $entity, ArrayObject $options): void {
		$this->createAutoTranslationJobs();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		$pagesTable->Contents->disableCascadeCallbacks();
		$pagesTable->Contents->forPageRole($pagesTable->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		$pagesTable->Contents->disableCascadeCallbacks();
		$pagesTable->Contents->forPageRole($pagesTable->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		$pagesTable->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		$pagesTable->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param int $menuId
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @return void
	 */
	protected function addMenuEntry(int $menuId, Page $entity): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $menuEntriesTable */
		$menuEntriesTable = $this->fetchTable('MenuEntries');
		$menuEntry = $menuEntriesTable->newDefaultEntity([
			'languageShortcode' => $entity->languageShortcode,
			'link' => $entity->languageShortcode . '/' . $entity->slug,
			'menuId' => $menuId,
			'title' => $entity->title,
		]);

		if (str_contains($entity->slug, '/')) {
			$testSlug = $entity->languageShortcode . '/';
			$testSlug .= substr($entity->slug, 0, strrpos($entity->slug, '/'));

			$records = $menuEntriesTable
				->find()
				->where([
					'languageShortcode' => $entity->languageShortcode,
					'link' => $testSlug,
					'menuId' => $menuId,
				])
				->all()
			;

			if ($records->count()) {
				/** @var \Awyiss\Model\Entity\MenuEntry $existingEntry */
				$existingEntry = $records->first();
				$menuEntry->parentId = $existingEntry->id;
			}
		}

		$menuEntriesTable->save($menuEntry);
	}


	/**
	 * @param \Awyiss\Model\Table\PagesTable $table
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param mixed $originalLanguage
	 * @param mixed $originalSlug
	 * @return void
	 */
	protected function createHistoricalSlugs(PagesTable $table, Page $entity, ?string $originalLanguage, ?string $originalSlug): void {
		$slug = $originalSlug ?? $entity->slug;
		$languageShortcode = $originalLanguage ?? $entity->languageShortcode;

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$userId = $this->getIdentity()?->id;
		$now = new DateTime('now');

		// Create a new historical slug entry for the original slug of the provided page
		$query = $table->UrlHistory->insertQuery()->insert(['url', 'scope', 'foreignKey', 'status', 'createdBy', 'createdOn']);
		$query->values([
			'url' => $languageShortcode . '/' . $slug,
			'scope' => 'Pages',
			'foreignKey' => $entity->id,
			'status' => 308,
			'createdBy' => $userId,
			'createdOn' => $now,
		]);

		// Find all pages whose slug starts with the original slug of the provided page
		$records = $table
			->find('all', skipPageRoleCheck: true)
			->where(function (QueryExpression $expression) use ($slug) {
				return $expression->like('slug', $slug . '/%');
			})
			->all()
		;

		if (!$records->count()) {
			$query->execute();

			return;
		}

		/**
		 * For each page that has a slug that starts with the original slug of the provided page,
		 * create a new historical slug entry.
		 *
		 * @var \Awyiss\Model\Entity\Page $page
		 */
		foreach ($records as $page) {
			$query->values([
				'url' => $page->languageShortcode . '/' . $page->slug,
				'scope' => 'Pages',
				'foreignKey' => $page->id,
				'status' => 308,
				'createdBy' => $userId,
				'createdOn' => $now,
			]);
		}

		$query->execute();
	}


	/**
	 * Will detect if the Page is moved or copied to a different language
	 * than the original one. If so, it will mark the transaction for auto-translation.
	 *
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	protected function detectLanguageChange(Page $entity, ArrayObject $options): void {
		if (
			Configure::read('Awyiss.System.Backend.autoTranslate.mode') !== 'auto'
			|| !isset($options['transactionId'])
		) {
			return;
		}

		$transactionId = $options['transactionId'];
		$this->checkedTransactions[ $transactionId ] ??= [
			'languageChanged' => null,
			'sourceLanguage' => null,
			'targetLanguage' => null,
			'pageIds' => [],
		];

		if ($this->checkedTransactions[ $transactionId ]['languageChanged'] === null) {
			$this->checkedTransactions[ $transactionId ]['languageChanged'] = $entity->hasOriginal('languageShortcode')
				&& $entity->getOriginal('languageShortcode') !== $entity->languageShortcode;

			$this->checkedTransactions[ $transactionId ]['sourceLanguage'] = $entity->hasOriginal('languageShortcode')
				? $entity->getOriginal('languageShortcode')
				: null;

			$this->checkedTransactions[ $transactionId ]['targetLanguage'] = $entity->languageShortcode;
		}

		if ($this->checkedTransactions[ $transactionId ]['languageChanged'] === true) {
			$this->checkedTransactions[ $transactionId ]['pageIds'][ $entity->pageRoleId->name ] ??= [];
			$this->checkedTransactions[ $transactionId ]['pageIds'][ $entity->pageRoleId->name ][] = $entity->id;
		}
	}


	/**
	 * @return void
	 */
	protected function createAutoTranslationJobs(): void {
		if (Configure::read('Awyiss.System.Backend.autoTranslate.mode') !== 'auto') {
			return;
		}

		// Bundle all transactions with the same source and target languages and the same page role into one job
		$jobsData = $this->bundleAutoTranslateJobData();
		$this->checkedTransactions = [];

		if (!$jobsData) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		foreach ($jobsData as $jobData) {
			$queuedJobsTable->createJob('AutoTranslate', $jobData, [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'System::autoTranslation',
			]);

			/** @var \Awyiss\Model\Table\LocksTable $locksTable */
			$locksTable = FactoryLocator::get('Table')->get('Locks');
			$locks = [];
			$dateTimeNow = new DateTime()->addHours(1);

			foreach ($jobData['ids'] as $pageId) {
				$locks[] = $locksTable->newDefaultEntity([
					'scope' => Inflector::camelize(Inflector::pluralize($jobData['type'])),
					'foreignKey' => $pageId,
					'uniqueId' => 'autoTranslate',
					'createdOn' => $dateTimeNow,
					'createdBy' => 0,
				]);
			}

			try {
				$locksTable->saveMany($locks, [
					'checkRules' => false,
				]);
			}
			catch (Exception) {
				// Ignore lock save errors
			}
		}
	}


	/**
	 * Bundles all transactions that have the same source and target language settings
	 * and the same page role into one entry to avoid creating multiple jobs for the same translation task.
	 * Transactions with no language changes are ignored.
	 *
	 * @return array|null
	 */
	protected function bundleAutoTranslateJobData(): ?array {
		$jobData = [];

		foreach ($this->checkedTransactions as $transactionData) {
			if ($transactionData['languageChanged'] !== true) {
				continue;
			}

			$key = $transactionData['sourceLanguage'] . '->' . $transactionData['targetLanguage'];

			foreach ($transactionData['pageIds'] as $pageRole => $pageIds) {
				$fullKey = $key . '::' . $pageRole;

				$jobData[ $fullKey ] ??= [
					'sourceLanguage' => $transactionData['sourceLanguage'],
					'targetLanguage' => $transactionData['targetLanguage'],
					'type' => Inflector::underscore($pageRole),
					'ids' => [],
				];

				$jobData[ $fullKey ]['ids'] = array_merge($jobData[ $fullKey ]['ids'], $pageIds);
			}
		}

		return $jobData;
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
		$query = $table
			->updateQuery()
			->where([
				'languageShortcode' => $originalLanguage ?? $entity->languageShortcode,
			])
		;

		if ($slugChanged) {
			/**
			 * UPDATE pages SET slug = (CONCAT('newslug', substr(slug, '8')))
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$query->set('slug', $query->expr($query
				->func()
				->concat([
					$entity->slug,
					$query
						->func()
						->substr([
							'slug' => 'identifier',
							mb_strlen($originalSlug) + 1,
						], [
							null,
							'integer',
						]),
				])));
		}

		$subQuery = null;
		if ($activeChanged || $parentsActiveChanged) {
			$parentsActive = $entity->active && $entity->parentsActive;

			if ($parentsActive) {
				/**
				 * When updating all pages with the same slug (LIKE 'oldslug/%'),
				 * do not set the parentsActive to true for pages that
				 * are descendants of inactive sites.
				 */
				$subPages = $table
					->find('all', skipPageRoleCheck: true)
					->where(
						function (QueryExpression $expression) use ($entity, $originalSlug) {
							return $expression->like('slug', ($originalSlug ?? $entity->slug) . '/%');
						}
					)
					->where(['active' => false])
					->all()
				;

				$subQuery = $slugChanged ? clone $query : null;

				foreach ($subPages as $subPage) {
					($subQuery ?? $query)->where(function (QueryExpression $expression/*, Query $query*/) use ($subPage) {
						return $expression->notLike('slug', $subPage->slug . '/%');
					})
					;
				}
			}

			($subQuery ?? $query)->set('parentsActive', $parentsActive);
		}

		/**
		 * WHERE slug LIKE 'oldslug/%'
		 */
		$where = function (QueryExpression $expression) use ($entity, $originalSlug) {
			return $expression->like('slug', ($originalSlug ?? $entity->slug) . '/%');
		};

		$subQuery?->where($where)->execute();

		$query->where($where)->execute();
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param string|null $originalLanguage
	 * @param string|null $originalSlug
	 * @return void
	 */
	protected function updateMenuEntries(Page $entity, ?string $originalLanguage, ?string $originalSlug): void {
		$slug = $originalSlug ?? $entity->slug;
		$languageShortcode = $originalLanguage ?? $entity->languageShortcode;

		/** @var \Awyiss\Model\Table\MenuEntriesTable $menuEntriesTable */
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		$query = $menuEntriesTable->updateQuery();

		/**
		 * UPDATE menu_entries SET link = (CONCAT('de/newslug', substr(link, '12')))
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$query->set('link', $query->expr($query
			->func()
			->concat([
				$entity->languageShortcode . '/' . $entity->slug,
				$query
					->func()
					->substr([
						'link' => 'identifier',
						mb_strlen($slug) + 4,
					], [
						null,
						'integer',
					]),
			])));

		/**
		 * WHERE
		 *    link LIKE 'xx/oldslug/%'
		 * OR
		 *    link LIKE 'xx/oldslug#%'
		 * OR
		 *    link = 'xx/oldslug'
		 * with xx being the old language
		 */
		$query->where([
			'OR' => [
				fn(QueryExpression $expression) => $expression->like('link', $languageShortcode . '/' . ($slug ?? $entity->slug) . '/%'),
				fn(QueryExpression $expression) => $expression->like('link', $languageShortcode . '/' . ($slug ?? $entity->slug) . '#%'),
				[
					'link' => $languageShortcode . '/' . ($slug ?? $entity->slug),
				],
			],
		]);

		$query->execute();
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
		$children = collection($entity->childPages)
			->listNested('desc', 'childPages')
			->filter(fn(Page $page): bool => isset($page->originalEntity) && $page->pageRoleId !== $entity->pageRoleId)
			// Group by page role ID value
			->groupBy(fn(Page $page) => $page->pageRoleId->value)
			->toArray()
		;

		// Early return if no child pages with different page roles are found
		if (!$children) {
			return;
		}

		// Process each group of pages by page role
		foreach ($children as $pageRoleId => $pages) {
			/** @var \Awyiss\Model\Enum\PageRole $pageRole */
			$pageRole = $pages[0]->pageRoleId;
			/** @var \Awyiss\Model\Table\PagesTable $pageRoleTable */
			$pageRoleTable = $this->fetchTable(Inflector::pluralize($pageRole->name));

			// Skip page roles that don't have attributes
			if (!$pageRoleTable->hasAttributes()) {
				unset($children[ $pageRoleId ]);
				continue;
			}

			// Extract original page IDs
			$originalIds = array_map(function (Page $page) {
				return $page->originalEntity->id;
			}, $pages);

			// Skip if no valid original IDs are found
			if (!$originalIds) {
				unset($children[ $pageRoleId ]);
				continue;
			}

			// Retrieve all attributes for the original pages
			$attributesTable = $this->fetchTable($pageRoleTable->getAttributesTableName(true));
			$attributes = $attributesTable
				->find()
				->where(['pageId IN' => $originalIds])
				->all()
				->indexBy('pageId')
				->toArray()
			;

			// Skip if no attributes are found for any of the original pages
			if (!$attributes) {
				continue;
			}

			// Process each page and prepare its attributes for copying
			foreach ($pages as $page) {
				$originalPage = $page->originalEntity;

				// Skip if no attribute for the page is found
				if (!isset($attributes[ $originalPage->id ])) {
					continue;
				}

				/** @var \Awyiss\Model\Entity $attribute */
				$attribute = $attributes[ $originalPage->id ];

				// Mark the attribute as new and unset its ID to prepare for insertion
				$attribute->setNew(true);
				$attribute->unset('id');
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$attribute->pageId = $page->id;
			}

			// Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
			$attributesTable->saveMany($attributes, [
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
