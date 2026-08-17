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
			/** @var \Awyiss\Middleware\DesignMiddleware $designMiddleware */
			$designMiddleware = Router::getRequest()->getAttribute('design');
			$designMiddleware->resetDesignVariables();
			$designMiddleware->compileScss(true, Awyiss::REALM_FRONTEND);

			$designMiddleware->resetDesignVariables();
			$designMiddleware->compileScss(true, Awyiss::REALM_BACKEND);
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
			'contentIds' => [],
		];

		if (
			$this->checkedTransactions[ $transactionId ]['languageChanged'] === null
			&& $entity->hasOriginal('pageId')
			&& $entity->getOriginal('pageId') !== $entity->pageId
		) {
			$oldPageId = $entity->getOriginal('pageId');
			$newPageId = $entity->pageId;

			if (!isset($this->pagesCache[ $newPageId ]) || !isset($this->pagesCache[ $oldPageId ])) {
				/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
				$pagesTable = FactoryLocator::get('Table')->get('Pages');
				/** @var array<\Awyiss\Model\Entity\Page> $pages */
				$pages = $pagesTable
					->find('all', skipPageRoleCheck: true)
					->select(['id', 'languageShortcode'])
					->where([
						'id IN' => [$oldPageId, $newPageId],
					])
					->all()
					->indexBy('id')
					->toArray()
				;

				$this->pagesCache[ $oldPageId ] = $pages[ $oldPageId ] ?? null;
				$this->pagesCache[ $newPageId ] = $pages[ $newPageId ] ?? null;
			}

			// Cannot determine language change if one of the pages does not exist
			if (!isset($this->pagesCache[ $newPageId ]) || !isset($this->pagesCache[ $oldPageId ])) {
				$this->checkedTransactions[ $transactionId ]['languageChanged'] = false;

				return;
			}

			$this->checkedTransactions[ $transactionId ]['languageChanged'] = $this->pagesCache[ $oldPageId ]->languageShortcode
				!== $this->pagesCache[ $newPageId ]->languageShortcode;
			$this->checkedTransactions[ $transactionId ]['sourceLanguage'] = $this->pagesCache[ $oldPageId ]->languageShortcode;
			$this->checkedTransactions[ $transactionId ]['targetLanguage'] = $this->pagesCache[ $newPageId ]->languageShortcode;
		}

		if ($this->checkedTransactions[ $transactionId ]['languageChanged'] === true) {
			$this->checkedTransactions[ $transactionId ]['contentIds'][] = $entity->id;
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

			foreach ($jobData['ids'] as $contentId) {
				$locks[] = $locksTable->newDefaultEntity([
					'scope' => 'Contents',
					'foreignKey' => $contentId,
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
	 * into one entry to avoid creating multiple jobs for the same translation task.
	 *
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

			$jobData[ $key ] ??= [
				'sourceLanguage' => $transactionData['sourceLanguage'],
				'targetLanguage' => $transactionData['targetLanguage'],
				'type' => 'content',
				'ids' => [],
			];

			$jobData[ $key ]['ids'] = array_merge($jobData[ $key ]['ids'], $transactionData['contentIds']);
		}

		return $jobData ?: null;
	}
}
