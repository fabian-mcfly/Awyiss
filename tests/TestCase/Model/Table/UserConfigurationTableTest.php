<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Model\Table\UserConfigurationTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * UserConfigurationTable Test Case
 *
 * @see \Awyiss\Model\Table\UserConfigurationTable
 */
class UserConfigurationTableTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\Model\Table\UserConfigurationTable
	 */
	protected UserConfigurationTable $userConfigurationTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'dashboard',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$user = $this->login(1); // Simulate a logged-in user with ID 1
		$request = $request->withAttribute('BackendIdentity', $user);

		Router::setRequest($request);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->userConfigurationTable = FactoryLocator::get('Table')->get('UserConfiguration');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->userConfigurationTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('user_configuration', $this->userConfigurationTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(2, $this->userConfigurationTable->associations()->keys());

		$this->assertTrue($this->userConfigurationTable->hasAssociation('Users'));
		$usersAssociation = $this->userConfigurationTable->getAssociation('Users');
		$this->assertInstanceOf(BelongsTo::class, $usersAssociation);
		$this->assertSame('LEFT', $usersAssociation->getJoinType());

		// MediaAssignments is also defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->userConfigurationTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('UserConfiguration', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('userId'));
		$this->assertSame('create', $result->field('userId')->isPresenceRequired());

		$this->assertTrue($result->hasField('scope'));
		$this->assertSame('create', $result->field('scope')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('value'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'userId' => 1,
			'scope' => 'TestScope',
			'identifier' => 'testIdentifier',
			'value' => 'test_value',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'value' => 'test_value',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('userId', $errors);
		$this->assertArrayHasKey('_required', $errors['userId']);
		$this->assertSame('UserConfiguration::error_required', $errors['userId']['_required']);

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('_required', $errors['scope']);
		$this->assertSame('UserConfiguration::error_required', $errors['scope']['_required']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_required', $errors['identifier']);
		$this->assertSame('UserConfiguration::error_required', $errors['identifier']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'userId' => 'not_an_integer',
			'scope' => true,
			'identifier' => true,
			'value' => true,
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('userId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('value', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'userId' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
			'value' => str_repeat('c', 1025), // exceeds 1024 char limit
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('userId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('value', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationEmptyScope(): void {
		$data = [
			'userId' => 1,
			'scope' => '',
			'identifier' => 'testIdentifier',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationBlankScope(): void {
		$data = [
			'userId' => 1,
			'scope' => '   ', // Only whitespace
			'identifier' => 'testIdentifier',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('notBlank', $errors['scope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationEmptyIdentifier(): void {
		$data = [
			'userId' => 1,
			'scope' => 'TestScope',
			'identifier' => '',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testEntityValidationBlankIdentifier(): void {
		$data = [
			'userId' => 1,
			'scope' => 'TestScope',
			'identifier' => '   ', // Only whitespace
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('notBlank', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesUserIdUnchanged(): void {
		// Create a new entity (userId should be allowed to be set)
		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['sendEmail']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesUserIdChanged(): void {
		$entity = $this->userConfigurationTable->get(2);

		$entity->set('userId', 2);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('userIdUnchanged', $errors['_general']);
		$this->assertSame('UserConfiguration::error_user_id_unchanged', $errors['_general']['userIdUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidScope(): void {
		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['sendEmail']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesInvalidScope(): void {
		$data = [
			'userId' => 1,
			'scope' => 'Unknown',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['sendEmail']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('validScope', $errors['scope']);
		$this->assertSame('UserConfiguration::error_valid_scope', $errors['scope']['validScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesInaccessibleScope(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('BackendIdentity', $user);
		Router::setRequest($request);

		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['sendEmail']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('validScope', $errors['scope']);
		$this->assertSame('UserConfiguration::error_valid_scope', $errors['scope']['validScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesUniqueIdentifierForScope(): void {
		$entity = $this->userConfigurationTable->get(2);

		$result = $this->userConfigurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesDuplicateIdentifierForScope(): void {
		$entity = $this->userConfigurationTable->get(2);

		$entity->unset('id');
		$entity->setNew(true);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUniqueForScope', $errors['identifier']);
		$this->assertSame('UserConfiguration::error_identifier_unique_for_scope', $errors['identifier']['identifierUniqueForScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesConfigOptionIsPersonalizable(): void {
		// Test with a valid personalizable config option
		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['sendEmail']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesConfigOptionNotPersonalizable(): void {
		// Test with a non-personalizable config option
		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'publicationData.enabled',
			'value' => false,
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('configOptionIsPersonalizable', $errors['_general']);
		$this->assertSame('UserConfiguration::error_config_option_is_personalizable', $errors['_general']['configOptionIsPersonalizable']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidValue(): void {
		// Test with a valid value for a known config option
		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['sendEmail']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesInvalidValue(): void {
		// Test with an invalid value for a known config option
		$data = [
			'userId' => 1,
			'scope' => 'Forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['unknown_column']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('validValue', $errors['value']);
		$this->assertSame('UserConfiguration::error_valid_value', $errors['value']['validValue']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesDeleteRuleValid(): void {
		$entity = $this->userConfigurationTable->get(65);

		$result = $this->userConfigurationTable->checkRules($entity, RulesChecker::DELETE);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesDeleteRuleInvalid(): void {
		$entity = $this->userConfigurationTable->get(66);

		$result = $this->userConfigurationTable->checkRules($entity, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('configOwnedByUser', $errors['_general']);
		$this->assertSame('UserConfiguration::error_config_owned_by_user', $errors['_general']['configOwnedByUser']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\UserConfiguration $entity */
		$entity = $this->userConfigurationTable->newDefaultEntity();

		$this->assertInstanceOf(UserConfiguration::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->userId);
		$this->assertSame('System', $entity->scope);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'userId' => 2,
			'scope' => 'CustomScope',
			'identifier' => 'customIdentifier',
			'value' => 'custom_value',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UserConfiguration::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->userId);
		$this->assertSame('CustomScope', $entity->scope);
		$this->assertSame('customIdentifier', $entity->identifier);
		$this->assertSame('custom_value', $entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildCategories()
	 * @throws \ReflectionException
	 */
	public function testBuildCategoriesForUser1(): void {
		$categories = $this->userConfigurationTable->buildCategories();

		$this->assertIsArray($categories);
		$this->assertSame([
			'Employers' => 'Arbeitgeber',
			'Attributes' => 'Attributes::menu_title',
			'Cars' => 'Autos',
			'Contents' => 'Contents::menu_title',
			'ContentTemplates' => 'ContentTemplates::menu_title',
			'CustomerGroups' => 'CustomerGroups::menu_title',
			'Customers' => 'Customers::menu_title',
			'DashboardElements' => 'DashboardElements::menu_title',
			'Datatables' => 'Datatables::menu_title',
			'EmailTemplates' => 'EmailTemplates::menu_title',
			'FormElements' => 'FormElements::menu_title',
			'FormEntries' => 'FormEntries::menu_title',
			'Forms' => 'Forms::menu_title',
			'GlobalContents' => 'GlobalContents::menu_title',
			'GlobalContentTemplates' => 'GlobalContentTemplates::menu_title',
			'Languages' => 'Languages::menu_title',
			'Media' => 'Media::menu_title',
			'MediaElements' => 'MediaElements::menu_title',
			'MediaFolders' => 'MediaFolders::menu_title',
			'MediaSelectors' => 'MediaSelectors::menu_title',
			'MenuEntries' => 'MenuEntries::menu_title',
			'Menus' => 'Menus::menu_title',
			'Employees' => 'Mitarbeiter',
			'News' => 'News',
			'Newscategories' => 'Newskategorie',
			'Products' => 'PageRoles::inactive Produkt',
			'PageRoles' => 'PageRoles::menu_title',
			'PageTemplates' => 'PageTemplates::menu_title',
			'QueuedJobs' => 'QueuedJobs::menu_title',
			'Pages' => 'Seite',
			'SurveyQuestions' => 'SurveyQuestions::menu_title',
			'Surveys' => 'Surveys::menu_title',
			'System' => 'System::menu_title',
			'UrlHistory' => 'UrlHistory::menu_title',
			'UrlsNotFound' => 'UrlsNotFound::menu_title',
			'Usergroups' => 'Usergroups::menu_title',
			'Users' => 'Users::menu_title',
		], $categories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildCategories()
	 * @throws \ReflectionException
	 */
	public function testBuildCategoriesWithoutAccess(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('BackendIdentity', $user);
		Router::setRequest($request);

		$categories = $this->userConfigurationTable->buildCategories();
		$this->assertIsArray($categories);
		$this->assertSame([
			'Contents',
			'System',
		], array_keys($categories));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::getScopes()
	 * @throws \ReflectionException
	 */
	public function testGetScopes(): void {
		$scopes = $this->userConfigurationTable->getScopes();

		$this->assertIsArray($scopes);
		$this->assertNotEmpty($scopes);

		$this->assertSame([
			'Attributes',
			'Cars',
			'ContentTemplates',
			'Contents',
			'CustomerGroups',
			'Customers',
			'DashboardElements',
			'Datatables',
			'EmailTemplates',
			'Employees',
			'Employers',
			'FormElements',
			'FormEntries',
			'Forms',
			'GlobalContentTemplates',
			'GlobalContents',
			'Languages',
			'Media',
			'MediaElements',
			'MediaFolders',
			'MediaSelectors',
			'MenuEntries',
			'Menus',
			'News',
			'Newscategories',
			'PageRoles',
			'PageTemplates',
			'Pages',
			'Products',
			'QueuedJobs',
			'SurveyQuestions',
			'Surveys',
			'System',
			'UrlHistory',
			'UrlsNotFound',
			'Usergroups',
			'Users',
		], array_keys($scopes));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->userConfigurationTable->hasBehavior('Categories'));

		$config = $this->userConfigurationTable->getBehavior('Categories')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertFalse($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertSame('scope', $config['identifier']);
		$this->assertFalse($config['useDatasource']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::$search
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->userConfigurationTable->hasBehavior('Search'));

		$config = $this->userConfigurationTable->getBehavior('Search')->getConfig();

		$this->assertIsArray($config['blocklistedColumns']);
		$this->assertContains('userId', $config['blocklistedColumns']);
	}
}
