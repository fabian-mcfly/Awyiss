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
		$data = [
			'q' => $text,
			'target' => $targetLanguage,
			'key' => $this->apiKey,
		];

		if ($sourceLanguage !== null) {
			$data['source'] = $sourceLanguage;
		}

		try {
			// Make API request
			$response = $this->apiRequest($data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($response['data']['translations'][0])) {
			return false;
		}

		$translation = $response['data']['translations'][0];

		return new TranslationResult(
			$text,
			$translation['translatedText'],
			$translation['detectedSourceLanguage'] ?? $sourceLanguage ?? '',
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
		$originalTexts = array_values($texts);

		$data = [
			'q' => $originalTexts,
			'target' => $targetLanguage,
			'format' => 'html',
		];

		if ($sourceLanguage !== null) {
			$data['source'] = $sourceLanguage;
		}

		try {
			// Make API request
			$response = $this->apiRequest($data);
		}
		catch (Exception) {
			return false;
		}

		if (!isset($response['data']['translations'])) {
			return false;
		}

		$translations = [];
		$keys = array_keys($texts);

		foreach ($response['data']['translations'] as $index => $translation) {
			$key = $keys[ $index ];
			$originalText = $originalTexts[ $index ];
			$translations[ $key ] = new TranslationResult(
				$originalText,
				$translation['translatedText'],
				$translation['detectedSourceLanguage'] ?? $sourceLanguage ?? '',
				$targetLanguage,
				true,
				null,
				[]
			);
		}

		return $translations;
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
		$url = $this->apiUrl . '?key=' . $this->apiKey;

		$client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		$response = $client->post($url, $data, ['type' => 'json']);

		if (!$response->isSuccess()) {
			$body = $response->getJson();
			$errorMessage = $body['error']['message'] ?? $response->getBody();
			throw new RuntimeException(sprintf('API Error: HTTP %s - %s', $response->getStatusCode(), $errorMessage));
		}

		return $response->getJson();
	}
}
