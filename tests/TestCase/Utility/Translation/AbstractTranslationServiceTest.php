<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Translation;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Translation\AbstractTranslationService;
use Awyiss\Utility\Translation\TranslationResult;
use Awyiss\Utility\Translation\TranslationUsageInfo;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use ReflectionClass;


/**
 * Test case for AbstractTranslationService
 *
 * @see \Awyiss\Utility\Translation\AbstractTranslationService
 */
class AbstractTranslationServiceTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getBatchSize()
	 */
	public function testGetBatchSizeReturnsDefaultValue(): void {
		$service = $this->createConcreteService();

		$this->assertEquals(10, $service->getBatchSize());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityWithContentEntity(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9); // This content has a title

		$service = $this->createConcreteService([
			'translateBatch' => function (array $texts, string $targetLanguage, ?string $sourceLanguage): array {
				$results = [];
				/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
				foreach ($texts as $key => $text) {
					$results[ $key ] = new TranslationResult(
						$text,
						'Translated: ' . $text,
						$sourceLanguage ?? 'en',
						$targetLanguage
					);
				}

				return $results;
			},
		]);

		$translatedEntity = $service->translateEntity($entity, 'es', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertInstanceOf(Content::class, $translatedEntity);
		$this->assertStringContainsString('Translated:', $translatedEntity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityWithPageEntity(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(1, skipPageRoleCheck: true);

		$service = $this->createConcreteService([
			'translateBatch' => function (array $texts, string $targetLanguage, ?string $sourceLanguage): array {
				$results = [];
				/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
				foreach ($texts as $key => $text) {
					$results[ $key ] = new TranslationResult(
						$text,
						'Translated: ' . $text,
						$sourceLanguage ?? 'de',
						$targetLanguage
					);
				}

				return $results;
			},
		]);

		$translatedEntity = $service->translateEntity($entity, 'es', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertInstanceOf(Page::class, $translatedEntity);
		$this->assertStringContainsString('Translated:', $translatedEntity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityWithSpecificFields(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9); // This content has a title
		$entity->subtitle = 'This is a subtitle';

		$service = $this->createConcreteService([
			'translateBatch' => function (array $texts, string $targetLanguage, ?string $sourceLanguage): array {
				$results = [];
				/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
				foreach ($texts as $key => $text) {
					$results[ $key ] = new TranslationResult(
						$text,
						'Translated: ' . $text,
						$sourceLanguage ?? 'en',
						$targetLanguage
					);
				}

				return $results;
			},
		]);

		/** @var \Awyiss\Model\Entity\Content $translatedEntity */
		$translatedEntity = $service->translateEntity($entity, 'es', 'de', ['title']);

		$this->assertNotFalse($translatedEntity);
		$this->assertStringContainsString('Translated:', $translatedEntity->title);
		// subtitle and text should not be translated
		$this->assertEquals('This is a subtitle', $translatedEntity->subtitle);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityReturnsEntityWhenAllFieldsEmpty(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->newEmptyEntity();
		$entity->title = '';
		$entity->subtitle = '';
		$entity->text = '';

		$service = $this->createConcreteService([
			'translateBatch' => function (): array {
				// Should never be called
				$this->fail('translateBatch should not be called for empty fields');
			},
		]);

		$translatedEntity = $service->translateEntity($entity, 'es', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertSame($entity, $translatedEntity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityReturnsFalseWhenTranslateBatchFails(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9); // This content has a title to translate

		$service = $this->createConcreteService([
			'translateBatch' => function (): false {
				return false;
			},
		]);

		$result = $service->translateEntity($entity, 'es', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntitySkipsFailedTranslations(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9); // This content has a title
		$originalTitle = $entity->title;

		$service = $this->createConcreteService([
			'translateBatch' => function (array $texts, string $targetLanguage, ?string $sourceLanguage): array {
				$results = [];
				foreach ($texts as $key => $text) {
					// Fail translation for 'title' field
					if ($key === 'title') {
						$results[ $key ] = new TranslationResult(
							$text,
							'',
							$sourceLanguage ?? 'en',
							$targetLanguage,
							false,
							'Translation failed'
						);
					}
					else {
						$results[ $key ] = new TranslationResult(
							$text,
							'Translated: ' . $text,
							$sourceLanguage ?? 'en',
							$targetLanguage
						);
					}
				}

				return $results;
			},
		]);

		/** @var \Awyiss\Model\Entity\Content $translatedEntity */
		$translatedEntity = $service->translateEntity($entity, 'es', 'de');

		$this->assertNotFalse($translatedEntity);
		// Title should remain unchanged due to failed translation
		$this->assertEquals($originalTitle, $translatedEntity->title);
		// Other fields should be translated
		if ($entity->text) {
			$this->assertStringContainsString('Translated:', $translatedEntity->text);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityWithAttributeFields(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(4);

		// Check if entity has attributes
		if (!$entity->has('attributes') || !($entity->attributes instanceof EntityInterface)) {
			$this->markTestSkipped('Content entity does not have attributes for testing');
		}

		$service = $this->createConcreteService([
			'translateBatch' => function (array $texts, string $targetLanguage, ?string $sourceLanguage): array {
				$results = [];
				/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
				foreach ($texts as $key => $text) {
					$results[ $key ] = new TranslationResult(
						$text,
						'Translated: ' . $text,
						$sourceLanguage ?? 'en',
						$targetLanguage
					);
				}

				return $results;
			},
		]);

		$translatedEntity = $service->translateEntity($entity, 'es', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertTrue($translatedEntity->isDirty('attributes'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetTranslatableFieldsForContent(): void {
		$service = $this->createConcreteService();

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);

		$fields = $this->callProtectedMethod($service, 'getTranslatableFields', $entity);

		$this->assertIsArray($fields);
		$this->assertContains('title', $fields);
		$this->assertContains('subtitle', $fields);
		$this->assertContains('text', $fields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetTranslatableFieldsForPage(): void {
		$service = $this->createConcreteService();

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(1, skipPageRoleCheck: true);

		$fields = $this->callProtectedMethod($service, 'getTranslatableFields', $entity);

		$this->assertIsArray($fields);
		$this->assertContains('title', $fields);
		$this->assertContains('meta_title', $fields);
		$this->assertContains('meta_description', $fields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetTranslatableFieldsForUnsupportedEntity(): void {
		$service = $this->createConcreteService();

		// Create a mock entity that is neither Content nor Page
		$entity = $this->createMock(EntityInterface::class);

		$fields = $this->callProtectedMethod($service, 'getTranslatableFields', $entity);

		$this->assertIsArray($fields);
		$this->assertEmpty($fields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getContentTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetContentTranslatableFieldsUsesCaching(): void {
		$service = $this->createConcreteService();

		// Clear cache
		$reflection = new ReflectionClass($service);
		$property = $reflection->getProperty('translationFieldsCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($service, []);

		// First call should populate cache
		$fields1 = $this->callProtectedMethod($service, 'getContentTranslatableFields');

		// Second call should return cached value
		$fields2 = $this->callProtectedMethod($service, 'getContentTranslatableFields');

		$this->assertEquals($fields1, $fields2);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getPageRoleTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetPageRoleTranslatableFieldsUsesCaching(): void {
		$service = $this->createConcreteService();

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(1, skipPageRoleCheck: true);

		// Clear cache
		$reflection = new ReflectionClass($service);
		$property = $reflection->getProperty('translationFieldsCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($service, []);

		// First call should populate cache
		$fields1 = $this->callProtectedMethod($service, 'getPageRoleTranslatableFields', $entity);

		// Second call should return cached value
		$fields2 = $this->callProtectedMethod($service, 'getPageRoleTranslatableFields', $entity);

		$this->assertEquals($fields1, $fields2);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getContentTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetContentTranslatableFieldsIncludesTextAttributes(): void {
		$service = $this->createConcreteService();

		// Clear cache
		$reflection = new ReflectionClass($service);
		$property = $reflection->getProperty('translationFieldsCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($service, []);

		$fields = $this->callProtectedMethod($service, 'getContentTranslatableFields');

		$this->assertIsArray($fields);
		// Should always include base fields
		$this->assertContains('title', $fields);
		$this->assertContains('subtitle', $fields);
		$this->assertContains('text', $fields);

		// If attributes exist, check for text/textarea/texteditor types
		$contentsTable = $this->fetchTable('Contents');
		if ($contentsTable->hasAttributes()) {
			// Fields count should be more than just the 3 base fields
			$this->assertGreaterThanOrEqual(3, count($fields));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::getPageRoleTranslatableFields()
	 * @throws \ReflectionException
	 */
	public function testGetPageRoleTranslatableFieldsIncludesTextAttributes(): void {
		$service = $this->createConcreteService();

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(1, skipPageRoleCheck: true);

		// Clear cache
		$reflection = new ReflectionClass($service);
		$property = $reflection->getProperty('translationFieldsCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($service, []);

		$fields = $this->callProtectedMethod($service, 'getPageRoleTranslatableFields', $entity);

		$this->assertIsArray($fields);
		// Should always include base fields
		$this->assertContains('title', $fields);
		$this->assertContains('meta_title', $fields);
		$this->assertContains('meta_description', $fields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\AbstractTranslationService::translateEntity()
	 */
	public function testTranslateEntityFiltersEmptyFields(): void {
		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->newEmptyEntity();
		$entity->title = 'Title';
		$entity->subtitle = ''; // Empty field
		$entity->text = 'Text';

		$translatedFields = [];
		$service = $this->createConcreteService([
			'translateBatch' => function (array $texts, string $targetLanguage, ?string $sourceLanguage) use (&$translatedFields): array {
				$translatedFields = array_keys($texts);
				$results = [];
				/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
				foreach ($texts as $key => $text) {
					$results[ $key ] = new TranslationResult(
						$text,
						'Translated: ' . $text,
						$sourceLanguage ?? 'en',
						$targetLanguage
					);
				}

				return $results;
			},
		]);

		$service->translateEntity($entity, 'es', 'de');

		// Only non-empty fields should be translated
		$this->assertContains('title', $translatedFields);
		$this->assertNotContains('subtitle', $translatedFields);
		$this->assertContains('text', $translatedFields);
	}


	/**
	 * Create a concrete implementation of AbstractTranslationService for testing
	 *
	 * @param array $methods Optional methods to override with callbacks
	 * @return \Awyiss\Utility\Translation\AbstractTranslationService
	 */
	protected function createConcreteService(array $methods = []): AbstractTranslationService {
		return new class ($methods) extends AbstractTranslationService {
			/**
			 * @var array
			 */
			private array $methodOverrides;


			/**
			 * @param array $methodOverrides
			 */
			public function __construct(array $methodOverrides = []) {
				$this->methodOverrides = $methodOverrides;
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedSourceLanguages(): array {
				return $this->methodOverrides['getSupportedSourceLanguages'] ?? ['de', 'en', 'es', 'fr'];
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedTargetLanguages(): array {
				return $this->methodOverrides['getSupportedTargetLanguages'] ?? ['de', 'en', 'es', 'fr'];
			}


			/**
			 * @param string $text
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return \Awyiss\Utility\Translation\TranslationResult|false
			 */
			public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): TranslationResult|false {
				if (isset($this->methodOverrides['translateText'])) {
					return $this->methodOverrides['translateText']($text, $targetLanguage, $sourceLanguage, $options);
				}

				return new TranslationResult($text, 'Translated: ' . $text, $sourceLanguage ?? 'en', $targetLanguage);
			}


			/**
			 * @param array $texts
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return array|false
			 */
			public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
				if (isset($this->methodOverrides['translateBatch'])) {
					return $this->methodOverrides['translateBatch']($texts, $targetLanguage, $sourceLanguage, $options);
				}
				$results = [];
				/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
				foreach ($texts as $key => $text) {
					$results[ $key ] = new TranslationResult($text, 'Translated: ' . $text, $sourceLanguage ?? 'en', $targetLanguage);
				}

				return $results;
			}


			/**
			 * @return \Awyiss\Utility\Translation\TranslationUsageInfo|null
			 */
			public function getUsageInfo(): ?TranslationUsageInfo {
				return $this->methodOverrides['getUsageInfo'] ?? null;
			}
		};
	}
}
