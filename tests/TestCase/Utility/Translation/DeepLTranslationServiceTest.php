<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Translation;


use Awyiss\Model\Entity\Content;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Translation\DeepLTranslationService;
use Awyiss\Utility\Translation\TranslationResult;
use Awyiss\Utility\Translation\TranslationUsageInfo;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use RuntimeException;


/**
 * Test case for DeepLTranslationService
 *
 * @see \Awyiss\Utility\Translation\DeepLTranslationService
 */
class DeepLTranslationServiceTest extends TestCase {
	use HttpClientTrait;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		// Set up a test API key
		Configure::write('Awyiss.System.Backend.autoTranslate.deeplApiKey', 'test-api-key-123');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorThrowsExceptionWhenApiKeyNotConfigured(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.deeplApiKey');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('DeepL API key is not configured.');

		new DeepLTranslationService();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::getSupportedSourceLanguages()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSupportedSourceLanguages(): void {
		$service = new DeepLTranslationService();
		$languages = $service->getSupportedSourceLanguages();

		$this->assertIsArray($languages);
		$this->assertContains('de', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('es', $languages);
		$this->assertContains('fr', $languages);
		$this->assertContains('ja', $languages);
		$this->assertContains('zh', $languages);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::getSupportedTargetLanguages()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSupportedTargetLanguages(): void {
		$service = new DeepLTranslationService();
		$languages = $service->getSupportedTargetLanguages();

		$this->assertIsArray($languages);
		$this->assertContains('de', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('es', $languages);
		$this->assertContains('fr', $languages);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::getBatchSize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetBatchSize(): void {
		$service = new DeepLTranslationService();

		$this->assertEquals(10, $service->getBatchSize());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextWithSuccess(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'Hello World',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateText('Hallo Welt', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('Hallo Welt', $result->getOriginalText());
		$this->assertEquals('Hello World', $result->getTranslatedText());
		$this->assertEquals('DE', $result->getDetectedSourceLanguage());
		$this->assertEquals('en', $result->getTargetLanguage());
		$this->assertTrue($result->isSuccess());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextWithAutoDetection(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'Hello World',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateText('Hallo Welt', 'en');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('DE', $result->getDetectedSourceLanguage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextReturnsFalseOnApiError(): void {
		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(403, ['Content-Type: application/json'], json_encode(['message' => 'Invalid API key']))
		);

		$service = new DeepLTranslationService();
		$result = $service->translateText('Hello', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextReturnsFalseOnMissingTranslation(): void {
		$responseBody = json_encode([
			'translations' => [],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateText('Hello', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextHandlesHtmlTags(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => '<b>Hello</b> World',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateText('<b>Hallo</b> Welt', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('<b>Hello</b> World', $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchWithSuccess(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'Hello',
				],
				[
					'detected_source_language' => 'DE',
					'text' => 'World',
				],
				[
					'detected_source_language' => 'DE',
					'text' => 'Test',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateBatch([
			'title' => 'Hallo',
			'subtitle' => 'Welt',
			'text' => 'Prüfung',
		], 'en', 'de');

		$this->assertIsArray($result);
		$this->assertCount(3, $result);
		$this->assertArrayHasKey('title', $result);
		$this->assertArrayHasKey('subtitle', $result);
		$this->assertArrayHasKey('text', $result);

		$this->assertInstanceOf(TranslationResult::class, $result['title']);
		$this->assertEquals('Hallo', $result['title']->getOriginalText());
		$this->assertEquals('Hello', $result['title']->getTranslatedText());

		$this->assertInstanceOf(TranslationResult::class, $result['subtitle']);
		$this->assertEquals('Welt', $result['subtitle']->getOriginalText());
		$this->assertEquals('World', $result['subtitle']->getTranslatedText());

		$this->assertInstanceOf(TranslationResult::class, $result['text']);
		$this->assertEquals('Prüfung', $result['text']->getOriginalText());
		$this->assertEquals('Test', $result['text']->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchPreservesArrayKeys(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'First',
				],
				[
					'detected_source_language' => 'DE',
					'text' => 'Second',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateBatch([
			'custom_key_1' => 'Erste',
			'custom_key_2' => 'Zweite',
		], 'en', 'de');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('custom_key_1', $result);
		$this->assertArrayHasKey('custom_key_2', $result);
		$this->assertEquals('First', $result['custom_key_1']->getTranslatedText());
		$this->assertEquals('Second', $result['custom_key_2']->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchReturnsFalseOnApiError(): void {
		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(500, ['Content-Type: application/json'], json_encode(['message' => 'Server error']))
		);

		$service = new DeepLTranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchReturnsFalseOnMissingTranslations(): void {
		$responseBody = json_encode([
			'some_other_key' => 'value',
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchWithEmptyArray(): void {
		$responseBody = json_encode([
			'translations' => [],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateBatch([], 'en');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::getUsageInfo()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetUsageInfoReturnsUsageData(): void {
		$responseBody = json_encode([
			'character_count' => 12345,
			'character_limit' => 500000,
		]);

		// Mock the GET request - HttpClientTrait should match regardless of query params
		$this->mockClientGet(
			'https://api-free.deepl.com/v2/usage?auth_key=test-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$usageInfo = $service->getUsageInfo();

		$this->assertInstanceOf(TranslationUsageInfo::class, $usageInfo);
		$this->assertEquals(12345, $usageInfo->getUsed());
		$this->assertEquals(500000, $usageInfo->getLimit());
		$this->assertEquals('characters', $usageInfo->getUnit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::getUsageInfo()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetUsageInfoReturnsNullOnApiError(): void {
		$this->mockClientGet(
			'https://api-free.deepl.com/v2/usage?auth_key=test-api-key-123',
			$this->newClientResponse(403, ['Content-Type: application/json'], json_encode(['message' => 'Forbidden']))
		);

		$service = new DeepLTranslationService();
		$usageInfo = $service->getUsageInfo();

		$this->assertNull($usageInfo);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::getUsageInfo()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetUsageInfoReturnsNullOnMissingData(): void {
		$responseBody = json_encode([
			'some_other_key' => 'value',
		]);

		$this->mockClientGet(
			'https://api-free.deepl.com/v2/usage?auth_key=test-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$usageInfo = $service->getUsageInfo();

		$this->assertNull($usageInfo);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextIncludesMetadata(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'Hello',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateText('Hallo', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$metadata = $result->getMetadata();
		$this->assertArrayHasKey('formality', $metadata);
		$this->assertEquals('default', $metadata['formality']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchIncludesMetadata(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'Hello',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new DeepLTranslationService();
		$result = $service->translateBatch(['title' => 'Hallo'], 'en', 'de');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('title', $result);
		$metadata = $result['title']->getMetadata();
		$this->assertArrayHasKey('formality', $metadata);
		$this->assertEquals('default', $metadata['formality']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\DeepLTranslationService::translateEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateEntityWithContentEntity(): void {
		$responseBody = json_encode([
			'translations' => [
				[
					'detected_source_language' => 'DE',
					'text' => 'Translated Title',
				],
				[
					'detected_source_language' => 'DE',
					'text' => 'Translated Subtitle',
				],
				[
					'detected_source_language' => 'DE',
					'text' => 'Translated Text',
				],
			],
		]);

		$this->mockClientPost(
			'https://api-free.deepl.com/v2/translate',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9);
		$entity->title = 'Ursprünglicher Titel';
		$entity->subtitle = 'Ursprünglicher Untertitel';
		$entity->text = 'Ursprünglicher Text';

		$service = new DeepLTranslationService();
		$translatedEntity = $service->translateEntity($entity, 'en', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertInstanceOf(Content::class, $translatedEntity);
		$this->assertEquals('Translated Title', $translatedEntity->title);
		$this->assertEquals('Translated Subtitle', $translatedEntity->subtitle);
		$this->assertEquals('Translated Text', $translatedEntity->text);
	}
}
