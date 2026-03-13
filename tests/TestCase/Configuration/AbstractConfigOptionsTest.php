<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration;


use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionsCollection;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;
use Customer\Configuration\ConfigOptions\PagesConfigOptions;
use InvalidArgumentException;


/**
 * Tests for `AbstractConfigOptions`
 * via `PagesConfigOptions`
 */
class AbstractConfigOptionsTest extends TestCase {
	/**
	 * @var \Customer\Configuration\ConfigOptions\PagesConfigOptions
	 */
	protected PagesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new PagesConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testAddAndRetrieveConfigOption(): void {
		$realm = 'Backend';

		$option = new ConfigOption(
			defaultValue: true,
			identifier: 'testOption',
			localizable: false,
			nullable: false,
			type: ConfigOptionType::Bool,
		);

		$this->configOptions->add($realm, $option);

		$retrievedOption = $this->configOptions->getConfigOption($realm, 'testOption');

		$this->assertSame($option, $retrievedOption);
	}


	/**
	 * @return void
	 */
	public function testAddAndRetrieveConfigOptionForInvalidRealm(): void {
		$realm = 'InvalidRealm';

		$option = new ConfigOption(
			defaultValue: true,
			identifier: 'testOption',
			localizable: false,
			nullable: false,
			type: ConfigOptionType::Bool,
		);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('The realm is not valid. `%s` given.', $realm));

		$this->configOptions->add('InvalidRealm', $option);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptions(): void {
		$realm = 'Backend';

		$options = $this->configOptions->getConfigOptions($realm);

		$this->assertInstanceOf(ConfigOptionsCollection::class, $options);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionsForInvalidRealm(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The realm is not valid. `InvalidRealm` given.');

		$this->configOptions->getConfigOptions('InvalidRealm');
	}


	/**
	 * @return void
	 */
	public function testGetConfigOption(): void {
		$realm = 'Backend';

		$path = 'contents.enabled';

		$option = $this->configOptions->getConfigOption($realm, $path);

		$this->assertInstanceOf(ConfigOption::class, $option);
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionForInvalidRealm(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The realm is not valid. `InvalidRealm` given.');

		$this->configOptions->getConfigOption('InvalidRealm', 'contents.enabled');
	}


	/**
	 * @return void
	 */
	public function testGetConfigOptionForUnknownPath(): void {
		$result = $this->configOptions->getConfigOption('Backend', 'unknownPath');

		$this->assertNull($result);
	}



	/**
	 * @return void
	 */
	public function testGetConfigOptionForCollectionPath(): void {
		$this->expectException(InvalidArgumentException::class);

		$this->expectExceptionMessage('Expected a path to a config option. Found `Awyiss\Configuration\ConfigOptionsCollection` instead.`');

		$this->configOptions->getConfigOption('Backend', 'contents');
	}


	/**
	 * @return void
	 */
	public function testValidateConfigValue(): void {
		$isValid = $this->configOptions->validateConfigValue('Backend', 'contents.enabled', true);

		$this->assertTrue($isValid);

		$isValid = $this->configOptions->validateConfigValue('Backend', 'contents.enabled', 'true');

		$this->assertFalse($isValid);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$isValid = $this->configOptions->validateConfigValue('Backend', 'unknownPath', true, null, true);

		$this->assertTrue($isValid);

		$isValid = $this->configOptions->validateConfigValue('Backend', 'unknownPath', true, null, false);

		$this->assertFalse($isValid);
	}


	/**
	 * @return void
	 */
	public function testValidateConfigValueForLocalizableOption(): void {
		$isValid = $this->configOptions->validateConfigValue('Backend', 'contents.enabled', true, 'en');
		$this->assertSame('Configuration::error_option_not_localizable', $isValid);

		$this->configOptions->add('Backend', [
			new ConfigOption(
				defaultValue: true,
				identifier: 'testOption',
				localizable: true,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
		]);

		$isValid = $this->configOptions->validateConfigValue('Backend', 'testOption', true, 'en');
		$this->assertTrue($isValid);
	}


	/**
	 * @return void
	 */
	public function testValidateConfigValueForNullableOption(): void {
		$isValid = $this->configOptions->validateConfigValue('Backend', 'contents.enabled', null);
		$this->assertSame('Configuration::error_option_not_nullable', $isValid);

		$this->configOptions->add('Backend', [
			new ConfigOption(
				defaultValue: true,
				identifier: 'testOption',
				localizable: false,
				nullable: true,
				type: ConfigOptionType::Bool,
			),
		]);

		$isValid = $this->configOptions->validateConfigValue('Backend', 'testOption', null);
		$this->assertTrue($isValid);
	}


	/**
	 * @return void
	 */
	public function testTypecastConfigValue(): void {
		$typecastedValue = $this->configOptions->typecastConfigValue('Backend', 'paginate.limit', '20');

		$this->assertSame(20, $typecastedValue);
	}


	/**
	 * @return void
	 */
	public function testTypecastConfigValueInvalid(): void {
		$typecastedValue = $this->configOptions->typecastConfigValue('Backend', 'paginate.limit', 'false');

		$this->assertSame(0, $typecastedValue);
	}


	/**
	 * @return void
	 */
	public function testGetScope(): void {
		$scope = PagesConfigOptions::getScope();

		$this->assertSame('Pages', $scope);
	}
}
