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

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($ls_pageRoleEnum::tryFromName($data['type'])) {
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
		/** @var class-string<\Awyiss\Utility\Translation\TranslationServiceInterface>|null $ls_translationServiceClass */
		$lo_translationService = $this->getTranslationService($sourceLanguage, $targetLanguage);

		$li_batchSize = $lo_translationService->getBatchSize();

		if (count($ids) > $li_batchSize) {
			$lo_queueJobsTable = $this->fetchTable('Queue.QueuedJobs');
			$la_remainingContentIds = array_slice($ids, $li_batchSize);
			$lo_queueJobsTable->createJob('AutoTranslate', [
				'sourceLanguage' => $sourceLanguage,
				'targetLanguage' => $targetLanguage,
				'ids' => $la_remainingContentIds,
				'type' => 'content',
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'system::auto_translation',
			]);
		}

		$la_contentIds = array_slice($ids, 0, $li_batchSize);
		$lo_contentsTable = $this->fetchTable('Contents');
		$this->translateEntities($lo_contentsTable, $la_contentIds, $lo_translationService, $targetLanguage, $sourceLanguage, 'content');
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
	protected function translatePages(string $type, mixed $sourceLanguage, mixed $targetLanguage, mixed $ids): void {
		$lo_translationService = $this->getTranslationService($sourceLanguage, $targetLanguage);

		$li_batchSize = $lo_translationService->getBatchSize();

		if (count($ids) > $li_batchSize) {
			$lo_queueJobsTable = $this->fetchTable('Queue.QueuedJobs');
			$la_remainingContentIds = array_slice($ids, $li_batchSize);
			$lo_queueJobsTable->createJob('AutoTranslate', [
				'sourceLanguage' => $sourceLanguage,
				'targetLanguage' => $targetLanguage,
				'ids' => $la_remainingContentIds,
				'type' => $type,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'system::auto_translation',
			]);
		}

		$la_pageIds = array_slice($ids, 0, $li_batchSize);
		$lo_pagesTable = $this->fetchTable(Inflector::camelize(Inflector::pluralize($type)));
		$this->translateEntities($lo_pagesTable, $la_pageIds, $lo_translationService, $targetLanguage, $sourceLanguage, $type);
	}


	/**
	 * @param string $sourceLanguage
	 * @param string $targetLanguage
	 * @return \Awyiss\Utility\Translation\TranslationServiceInterface
	 */
	protected function getTranslationService(string $sourceLanguage, string $targetLanguage): TranslationServiceInterface {
		/** @var class-string<\Awyiss\Utility\Translation\TranslationServiceInterface>|null $ls_translationServiceClass */
		$ls_translationServiceClass = Configure::read('Awyiss.System.Backend.autoTranslate.translationService');
		if (!$ls_translationServiceClass) {
			throw new RuntimeException('No translation service configured for auto translation.');
		}

		/** @var \Awyiss\Utility\Translation\TranslationServiceInterface $lo_translationService */
		$lo_translationService = new $ls_translationServiceClass();

		if (!in_array($sourceLanguage, $lo_translationService->getSupportedSourceLanguages())) {
			throw new RuntimeException(sprintf('Source language `%s` is not supported by the translation service.', $sourceLanguage));
		}

		if (!in_array($targetLanguage, $lo_translationService->getSupportedTargetLanguages())) {
			throw new RuntimeException(sprintf('Target language `%s` is not supported by the translation service.', $targetLanguage));
		}

		return $lo_translationService;
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
		$lo_entities = $table->find()->where(['id IN' => $ids])->all();

		if (!$lo_entities->count()) {
			return;
		}

		$la_translatedEntities = [];
		foreach ($lo_entities as $lo_entity) {
			if ($table->hasAttributes()) {
				$lo_entity->setAccess('attributes', true);
			}

			$lo_translatedEntity = $translationService->translateEntity(
				$lo_entity,
				$targetLanguage,
				$sourceLanguage,
			);

			if ($lo_translatedEntity !== false) {
				$la_translatedEntities[] = $lo_translatedEntity;
			}
		}

		if (!empty($la_translatedEntities)) {
			$la_options = [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			];

			if ($table->hasAttributes()) {
				$la_options['associated'] = [$table->getAttributesTableName(true)];
			}

			$table->saveMany($la_translatedEntities, $la_options);
		}

		// Remove locks
		/** @var \Awyiss\Model\Table\LocksTable $lo_locksTable */
		$lo_locksTable = $this->fetchTable('Locks');
		$lo_locksTable->deleteAll([
			'scope' => Inflector::pluralize($type),
			'foreign_key IN' => $ids,
			'unique_id' => 'autoTranslate',
		]);
	}
}
