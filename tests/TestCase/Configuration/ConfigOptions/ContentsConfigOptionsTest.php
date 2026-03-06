<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\ContentsConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * ContentsConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\ContentsConfigOptions
 */
class ContentsConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\ContentsConfigOptions
	 */
	protected ContentsConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new ContentsConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(5, $configOptions);

		$this->assertArrayHasKey('Backend.columnSystem.className', $configOptions);
		$this->assertFalse($configOptions['Backend.columnSystem.className']->isLocalizable());
		$this->assertFalse($configOptions['Backend.columnSystem.className']->isNullable());
		$this->assertFalse($configOptions['Backend.columnSystem.className']->isPersonalizable());
		$this->assertSame('\Awyiss\Utility\Content\AwyissColumnSystem', $configOptions['Backend.columnSystem.className']->getDefaultValue());
		$this->assertSame('Awyiss', $configOptions['Backend.columnSystem.className']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.columnSystem.className']->getType());
		$this->assertNull($configOptions['Backend.columnSystem.className']->getTypecast());
		$this->assertNull($configOptions['Backend.columnSystem.className']->getValidate());
		$this->assertSame([
			'\Awyiss\Utility\Content\AwyissColumnSystem' => 'Awyiss',
			'\Awyiss\Utility\Content\BootstrapColumnSystem' => 'Bootstrap',
		], $configOptions['Backend.columnSystem.className']->getValues(true));

		$this->assertArrayHasKey('Backend.columnSystem.maxColumns', $configOptions);
		$this->assertFalse($configOptions['Backend.columnSystem.maxColumns']->isLocalizable());
		$this->assertFalse($configOptions['Backend.columnSystem.maxColumns']->isNullable());
		$this->assertFalse($configOptions['Backend.columnSystem.maxColumns']->isPersonalizable());
		$this->assertSame(5, $configOptions['Backend.columnSystem.maxColumns']->getDefaultValue());
		$this->assertSame(5, $configOptions['Backend.columnSystem.maxColumns']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Backend.columnSystem.maxColumns']->getType());
		$this->assertNull($configOptions['Backend.columnSystem.maxColumns']->getTypecast());
		$this->assertNull($configOptions['Backend.columnSystem.maxColumns']->getValidate());
		$this->assertNull($configOptions['Backend.columnSystem.maxColumns']->getValues());

		$this->assertArrayHasKey('Backend.overview.columnView.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.columnView.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.overview.columnView.enabled']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.columnView.enabled']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.overview.columnView.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.overview.columnView.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.overview.columnView.enabled']->getType());
		$this->assertNull($configOptions['Backend.overview.columnView.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.columnView.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.overview.columnView.enabled']->getValues());

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([
			'contentTemplateId',
			'columnWidth',
			'columnIndent',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('contents::content_template_id, contents::column_width, contents::column_indent', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertEquals([
			'pageId' => 'contents::page_id',
			'contentAreaId' => 'contents::content_area_id',
			'contentTemplateId' => 'contents::content_template_id',
			'parentId' => 'contents::parent_id',
			'title' => 'contents::title',
			'titleTag' => 'contents::title_tag',
			'subtitle' => 'contents::subtitle',
			'subtitleTag' => 'contents::subtitle_tag',
			'text' => 'contents::text',
			'link' => 'contents::link',
			'columnWidth' => 'contents::column_width',
			'columnIndent' => 'contents::column_indent',
			'columnLast' => 'contents::column_last',
			'columnRtl' => 'contents::column_rtl',
			'cssClass' => 'contents::css_class',
			'css' => 'contents::css',
			'duplicateOf' => 'contents::duplicate_of',
			'data' => 'contents::data',
			'formId' => 'contents::form_id',
			'surveyId' => 'contents::survey_id',
			'systemOrder' => 'contents::system_order',
			'active' => 'contents::active',
			'createdBy' => 'contents::created_by',
			'createdOn' => 'contents::created_on',
			'changedBy' => 'contents::changed_by',
			'changedOn' => 'contents::changed_on',
			'attributes.backgroundColor' => 'Hintergrundfarbe',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

		$this->assertArrayHasKey('Backend.publicationData.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.publicationData.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.publicationData.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.publicationData.enabled']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.publicationData.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.publicationData.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.publicationData.enabled']->getType());
		$this->assertNull($configOptions['Backend.publicationData.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.publicationData.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.publicationData.enabled']->getValues());
	}
}
