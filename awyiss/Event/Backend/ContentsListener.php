<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Exception;
use ScssPhp\ScssPhp\Exception\SassException;


/**
 * Event listeners for the Contents scope of the backend
 */
class ContentsListener implements EventListenerInterface {
	/**
	 * @var array
	 */
	protected array $checkedTransactions = [];
	/**
	 * @var array<int, \Awyiss\Model\Entity\Page>
	 */
	protected array $pagesCache = [];


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Contents.beforeSave' => 'beforeSave',
			'Model.Contents.afterSave' => 'afterSave',
			'Model.Contents.afterSaveCommit' => 'afterSaveCommit',
			'Model.Pages.afterSaveCommit' => 'afterPageSaveCommit',
			'Configuration.Contents.Backend.columnSystem.className.afterSaveCommit' => 'recompileAfterClassNameSave',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterSaveCommit' => 'recompileAfterMaxColumnsSave',
			'Configuration.Contents.Backend.columnSystem.className.afterDeleteCommit' => 'recompileAfterClassNameDelete',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterDeleteCommit' => 'recompileAfterMaxColumnsDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Content $entity): void {
		// Unset titleTag and subtitleTag if title and subtitle are empty
		if (!$entity->title && $entity->titleTag) {
			$entity->titleTag = null;
		}

		if (!$entity->subtitle && $entity->subtitleTag) {
			$entity->subtitleTag = null;
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, Content $entity, ArrayObject $options): void {
		$this->detectLanguageChange($entity, $options);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Content $entity, ArrayObject $options): void {
		$this->createAutoTranslationJobs();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnused,PhpUnusedParameterInspection
	 */
	public function afterPageSaveCommit(Event $event, Page $entity, ArrayObject $options): void {
		$this->createAutoTranslationJobs();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function recompileAfterClassNameSave(Event $event, Configuration $configuration): void {
		$this->recompileScss('className', $configuration->value);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function recompileAfterMaxColumnsSave(Event $event, Configuration $configuration): void {
		$this->recompileScss('maxColumns', (int)$configuration->value);
	}


	/**
	 * @return void
	 */
	public function recompileAfterClassNameDelete(): void {
		$this->recompileScss('className', false);
	}


	/**
	 * @return void
	 */
	public function recompileAfterMaxColumnsDelete(): void {
		$this->recompileScss('maxColumns', false);
	}


	/**
	 * If the class name or the max columns of the column system is changed,
	 * we need to recompile the SCSS files to apply the changes.
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return void
	 */
	protected function recompileScss(string $type, mixed $value): void {
		if ($value !== false) {
			Configure::write('Awyiss.Contents.Backend.columnSystem.' . $type, $value);
		}
		else {
			Configure::delete('Awyiss.Contents.Backend.columnSystem.' . $type);
		}

		try {
			/** @var \Awyiss\Middleware\DesignMiddleware $lo_designMiddleware */
			$lo_designMiddleware = Router::getRequest()->getAttribute('design');
			$lo_designMiddleware->resetDesignVariables();
			$lo_designMiddleware->compileScss(true, Awyiss::REALM_FRONTEND);

			$lo_designMiddleware->resetDesignVariables();
			$lo_designMiddleware->compileScss(true, Awyiss::REALM_BACKEND);
		}
		catch (SassException) {
			// Ignore SCSS compilation errors here
		}
	}


	/**
	 * Will detect if the content is moved or copied to a page with a different language
	 * than the original one. If so, it will mark the transaction for auto-translation.
	 *
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	protected function detectLanguageChange(Content $entity, ArrayObject $options): void {
		if (
			Configure::read('Awyiss.System.Backend.autoTranslate.mode') !== 'auto' ||
			!isset($options['transactionId'])
		) {
			return;
		}

		$ls_transactionId = $options['transactionId'];
		$this->checkedTransactions[ $ls_transactionId ] ??= [
			'languageChanged' => null,
			'sourceLanguage' => null,
			'targetLanguage' => null,
			'contentIds' => [],
		];

		if (
			$this->checkedTransactions[ $ls_transactionId ]['languageChanged'] === null &&
			$entity->hasOriginal('pageId') &&
			$entity->getOriginal('pageId') !== $entity->pageId
		) {
			$li_oldPageId = $entity->getOriginal('pageId');
			$li_newPageId = $entity->pageId;

			if (!isset($this->pagesCache[ $li_newPageId ]) || !isset($this->pagesCache[ $li_oldPageId ])) {
				/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
				$lo_pagesTable = FactoryLocator::get('Table')->get('Pages');
				/** @var array<\Awyiss\Model\Entity\Page> $la_pages */
				$la_pages = $lo_pagesTable->find('all', skipPageRoleCheck: true)->select(['id', 'language_shortcode'])->where([
					'id IN' => [$li_oldPageId, $li_newPageId],
				])->all()->indexBy('id')->toArray();

				$this->pagesCache[ $li_oldPageId ] = $la_pages[ $li_oldPageId ] ?? null;
				$this->pagesCache[ $li_newPageId ] = $la_pages[ $li_newPageId ] ?? null;
			}

			// Cannot determine language change if one of the pages does not exist
			if (!isset($this->pagesCache[ $li_newPageId ]) || !isset($this->pagesCache[ $li_oldPageId ])) {
				$this->checkedTransactions[ $ls_transactionId ]['languageChanged'] = false;
				return;
			}

			$this->checkedTransactions[ $ls_transactionId ]['languageChanged'] = $this->pagesCache[ $li_oldPageId ]->languageShortcode !== $this->pagesCache[ $li_newPageId ]->languageShortcode;
			$this->checkedTransactions[ $ls_transactionId ]['sourceLanguage'] = $this->pagesCache[ $li_oldPageId ]->languageShortcode;
			$this->checkedTransactions[ $ls_transactionId ]['targetLanguage'] = $this->pagesCache[ $li_newPageId ]->languageShortcode;
		}

		if ($this->checkedTransactions[ $ls_transactionId ]['languageChanged'] === true) {
			$this->checkedTransactions[ $ls_transactionId ]['contentIds'][] = $entity->id;
		}
	}


	/**
	 * @return void
	 */
	protected function createAutoTranslationJobs(): void {
		if (Configure::read('Awyiss.System.Backend.autoTranslate.mode') !== 'auto') {
			return;
		}

		// Bundle all transactions with the same source and target languages into one job
		$la_jobsData = $this->bundleAutoTranslateJobData();
		$this->checkedTransactions = [];

		if (!$la_jobsData) {
			return;
		}

		foreach ($la_jobsData as $la_jobData) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$lo_queue->createJob('AutoTranslate', $la_jobData, [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'system::auto_translation',
			]);

			/** @var \Awyiss\Model\Table\LocksTable $lo_locksTable */
			$lo_locksTable = FactoryLocator::get('Table')->get('Locks');
			$la_locks = [];
			$ld_dateTimeNow = new DateTime()->addHours(1);

			foreach ($la_jobData['ids'] as $li_contentId) {
				$la_locks[] = $lo_locksTable->newDefaultEntity([
					'scope' => 'contents',
					'foreignKey' => $li_contentId,
					'uniqueId' => 'autoTranslate',
					'createdOn' => $ld_dateTimeNow,
					'createdBy' => 0,
				]);
			}

			try {
				$lo_locksTable->saveMany($la_locks, [
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
	 * into one entry to avoid creating multiple jobs for the same translation task.
	 *
	 * Transactions with no language changes are ignored.
	 *
	 * @return array|null
	 */
	protected function bundleAutoTranslateJobData(): ?array {
		$la_jobData = [];

		foreach ($this->checkedTransactions as $la_transactionData) {
			if ($la_transactionData['languageChanged'] !== true) {
				continue;
			}

			$ls_key = $la_transactionData['sourceLanguage'] . '->' . $la_transactionData['targetLanguage'];

			$la_jobData[ $ls_key ] ??= [
				'sourceLanguage' => $la_transactionData['sourceLanguage'],
				'targetLanguage' => $la_transactionData['targetLanguage'],
				'type' => 'content',
				'ids' => [],
			];

			$la_jobData[ $ls_key ]['ids'] = array_merge($la_jobData[ $ls_key ]['ids'], $la_transactionData['contentIds']);
		}

		return $la_jobData ?: null;
	}
}
