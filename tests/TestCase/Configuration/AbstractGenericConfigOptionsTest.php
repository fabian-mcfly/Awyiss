<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration;


use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionCollection;
use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;
use InvalidArgumentException;


/**
 * Tests for `AbstractGenericConfigOptions`
 * via `GenericPagesConfigOptions`
 */
class AbstractGenericConfigOptionsTest extends TestCase {
	/**
	 * @var \Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions
	 */
	protected GenericPagesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->configOptions = ConfigOptionsProvider::loadConfigOptions('News');
	}


	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->configOptions->setDynamicScope('News');

		parent::tearDown();
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorInitializesConfigOptions(): void {
		$mock = $this->getMockBuilder(GenericPagesConfigOptions::class)->setConstructorArgs(['news'])
		->onlyMethods(['initializeConfigOptions'])->getMock();

		$mock->expects($this->once())->method('initializeConfigOptions');

		// Trigger the constructor
		$mock->__construct('news');
	}


	/**
	 * @return void
	 */
	public function testGetDynamicScope(): void {
		$this->assertSame('News', $this->configOptions->getDynamicScope());
	}


	/**
	 * @return void
	 */
	public function testSetDynamicScope(): void {
		$this->configOptions->setDynamicScope('newScope');
		$this->assertSame('NewScopes', $this->configOptions->getDynamicScope());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddAndRetrieveConfigOption(): void {
		$realm = 'Backend';

		$option = new ConfigOption(
			defaultValue: true,
			identifier: 'testOption',
			localizable: false,
			nullable: false,
			type: ConfigOptionType::Bool
		);

		$this->configOptions->add($realm, $option);
		$retrievedOption = $this->configOptions->getConfigOption($realm, 'testOption');

		$this->assertSame($option, $retrievedOption);
	}


	/**
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddConfigOptionThrowsExceptionForInvalidRealm(): void {
		$this->expectException(InvalidArgumentException::class);

		$this->configOptions->add(
			'invalidRealm',
			new ConfigOption(
				defaultValue: true,
				identifier: 'testOption',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool
			)
		);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetConfigOptions(): void {
		$options = $this->configOptions->getConfigOptions('Backend');

		$this->assertInstanceOf(ConfigOptionCollection::class, $options);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateConfigValue(): void {
		$isValid = $this->configOptions->validateConfigValue('Backend', 'contents.enabled', true);

		$this->assertTrue($isValid);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTypecastConfigValue(): void {
		$typecastedValue = $this->configOptions->typecastConfigValue('Backend', 'paginate.limit', '20');

		$this->assertSame(20, $typecastedValue);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetScope(): void {
		$scope = GenericPagesConfigOptions::getScope();

		$this->assertSame('GenericPages', $scope);
	}
}
