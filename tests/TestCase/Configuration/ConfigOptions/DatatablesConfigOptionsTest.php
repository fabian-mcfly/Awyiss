<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\DatatablesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * DatatablesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\DatatablesConfigOptions
 */
class DatatablesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\DatatablesConfigOptions
	 */
	protected DatatablesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new DatatablesConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(3, $configOptions);

		$this->assertArrayHasKey('Backend.autoCreateMenuEntries', $configOptions);
		$this->assertFalse($configOptions['Backend.autoCreateMenuEntries']->isLocalizable());
		$this->assertFalse($configOptions['Backend.autoCreateMenuEntries']->isNullable());
		$this->assertFalse($configOptions['Backend.autoCreateMenuEntries']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.autoCreateMenuEntries']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.autoCreateMenuEntries']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.autoCreateMenuEntries']->getType());
		$this->assertNull($configOptions['Backend.autoCreateMenuEntries']->getTypecast());
		$this->assertNull($configOptions['Backend.autoCreateMenuEntries']->getValidate());
		$this->assertNull($configOptions['Backend.autoCreateMenuEntries']->getValues());

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([
			'identifier',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('datatables::identifier', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'identifier' => 'datatables::identifier',
			'active' => 'datatables::active',
			'createdBy' => 'datatables::created_by',
			'createdOn' => 'datatables::created_on',
			'changedBy' => 'datatables::changed_by',
			'changedOn' => 'datatables::changed_on',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

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
