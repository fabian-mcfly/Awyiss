<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Translation;


use Awyiss\Model\Entity\Content;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Translation\OpenAITranslationService;
use Awyiss\Utility\Translation\TranslationResult;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use RuntimeException;


/**
 * Test case for OpenAITranslationService
 *
 * @see \Awyiss\Utility\Translation\OpenAITranslationService
 */
class OpenAITranslationServiceTest extends TestCase {
	use HttpClientTrait;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		// Set up a test API key
		Configure::write('Awyiss.System.Backend.autoTranslate.openAiApiKey', 'test-openai-api-key-123');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorThrowsExceptionWhenApiKeyNotConfigured(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.openAiApiKey');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenAI API key is not configured.');

		new OpenAITranslationService();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorUsesCustomModel(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.openAiModel', 'gpt-4-foobar');

		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => 'Hello',
					],
				],
			],
			'model' => 'gpt-4-foobar',
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateText('Hallo', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$metadata = $result->getMetadata();
		$this->assertEquals('gpt-4-foobar', $metadata['model']);

		Configure::write('Awyiss.System.Backend.autoTranslate.openAiModel');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::getSupportedSourceLanguages()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSupportedSourceLanguages(): void {
		$service = new OpenAITranslationService();
		$languages = $service->getSupportedSourceLanguages();

		$this->assertIsArray($languages);
		$this->assertContains('de', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('es', $languages);
		$this->assertContains('fr', $languages);
		$this->assertContains('ja', $languages);
		$this->assertContains('zh', $languages);
		$this->assertGreaterThan(90, count($languages));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::getSupportedTargetLanguages()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSupportedTargetLanguages(): void {
		$service = new OpenAITranslationService();
		$languages = $service->getSupportedTargetLanguages();

		$this->assertIsArray($languages);
		$this->assertContains('de', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('es', $languages);
		$this->assertContains('fr', $languages);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::getBatchSize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetBatchSize(): void {
		$service = new OpenAITranslationService();

		$this->assertEquals(10, $service->getBatchSize());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextWithSuccess(): void {
		$responseBody = json_encode([
			'id' => 'chatcmpl-123',
			'object' => 'chat.completion',
			'created' => 1677652288,
			'model' => 'gpt-4o-mini',
			'choices' => [
				[
					'index' => 0,
					'message' => [
						'role' => 'assistant',
						'content' => 'Hello World',
					],
					'finish_reason' => 'stop',
				],
			],
			'usage' => [
				'prompt_tokens' => 56,
				'completion_tokens' => 2,
				'total_tokens' => 58,
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
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
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextWithAutoDetection(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => 'Hello World',
					],
				],
			],
			'model' => 'gpt-4o-mini',
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateText('Hallo Welt', 'en');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('', $result->getDetectedSourceLanguage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextReturnsFalseOnApiError(): void {
		$errorBody = json_encode([
			'error' => [
				'message' => 'Incorrect API key provided',
				'type' => 'invalid_request_error',
				'code' => 'invalid_api_key',
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(401, ['Content-Type: application/json'], $errorBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateText('Hello', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextReturnsFalseOnMissingContent(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'role' => 'assistant',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateText('Hello', 'de');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextIncludesMetadata(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => 'Hello',
					],
				],
			],
			'model' => 'gpt-4o-mini',
			'usage' => [
				'prompt_tokens' => 10,
				'completion_tokens' => 5,
				'total_tokens' => 15,
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateText('Hallo', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$metadata = $result->getMetadata();
		$this->assertArrayHasKey('model', $metadata);
		$this->assertArrayHasKey('usage', $metadata);
		$this->assertEquals('gpt-4o-mini', $metadata['model']);
		$this->assertEquals(15, $metadata['usage']['total_tokens']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchWithSuccess(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => '["Hello","World","Test"]',
					],
				],
			],
			'model' => 'gpt-4o-mini',
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
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
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchPreservesArrayKeys(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => '["First","Second"]',
					],
				],
			],
			'model' => 'gpt-4o-mini',
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
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
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchReturnsFalseOnApiError(): void {
		$errorBody = json_encode([
			'error' => [
				'message' => 'Rate limit exceeded',
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(429, ['Content-Type: application/json'], $errorBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchReturnsFalseOnMissingContent(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'role' => 'assistant',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchReturnsFalseOnInvalidJsonResponse(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => 'This is not valid JSON',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateBatch(['title' => 'Test'], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchReturnsFalseOnMismatchedArrayCount(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => '["Hello"]', // Only 1 item, but 3 were sent
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateBatch([
			'title' => 'Test',
			'subtitle' => 'Test2',
			'text' => 'Test3',
		], 'en');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateBatch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBatchWithEmptyArray(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => '[]',
					],
				],
			],
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateBatch([], 'en');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::getUsageInfo()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetUsageInfoReturnsNull(): void {
		$service = new OpenAITranslationService();
		$usageInfo = $service->getUsageInfo();

		// OpenAI doesn't provide a simple usage endpoint
		$this->assertNull($usageInfo);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateEntityWithContentEntity(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => '["Translated Title","Translated Subtitle","Translated Text"]',
					],
				],
			],
			'model' => 'gpt-4o-mini',
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(9);
		$entity->title = 'Test Title';
		$entity->subtitle = 'Test Subtitle';
		$entity->text = 'Test Text';

		$service = new OpenAITranslationService();
		$translatedEntity = $service->translateEntity($entity, 'en', 'de');

		$this->assertNotFalse($translatedEntity);
		$this->assertInstanceOf(Content::class, $translatedEntity);
		$this->assertEquals('Translated Title', $translatedEntity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\OpenAITranslationService::translateText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateTextHandlesModuleTags(): void {
		$responseBody = json_encode([
			'choices' => [
				[
					'message' => [
						'content' => 'Hello <module>ignored content</module> World',
					],
				],
			],
			'model' => 'gpt-4o-mini',
		]);

		$this->mockClientPost(
			'https://api.openai.com/v1/chat/completions',
			$this->newClientResponse(200, ['Content-Type: application/json'], $responseBody)
		);

		$service = new OpenAITranslationService();
		$result = $service->translateText('Hallo <module>ignored content</module> Welt', 'en', 'de');

		$this->assertInstanceOf(TranslationResult::class, $result);
		$this->assertEquals('Hello <module>ignored content</module> World', $result->getTranslatedText());
	}
}
