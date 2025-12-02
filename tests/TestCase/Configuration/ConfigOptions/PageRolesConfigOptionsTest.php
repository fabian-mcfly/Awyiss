<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\PageRolesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * PageRolesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\PageRolesConfigOptions
 */
class PageRolesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\PageRolesConfigOptions
	 */
	protected PageRolesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new PageRolesConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(4, $configOptions);

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
			'include_in_linklist',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('page_roles::identifier, page_roles::include_in_linklist', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'identifier' => 'page_roles::identifier',
			'include_in_linklist' => 'page_roles::include_in_linklist',
			'system_order' => 'page_roles::system_order',
			'active' => 'page_roles::active',
			'created_by' => 'page_roles::created_by',
			'created_on' => 'page_roles::created_on',
			'changed_by' => 'page_roles::changed_by',
			'changed_on' => 'page_roles::changed_on',
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
