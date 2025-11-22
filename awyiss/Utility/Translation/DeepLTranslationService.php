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
		'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr',
		'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv', 'nb', 'nl', 'pl',
		'pt', 'ro', 'ru', 'sk', 'sl', 'sv', 'tr', 'uk', 'zh',
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
	public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): TranslationResult|false {
		$la_data = [
			'text' => $text,
			'target_lang' => $targetLanguage,
			'tag_handling' => 'html',
			'tag_handling_version' => 'v2',
			'formality' => $this->formality,
			'model_type' => $this->modelType,
		];

		if ($sourceLanguage !== null) {
			$la_data['source_lang'] = $sourceLanguage;
		}

		try {
			// Make API request
			$la_response = $this->apiRequest('/translate', $la_data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($la_response['translations'][0])) {
			return false;
		}

		$la_translation = $la_response['translations'][0];

		return new TranslationResult(
			$text,
			$la_translation['text'],
			$la_translation['detected_source_language'] ?? $sourceLanguage ?? '',
			$targetLanguage,
			true,
			null,
			[
				'formality' => $la_data['formality'],
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
		$la_originalTexts = array_values($texts);

		$la_data = [
			'text' => $la_originalTexts,
			'target_lang' => $targetLanguage,
			'tag_handling' => 'html',
			'tag_handling_version' => 'v2',
			'formality' => $this->formality,
			'model_type' => $this->modelType,
		];

		if ($sourceLanguage !== null) {
			$la_data['source_lang'] = $sourceLanguage;
		}

		try {
			// Make API request
			$la_response = $this->apiRequest('/translate', $la_data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($la_response['translations'])) {
			return false;
		}

		$la_translations = [];
		$la_keys = array_keys($texts);

		foreach ($la_response['translations'] as $li_index => $la_translation) {
			$ls_key = $la_keys[ $li_index ];
			$ls_originalText = $la_originalTexts[ $li_index ];
			$la_translations[ $ls_key ] = new TranslationResult(
				$ls_originalText,
				$la_translation['text'],
				$la_translation['detected_source_language'] ?? $sourceLanguage ?? '',
				$targetLanguage,
				true,
				null,
				[
					'formality' => $la_data['formality'],
				]
			);
		}

		return $la_translations;
	}


	/**
	 * @inheritDoc
	 */
	public function getUsageInfo(): ?TranslationUsageInfo {
		try {
			$la_response = $this->apiRequest('/usage', [], 'GET');
		}
		catch (Exception) {
			return null;
		}

		if (isset($la_response['character_count']) && isset($la_response['character_limit'])) {
			return new TranslationUsageInfo(
				used: $la_response['character_count'],
				limit: $la_response['character_limit'],
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
		$ls_url = rtrim($this->apiUrl, '/') . $endpoint;

		$la_data = $data;
		$la_data['auth_key'] = $this->apiKey;

		$lo_client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		if ($method === 'POST') {
			$lo_response = $lo_client->post($ls_url, $la_data);
		}
		else {
			$lo_response = $lo_client->get($ls_url, $la_data);
		}

		if (!$lo_response->isSuccess()) {
			throw new RuntimeException(sprintf('API Error: HTTP %s - %s', $lo_response->getStatusCode(), $lo_response->getBody()));
		}

		return $lo_response->getJson();
	}
}
