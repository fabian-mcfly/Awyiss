<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;


/**
 * GenericDatatablesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions
 */
class GenericDatatablesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions
	 */
	protected GenericDatatablesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new GenericDatatablesConfigOptions('cars');
	}


	/**
	 * @return void
	 * @see \Awyiss\Configuration\AbstractGenericConfigOptions::getDynamicScope()
	 */
	public function testGetDynamicScope(): void {
		$this->assertSame('Cars', $this->configOptions->getDynamicScope());

		$configOptions = new GenericDatatablesConfigOptions('  - invalid scope !! ');
		$this->assertSame('InvalidScopes', $configOptions->getDynamicScope());

		$configOptions = new GenericDatatablesConfigOptions('');
		$this->assertSame('', $configOptions->getDynamicScope());
	}


	/**
	 * @return void
	 * @see \Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions::initializeConfigOptions()
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(18, $configOptions);

		$this->assertArrayHasKey('Backend.categories.allowAggregation', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.allowAggregation']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.allowAggregation']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.allowAggregation']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.categories.allowAggregation']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.categories.allowAggregation']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.categories.allowAggregation']->getType());
		$this->assertNull($configOptions['Backend.categories.allowAggregation']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.allowAggregation']->getValidate());
		$this->assertNull($configOptions['Backend.categories.allowAggregation']->getValues());

		$this->assertArrayHasKey('Backend.categories.allowUnassigned', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.allowUnassigned']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.allowUnassigned']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.allowUnassigned']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.categories.allowUnassigned']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.categories.allowUnassigned']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.categories.allowUnassigned']->getType());
		$this->assertNull($configOptions['Backend.categories.allowUnassigned']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.allowUnassigned']->getValidate());
		$this->assertNull($configOptions['Backend.categories.allowUnassigned']->getValues());

		$this->assertArrayHasKey('Backend.categories.associationName', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.associationName']->isLocalizable());
		$this->assertTrue($configOptions['Backend.categories.associationName']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.associationName']->isPersonalizable());
		$this->assertNull($configOptions['Backend.categories.associationName']->getDefaultValue());
		$this->assertSame(null, $configOptions['Backend.categories.associationName']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.categories.associationName']->getType());
		$this->assertNull($configOptions['Backend.categories.associationName']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.associationName']->getValidate());
		$this->assertIsCallable($configOptions['Backend.categories.associationName']->getValues());
		$this->assertSame([
			'Attributes' => 'Attributes',
			'AttributesCars' => 'AttributesCars',
			'AttributesContents' => 'AttributesContents',
			'AttributesGlobalContents' => 'AttributesGlobalContents',
			'AttributesNews' => 'AttributesNews',
			'AttributesPages' => 'AttributesPages',
			'Audit' => 'Audit',
			'BackendMenuEntries' => 'BackendMenuEntries',
			'Cars' => 'Cars',
			'Configuration' => 'Configuration',
			'ContentAreas' => 'ContentAreas',
			'ContentTemplateContentAreas' => 'ContentTemplateContentAreas',
			'ContentTemplateElements' => 'ContentTemplateElements',
			'ContentTemplates' => 'ContentTemplates',
			'Contents' => 'Contents',
			'CustomerGroupAccessSettings' => 'CustomerGroupAccessSettings',
			'CustomerGroupAssignments' => 'CustomerGroupAssignments',
			'CustomerGroups' => 'CustomerGroups',
			'CustomerGroupsCustomers' => 'CustomerGroupsCustomers',
			'Customers' => 'Customers',
			'DashboardElements' => 'DashboardElements',
			'Datatables' => 'Datatables',
			'Designs' => 'Designs',
			'DummyUsers' => 'DummyUsers',
			'EmailTemplates' => 'EmailTemplates',
			'Employees' => 'Employees',
			'Employers' => 'Employers',
			'FormConditionalRecipients' => 'FormConditionalRecipients',
			'FormElements' => 'FormElements',
			'FormEntries' => 'FormEntries',
			'Forms' => 'Forms',
			'GlobalContentTemplateElements' => 'GlobalContentTemplateElements',
			'GlobalContentTemplates' => 'GlobalContentTemplates',
			'GlobalContents' => 'GlobalContents',
			'I18n' => 'I18n',
			'Languages' => 'Languages',
			'Locks' => 'Locks',
			'Media' => 'Media',
			'MediaAssignments' => 'MediaAssignments',
			'MediaElementAssignments' => 'MediaElementAssignments',
			'MediaElementSelectors' => 'MediaElementSelectors',
			'MediaElements' => 'MediaElements',
			'MediaFolders' => 'MediaFolders',
			'MediaResizedImages' => 'MediaResizedImages',
			'MediaSelectors' => 'MediaSelectors',
			'MenuEntries' => 'MenuEntries',
			'Menus' => 'Menus',
			'News' => 'News',
			'Newscategories' => 'Newscategories',
			'PageRoles' => 'PageRoles',
			'PageTemplateContentAreas' => 'PageTemplateContentAreas',
			'PageTemplates' => 'PageTemplates',
			'Pages' => 'Pages',
			'Products' => 'Products',
			'PublicationData' => 'PublicationData',
			'SurveyAnswers' => 'SurveyAnswers',
			'SurveyEntries' => 'SurveyEntries',
			'SurveyQuestions' => 'SurveyQuestions',
			'SurveySurveyAnswers' => 'SurveySurveyAnswers',
			'SurveySurveyQuestions' => 'SurveySurveyQuestions',
			'Surveys' => 'Surveys',
			'ThirdPartyConsents' => 'ThirdPartyConsents',
			'UrlHistory' => 'UrlHistory',
			'UrlsNotFound' => 'UrlsNotFound',
			'UserConfiguration' => 'UserConfiguration',
			'UsergroupPermissions' => 'UsergroupPermissions',
			'Usergroups' => 'Usergroups',
			'UsergroupsUsers' => 'UsergroupsUsers',
			'Users' => 'Users',
		], $configOptions['Backend.categories.associationName']->getValues(true));

		$this->assertArrayHasKey('Backend.categories.categories', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.categories']->isLocalizable());
		$this->assertTrue($configOptions['Backend.categories.categories']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.categories']->isPersonalizable());
		$this->assertNull($configOptions['Backend.categories.categories']->getDefaultValue());
		$this->assertSame(null, $configOptions['Backend.categories.categories']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Json, $configOptions['Backend.categories.categories']->getType());
		$this->assertNull($configOptions['Backend.categories.categories']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.categories']->getValidate());
		$this->assertNull($configOptions['Backend.categories.categories']->getValues());

		$this->assertArrayHasKey('Backend.categories.identifier', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.identifier']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.identifier']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.identifier']->isPersonalizable());
		$this->assertSame('category', $configOptions['Backend.categories.identifier']->getDefaultValue());
		$this->assertSame('category', $configOptions['Backend.categories.identifier']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Backend.categories.identifier']->getType());
		$this->assertNull($configOptions['Backend.categories.identifier']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.identifier']->getValidate());
		$this->assertNull($configOptions['Backend.categories.identifier']->getValues());

		$this->assertArrayHasKey('Backend.categories.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.categories.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.categories.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.categories.enabled']->getType());
		$this->assertNull($configOptions['Backend.categories.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.categories.enabled']->getValues());

		$this->assertArrayHasKey('Backend.categories.threaded', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.threaded']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.threaded']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.threaded']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.categories.threaded']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.categories.threaded']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.categories.threaded']->getType());
		$this->assertNull($configOptions['Backend.categories.threaded']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.threaded']->getValidate());
		$this->assertNull($configOptions['Backend.categories.threaded']->getValues());

		$this->assertArrayHasKey('Backend.categories.useDatasource', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.useDatasource']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.useDatasource']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.useDatasource']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.categories.useDatasource']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.categories.useDatasource']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.categories.useDatasource']->getType());
		$this->assertNull($configOptions['Backend.categories.useDatasource']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.useDatasource']->getValidate());
		$this->assertNull($configOptions['Backend.categories.useDatasource']->getValues());

		$this->assertArrayHasKey('Backend.mediaFolders.autoCreate', $configOptions);
		$this->assertFalse($configOptions['Backend.mediaFolders.autoCreate']->isLocalizable());
		$this->assertFalse($configOptions['Backend.mediaFolders.autoCreate']->isNullable());
		$this->assertFalse($configOptions['Backend.mediaFolders.autoCreate']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.mediaFolders.autoCreate']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.mediaFolders.autoCreate']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.mediaFolders.autoCreate']->getType());
		$this->assertNull($configOptions['Backend.mediaFolders.autoCreate']->getTypecast());
		$this->assertNull($configOptions['Backend.mediaFolders.autoCreate']->getValidate());
		$this->assertNull($configOptions['Backend.mediaFolders.autoCreate']->getValues());

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertEquals([
			'parentId' => 'Cars::parent_id',
			'languageShortcode' => 'Cars::language_shortcode',
			'systemOrder' => 'Cars::system_order',
			'active' => 'Cars::active',
			'createdBy' => 'Cars::created_by',
			'createdOn' => 'Cars::created_on',
			'changedBy' => 'Cars::changed_by',
			'changedOn' => 'Cars::changed_on',
			'attributes.freeText' => 'Freitext',
			'attributes.inputList' => 'Input List',
			'attributes.dropdownSelect' => 'Auswahlfeld (Pflichtfeld, übersetzbar)',
			'attributes.inputKeyValueList' => 'Input Key-Value List',
			'attributes.dummyPw' => 'Password',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

		$this->assertArrayHasKey('Backend.nest.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.nest.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.nest.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.nest.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.nest.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.nest.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.nest.enabled']->getType());
		$this->assertNull($configOptions['Backend.nest.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.nest.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.nest.enabled']->getValues());

		$this->assertArrayHasKey('Backend.paginate.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.paginate.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.paginate.enabled']->isNullable());
		$this->assertTrue($configOptions['Backend.paginate.enabled']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.paginate.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.paginate.enabled']->getPrintableValue());
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

		$this->assertArrayHasKey('Backend.systemOrder.direction', $configOptions);
		$this->assertFalse($configOptions['Backend.systemOrder.direction']->isLocalizable());
		$this->assertFalse($configOptions['Backend.systemOrder.direction']->isNullable());
		$this->assertFalse($configOptions['Backend.systemOrder.direction']->isPersonalizable());
		$this->assertSame(SORT_ASC, $configOptions['Backend.systemOrder.direction']->getDefaultValue());
		$this->assertSame('Cars::sort_asc', $configOptions['Backend.systemOrder.direction']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.systemOrder.direction']->getType());
		$this->assertIsCallable($configOptions['Backend.systemOrder.direction']->getTypecast());
		$this->assertNull($configOptions['Backend.systemOrder.direction']->getValidate());
		$this->assertIsArray($configOptions['Backend.systemOrder.direction']->getValues());
		$this->assertSame([
			4 => 'Cars::sort_asc',
			3 => 'Cars::sort_desc',
		], $configOptions['Backend.systemOrder.direction']->getValues());

		$this->assertArrayHasKey('Backend.systemOrder.field', $configOptions);
		$this->assertFalse($configOptions['Backend.systemOrder.field']->isLocalizable());
		$this->assertFalse($configOptions['Backend.systemOrder.field']->isNullable());
		$this->assertFalse($configOptions['Backend.systemOrder.field']->isPersonalizable());
		$this->assertSame('title', $configOptions['Backend.systemOrder.field']->getDefaultValue());
		$this->assertSame('Cars::title', $configOptions['Backend.systemOrder.field']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.systemOrder.field']->getType());
		$this->assertNull($configOptions['Backend.systemOrder.field']->getTypecast());
		$this->assertNull($configOptions['Backend.systemOrder.field']->getValidate());
		$this->assertIsCallable($configOptions['Backend.systemOrder.field']->getValues());
		$this->assertSame([
			'id' => 'Cars::id',
			'parentId' => 'Cars::parent_id',
			'languageShortcode' => 'Cars::language_shortcode',
			'title' => 'Cars::title',
			'systemOrder' => 'Cars::system_order',
			'active' => 'Cars::active',
			'createdBy' => 'Cars::created_by',
			'createdOn' => 'Cars::created_on',
			'changedBy' => 'Cars::changed_by',
			'changedOn' => 'Cars::changed_on',
			'attributes.freeText' => 'Freitext',
			'attributes.inputList' => 'Input List',
			'attributes.dropdownSelect' => 'Auswahlfeld (Pflichtfeld, übersetzbar)',
			'attributes.inputKeyValueList' => 'Input Key-Value List',
			'attributes.dummyPw' => 'Password',
		], $configOptions['Backend.systemOrder.field']->getValues(true));

		$this->assertArrayHasKey('Backend.splitIntoLanguages', $configOptions);
		$this->assertSame(false, $configOptions['Backend.splitIntoLanguages']->isLocalizable());
		$this->assertFalse($configOptions['Backend.splitIntoLanguages']->isNullable());
		$this->assertFalse($configOptions['Backend.splitIntoLanguages']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.splitIntoLanguages']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.splitIntoLanguages']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.splitIntoLanguages']->getType());
		$this->assertNull($configOptions['Backend.splitIntoLanguages']->getTypecast());
		$this->assertIsCallable($configOptions['Backend.splitIntoLanguages']->getValidate());
		$this->assertNull($configOptions['Backend.splitIntoLanguages']->getValues());

		$this->assertArrayHasKey('Backend.translatable', $configOptions);
		$this->assertFalse($configOptions['Backend.translatable']->isLocalizable());
		$this->assertFalse($configOptions['Backend.translatable']->isNullable());
		$this->assertFalse($configOptions['Backend.translatable']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.translatable']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.translatable']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.translatable']->getType());
		$this->assertNull($configOptions['Backend.translatable']->getTypecast());
		$this->assertIsCallable($configOptions['Backend.translatable']->getValidate());
		$this->assertNull($configOptions['Backend.translatable']->getValues());

		$this->assertArrayHasKey('Frontend.mediaFolders.parentFolderId', $configOptions);
		$this->assertTrue($configOptions['Frontend.mediaFolders.parentFolderId']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.mediaFolders.parentFolderId']->isNullable());
		$this->assertFalse($configOptions['Frontend.mediaFolders.parentFolderId']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.mediaFolders.parentFolderId']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.mediaFolders.parentFolderId']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.mediaFolders.parentFolderId']->getType());
		$this->assertNull($configOptions['Frontend.mediaFolders.parentFolderId']->getTypecast());
		$this->assertNull($configOptions['Frontend.mediaFolders.parentFolderId']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.mediaFolders.parentFolderId']->getValues());
		$this->assertSame([], $configOptions['Frontend.mediaFolders.parentFolderId']->getValues(true));
		$this->assertSame([
			2 => 'Testfolder1',
			5 => '- Subfolder1',
			6 => '- - Subfolder2',
		], $configOptions['Frontend.mediaFolders.parentFolderId']->getValues(true, 'de'));
	}


	/**
	 * @return void
	 */
	public function testValidateSplitIntoLanguages(): void {
		$configOption = $this->configOptions->getConfigOption('Backend', 'splitIntoLanguages');

		Configure::write('Awyiss.Cars.Backend.translatable', true);
		$this->assertSame('Configuration::error_option_when_split_into_languages_when_translatable', $configOption->validateConfigValue(true));
		$this->assertTrue($configOption->validateConfigValue(false));

		Configure::write('Awyiss.Cars.Backend.translatable', false);
		$this->assertTrue($configOption->validateConfigValue(true));
		$this->assertTrue($configOption->validateConfigValue(false));
	}


	/**
	 * @return void
	 */
	public function testValidateTranslatable(): void {
		$configOption = $this->configOptions->getConfigOption('Backend', 'translatable');

		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);
		$this->assertSame('Configuration::error_option_not_translatable_when_split_into_languages', $configOption->validateConfigValue(true));
		$this->assertTrue($configOption->validateConfigValue(false));

		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);
		$this->assertTrue($configOption->validateConfigValue(true));
		$this->assertTrue($configOption->validateConfigValue(false));
	}


	/**
	 * @return void
	 */
	public function testTypecastSystemOrder(): void {
		$configOption = $this->configOptions->getConfigOption('Backend', 'systemOrder.direction');

		$this->assertSame(SORT_ASC, $configOption->typecastConfigValue('asc'));
		$this->assertSame(SORT_DESC, $configOption->typecastConfigValue('desc'));
		$this->assertSame(SORT_ASC, $configOption->typecastConfigValue(SORT_ASC));
		$this->assertSame(SORT_DESC, $configOption->typecastConfigValue(SORT_DESC));

		$this->assertNull($configOption->typecastConfigValue('invalid'));
	}
}
