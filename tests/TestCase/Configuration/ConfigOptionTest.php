<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration;


use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Tests for `ConfigOption`
 */
class ConfigOptionTest extends TestCase {
	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorSetsProperties(): void {
		$configOption = new ConfigOption(
			'test',
			ConfigOptionType::String,
			'default',
			'description',
			true,
			['global' => true, 'localized' => false],
			true,
			'title',
			function ($value) {
				return (string)$value;
			},
			['value1', 'value2']
		);

		$this->assertSame('default', $configOption->getDefaultValue());
		$this->assertSame('description', $configOption->getDescription());
		$this->assertSame('test', $configOption->getIdentifier());
		$this->assertTrue($configOption->isLocalizable());
		$this->assertTrue($configOption->isNullable());
		$this->assertFalse($configOption->isNullable(true));
		$this->assertTrue($configOption->isPersonalizable());
		$this->assertSame('title', $configOption->getTitle());
		$this->assertSame(ConfigOptionType::String, $configOption->getType());
		$this->assertIsCallable($configOption->getTypecast());
		$this->assertSame(['value1', 'value2'], $configOption->getValues());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithNamedArguments(): void {
		$configOption = new ConfigOption(
			identifier: 'test',
			type: ConfigOptionType::String,
			defaultValue: 'default'
		);

		$this->assertSame('test', $configOption->getIdentifier());
		$this->assertSame(ConfigOptionType::String, $configOption->getType());
		$this->assertSame('default', $configOption->getDefaultValue());
		$this->assertSame('', $configOption->getDescription()); // default value
		$this->assertTrue($configOption->isLocalizable()); // default value
		$this->assertTrue($configOption->isNullable()); // default value
		$this->assertFalse($configOption->isPersonalizable()); // default value
		$this->assertNull($configOption->getTitle()); // default value
		$this->assertNull($configOption->getTypecast()); // default value
		$this->assertNull($configOption->getValues()); // default value
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetDefaultValue(): void {
		$configOption = new ConfigOption('test');
		$configOption->setDefaultValue('default');
		$this->assertSame('default', $configOption->getDefaultValue());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetDescription(): void {
		$configOption = new ConfigOption('test');
		$configOption->setDescription('description');
		$this->assertSame('description', $configOption->getDescription());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetIdentifier(): void {
		$configOption = new ConfigOption('test');
		$configOption->setIdentifier('identifier');
		$this->assertSame('identifier', $configOption->getIdentifier());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetLocalizable(): void {
		$configOption = new ConfigOption('test');
		$configOption->setLocalizable(false);
		$this->assertFalse($configOption->isLocalizable());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetNullable(): void {
		$configOption = new ConfigOption('test');
		$configOption->setNullable(false);
		$this->assertFalse($configOption->isNullable());
		$configOption->setNullable(true, true);
		$this->assertTrue($configOption->isNullable(true));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetPersonalizable(): void {
		$configOption = new ConfigOption('test');
		$configOption->setPersonalizable(true);
		$this->assertTrue($configOption->isPersonalizable());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetTitle(): void {
		$configOption = new ConfigOption('test');
		$configOption->setTitle('title');
		$this->assertSame('title', $configOption->getTitle());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetType(): void {
		$configOption = new ConfigOption('test');
		$configOption->setType(ConfigOptionType::Integer);
		$this->assertSame(ConfigOptionType::Integer, $configOption->getType());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetTypecast(): void {
		$configOption = new ConfigOption('test');
		$typecast = function ($value) {
			return (int)$value;
		};
		$configOption->setTypecast($typecast);
		$this->assertSame($typecast, $configOption->getTypecast());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetValues(): void {
		$configOption = new ConfigOption('test');
		$values = ['value1', 'value2'];
		$configOption->setValues($values);
		$this->assertSame($values, $configOption->getValues());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateConfigValue(): void {
		$configOption = new ConfigOption('test', ConfigOptionType::String);
		$this->assertTrue($configOption->validateConfigValue('valid'));
		$this->assertFalse($configOption->validateConfigValue(123));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateConfigValueLocalizable(): void {
		$configOption = new ConfigOption('test', ConfigOptionType::String, 'default', '', false);
		$this->assertSame('configuration::error_option_not_localizable', $configOption->validateConfigValue('valid', 'en'));

		$configOption = new ConfigOption('test', ConfigOptionType::String, 'default', '', true);
		$this->assertTrue($configOption->validateConfigValue('valid', 'en'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateConfigValueNullable(): void {
		$configOption = new ConfigOption('test', ConfigOptionType::String, 'default', '', true, false);
		$this->assertSame('configuration::error_option_not_nullable', $configOption->validateConfigValue(null));

		$configOption = new ConfigOption('test', ConfigOptionType::String, 'default', '', true, true);
		$this->assertTrue($configOption->validateConfigValue(null));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTypecastConfigValue(): void {
		$configOption = new ConfigOption('test', ConfigOptionType::Integer);
		$this->assertSame(123, $configOption->typecastConfigValue('123'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPrintableValue(): void {
		// Test for boolean type
		$configOption = new ConfigOption('test', ConfigOptionType::Bool, true);
		$this->assertSame('true', $configOption->getPrintableValue());

		$configOption->setDefaultValue(false);
		$this->assertSame('false', $configOption->getPrintableValue());

		// Test for string type
		$configOption = new ConfigOption('test', ConfigOptionType::String, 'example');
		$this->assertSame('example', $configOption->getPrintableValue());

		// Test for integer type
		$configOption = new ConfigOption('test', ConfigOptionType::Integer, 123);
		$this->assertSame(123, $configOption->getPrintableValue());

		// Test for float type
		$configOption = new ConfigOption('test', ConfigOptionType::Float, 123.45);
		$this->assertSame(123.45, $configOption->getPrintableValue());

		// Test for array type
		$configOption = new ConfigOption('test', ConfigOptionType::List, ['value1', 'value2']);
		$this->assertSame('value1, value2', $configOption->getPrintableValue());

		// Test for null value
		$configOption = new ConfigOption('test', ConfigOptionType::String, null);
		$this->assertSame(null, $configOption->getPrintableValue());
	}
}
