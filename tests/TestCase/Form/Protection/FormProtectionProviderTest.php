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
	 */
	public function testSanitizeIdentifier(): void {
		$result = FormProtectionProvider::sanitizeIdentifier('ipCheck');
		$this->assertSame('ipCheck', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('IpCheck');
		$this->assertSame('ipCheck', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('ip-check');
		$this->assertSame('ipCheck', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('ip check');
		$this->assertSame('ipCheck', $result);

		$result = FormProtectionProvider::sanitizeIdentifier('Hidden-Input_Form');
		$this->assertSame('hiddenInputForm', $result);

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
	 */
	public function testGetFormProtectionFileWithKnownProtection(): void {
		$result = FormProtectionProvider::getFormProtectionFile('ipCheck');

		$this->assertSame('\Awyiss\Form\Protection\IpCheckFormProtection', $result);
		$this->assertTrue(class_exists($result));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::getFormProtectionFile()
	 */
	public function testGetFormProtectionFileWithUnknownProtection(): void {
		$result = FormProtectionProvider::getFormProtectionFile('nonExistent');
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\FormProtectionProvider::getFormProtectionFile()
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
	 */
	public function testGetFormProtectionFiles(): void {
		$result = FormProtectionProvider::getFormProtectionFiles();

		$this->assertEquals([
			'ipCheck' => '\Awyiss\Form\Protection\IpCheckFormProtection',
			'dummy' => '\Customer\Form\Protection\DummyFormProtection',
			'dummyStopsFormEntry' => '\Customer\Form\Protection\DummyStopsFormEntryFormProtection',
			'hiddenInput' => '\Customer\Form\Protection\HiddenInputFormProtection',
			'altcha' => '\Awyiss\Form\Protection\AltchaFormProtection',
			'duplicateCheck' => '\Awyiss\Form\Protection\DuplicateCheckFormProtection',
		], $result);
	}
}
