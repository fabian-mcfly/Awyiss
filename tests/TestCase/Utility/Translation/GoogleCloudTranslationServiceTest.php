<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Translation;


use Awyiss\Model\Entity\Content;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Translation\GoogleCloudTranslationService;
use Awyiss\Utility\Translation\TranslationResult;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use RuntimeException;


/**
 * Test case for GoogleCloudTranslationService
 *
 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService
 */
class GoogleCloudTranslationServiceTest extends TestCase {
	use HttpClientTrait;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		// Set up a test API key
		Configure::write('Awyiss.System.Backend.autoTranslate.googleApiKey', 'test-google-api-key-123');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::__construct()
	 */
	public function testConstructorThrowsExceptionWhenApiKeyNotConfigured(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.googleApiKey');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Google Cloud Translation API key is not configured.');

		new GoogleCloudTranslationService();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::getSupportedSourceLanguages()
	 */
	public function testGetSupportedSourceLanguages(): void {
		$service = new GoogleCloudTranslationService();
		$languages = $service->getSupportedSourceLanguages();

		$this->assertIsArray($languages);
		$this->assertContains('de', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('es', $languages);
		$this->assertContains('fr', $languages);
		$this->assertContains('ja', $languages);
		$this->assertContains('zh', $languages);
		$this->assertGreaterThan(90, count($languages)); // Google supports 100+ languages
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::getSupportedTargetLanguages()
	 */
	public function testGetSupportedTargetLanguages(): void {
		$service = new GoogleCloudTranslationService();
		$languages = $service->getSupportedTargetLanguages();

		$this->assertIsArray($languages);
		$this->assertContains('de', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('es', $languages);
		$this->assertContains('fr', $languages);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::getBatchSize()
	 */
	public function testGetBatchSize(): void {
		$service = new GoogleCloudTranslationService();

		$this->assertEquals(10, $service->getBatchSize());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateText()
	 */
	public function testTranslateTextWithSuccess(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'Hello World',
						'detectedSourceLanguage' => 'de',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateText('Hallo Welt', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('Hallo Welt', $result->getOriginalText());
		$this->assertEquals('Hello World', $result->getTranslatedText());
		$this->assertEquals('de', $result->getDetectedSourceLanguage());
		$this->assertEquals('en', $result->getTargetLanguage());
		$this->assertTrue($result->isSuccess());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateText()
	 */
	public function testTranslateTextWithAutoDetection(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'Hello World',
						'detectedSourceLanguage' => 'de',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateText('Hallo Welt', 'en');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('de', $result->getDetectedSourceLanguage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateText()
	 */
	public function testTranslateTextReturnsFalseOnApiError(): void {
		$errorBody = json_encode([
			'error' => [
				'code' => 403,
				'message' => 'The request is missing a valid API key.',
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(403, ['Content-Type: application/json'], $errorBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateText('Hello', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateText()
	 */
	public function testTranslateTextReturnsFalseOnMissingTranslation(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateText('Hello', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateText()
	 */
	public function testTranslateTextHandlesHtmlTags(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => '&lt;b&gt;Hello&lt;/b&gt; World',
						'detectedSourceLanguage' => 'de',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateText('<b>Hallo</b> Welt', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('&lt;b&gt;Hello&lt;/b&gt; World', $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateBatch()
	 */
	public function testTranslateBatchWithSuccess(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'Hello',
						'detectedSourceLanguage' => 'de',
					],
					[
						'translatedText' => 'World',
						'detectedSourceLanguage' => 'de',
					],
					[
						'translatedText' => 'Test',
						'detectedSourceLanguage' => 'de',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
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
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateBatch()
	 */
	public function testTranslateBatchPreservesArrayKeys(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'First',
						'detectedSourceLanguage' => 'de',
					],
					[
						'translatedText' => 'Second',
						'detectedSourceLanguage' => 'de',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
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
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateBatch()
	 */
	public function testTranslateBatchReturnsFalseOnApiError(): void {
		$errorBody = json_encode([
			'error' => [
				'code' => 500,
				'message' => 'Internal server error',
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(500, ['Content-Type: application/json'], $errorBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateBatch()
	 */
	public function testTranslateBatchReturnsFalseOnMissingTranslations(): void {
		$responseBody = json_encode([
			'data' => [
				'some_other_key' => 'value',
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateBatch()
	 */
	public function testTranslateBatchWithEmptyArray(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateBatch([], 'en');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::getUsageInfo()
	 */
	public function testGetUsageInfoReturnsNull(): void {
		$service = new GoogleCloudTranslationService();
		$usageInfo = $service->getUsageInfo();

		// Google Cloud Translation API v2 doesn't provide usage information
		$this->assertNull($usageInfo);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateEntity()
	 */
	public function testTranslateEntityWithContentEntity(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'Translated Title',
						'detectedSourceLanguage' => 'de',
					],
					[
						'translatedText' => 'Translated Subtitle',
						'detectedSourceLanguage' => 'de',
					],
					[
						'translatedText' => 'Translated Text',
						'detectedSourceLanguage' => 'de',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9);
		$entity->title = 'Test Title';
		$entity->subtitle = 'Test Subtitle';
		$entity->text = 'Test Text';

		$service = new GoogleCloudTranslationService();
		$translatedEntity = $service->translateEntity($entity, 'en', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertInstanceOf(Content::class, $translatedEntity);
		$this->assertEquals('Translated Title', $translatedEntity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateText()
	 */
	public function testTranslateTextWithoutDetectedSourceLanguage(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'Hello World',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateText('Hallo Welt', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('de', $result->getDetectedSourceLanguage());
		$this->assertEquals('Hello World', $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\GoogleCloudTranslationService::translateBatch()
	 */
	public function testTranslateBatchWithoutDetectedSourceLanguage(): void {
		$responseBody = json_encode([
			'data' => [
				'translations' => [
					[
						'translatedText' => 'Hello',
					],
					[
						'translatedText' => 'World',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://translation.googleapis.com/language/translate/v2?key=test-google-api-key-123',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new GoogleCloudTranslationService();
		$result = $service->translateBatch([
			'title' => 'Hallo',
			'subtitle' => 'Welt',
		], 'en', 'de');

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertEquals('de', $result['title']->getDetectedSourceLanguage());
		$this->assertEquals('de', $result['subtitle']->getDetectedSourceLanguage());
	}
}
