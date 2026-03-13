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
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(7, $configOptions);

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
			'pageTemplateId',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('Pages::page_template_id', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'pageRoleId' => 'Pages::page_role_id',
			'pageTemplateId' => 'Pages::page_template_id',
			'parentId' => 'Pages::parent_id',
			'languageShortcode' => 'Pages::language_shortcode',
			'redirectLink' => 'Pages::redirect_link',
			'metaTitle' => 'Seo::meta_title',
			'metaDescription' => 'Seo::meta_description',
			'robotsIndex' => 'Seo::robots_index',
			'robotsFollow' => 'Seo::robots_follow',
			'duplicateOf' => 'Pages::duplicate_of',
			'formId' => 'Pages::form_id',
			'surveyId' => 'Pages::survey_id',
			'systemOrder' => 'Pages::system_order',
			'active' => 'Pages::active',
			'parentsActive' => 'Pages::parents_active',
			'createdBy' => 'Pages::created_by',
			'createdOn' => 'Pages::created_on',
			'changedBy' => 'Pages::changed_by',
			'changedOn' => 'Pages::changed_on',
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

		$this->assertArrayHasKey('Backend.surveys.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.surveys.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.surveys.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.surveys.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.surveys.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.surveys.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.surveys.enabled']->getType());
		$this->assertNull($configOptions['Backend.surveys.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.surveys.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.surveys.enabled']->getValues());
	}
}
