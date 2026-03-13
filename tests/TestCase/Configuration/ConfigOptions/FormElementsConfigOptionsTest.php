<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\FormElementsConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * FormElementsConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\FormElementsConfigOptions
 */
class FormElementsConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\FormElementsConfigOptions
	 */
	protected FormElementsConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new FormElementsConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(3, $configOptions);

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
			'identifier',
			'required',
			'columnWidth',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('FormElements::identifier, FormElements::required, FormElements::column_width', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'formId' => 'FormElements::form_id',
			'parentId' => 'FormElements::parent_id',
			'type' => 'FormElements::type',
			'identifier' => 'FormElements::identifier',
			'titleEmail' => 'FormElements::title_email',
			'placeholder' => 'FormElements::placeholder',
			'text' => 'FormElements::text',
			'options' => 'FormElements::options',
			'columnWidth' => 'FormElements::column_width',
			'columnIndent' => 'FormElements::column_indent',
			'columnLast' => 'FormElements::column_last',
			'columnRtl' => 'FormElements::column_rtl',
			'cssClass' => 'FormElements::css_class',
			'required' => 'FormElements::required',
			'systemOrder' => 'FormElements::system_order',
			'active' => 'FormElements::active',
			'createdBy' => 'FormElements::created_by',
			'createdOn' => 'FormElements::created_on',
			'changedBy' => 'FormElements::changed_by',
			'changedOn' => 'FormElements::changed_on',
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
