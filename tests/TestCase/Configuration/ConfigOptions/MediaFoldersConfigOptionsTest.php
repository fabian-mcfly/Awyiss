<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\MediaFoldersConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * MediaFoldersConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\MediaFoldersConfigOptions
 */
class MediaFoldersConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\MediaFoldersConfigOptions
	 */
	protected MediaFoldersConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new MediaFoldersConfigOptions();
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
			'path',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('MediaFolders::path', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'parentId' => 'MediaFolders::parent_id',
			'languageShortcode' => 'MediaFolders::language_shortcode',
			'path' => 'MediaFolders::path',
			'hidden' => 'MediaFolders::hidden',
			'systemOrder' => 'MediaFolders::system_order',
			'active' => 'MediaFolders::active',
			'parentsActive' => 'MediaFolders::parents_active',
			'createdBy' => 'MediaFolders::created_by',
			'createdOn' => 'MediaFolders::created_on',
			'changedBy' => 'MediaFolders::changed_by',
			'changedOn' => 'MediaFolders::changed_on',
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
