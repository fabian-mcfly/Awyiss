<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\LanguagesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * LanguagesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\LanguagesConfigOptions
 */
class LanguagesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\LanguagesConfigOptions
	 */
	protected LanguagesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new LanguagesConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(3, $configOptions);

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([
			'shortcode',
			'timezone',
			'locale',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('Languages::shortcode, Languages::timezone, Languages::locale', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'realm' => 'Languages::realm',
			'shortcode' => 'Languages::shortcode',
			'timezone' => 'Languages::timezone',
			'locale' => 'Languages::locale',
			'dateFormat' => 'Languages::date_format',
			'timeFormat' => 'Languages::time_format',
			'systemOrder' => 'Languages::system_order',
			'active' => 'Languages::active',
			'createdBy' => 'Languages::created_by',
			'createdOn' => 'Languages::created_on',
			'changedBy' => 'Languages::changed_by',
			'changedOn' => 'Languages::changed_on',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

		$this->assertArrayHasKey('Backend.paginate.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.paginate.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.paginate.enabled']->isNullable());
		$this->assertTrue($configOptions['Backend.paginate.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.paginate.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.paginate.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.paginate.enabled']->getType());
		$this->assertNull($configOptions['Backend.paginate.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.paginate.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.paginate.enabled']->getValues());

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
	}
}
