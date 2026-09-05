<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Translation\TranslationServiceInterface;
use Cake\Core\Configure;
use Cake\ORM\Table;
use Queue\Queue\Task;
use RuntimeException;


/**
 * AutoTranslateTask
 * Automatically translates content to the specified target language
 */
class AutoTranslateTask extends Task {
	/**
	 * @inheritDoc
	 */
	public ?int $timeout = 180;
	/**
	 * @inheritDoc
	 */
	public ?int $retries = 0;


	/**
	 * @inheritDoc
	 * @param array $data
	 * @param int $jobId
	 * @return void
	 * @throws \Exception
	 */
	public function run(array $data, int $jobId): void {
		if (empty($data['type'])) {
			throw new RuntimeException('AutoTranslateTask: Missing type in job data.');
		}

		if (!Configure::read('Awyiss')) {
			Awyiss::loadConfiguration(
				LocaleMiddleware::getLanguage()->shortcode,
				LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)->shortcode,
			);
		}

		if ($data['type'] === 'content') {
			$this->translateContents($data['sourceLanguage'], $data['targetLanguage'], $data['ids']);

			return;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($pageRoleEnum::tryFromName($data['type'])) {
			$this->translatePages($data['type'], $data['sourceLanguage'], $data['targetLanguage'], $data['ids']);
		}
	}


	/**
	 * Translates contents from source language to target language
	 * using the configured translation service.
	 *
	 * @param string $sourceLanguage
	 * @param string $targetLanguage
	 * @param array<int> $ids
	 * @return void
	 * @throws \Exception
	 */
	protected function translateContents(string $sourceLanguage, string $targetLanguage, array $ids): void {
		/** @var class-string<\Awyiss\Utility\Translation\TranslationServiceInterface>|null $translationServiceClass */
		$translationService = $this->getTranslationService($sourceLanguage, $targetLanguage);

		$batchSize = $translationService->getBatchSize();

		if (count($ids) > $batchSize) {
			$queueJobsTable = $this->fetchTable('Queue.QueuedJobs');
			$remainingContentIds = array_slice($ids, $batchSize);
			$queueJobsTable->createJob('AutoTranslate', [
				'sourceLanguage' => $sourceLanguage,
				'targetLanguage' => $targetLanguage,
				'ids' => $remainingContentIds,
				'type' => 'content',
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'System::autoTranslation',
			]);
		}

		$contentIds = array_slice($ids, 0, $batchSize);
		$contentsTable = $this->fetchTable('Contents');
		$this->translateEntities($contentsTable, $contentIds, $translationService, $targetLanguage, $sourceLanguage, 'content');
	}


	/**
	 * Translates pages from source language to target language
	 * using the configured translation service.
	 *
	 * @param string $type
	 * @param string $sourceLanguage
	 * @param string $targetLanguage
	 * @param array<int> $ids
	 * @return void
	 * @throws \Exception
	 */
	protected function translatePages(string $type, string $sourceLanguage, string $targetLanguage, array $ids): void {
		$translationService = $this->getTranslationService($sourceLanguage, $targetLanguage);

		$batchSize = $translationService->getBatchSize();

		if (count($ids) > $batchSize) {
			$queueJobsTable = $this->fetchTable('Queue.QueuedJobs');
			$remainingContentIds = array_slice($ids, $batchSize);
			$queueJobsTable->createJob('AutoTranslate', [
				'sourceLanguage' => $sourceLanguage,
				'targetLanguage' => $targetLanguage,
				'ids' => $remainingContentIds,
				'type' => $type,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'System::autoTranslation',
			]);
		}

		$pageIds = array_slice($ids, 0, $batchSize);
		$pagesTable = $this->fetchTable(Inflector::camelize(Inflector::pluralize($type)));
		$this->translateEntities($pagesTable, $pageIds, $translationService, $targetLanguage, $sourceLanguage, $type);
	}


	/**
	 * @param string $sourceLanguage
	 * @param string $targetLanguage
	 * @return \Awyiss\Utility\Translation\TranslationServiceInterface
	 */
	protected function getTranslationService(string $sourceLanguage, string $targetLanguage): TranslationServiceInterface {
		/** @var class-string<\Awyiss\Utility\Translation\TranslationServiceInterface>|null $translationServiceClass */
		$translationServiceClass = Configure::read('Awyiss.System.Backend.autoTranslate.translationService');
		if (!$translationServiceClass) {
			throw new RuntimeException('No translation service configured for auto translation.');
		}

		/** @var \Awyiss\Utility\Translation\TranslationServiceInterface $translationService */
		$translationService = new $translationServiceClass();

		if (!in_array($sourceLanguage, $translationService->getSupportedSourceLanguages())) {
			throw new RuntimeException(sprintf('Source language `%s` is not supported by the translation service.', $sourceLanguage));
		}

		if (!in_array($targetLanguage, $translationService->getSupportedTargetLanguages())) {
			throw new RuntimeException(sprintf('Target language `%s` is not supported by the translation service.', $targetLanguage));
		}

		return $translationService;
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param array<int> $ids
	 * @param \Awyiss\Utility\Translation\TranslationServiceInterface $translationService
	 * @param string $targetLanguage
	 * @param string $sourceLanguage
	 * @param string $type
	 * @return void
	 * @throws \Exception
	 */
	protected function translateEntities(
		Table $table,
		array $ids,
		TranslationServiceInterface $translationService,
		string $targetLanguage,
		string $sourceLanguage,
		string $type
	): void {
		$entities = $table
			->find()
			->where(['id IN' => $ids])
			->all()
		;

		if (!$entities->count()) {
			return;
		}

		$translatedEntities = [];
		foreach ($entities as $entity) {
			if ($table->hasAttributes()) {
				$entity->setAccess('attributes', true);
			}

			$translatedEntity = $translationService->translateEntity(
				$entity,
				$targetLanguage,
				$sourceLanguage,
			);

			if ($translatedEntity !== false) {
				$translatedEntities[] = $translatedEntity;
			}
		}

		if (!empty($translatedEntities)) {
			$options = [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			];

			if ($table->hasAttributes()) {
				$options['associated'] = [$table->getAttributesTableName(true)];
			}

			$table->saveMany($translatedEntities, $options);
		}

		// Remove locks
		/** @var \Awyiss\Model\Table\LocksTable $locksTable */
		$locksTable = $this->fetchTable('Locks');
		$locksTable->deleteAll([
			'scope' => Inflector::camelize(Inflector::pluralize($type)),
			'foreignKey IN' => $ids,
			'uniqueId' => 'autoTranslate',
		]);
	}
}
