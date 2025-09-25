<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\PagesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * PagesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\PagesConfigOptions
 */
class PagesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\PagesConfigOptions
	 */
	protected PagesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new PagesConfigOptions();
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(6, $configOptions);

		$this->assertArrayHasKey('Backend.contents.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.contents.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.contents.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.contents.enabled']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.contents.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.contents.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.contents.enabled']->getType());
		$this->assertNull($configOptions['Backend.contents.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.contents.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.contents.enabled']->getValues());

		$this->assertArrayHasKey('Backend.forms.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.forms.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.forms.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.forms.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.forms.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.forms.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.forms.enabled']->getType());
		$this->assertNull($configOptions['Backend.forms.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.forms.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.forms.enabled']->getValues());

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([
			'page_template_id',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('pages::page_template_id', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'page_role_id' => 'pages::page_role_id',
			'page_template_id' => 'pages::page_template_id',
			'parent_id' => 'pages::parent_id',
			'language_shortcode' => 'pages::language_shortcode',
			'redirect_link' => 'pages::redirect_link',
			'meta_title' => 'seo::meta_title',
			'meta_description' => 'seo::meta_description',
			'robots_index' => 'seo::robots_index',
			'robots_follow' => 'seo::robots_follow',
			'duplicate_of' => 'pages::duplicate_of',
			'form_id' => 'pages::form_id',
			'survey_id' => 'pages::survey_id',
			'system_order' => 'pages::system_order',
			'active' => 'pages::active',
			'parents_active' => 'pages::parents_active',
			'created_by' => 'pages::created_by',
			'created_on' => 'pages::created_on',
			'changed_by' => 'pages::changed_by',
			'changed_on' => 'pages::changed_on',
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
