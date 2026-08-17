<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


use Cake\Core\Configure;
use Cake\Http\Client;
use Exception;
use RuntimeException;


/**
 * DeepL Translation Service
 */
class DeepLTranslationService extends AbstractTranslationService {
	/**
	 * @var string|null
	 */
	protected ?string $apiKey;
	/**
	 * @var string
	 */
	protected string $apiUrl = 'https://api-free.deepl.com/v2';
	/**
	 * @var string
	 */
	protected string $formality = 'default';
	/**
	 * @var string
	 */
	protected string $modelType = 'prefer_quality_optimized';
	/**
	 * @var array Supported language codes
	 */
	protected array $supportedLanguages = [
		'bg',
		'cs',
		'da',
		'de',
		'el',
		'en',
		'es',
		'et',
		'fi',
		'fr',
		'hu',
		'id',
		'it',
		'ja',
		'ko',
		'lt',
		'lv',
		'nb',
		'nl',
		'pl',
		'pt',
		'ro',
		'ru',
		'sk',
		'sl',
		'sv',
		'tr',
		'uk',
		'zh',
	];


	/**
	 * Constructor
	 *
	 * @throws \RuntimeException
	 */
	public function __construct() {
		$this->apiKey = Configure::read('Awyiss.System.Backend.autoTranslate.deeplApiKey');

		if (!$this->apiKey) {
			throw new RuntimeException('DeepL API key is not configured.');
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
	public function translateText(
		string $text,
		string $targetLanguage,
		?string $sourceLanguage = null,
		array $options = []
	): TranslationResult|false {
		$data = [
			'text' => $text,
			'target_lang' => $targetLanguage,
			'tag_handling' => 'html',
			'tag_handling_version' => 'v2',
			'formality' => $this->formality,
			'model_type' => $this->modelType,
		];

		if ($sourceLanguage !== null) {
			$data['source_lang'] = $sourceLanguage;
		}

		try {
			// Make API request
			$response = $this->apiRequest('/translate', $data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($response['translations'][0])) {
			return false;
		}

		$translation = $response['translations'][0];

		return new TranslationResult(
			$text,
			$translation['text'],
			$translation['detected_source_language'] ?? $sourceLanguage ?? '',
			$targetLanguage,
			true,
			null,
			[
				'formality' => $data['formality'],
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
		$originalTexts = array_values($texts);

		$data = [
			'text' => $originalTexts,
			'target_lang' => $targetLanguage,
			'tag_handling' => 'html',
			'tag_handling_version' => 'v2',
			'formality' => $this->formality,
			'model_type' => $this->modelType,
		];

		if ($sourceLanguage !== null) {
			$data['source_lang'] = $sourceLanguage;
		}

		try {
			// Make API request
			$response = $this->apiRequest('/translate', $data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($response['translations'])) {
			return false;
		}

		$translations = [];
		$keys = array_keys($texts);

		foreach ($response['translations'] as $index => $translation) {
			$key = $keys[ $index ];
			$originalText = $originalTexts[ $index ];
			$translations[ $key ] = new TranslationResult(
				$originalText,
				$translation['text'],
				$translation['detected_source_language'] ?? $sourceLanguage ?? '',
				$targetLanguage,
				true,
				null,
				[
					'formality' => $data['formality'],
				]
			);
		}

		return $translations;
	}


	/**
	 * @inheritDoc
	 */
	public function getUsageInfo(): ?TranslationUsageInfo {
		try {
			$response = $this->apiRequest('/usage', [], 'GET');
		}
		catch (Exception) {
			return null;
		}

		if (isset($response['character_count']) && isset($response['character_limit'])) {
			return new TranslationUsageInfo(
				used: $response['character_count'],
				limit: $response['character_limit'],
				unit: 'characters'
			);
		}

		return null;
	}


	/**
	 * @param string $endpoint
	 * @param array $data
	 * @param string $method
	 * @return array
	 */
	protected function apiRequest(string $endpoint, array $data, string $method = 'POST'): array {
		$url = rtrim($this->apiUrl, '/') . $endpoint;

		$data['auth_key'] = $this->apiKey;

		$client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		if ($method === 'POST') {
			$response = $client->post($url, $data);
		}
		else {
			$response = $client->get($url, $data);
		}

		if (!$response->isSuccess()) {
			throw new RuntimeException(sprintf('API Error: HTTP %s - %s', $response->getStatusCode(), $response->getBody()));
		}

		return $response->getJson();
	}
}
