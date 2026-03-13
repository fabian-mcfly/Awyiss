<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\PageTemplatesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * PageTemplatesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\PageTemplatesConfigOptions
 */
class PageTemplatesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\PageTemplatesConfigOptions
	 */
	protected PageTemplatesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new PageTemplatesConfigOptions();
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
			'fileName',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('PageTemplates::file_name', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'fileName' => 'PageTemplates::file_name',
			'systemOrder' => 'PageTemplates::system_order',
			'active' => 'PageTemplates::active',
			'createdBy' => 'PageTemplates::created_by',
			'createdOn' => 'PageTemplates::created_on',
			'changedBy' => 'PageTemplates::changed_by',
			'changedOn' => 'PageTemplates::changed_on',
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
