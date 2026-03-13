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
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->dashboardElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('DashboardElements', $result->getI18nDomain());

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
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListFieldsSuccess(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListFieldsEmptySettings(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListFieldsNoFields(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListFieldsNotArray(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListFieldsInvalidField(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidFilterSettingsSuccess(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidFilterSettingsEmptyFilter(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidFilterSettingsNotArray(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidFilterSettingsInvalidColumn(): void {
		$data = [
			'scope' => 'Users',
			'title' => 'Test Dashboard',
			'settings' => [
				'filter' => [
					'nonexistentColumn' => [
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
	 */
	public function testBuildRulesValidFilterSettingsBooleanWithoutOperator(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortSuccess(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortEmptySort(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortNotArray(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortInvalidField(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortMissingField(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortInvalidDirection(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortDescDirection(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testBuildRulesValidListSortMissingDirection(): void {
		$data = [
			'scope' => 'Users',
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
	 */
	public function testInitializeSchema(): void {
		$schema = $this->dashboardElementsTable->getSchema();

		$this->assertEquals('json', $schema->getColumnType('access'));
		$this->assertEquals('json', $schema->getColumnType('settings'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DashboardElementsTable::getAvailableScopes()
	 */
	public function testGetAvailableScopes(): void {
		$scopes = $this->dashboardElementsTable->getAvailableScopes();
		$scopeLabels = array_values($scopes);

		$this->assertEquals([
			'Arbeitgeber',
			'Attributes::headline_overview',
			'Autos',
			'BackendMenuEntries::headline_overview',
			'Configuration::headline_overview',
			'ContentAreas::headline_overview',
			'Contents::headline_overview',
			'ContentTemplates::headline_overview',
			'CustomerGroupAccessSettings::headline_overview',
			'CustomerGroupAssignments::headline_overview',
			'CustomerGroups::headline_overview',
			'Customers::headline_overview',
			'Datatables::headline_overview',
			'Designs::headline_overview',
			'DummyUsers::headline_overview',
			'EmailTemplates::headline_overview',
			'FormElements::headline_overview',
			'FormEntries::headline_overview',
			'Forms::headline_overview',
			'GlobalContents::headline_overview',
			'GlobalContentTemplates::headline_overview',
			'Languages::headline_overview',
			'Media::headline_overview',
			'MediaAssignments::headline_overview',
			'MediaElementAssignments::headline_overview',
			'MediaElements::headline_overview',
			'MediaElementSelectors::headline_overview',
			'MediaFolders::headline_overview',
			'MediaResizedImages::headline_overview',
			'MediaSelectors::headline_overview',
			'MenuEntries::headline_overview',
			'Menus::headline_overview',
			'Mitarbeiter',
			'News',
			'Newskategorie',
			'PageRoles::headline_overview',
			'PageRoles::inactive Produkt',
			'PageTemplates::headline_overview',
			'Seite',
			'SurveyAnswers::headline_overview',
			'SurveyEntries::headline_overview',
			'SurveyQuestions::headline_overview',
			'Surveys::headline_overview',
			'ThirdPartyConsents::headline_overview',
			'UrlHistory::headline_overview',
			'UrlsNotFound::headline_overview',
			'Usergroups::headline_overview',
			'Users::headline_overview',
		], $scopeLabels);
	}
}
