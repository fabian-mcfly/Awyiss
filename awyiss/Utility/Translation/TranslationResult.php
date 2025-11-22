<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


/**
 * Class TranslationResult
 * Represents the result of a single text translation
 */
class TranslationResult {
	/**
	 * @param string $originalText The original text that was translated
	 * @param string $translatedText The translated text
	 * @param string $detectedSourceLanguage The detected or provided source language code
	 * @param string $targetLanguage The target language code
	 * @param bool $success Whether the translation was successful
	 * @param string|null $errorMessage Error message if translation failed
	 * @param array $metadata Additional metadata from the translation service (e.g., confidence, alternatives)
	 */
	public function __construct(
		protected string $originalText,
		protected string $translatedText,
		protected string $detectedSourceLanguage,
		protected string $targetLanguage,
		protected bool $success = true,
		protected ?string $errorMessage = null,
		protected array $metadata = []
	) {
	}


	/**
	 * Get the original text that was translated
	 *
	 * @return string
	 */
	public function getOriginalText(): string {
		return $this->originalText;
	}


	/**
	 * Get the translated text
	 *
	 * @return string
	 */
	public function getTranslatedText(): string {
		return $this->translatedText;
	}


	/**
	 * Get the detected or provided source language
	 *
	 * @return string
	 */
	public function getDetectedSourceLanguage(): string {
		return $this->detectedSourceLanguage;
	}


	/**
	 * Get the target language
	 *
	 * @return string
	 */
	public function getTargetLanguage(): string {
		return $this->targetLanguage;
	}


	/**
	 * Check if the translation was successful
	 *
	 * @return bool
	 */
	public function isSuccess(): bool {
		return $this->success;
	}


	/**
	 * Get the error message if translation failed
	 *
	 * @return string|null
	 */
	public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}


	/**
	 * Get additional metadata from the translation service
	 *
	 * @return array
	 */
	public function getMetadata(): array {
		return $this->metadata;
	}


	/**
	 * Get a specific metadata value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getMetadataValue(string $key, mixed $default = null): mixed {
		return $this->metadata[ $key ] ?? $default;
	}
}
