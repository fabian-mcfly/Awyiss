<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Translation;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Translation\TranslationResult;


/**
 * Test case for the TranslationResult class.
 *
 * @see \Awyiss\Utility\Translation\TranslationResult
 */
class TranslationResultTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithAllParameters(): void {
		$result = new TranslationResult(
			originalText: 'Hello World',
			translatedText: 'Hola Mundo',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			success: true,
			errorMessage: null,
			metadata: ['confidence' => 0.95, 'service' => 'DeepL']
		);

		$this->assertEquals('Hello World', $result->getOriginalText());
		$this->assertEquals('Hola Mundo', $result->getTranslatedText());
		$this->assertEquals('en', $result->getDetectedSourceLanguage());
		$this->assertEquals('es', $result->getTargetLanguage());
		$this->assertTrue($result->isSuccess());
		$this->assertNull($result->getErrorMessage());
		$this->assertEquals(['confidence' => 0.95, 'service' => 'DeepL'], $result->getMetadata());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithMinimalParameters(): void {
		$result = new TranslationResult(
			originalText: 'Hello',
			translatedText: 'Hola',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertEquals('Hello', $result->getOriginalText());
		$this->assertEquals('Hola', $result->getTranslatedText());
		$this->assertEquals('en', $result->getDetectedSourceLanguage());
		$this->assertEquals('es', $result->getTargetLanguage());
		$this->assertTrue($result->isSuccess());
		$this->assertNull($result->getErrorMessage());
		$this->assertEquals([], $result->getMetadata());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithFailedTranslation(): void {
		$result = new TranslationResult(
			originalText: 'Test text',
			translatedText: '',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			success: false,
			errorMessage: 'API quota exceeded',
			metadata: ['statusCode' => 429]
		);

		$this->assertEquals('Test text', $result->getOriginalText());
		$this->assertEquals('', $result->getTranslatedText());
		$this->assertEquals('en', $result->getDetectedSourceLanguage());
		$this->assertEquals('es', $result->getTargetLanguage());
		$this->assertFalse($result->isSuccess());
		$this->assertEquals('API quota exceeded', $result->getErrorMessage());
		$this->assertEquals(['statusCode' => 429], $result->getMetadata());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getOriginalText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetOriginalText(): void {
		$result = new TranslationResult(
			originalText: 'Original text with special chars: äöü',
			translatedText: 'Translated',
			detectedSourceLanguage: 'de',
			targetLanguage: 'en'
		);

		$this->assertEquals('Original text with special chars: äöü', $result->getOriginalText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getTranslatedText()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTranslatedText(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Translated text with HTML: <b>bold</b>',
			detectedSourceLanguage: 'en',
			targetLanguage: 'de'
		);

		$this->assertEquals('Translated text with HTML: <b>bold</b>', $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getDetectedSourceLanguage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetDetectedSourceLanguage(): void {
		$result = new TranslationResult(
			originalText: 'Bonjour',
			translatedText: 'Hello',
			detectedSourceLanguage: 'fr',
			targetLanguage: 'en'
		);

		$this->assertEquals('fr', $result->getDetectedSourceLanguage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getTargetLanguage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTargetLanguage(): void {
		$result = new TranslationResult(
			originalText: 'Hello',
			translatedText: 'Ciao',
			detectedSourceLanguage: 'en',
			targetLanguage: 'it'
		);

		$this->assertEquals('it', $result->getTargetLanguage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::isSuccess()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsSuccessReturnsTrueByDefault(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertTrue($result->isSuccess());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::isSuccess()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsSuccessReturnsFalseWhenSetExplicitly(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: '',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			success: false
		);

		$this->assertFalse($result->isSuccess());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getErrorMessage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetErrorMessageReturnsNullByDefault(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertNull($result->getErrorMessage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getErrorMessage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetErrorMessageReturnsSetMessage(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: '',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			success: false,
			errorMessage: 'Network timeout occurred'
		);

		$this->assertEquals('Network timeout occurred', $result->getErrorMessage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getMetadata()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetMetadataReturnsEmptyArrayByDefault(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertEquals([], $result->getMetadata());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getMetadata()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetMetadataReturnsSetMetadata(): void {
		$metadata = [
			'confidence' => 0.98,
			'alternatives' => ['Pruebas', 'Examen'],
			'model' => 'gpt-4',
			'tokens_used' => 15,
		];

		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			metadata: $metadata
		);

		$this->assertEquals($metadata, $result->getMetadata());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getMetadataValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetMetadataValueReturnsExistingValue(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			metadata: [
				'confidence' => 0.95,
				'service' => 'DeepL',
			]
		);

		$this->assertEquals(0.95, $result->getMetadataValue('confidence'));
		$this->assertEquals('DeepL', $result->getMetadataValue('service'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getMetadataValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetMetadataValueReturnsNullForNonExistentKey(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			metadata: ['confidence' => 0.95]
		);

		$this->assertNull($result->getMetadataValue('nonexistent'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getMetadataValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetMetadataValueReturnsDefaultForNonExistentKey(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			metadata: ['confidence' => 0.95]
		);

		$this->assertEquals('default_value', $result->getMetadataValue('nonexistent', 'default_value'));
		$this->assertEquals(0, $result->getMetadataValue('missing_int', 0));
		$this->assertEquals([], $result->getMetadataValue('missing_array', []));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult::getMetadataValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetMetadataValueWithComplexDataTypes(): void {
		$result = new TranslationResult(
			originalText: 'Test',
			translatedText: 'Prueba',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es',
			metadata: [
				'alternatives' => ['Option 1', 'Option 2'],
				'scores' => ['grammar' => 0.95, 'fluency' => 0.92],
				'is_formal' => true,
				'processing_time' => 1.25,
			]
		);

		$this->assertEquals(['Option 1', 'Option 2'], $result->getMetadataValue('alternatives'));
		$this->assertEquals(['grammar' => 0.95, 'fluency' => 0.92], $result->getMetadataValue('scores'));
		$this->assertTrue($result->getMetadataValue('is_formal'));
		$this->assertEquals(1.25, $result->getMetadataValue('processing_time'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslationResultWithEmptyStrings(): void {
		$result = new TranslationResult(
			originalText: '',
			translatedText: '',
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertEquals('', $result->getOriginalText());
		$this->assertEquals('', $result->getTranslatedText());
		$this->assertTrue($result->isSuccess());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslationResultWithMultilineText(): void {
		$originalText = "Line 1\nLine 2\nLine 3";
		$translatedText = "Línea 1\nLínea 2\nLínea 3";

		$result = new TranslationResult(
			originalText: $originalText,
			translatedText: $translatedText,
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertEquals($originalText, $result->getOriginalText());
		$this->assertEquals($translatedText, $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslationResultWithSpecialCharacters(): void {
		$originalText = 'Special chars: <>&"\'@#$%^&*()';
		$translatedText = 'Caracteres especiales: <>&"\'@#$%^&*()';

		$result = new TranslationResult(
			originalText: $originalText,
			translatedText: $translatedText,
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertEquals($originalText, $result->getOriginalText());
		$this->assertEquals($translatedText, $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslationResultWithUnicodeCharacters(): void {
		$originalText = 'Unicode: 你好世界 🌍 café';
		$translatedText = 'Unicode: Hello World 🌍 coffee';

		$result = new TranslationResult(
			originalText: $originalText,
			translatedText: $translatedText,
			detectedSourceLanguage: 'zh',
			targetLanguage: 'en'
		);

		$this->assertEquals($originalText, $result->getOriginalText());
		$this->assertEquals($translatedText, $result->getTranslatedText());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Translation\TranslationResult
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslationResultWithLongText(): void {
		$originalText = str_repeat('This is a long text. ', 100);
		$translatedText = str_repeat('Este es un texto largo. ', 100);

		$result = new TranslationResult(
			originalText: $originalText,
			translatedText: $translatedText,
			detectedSourceLanguage: 'en',
			targetLanguage: 'es'
		);

		$this->assertEquals($originalText, $result->getOriginalText());
		$this->assertEquals($translatedText, $result->getTranslatedText());
		$this->assertGreaterThan(2000, strlen($result->getOriginalText()));
	}
}
