<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Table\DashboardElementsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * DashboardElementsTable Test Case
 *
 * @see \Awyiss\Model\Table\DashboardElementsTable
 */
class DashboardElementsTableTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\Model\Table\DashboardElementsTable
	 */
	protected DashboardElementsTable $dashboardElementsTable;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->dashboardElementsTable = FactoryLocator::get('Table')->get('DashboardElements');

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('xy', 'yx');

		$request = new ServerRequest([
			'url' => '/backend/xy/some-controller/the-action',
			'params' => [
				'lang' => 'xy',
				'controller' => 'SomeController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->loadRoutes();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->dashboardElementsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('dashboard_elements', $this->dashboardElementsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(6, $this->dashboardElementsTable->associations()->keys());

		// 'MediaAssignments' must exist
		$this->assertTrue($this->dashboardElementsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->dashboardElementsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must exist
		$this->assertTrue($this->dashboardElementsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->dashboardElementsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must exist
		$this->assertTrue($this->dashboardElementsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->dashboardElementsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must exist
		$this->assertTrue($this->dashboardElementsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->dashboardElementsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->dashboardElementsTable->hasAssociation('DashboardElements_title_translation'));
		$titleTranslationAssociation = $this->dashboardElementsTable->getAssociation('DashboardElements_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->dashboardElementsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->dashboardElementsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->dashboardElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('dashboard_elements', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('scope'));
		$this->assertSame('create', $result->field('scope')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard Element',
			'access' => ['admin'],
			'settings' => ['limit' => 10],
			'systemOrder' => 1,
			'active' => true,
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'scope' => true,
			'title' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 101), // exceeds 100 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'scope' => '   ', // only whitespace
			'title' => '   ', // only whitespace
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListFieldsSuccess(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'fields' => ['id', 'username', 'email'],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListFieldsEmptySettings(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [],
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListFieldsNoFields(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'other' => 'value',
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListFieldsNotArray(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'fields' => 'not_an_array',
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listFields', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListFieldsInvalidField(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'fields' => ['id', 'nonexistent_field', 'username'],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listFields', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFilterSettingsSuccess(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'filter' => [
					'username' => [
						'operator' => '=',
						'value' => 'test',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFilterSettingsEmptyFilter(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'other' => 'value',
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFilterSettingsNotArray(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'filter' => 'not_an_array',
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('filterSettings', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFilterSettingsInvalidColumn(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'filter' => [
					'nonexistent_column' => [
						'operator' => '=',
						'value' => 'test',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('filterSettings', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFilterSettingsBooleanWithoutOperator(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'filter' => [
					'active' => [
						'value' => true,
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortSuccess(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => [
					[
						'field' => 'username',
						'direction' => 'asc',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortEmptySort(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'other' => 'value',
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortNotArray(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => 'not_an_array',
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listSort', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortInvalidField(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => [
					[
						'field' => 'nonexistent_field',
						'direction' => 'asc',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listSort', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortMissingField(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => [
					[
						'direction' => 'asc',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listSort', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortInvalidDirection(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => [
					[
						'field' => 'username',
						'direction' => 'invalid',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listSort', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortDescDirection(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => [
					[
						'field' => 'username',
						'direction' => 'DESC',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidListSortMissingDirection(): void {
		$data = [
			'scope' => 'users',
			'title' => 'Test Dashboard',
			'settings' => [
				'sort' => [
					[
						'field' => 'username',
					],
				],
			],
		];

		$entity = $this->dashboardElementsTable->newEntity($data, ['guard' => false]);
		$result = $this->dashboardElementsTable->checkRules($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('listSort', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchema(): void {
		$schema = $this->dashboardElementsTable->getSchema();

		$this->assertEquals('json', $schema->getColumnType('access'));
		$this->assertEquals('json', $schema->getColumnType('settings'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::getAvailableScopes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAvailableScopes(): void {
		$scopes = $this->dashboardElementsTable->getAvailableScopes();
		$scopeLabels = array_values($scopes);

		$this->assertEquals([
			'Arbeitgeber',
			'attributes::headline_overview',
			'Autos',
			'backend_menu_entries::headline_overview',
			'configuration::headline_overview',
			'content_areas::headline_overview',
			'content_templates::headline_overview',
			'contents::headline_overview',
			'datatables::headline_overview',
			'designs::headline_overview',
			'dummy_users::headline_overview',
			'email_templates::headline_overview',
			'form_elements::headline_overview',
			'form_entries::headline_overview',
			'forms::headline_overview',
			'languages::headline_overview',
			'media_assignments::headline_overview',
			'media_element_assignments::headline_overview',
			'media_element_selectors::headline_overview',
			'media_elements::headline_overview',
			'media_folders::headline_overview',
			'media_resized_images::headline_overview',
			'media_selectors::headline_overview',
			'media::headline_overview',
			'menu_entries::headline_overview',
			'menus::headline_overview',
			'Mitarbeiter',
			'News',
			'Newskategorie',
			'page_roles::headline_overview',
			'page_roles::inactive Produkt',
			'page_templates::headline_overview',
			'Seite',
			'survey_answers::headline_overview',
			'survey_entries::headline_overview',
			'survey_questions::headline_overview',
			'surveys::headline_overview',
			'third_party_consents::headline_overview',
			'url_history::headline_overview',
			'urls_not_found::headline_overview',
			'usergroups::headline_overview',
			'users::headline_overview',
			'widget_templates::headline_overview',
			'widgets::headline_overview',
		], $scopeLabels);
	}
}
