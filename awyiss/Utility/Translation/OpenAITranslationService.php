<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


use Cake\Core\Configure;
use Cake\Http\Client;
use Exception;
use RuntimeException;


/**
 * OpenAI Translation Service Implementation
 * Uses OpenAI's chat completion API for translation with GPT models
 */
class OpenAITranslationService extends AbstractTranslationService {
	/**
	 * @var string|null
	 */
	protected ?string $apiKey;
	/**
	 * @var string
	 */
	protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
	/**
	 * @var int
	 */
	protected int $batchSize = 10;
	/**
	 * @var string
	 */
	protected string $model = 'gpt-4o-mini';
	/**
	 * @var array Supported language codes (OpenAI supports many languages)
	 */
	protected array $supportedLanguages = [
		'af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs',
		'bg', 'ca', 'zh', 'hr', 'cs', 'da', 'nl', 'en', 'eo', 'et',
		'fi', 'fr', 'gl', 'ka', 'de', 'el', 'gu', 'ht', 'he', 'hi',
		'hu', 'is', 'id', 'ga', 'it', 'ja', 'jw', 'kn', 'kk', 'km',
		'ko', 'ku', 'ky', 'lo', 'la', 'lv', 'lt', 'lb', 'mk', 'mg',
		'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my', 'ne', 'no', 'nb',
		'or', 'ps', 'fa', 'pl', 'pt', 'pa', 'ro', 'ru', 'sm', 'gd',
		'sr', 'st', 'sn', 'sd', 'si', 'sk',	'sl', 'so', 'es', 'su',
		'sw', 'sv', 'tl', 'tg', 'ta', 'te', 'th', 'tr', 'uk', 'ur',
		'uz', 'vi', 'cy', 'xh', 'yi', 'yo', 'zu',
	];


	/**
	 * Constructor
	 *
	 * @throws \RuntimeException
	 */
	public function __construct() {
		$this->apiKey = Configure::read('Awyiss.System.Backend.autoTranslate.openAiApiKey');

		if (!$this->apiKey) {
			throw new RuntimeException('OpenAI API key is not configured.');
		}

		// Allow custom model configuration
		$model = Configure::read('Awyiss.System.Backend.autoTranslate.openAiModel');
		if ($model) {
			$this->model = $model;
		}
	}


	/**
	 * @inheritDoc
	 */
	public function getSupportedSourceLanguages(): array {
		return $this->supportedLanguages;
	}


	/**
	 * @inheritDoc
	 */
	public function getSupportedTargetLanguages(): array {
		return $this->supportedLanguages;
	}


	/**
	 * @inheritDoc
	 */
	public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): TranslationResult|false {
		$systemMessage = $this->buildSystemMessage($targetLanguage, $sourceLanguage, false);

		$messages = [
			['role' => 'system', 'content' => $systemMessage],
			['role' => 'user', 'content' => $text],
		];

		try {
			$response = $this->apiRequest($messages);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($response['choices'][0]['message']['content'])) {
			return false;
		}

		$translatedText = $response['choices'][0]['message']['content'];

		return new TranslationResult(
			$text,
			$translatedText,
			$sourceLanguage ?? '',
			$targetLanguage,
			true,
			null,
			[
				'model' => $response['model'] ?? $this->model,
				'usage' => $response['usage'] ?? [],
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
		$originalTexts = array_values($texts);
		$keys = array_keys($texts);

		$systemMessage = $this->buildSystemMessage($targetLanguage, $sourceLanguage, true);

		$messages = [
			['role' => 'system', 'content' => $systemMessage],
			['role' => 'user', 'content' => json_encode($originalTexts)],
		];

		try {
			$response = $this->apiRequest($messages);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($response['choices'][0]['message']['content'])) {
			return false;
		}

		$responseContent = $response['choices'][0]['message']['content'];

		// Parse the JSON response
		$translatedTexts = json_decode($responseContent, true);

		if (!is_array($translatedTexts) || count($translatedTexts) !== count($originalTexts)) {
			return false;
		}

		$translations = [];

		foreach ($translatedTexts as $index => $translatedText) {
			$key = $keys[ $index ];
			$originalText = $originalTexts[ $index ];
			$translations[ $key ] = new TranslationResult(
				$originalText,
				$translatedText,
				$sourceLanguage ?? '',
				$targetLanguage,
				true,
				null,
				[
					'model' => $response['model'] ?? $this->model,
				]
			);
		}

		return $translations;
	}


	/**
	 * @inheritDoc
	 */
	public function getUsageInfo(): ?TranslationUsageInfo {
		// OpenAI doesn't provide a simple usage endpoint
		// Usage information is returned per request in the response
		return null;
	}


	/**
	 * Build the system message for translation
	 *
	 * @param string $targetLanguage The target language code
	 * @param string|null $sourceLanguage The source language code (optional)
	 * @param bool $isBatch Whether this is for batch translation (JSON format)
	 * @return string
	 */
	protected function buildSystemMessage(string $targetLanguage, ?string $sourceLanguage, bool $isBatch): string {
		$fromLanguage = $sourceLanguage ? ' from language with code `' . $sourceLanguage : '`';

		$message = 'You are a professional translator. Translate all user messages' . $fromLanguage . ' into the language with code `' . $targetLanguage . '`';
		$message .= ' accurately and fluently, but ignore `<widget>` tags and their content. Ignoring `<widget>` tags means that you should not translate or alter any text within these tags,';
		$message .= ' and the tags themselves should remain unchanged in the output. THIS IS IMPORTANT.';
		$message .= ' Keep the HTML structure intact. Only translate the text content, do not modify any HTML tags or attributes.';
		$message .= ' Respond only with the translated text, without any additional explanations or comments.';
		$message .= ' Do not add anything that is not in the original text and no information about yourself.';
		$message .= ' Ensure that the translation is accurate and contextually appropriate.';
		$message .= ' Preserve the meaning and tone of the original content. Avoid literal translations that may not convey the intended message.';
		$message .= ' If you encounter any text that is not in a language you recognize, keep it unchanged in the translation.';

		if ($isBatch) {
			$message .= ' The content is provided as a JSON encoded array. Decode it, translate the elements, and respond with a JSON encoded array in the same structure.';
			$message .= ' Ignore json inside the text. Only translate the text values in the array.';
		}

		return $message;
	}


	/**
	 * Make an API request to OpenAI
	 *
	 * @param array $messages The messages array for the chat completion
	 * @return array
	 * @throws RuntimeException
	 */
	protected function apiRequest(array $messages): array {
		$client = new Client([
			'timeout' => 60,
			'http_errors' => false,
		]);

		$body = [
			'model' => $this->model,
			'messages' => $messages,
		];

		$response = $client->post(
			$this->apiUrl,
			json_encode($body),
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->apiKey,
					'Content-Type' => 'application/json',
				],
			]
		);

		if (!$response->isSuccess()) {
			$body = $response->getJson();
			$errorMessage = $body['error']['message'] ?? $response->getBody();
			throw new RuntimeException(sprintf('OpenAI API Error: HTTP %s - %s', $response->getStatusCode(), $errorMessage));
		}

		return $response->getJson();
	}
}
