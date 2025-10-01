<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\UrlsNotFoundConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * UrlsNotFoundConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\UrlsNotFoundConfigOptions
 */
class UrlsNotFoundConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\UrlsNotFoundConfigOptions
	 */
	protected UrlsNotFoundConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new UrlsNotFoundConfigOptions();
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(2, $configOptions);

		$this->assertArrayHasKey('Backend.paginate.limit', $configOptions);
		$this->assertFalse($configOptions['Backend.paginate.limit']->isLocalizable());
		$this->assertFalse($configOptions['Backend.paginate.limit']->isNullable());
		$this->assertTrue($configOptions['Backend.paginate.limit']->isPersonalizable());
		$this->assertSame(20, $configOptions['Backend.paginate.limit']->getDefaultValue());
		$this->assertSame(20, $configOptions['Backend.paginate.limit']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Backend.paginate.limit']->getType());
		$this->assertNull($configOptions['Backend.paginate.limit']->getTypecast());
		$this->assertNull($configOptions['Backend.paginate.limit']->getValidate());
		$this->assertNull($configOptions['Backend.paginate.limit']->getValues());

		$this->assertArrayHasKey('Frontend.blocklistedUrls', $configOptions);
		$this->assertFalse($configOptions['Frontend.blocklistedUrls']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.blocklistedUrls']->isNullable());
		$this->assertFalse($configOptions['Frontend.blocklistedUrls']->isPersonalizable());
		$this->assertSame(null, $configOptions['Frontend.blocklistedUrls']->getDefaultValue());
		$this->assertSame(null, $configOptions['Frontend.blocklistedUrls']->getPrintableValue());
		$this->assertSame(ConfigOptionType::List, $configOptions['Frontend.blocklistedUrls']->getType());
		$this->assertNull($configOptions['Frontend.blocklistedUrls']->getTypecast());
		$this->assertNull($configOptions['Frontend.blocklistedUrls']->getValidate());
		$this->assertNull($configOptions['Frontend.blocklistedUrls']->getValues());
	}
}
