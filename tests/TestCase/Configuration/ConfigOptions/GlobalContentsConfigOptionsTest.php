<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\GlobalContentsConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * GlobalContentsConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\GlobalContentsConfigOptions
 */
class GlobalContentsConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\GlobalContentsConfigOptions
	 */
	protected GlobalContentsConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new GlobalContentsConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(4, $configOptions);

		$this->assertArrayHasKey('Backend.knownIdentifiers', $configOptions);
		$this->assertFalse($configOptions['Backend.knownIdentifiers']->isLocalizable());
		$this->assertTrue($configOptions['Backend.knownIdentifiers']->isNullable());
		$this->assertFalse($configOptions['Backend.knownIdentifiers']->isPersonalizable());
		$this->assertSame([], $configOptions['Backend.knownIdentifiers']->getDefaultValue());
		$this->assertSame('', $configOptions['Backend.knownIdentifiers']->getPrintableValue());
		$this->assertSame(ConfigOptionType::List, $configOptions['Backend.knownIdentifiers']->getType());
		$this->assertNull($configOptions['Backend.knownIdentifiers']->getTypecast());
		$this->assertNull($configOptions['Backend.knownIdentifiers']->getValidate());
		$this->assertNull($configOptions['Backend.knownIdentifiers']->getValues());

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
			'globalContentTemplateId',
			'columnWidth',
			'columnIndent',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('global_contents::global_content_template_id, global_contents::column_width, global_contents::column_indent', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertEquals([
			'identifier' => 'global_contents::identifier',
			'globalContentTemplateId' => 'global_contents::global_content_template_id',
			'parentId' => 'global_contents::parent_id',
			'title' => 'global_contents::title',
			'titleTag' => 'global_contents::title_tag',
			'subtitle' => 'global_contents::subtitle',
			'subtitleTag' => 'global_contents::subtitle_tag',
			'text' => 'global_contents::text',
			'link' => 'global_contents::link',
			'columnWidth' => 'global_contents::column_width',
			'columnIndent' => 'global_contents::column_indent',
			'columnLast' => 'global_contents::column_last',
			'columnRtl' => 'global_contents::column_rtl',
			'cssClass' => 'global_contents::css_class',
			'css' => 'global_contents::css',
			'data' => 'global_contents::data',
			'formId' => 'global_contents::form_id',
			'surveyId' => 'global_contents::survey_id',
			'systemOrder' => 'global_contents::system_order',
			'active' => 'global_contents::active',
			'createdBy' => 'global_contents::created_by',
			'createdOn' => 'global_contents::created_on',
			'changedBy' => 'global_contents::changed_by',
			'changedOn' => 'global_contents::changed_on',
			'attributes.freeText' => 'Freitext',
			'attributes.teaser' => 'Teaser',
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
