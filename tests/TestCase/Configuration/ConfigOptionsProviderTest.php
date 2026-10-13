<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration;


use Awyiss\Configuration\ConfigOptions\FormsConfigOptions;
use Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions;
use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Test\TestSuite\TestCase;
use Customer\Configuration\ConfigOptions\DummyConfigOptions;
use Customer\Model\Enum\PageRole;


/**
 * Tests for `ConfigOptionsProvider`
 */
class ConfigOptionsProviderTest extends TestCase {
	/**
	 * @return void
	 */
	public function testGetConfigOptionsFiles(): void {
		$files = ConfigOptionsProvider::getConfigOptionsFiles();

		$this->assertIsArray($files);

		$this->assertArrayHasKey('Dummies', $files);
		$this->assertArrayHasKey('Employers', $files);
		$this->assertArrayHasKey('Forms', $files);
		$this->assertArrayHasKey('Newscategories', $files);

		$this->assertArrayNotHasKey('Abstracts', $files);
		$this->assertArrayNotHasKey('Ignoreds', $files);

		$this->assertEquals('\Customer\Configuration\ConfigOptions\DummyConfigOptions', $files['Dummies']);
		$this->assertInstanceOf(GenericDatatablesConfigOptions::class, $files['Employers']);
		$this->assertEquals('\Awyiss\Configuration\ConfigOptions\FormsConfigOptions', $files['Forms']);
		$this->assertInstanceOf(PageRole::class, $files['Newscategories']);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionsFilesLoaded(): void {
		$files = ConfigOptionsProvider::getConfigOptionsFiles(true);

		$this->assertIsArray($files);

		$this->assertArrayHasKey('Dummies', $files);
		$this->assertArrayHasKey('Employers', $files);
		$this->assertArrayHasKey('Forms', $files);
		$this->assertArrayHasKey('Newscategories', $files);

		$this->assertArrayNotHasKey('Abstracts', $files);
		$this->assertArrayNotHasKey('Ignoreds', $files);

		$this->assertInstanceOf(DummyConfigOptions::class, $files['Dummies']);
		$this->assertInstanceOf(GenericDatatablesConfigOptions::class, $files['Employers']);
		$this->assertInstanceOf(FormsConfigOptions::class, $files['Forms']);
		$this->assertInstanceOf(GenericPagesConfigOptions::class, $files['Newscategories']);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionsFile(): void {
		$file = ConfigOptionsProvider::getConfigOptionsFile('dummy');
		$this->assertEquals('\Customer\Configuration\ConfigOptions\DummyConfigOptions', $file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('Employer');
		$this->assertInstanceOf(GenericDatatablesConfigOptions::class, $file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('Forms');
		$this->assertEquals('\Awyiss\Configuration\ConfigOptions\FormsConfigOptions', $file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('Newscategory');
		$this->assertInstanceOf(PageRole::class, $file);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionsFileLoaded(): void {
		$file = ConfigOptionsProvider::getConfigOptionsFile('dummy', true);
		$this->assertInstanceOf(DummyConfigOptions::class, $file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('Employer', true);
		$this->assertInstanceOf(GenericDatatablesConfigOptions::class, $file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('Forms', true);
		$this->assertInstanceOf(FormsConfigOptions::class, $file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('Newscategory', true);
		$this->assertInstanceOf(GenericPagesConfigOptions::class, $file);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionsFileNotFound(): void {
		$file = ConfigOptionsProvider::getConfigOptionsFile('notfound');
		$this->assertNull($file);

		$file = ConfigOptionsProvider::getConfigOptionsFile('notfound', true);
		$this->assertNull($file);
	}


	/**
	 * @return void
	 */
	public function testLoadConfigOptions(): void {
		$options = ConfigOptionsProvider::loadConfigOptions('dummy');
		$this->assertInstanceOf(DummyConfigOptions::class, $options);

		$options = ConfigOptionsProvider::loadConfigOptions('Employer');
		$this->assertInstanceOf(GenericDatatablesConfigOptions::class, $options);

		$options = ConfigOptionsProvider::loadConfigOptions('Forms');
		$this->assertInstanceOf(FormsConfigOptions::class, $options);

		$options = ConfigOptionsProvider::loadConfigOptions('Newscategory');
		$this->assertInstanceOf(GenericPagesConfigOptions::class, $options);
	}


	/**
	 * @return void
	 */
	public function testLoadConfigOptionsNotFound(): void {
		$options = ConfigOptionsProvider::loadConfigOptions('notfound');
		$this->assertNull($options);
	}


	/**
	 * @return void
	 */
	public function testValidateConfigValue(): void {
		$value = ConfigOptionsProvider::validateConfigValue('dummy', 'Backend', 'paginate.enabled', true);
		$this->assertTrue($value);

		$value = ConfigOptionsProvider::validateConfigValue('dummy', 'Backend', 'paginate.enabled', 'false');
		$this->assertFalse($value);

		$value = ConfigOptionsProvider::validateConfigValue('dummy', 'Backend', 'paginate.limit', 10);
		$this->assertTrue($value);

		$value = ConfigOptionsProvider::validateConfigValue('dummy', 'Backend', 'paginate.limit', '50');
		$this->assertFalse($value);
	}


	/**
	 * @return void
	 */
	public function testValidateConfigValueNotFound(): void {
		$value = ConfigOptionsProvider::validateConfigValue('notfound', 'Backend', 'paginate.enabled', 'unknown');
		$this->assertFalse($value);
	}


	/**
	 * @return void
	 */
	public function testTypecastConfigValue(): void {
		$value = ConfigOptionsProvider::typecastConfigValue('dummy', 'Backend', 'paginate.enabled', 'true');
		$this->assertTrue($value);

		$value = ConfigOptionsProvider::typecastConfigValue('dummy', 'Backend', 'paginate.enabled', 'false');
		$this->assertFalse($value);

		$value = ConfigOptionsProvider::typecastConfigValue('dummy', 'Backend', 'paginate.limit', '10');
		$this->assertEquals(10, $value);

		$value = ConfigOptionsProvider::typecastConfigValue('dummy', 'Backend', 'paginate.limit', '50');
		$this->assertEquals(50, $value);
	}


	/**
	 * Test the sanitizeScope method
	 *
	 * @return void
	 */
	public function testSanitizeScope(): void {
		$scope = ConfigOptionsProvider::sanitizeScope('Empty');
		$this->assertEquals('Empties', $scope);

		$scope = ConfigOptionsProvider::sanitizeScope('_abstract');
		$this->assertEquals('Abstracts', $scope);

		$scope = ConfigOptionsProvider::sanitizeScope('content');
		$this->assertEquals('Contents', $scope);

		$scope = ConfigOptionsProvider::sanitizeScope('new_scope');
		$this->assertEquals('NewScopes', $scope);
	}


	/**
	 * Test the sanitizeIdentifier method
	 *
	 * @return void
	 */
	public function testSanitizeIdentifier(): void {
		$identifier = ConfigOptionsProvider::sanitizeIdentifier('Empty');
		$this->assertEquals('empty', $identifier);

		$identifier = ConfigOptionsProvider::sanitizeIdentifier('_abstract');
		$this->assertEquals('abstract', $identifier);

		$identifier = ConfigOptionsProvider::sanitizeIdentifier('content');
		$this->assertEquals('content', $identifier);

		$identifier = ConfigOptionsProvider::sanitizeIdentifier('new_scope');
		$this->assertEquals('newScope', $identifier);
	}
}
