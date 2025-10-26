<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\WidgetsConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * WidgetsConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\WidgetsConfigOptions
 */
class WidgetsConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\WidgetsConfigOptions
	 */
	protected WidgetsConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new WidgetsConfigOptions();
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
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
			'widget_template_id',
			'column_width',
			'column_indent',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('widgets::widget_template_id, widgets::column_width, widgets::column_indent', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'identifier' => 'widgets::identifier',
			'widget_template_id' => 'widgets::widget_template_id',
			'parent_id' => 'widgets::parent_id',
			'title' => 'widgets::title',
			'title_tag' => 'widgets::title_tag',
			'subtitle' => 'widgets::subtitle',
			'subtitle_tag' => 'widgets::subtitle_tag',
			'text' => 'widgets::text',
			'link' => 'widgets::link',
			'column_width' => 'widgets::column_width',
			'column_indent' => 'widgets::column_indent',
			'column_last' => 'widgets::column_last',
			'column_rtl' => 'widgets::column_rtl',
			'css_class' => 'widgets::css_class',
			'data' => 'widgets::data',
			'form_id' => 'widgets::form_id',
			'survey_id' => 'widgets::survey_id',
			'system_order' => 'widgets::system_order',
			'active' => 'widgets::active',
			'created_by' => 'widgets::created_by',
			'created_on' => 'widgets::created_on',
			'changed_by' => 'widgets::changed_by',
			'changed_on' => 'widgets::changed_on',
			'attributes.free_text' => 'Freitext',
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
