<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * GenericPagesConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions
 */
class GenericPagesConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions
	 */
	protected GenericPagesConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new GenericPagesConfigOptions('news');
	}


	/**
	 * @return void
	 * @see \Awyiss\Configuration\AbstractGenericConfigOptions::getDynamicScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetDynamicScope(): void {
		$this->assertSame('News', $this->configOptions->getDynamicScope());

		$configOptions = new GenericPagesConfigOptions('  - invalid scope !! ');
		$this->assertSame('InvalidScopes', $configOptions->getDynamicScope());

		$configOptions = new GenericPagesConfigOptions('');
		$this->assertSame('', $configOptions->getDynamicScope());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(20, $configOptions);

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
		$this->assertIsArray($configOptions['Backend.categories.associationName']->getValues(true));

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

		$this->assertArrayHasKey('Backend.categories.includeParentCategories', $configOptions);
		$this->assertFalse($configOptions['Backend.categories.includeParentCategories']->isLocalizable());
		$this->assertFalse($configOptions['Backend.categories.includeParentCategories']->isNullable());
		$this->assertFalse($configOptions['Backend.categories.includeParentCategories']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.categories.includeParentCategories']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.categories.includeParentCategories']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.categories.includeParentCategories']->getType());
		$this->assertNull($configOptions['Backend.categories.includeParentCategories']->getTypecast());
		$this->assertNull($configOptions['Backend.categories.includeParentCategories']->getValidate());
		$this->assertNull($configOptions['Backend.categories.includeParentCategories']->getValues());

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

		$this->assertArrayHasKey('Backend.contents.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.contents.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.contents.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.contents.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.contents.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.contents.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.contents.enabled']->getType());
		$this->assertNull($configOptions['Backend.contents.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.contents.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.contents.enabled']->getValues());

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
		$this->assertSame(['page_template_id'], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('system::page_template_id', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertIsArray($configOptions['Backend.overview.displayedFields']->getValues(true));

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

		$this->assertArrayHasKey('Backend.systemOrder.direction', $configOptions);
		$this->assertFalse($configOptions['Backend.systemOrder.direction']->isLocalizable());
		$this->assertFalse($configOptions['Backend.systemOrder.direction']->isNullable());
		$this->assertFalse($configOptions['Backend.systemOrder.direction']->isPersonalizable());
		$this->assertSame(SORT_ASC, $configOptions['Backend.systemOrder.direction']->getDefaultValue());
		$this->assertSame('news::sort_asc', $configOptions['Backend.systemOrder.direction']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.systemOrder.direction']->getType());
		$this->assertIsCallable($configOptions['Backend.systemOrder.direction']->getTypecast());
		$this->assertNull($configOptions['Backend.systemOrder.direction']->getValidate());
		$this->assertIsArray($configOptions['Backend.systemOrder.direction']->getValues());
		$this->assertSame([
			4 => 'news::sort_asc',
			3 => 'news::sort_desc',
		], $configOptions['Backend.systemOrder.direction']->getValues());

		$this->assertArrayHasKey('Backend.systemOrder.field', $configOptions);
		$this->assertFalse($configOptions['Backend.systemOrder.field']->isLocalizable());
		$this->assertFalse($configOptions['Backend.systemOrder.field']->isNullable());
		$this->assertFalse($configOptions['Backend.systemOrder.field']->isPersonalizable());
		$this->assertSame('title', $configOptions['Backend.systemOrder.field']->getDefaultValue());
		$this->assertSame('system::title', $configOptions['Backend.systemOrder.field']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.systemOrder.field']->getType());
		$this->assertNull($configOptions['Backend.systemOrder.field']->getTypecast());
		$this->assertNull($configOptions['Backend.systemOrder.field']->getValidate());
		$this->assertIsCallable($configOptions['Backend.systemOrder.field']->getValues());
		$this->assertSame([
			'id' => 'system::id',
			'page_role_id' => 'system::page_role_id',
			'page_template_id' => 'system::page_template_id',
			'parent_id' => 'system::parent_id',
			'language_shortcode' => 'system::language_shortcode',
			'slug' => 'system::slug',
			'title' => 'system::title',
			'redirect_link' => 'system::redirect_link',
			'meta_title' => 'seo::meta_title',
			'meta_description' => 'seo::meta_description',
			'robots_index' => 'seo::robots_index',
			'robots_follow' => 'seo::robots_follow',
			'duplicate_of' => 'system::duplicate_of',
			'form_id' => 'system::form_id',
			'survey_id' => 'system::survey_id',
			'system_order' => 'system::system_order',
			'active' => 'system::active',
			'parents_active' => 'system::parents_active',
			'created_by' => 'system::created_by',
			'created_on' => 'system::created_on',
			'changed_by' => 'system::changed_by',
			'changed_on' => 'system::changed_on',
			'attributes.date' => 'Datum',
			'attributes.teaser' => 'Teaser',
			'attributes.text' => 'Text',
		], $configOptions['Backend.systemOrder.field']->getValues(true));

		$this->assertArrayHasKey('Frontend.categories.forcedRootPageId', $configOptions);
		$this->assertTrue($configOptions['Frontend.categories.forcedRootPageId']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.categories.forcedRootPageId']->isNullable());
		$this->assertFalse($configOptions['Frontend.categories.forcedRootPageId']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.categories.forcedRootPageId']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.categories.forcedRootPageId']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.categories.forcedRootPageId']->getType());
		$this->assertNull($configOptions['Frontend.categories.forcedRootPageId']->getTypecast());
		$this->assertNull($configOptions['Frontend.categories.forcedRootPageId']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.categories.forcedRootPageId']->getValues());
		$this->assertSame([
			1 => 'Startseite',
			2 => 'Über uns',
			3 => '- Unternehmensgeschichte',
			4 => '- Mission und Vision',
			5 => '- Teamvorstellung',
			6 => '- Zertifikate und Auszeichnungen',
			7 => '- Aktuelles',
			8 => 'Dienstleistungen',
			9 => '- Seefracht',
			10 => '- Luftfracht',
			11 => '- Landtransport',
			12 => '- Lagerung und Logistik',
			13 => '- Zollabwicklung',
			14 => 'Flotte',
			15 => '- Übersicht der Schiffe',
			16 => '- Technische Daten',
			17 => '- Sicherheitsstandards',
			18 => '- Umweltfreundlichkeit',
			19 => 'Kundenbereich',
			20 => '- Anmeldung/Registrierung',
			22 => '- Dokumentenverwaltung',
			23 => '- Rechnungsübersicht',
			24 => 'Karriere',
			25 => '- Offene Stellen',
			26 => '- Ausbildungsprogramme',
			27 => '- Mitarbeiterbenefits',
			28 => '- Bewerbungsprozess',
			29 => 'Kontakt',
			30 => 'Impressum',
			31 => 'Datenschutzrichtlinien',
			32 => 'Fehler 404',
			33 => 'Fehler 410',
		], $configOptions['Frontend.categories.forcedRootPageId']->getValues(true));

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
		$this->assertSame([
			2 => 'Testfolder1',
			5 => '- Subfolder1',
			6 => '- - Subfolder2',
		], $configOptions['Frontend.mediaFolders.parentFolderId']->getValues(true));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
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
