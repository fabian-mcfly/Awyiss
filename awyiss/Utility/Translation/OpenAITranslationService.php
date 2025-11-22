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
		$ls_model = Configure::read('Awyiss.System.Backend.autoTranslate.openAiModel');
		if ($ls_model) {
			$this->model = $ls_model;
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
		$ls_systemMessage = $this->buildSystemMessage($targetLanguage, $sourceLanguage, false);

		$la_messages = [
			['role' => 'system', 'content' => $ls_systemMessage],
			['role' => 'user', 'content' => $text],
		];

		try {
			$la_response = $this->apiRequest($la_messages);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($la_response['choices'][0]['message']['content'])) {
			return false;
		}

		$ls_translatedText = $la_response['choices'][0]['message']['content'];

		return new TranslationResult(
			$text,
			$ls_translatedText,
			$sourceLanguage ?? '',
			$targetLanguage,
			true,
			null,
			[
				'model' => $la_response['model'] ?? $this->model,
				'usage' => $la_response['usage'] ?? [],
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
		$la_originalTexts = array_values($texts);
		$la_keys = array_keys($texts);

		$ls_systemMessage = $this->buildSystemMessage($targetLanguage, $sourceLanguage, true);

		$la_messages = [
			['role' => 'system', 'content' => $ls_systemMessage],
			['role' => 'user', 'content' => json_encode($la_originalTexts)],
		];

		try {
			$la_response = $this->apiRequest($la_messages);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($la_response['choices'][0]['message']['content'])) {
			return false;
		}

		$ls_responseContent = $la_response['choices'][0]['message']['content'];

		// Parse the JSON response
		$la_translatedTexts = json_decode($ls_responseContent, true);

		if (!is_array($la_translatedTexts) || count($la_translatedTexts) !== count($la_originalTexts)) {
			return false;
		}

		$la_translations = [];

		foreach ($la_translatedTexts as $li_index => $ls_translatedText) {
			$ls_key = $la_keys[ $li_index ];
			$ls_originalText = $la_originalTexts[ $li_index ];
			$la_translations[ $ls_key ] = new TranslationResult(
				$ls_originalText,
				$ls_translatedText,
				$sourceLanguage ?? '',
				$targetLanguage,
				true,
				null,
				[
					'model' => $la_response['model'] ?? $this->model,
				]
			);
		}

		return $la_translations;
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
		$ls_fromLanguage = $sourceLanguage ? ' from language with code `' . $sourceLanguage : '`';

		$ls_message = 'You are a professional translator. Translate all user messages' . $ls_fromLanguage . ' into the language with code `' . $targetLanguage . '`';
		$ls_message .= ' accurately and fluently, but ignore `<module>` tags and their content. Ignoring `<module>` tags means that you should not translate or alter any text within these tags,';
		$ls_message .= ' and the tags themselves should remain unchanged in the output. THIS IS IMPORTANT.';
		$ls_message .= ' Keep the HTML structure intact. Only translate the text content, do not modify any HTML tags or attributes.';
		$ls_message .= ' Respond only with the translated text, without any additional explanations or comments.';
		$ls_message .= ' Do not add anything that is not in the original text and no information about yourself.';
		$ls_message .= ' Ensure that the translation is accurate and contextually appropriate.';
		$ls_message .= ' Preserve the meaning and tone of the original content. Avoid literal translations that may not convey the intended message.';
		$ls_message .= ' If you encounter any text that is not in a language you recognize, keep it unchanged in the translation.';

		if ($isBatch) {
			$ls_message .= ' The content is provided as a JSON encoded array. Decode it, translate the elements, and respond with a JSON encoded array in the same structure.';
			$ls_message .= ' Ignore json inside the text. Only translate the text values in the array.';
		}

		return $ls_message;
	}


	/**
	 * Make an API request to OpenAI
	 *
	 * @param array $messages The messages array for the chat completion
	 * @return array
	 * @throws RuntimeException
	 */
	protected function apiRequest(array $messages): array {
		$lo_client = new Client([
			'timeout' => 60,
			'http_errors' => false,
		]);

		$la_body = [
			'model' => $this->model,
			'messages' => $messages,
		];

		$lo_response = $lo_client->post(
			$this->apiUrl,
			json_encode($la_body),
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->apiKey,
					'Content-Type' => 'application/json',
				],
			]
		);

		if (!$lo_response->isSuccess()) {
			$la_body = $lo_response->getJson();
			$ls_errorMessage = $la_body['error']['message'] ?? $lo_response->getBody();
			throw new RuntimeException(sprintf('OpenAI API Error: HTTP %s - %s', $lo_response->getStatusCode(), $ls_errorMessage));
		}

		return $lo_response->getJson();
	}
}
