<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


use Cake\Core\Configure;
use Cake\Http\Client;
use Exception;
use RuntimeException;


/**
 * Google Cloud Translation Service
 */
class GoogleCloudTranslationService extends AbstractTranslationService {
	/**
	 * @var string|null
	 */
	protected ?string $apiKey;
	/**
	 * @var string
	 */
	protected string $apiUrl = 'https://translation.googleapis.com/language/translate/v2';
	/**
	 * @var array Supported language codes
	 */
	protected array $supportedLanguages = [
		'af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs',
		'bg', 'ca', 'ny', 'zh', 'co', 'hr', 'cs', 'da', 'nl', 'en',
		'eo', 'et', 'tl', 'fi', 'fr', 'fy', 'gl', 'ka', 'de', 'el',
		'gu', 'ht', 'ha', 'iw', 'he', 'hi', 'hu', 'is', 'ig', 'id',
		'ga', 'it', 'ja', 'jw', 'kn', 'kk', 'km', 'ko', 'ku', 'ky',
		'lo', 'la', 'lv', 'lt', 'lb', 'mk', 'mg', 'ms', 'ml', 'mt',
		'mi', 'mr', 'mn', 'my', 'ne', 'no', 'or', 'ps', 'fa', 'pl',
		'pt', 'pa', 'ro', 'ru', 'sm', 'gd', 'sr', 'st', 'sn', 'sd',
		'si', 'sk', 'sl', 'so', 'es', 'su', 'sw', 'sv', 'tg', 'ta',
		'te', 'th', 'tr', 'uk', 'ur', 'ug', 'uz', 'vi', 'cy', 'xh',
		'yi', 'yo', 'zu',
	];


	/**
	 * Constructor
	 *
	 * @throws \RuntimeException
	 */
	public function __construct() {
		$this->apiKey = Configure::read('Awyiss.System.Backend.autoTranslate.googleApiKey');

		if (!$this->apiKey) {
			throw new RuntimeException('Google Cloud Translation API key is not configured.');
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
			'q' => $text,
			'target' => $targetLanguage,
			'key' => $this->apiKey,
		];

		if ($sourceLanguage !== null) {
			$la_data['source'] = $sourceLanguage;
		}

		try {
			// Make API request
			$la_response = $this->apiRequest($la_data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($la_response['data']['translations'][0])) {
			return false;
		}

		$la_translation = $la_response['data']['translations'][0];

		return new TranslationResult(
			$text,
			$la_translation['translatedText'],
			$la_translation['detectedSourceLanguage'] ?? $sourceLanguage ?? '',
			$targetLanguage,
			true,
			null,
			[]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
		$la_originalTexts = array_values($texts);

		$la_data = [
			'q' => $la_originalTexts,
			'target' => $targetLanguage,
			'format' => 'html',
		];

		if ($sourceLanguage !== null) {
			$la_data['source'] = $sourceLanguage;
		}

		try {
			// Make API request
			$la_response = $this->apiRequest($la_data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($la_response['data']['translations'])) {
			return false;
		}

		$la_translations = [];
		$la_keys = array_keys($texts);

		foreach ($la_response['data']['translations'] as $li_index => $la_translation) {
			$ls_key = $la_keys[ $li_index ];
			$ls_originalText = $la_originalTexts[ $li_index ];
			$la_translations[ $ls_key ] = new TranslationResult(
				$ls_originalText,
				$la_translation['translatedText'],
				$la_translation['detectedSourceLanguage'] ?? $sourceLanguage ?? '',
				$targetLanguage,
				true,
				null,
				[]
			);
		}

		return $la_translations;
	}


	/**
	 * @inheritDoc
	 */
	public function getUsageInfo(): ?TranslationUsageInfo {
		// Google Cloud Translation API v2 doesn't provide usage information
		// This would require the Cloud Translation Advanced API (v3) or Cloud Billing API
		return null;
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function apiRequest(array $data): array {
		$ls_url = $this->apiUrl . '?key=' . $this->apiKey;

		$lo_client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		$lo_response = $lo_client->post($ls_url, $data, ['type' => 'json']);

		if (!$lo_response->isSuccess()) {
			$la_body = $lo_response->getJson();
			$ls_errorMessage = $la_body['error']['message'] ?? $lo_response->getBody();
			throw new RuntimeException(sprintf('API Error: HTTP %s - %s', $lo_response->getStatusCode(), $ls_errorMessage));
		}

		return $lo_response->getJson();
	}
}
