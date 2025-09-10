<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use Awyiss\Form\Protection\FormProtectionProvider;
use Awyiss\Test\TestSuite\TestCase;


/**
 * FormProtectionProvider Test Case
 *
 * @see \Awyiss\Form\Protection\FormProtectionProvider
 */
class FormProtectionProviderTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::sanitizeIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSanitizeIdentifier(): void {
		$result = FormProtectionProvider::sanitizeIdentifier('ipCheck');
		$this->assertSame('ip_check', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('IpCheck');
		$this->assertSame('ip_check', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('ip-check');
		$this->assertSame('ip_check', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('ip check');
		$this->assertSame('ip_check', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('Hidden-Input_Form');
		$this->assertSame('hidden_input_form', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('captcha2');
		$this->assertSame('captcha2', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('protección');
		$this->assertSame('proteccion', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('');
		$this->assertSame('', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('A');
		$this->assertSame('a', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::getFormProtectionFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFormProtectionFileWithKnownProtection(): void {
		$result = FormProtectionProvider::getFormProtectionFile('ipCheck');

		$this->assertSame('\Awyiss\Form\Protection\IpCheckFormProtection', $result);
		$this->assertTrue(class_exists($result));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::getFormProtectionFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFormProtectionFileWithUnknownProtection(): void {
		$result = FormProtectionProvider::getFormProtectionFile('nonExistent');
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::getFormProtectionFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFormProtectionFileWithDifferentIdentifierFormats(): void {
		$result1 = FormProtectionProvider::getFormProtectionFile('ipCheck');

		$result2 = FormProtectionProvider::getFormProtectionFile('ip_check');

		$result3 = FormProtectionProvider::getFormProtectionFile('ip-check');

		$this->assertSame('\Awyiss\Form\Protection\IpCheckFormProtection', $result1);

		$this->assertSame($result1, $result2);
		$this->assertSame($result2, $result3);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::getFormProtectionFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFormProtectionFiles(): void {
		$result = FormProtectionProvider::getFormProtectionFiles();

		$this->assertEquals([
			'ip_check' => '\Awyiss\Form\Protection\IpCheckFormProtection',
			'dummy' => '\Customer\Form\Protection\DummyFormProtection',
			'dummy_stops_form_entry' => '\Customer\Form\Protection\DummyStopsFormEntryFormProtection',
			'hidden_input' => '\Customer\Form\Protection\HiddenInputFormProtection',
			'altcha' => '\Awyiss\Form\Protection\AltchaFormProtection',
			'duplicate_check' => '\Awyiss\Form\Protection\DuplicateCheckFormProtection',
		], $result);
	}
}
