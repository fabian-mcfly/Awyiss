<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


use Cake\Datasource\EntityInterface;


/**
 * Interface TranslationServiceInterface
 * Each implementation represents a translation service provider (e.g., DeepL, Google Translate, Azure, etc.)
 * that can automatically translate contents to other languages.
 * Implementations are responsible for handling their own HTTP requests (cURL, Guzzle, etc.)
 * and managing API authentication/configuration.
 */
interface TranslationServiceInterface {
	/**
	 * @return int
	 */
	public function getBatchSize(): int;


	/**
	 * Get a list of supported source languages
	 *
	 * @return array Array of language codes (e.g., ['en', 'de', 'fr'])
	 */
	public function getSupportedSourceLanguages(): array;


	/**
	 * Get a list of supported target languages
	 *
	 * @return array Array of language codes (e.g., ['en', 'de', 'fr'])
	 */
	public function getSupportedTargetLanguages(): array;


	/**
	 * Translate a single text string
	 *
	 * @param string $text The text to translate
	 * @param string $targetLanguage The target language code (e.g., 'de', 'fr')
	 * @param string|null $sourceLanguage The source language code, or null for auto-detection
	 * @param array $options Additional service-specific options (e.g., formality, context)
	 * @return \Awyiss\Utility\Translation\TranslationResult|false The translation result, or false on failure
	 */
	public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): TranslationResult|false;


	/**
	 * Translate multiple text strings in a single request
	 * This is more efficient than calling translateText() multiple times
	 * as it can batch the API requests.
	 *
	 * @param array $texts Array of texts to translate
	 * @param string $targetLanguage The target language code
	 * @param string|null $sourceLanguage The source language code, or null for auto-detection
	 * @param array $options Additional service-specific options
	 * @return array<\Awyiss\Utility\Translation\TranslationResult>|false Array of TranslationResult objects, keyed by the original array keys or false on failure
	 */
	public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false;


	/**
	 * Translate specific fields of an entity
	 * This is a convenience method for translating entity properties.
	 * It internally uses translateBatch() for efficiency.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity The entity to translate
	 * @param string $targetLanguage The target language code
	 * @param string|null $sourceLanguage The source language code, or null for auto-detection
	 * @param array $fields Array of field names to translate (e.g., ['title', 'description'])
	 * @param array $options Additional service-specific options
	 * @return \Cake\Datasource\EntityInterface|false The entity with translated fields
	 */
	public function translateEntity(
		EntityInterface $entity,
		string $targetLanguage,
		?string $sourceLanguage = null,
		array $fields = [],
		array $options = []
	): EntityInterface|false;


	/**
	 * Get the usage/quota information for this service
	 * Returns information about API usage limits, remaining quota, etc.
	 * Returns null if the service doesn't provide this information.
	 *
	 * @return \Awyiss\Utility\Translation\TranslationUsageInfo|null
	 */
	public function getUsageInfo(): ?TranslationUsageInfo;
}
