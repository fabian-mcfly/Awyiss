<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Attribute;


use Awyiss\Attribute\AttributeOptionsInterface;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Test class for Awyiss\Attribute\AttributeOptiosProvider
 */
class AttributeOptionsProviderTest extends TestCase {
	/**
	 * Test the getAttributeOptionsFiles method
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @see AttributeOptionsProvider::getAttributeOptionsFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOptionsFiles(): void {
		$files = AttributeOptionsProvider::getAttributeOptionsFiles();

		$this->assertEquals([
			'Contents' => '\Customer\Attribute\AttributeOptionsCollection\ContentsAttributeOptionsCollection',
			'Empties' => '\Customer\Attribute\AttributeOptionsCollection\EmptyAttributeOptionsCollection',
			'Widgets' => '\Customer\Attribute\AttributeOptionsCollection\WidgetsAttributeOptionsCollection',
			'Cars' => '\Customer\Attribute\AttributeOptionsCollection\CarsAttributeOptionsCollection',
		], $files);
	}


	/**
	 * Test the getAttributeOptionsFiles method
	 * with the `$returnLoaded`-argument set to true
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @see AttributeOptionsProvider::getAttributeOptionsFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOptionsFilesReturnLoaded(): void {
		$files = AttributeOptionsProvider::getAttributeOptionsFiles(true);

		foreach ($files as $file) {
			$this->assertInstanceOf(AttributeOptionsInterface::class, $file);
		}
	}


	/**
	 * Test loading an AttributeOptions class by scope
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @see AttributeOptionsProvider::loadAttributeOptions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadAttributeOptions(): void {
		$attributeOptions = AttributeOptionsProvider::loadAttributeOptions('Empty');

		$this->assertInstanceOf(AttributeOptionsInterface::class, $attributeOptions);

		$attributeOptions = AttributeOptionsProvider::loadAttributeOptions('Abstract');

		$this->assertNull($attributeOptions);
	}


	/**
	 * Test getAttributeOptionsFile method
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @see AttributeOptionsProvider::getAttributeOptionsFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOptionsFile(): void {
		$file = AttributeOptionsProvider::getAttributeOptionsFile('Empty');

		$this->assertEquals('\Customer\Attribute\AttributeOptionsCollection\EmptyAttributeOptionsCollection', $file);

		$file = AttributeOptionsProvider::getAttributeOptionsFile('Abstract');

		$this->assertNull($file);

		$file = AttributeOptionsProvider::getAttributeOptionsFile('Contents', true);

		$this->assertInstanceOf(AttributeOptionsInterface::class, $file);
	}


	/**
	 * Test the sanitizeScope method
	 *
	 * @return void
	 * @see AttributeOptionsProvider::sanitizeScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSanitizeScope(): void {
		$scope = AttributeOptionsProvider::sanitizeScope('Empty');

		$this->assertEquals('Empties', $scope);

		$scope = AttributeOptionsProvider::sanitizeScope('_abstract');

		$this->assertEquals('Abstracts', $scope);

		$scope = AttributeOptionsProvider::sanitizeScope('content');

		$this->assertEquals('Contents', $scope);

		$scope = AttributeOptionsProvider::sanitizeScope('new_scope');

		$this->assertEquals('NewScopes', $scope);
	}


	/**
	 * Test the sanitizeIdentifier method
	 *
	 * @return void
	 * @see AttributeOptionsProvider::getAttributeOptions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSanitizeIdentifier(): void {
		$identifier = AttributeOptionsProvider::sanitizeIdentifier('Empty');

		$this->assertEquals('empty', $identifier);

		$identifier = AttributeOptionsProvider::sanitizeIdentifier('_abstract');

		$this->assertEquals('abstract', $identifier);

		$identifier = AttributeOptionsProvider::sanitizeIdentifier('content');

		$this->assertEquals('content', $identifier);

		$identifier = AttributeOptionsProvider::sanitizeIdentifier('new_scope');

		$this->assertEquals('newScope', $identifier);
	}
}
