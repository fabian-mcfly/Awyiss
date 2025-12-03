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
		$request = $request->withAttribute('identity', $user);

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

		// MediaAssignments is defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->userConfigurationTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('user_configuration', $result->getI18nDomain());

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
			'scope' => 'test_scope',
			'identifier' => 'test_identifier',
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
		$this->assertSame('user_configuration::error_required', $errors['userId']['_required']);

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('_required', $errors['scope']);
		$this->assertSame('user_configuration::error_required', $errors['scope']['_required']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_required', $errors['identifier']);
		$this->assertSame('user_configuration::error_required', $errors['identifier']['_required']);
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
			'value' => str_repeat('c', 256), // exceeds 255 char limit
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
			'identifier' => 'test_identifier',
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
			'identifier' => 'test_identifier',
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
			'scope' => 'test_scope',
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
			'scope' => 'test_scope',
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
			'scope' => 'forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['send_email']),
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
		$this->assertSame('user_configuration::error_user_id_unchanged', $errors['_general']['userIdUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidScope(): void {
		$data = [
			'userId' => 1,
			'scope' => 'forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['send_email']),
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
			'scope' => 'unknown',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['send_email']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('validScope', $errors['scope']);
		$this->assertSame('user_configuration::error_valid_scope', $errors['scope']['validScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildRules()
	 */
	public function testBuildRulesInaccessibleScope(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('identity', $user);
		Router::setRequest($request);

		$data = [
			'userId' => 1,
			'scope' => 'forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['send_email']),
		];

		$entity = $this->userConfigurationTable->newDefaultEntity();
		$this->userConfigurationTable->patchEntity($entity, $data);

		$result = $this->userConfigurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('validScope', $errors['scope']);
		$this->assertSame('user_configuration::error_valid_scope', $errors['scope']['validScope']);
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
		$this->assertSame('user_configuration::error_identifier_unique_for_scope', $errors['identifier']['identifierUniqueForScope']);
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
			'value' => json_encode(['send_email']),
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
		$this->assertSame('user_configuration::error_config_option_is_personalizable', $errors['_general']['configOptionIsPersonalizable']);
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
			'value' => json_encode(['send_email']),
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
		$this->assertSame('user_configuration::error_valid_value', $errors['value']['validValue']);
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
		$this->assertSame('user_configuration::error_config_owned_by_user', $errors['_general']['configOwnedByUser']);
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
		$this->assertSame('system', $entity->scope);
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
			'scope' => 'custom_scope',
			'identifier' => 'custom_identifier',
			'value' => 'custom_value',
		];

		$entity = $this->userConfigurationTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UserConfiguration::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->userId);
		$this->assertSame('custom_scope', $entity->scope);
		$this->assertSame('custom_identifier', $entity->identifier);
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
			'employers' => 'Arbeitgeber',
			'attributes' => 'attributes::menu_title',
			'cars' => 'Autos',
			'content_templates' => 'content_templates::menu_title',
			'contents' => 'contents::menu_title',
			'dashboard_elements' => 'dashboard_elements::menu_title',
			'datatables' => 'datatables::menu_title',
			'email_templates' => 'email_templates::menu_title',
			'form_elements' => 'form_elements::menu_title',
			'form_entries' => 'form_entries::menu_title',
			'forms' => 'forms::menu_title',
			'languages' => 'languages::menu_title',
			'media_elements' => 'media_elements::menu_title',
			'media_folders' => 'media_folders::menu_title',
			'media_selectors' => 'media_selectors::menu_title',
			'media' => 'media::menu_title',
			'menu_entries' => 'menu_entries::menu_title',
			'menus' => 'menus::menu_title',
			'employees' => 'Mitarbeiter',
			'news' => 'News',
			'newscategories' => 'Newskategorie',
			'products' => 'page_roles::inactive Produkt',
			'page_roles' => 'page_roles::menu_title',
			'page_templates' => 'page_templates::menu_title',
			'queued_jobs' => 'queued_jobs::menu_title',
			'pages' => 'Seite',
			'survey_questions' => 'survey_questions::menu_title',
			'surveys' => 'surveys::menu_title',
			'system' => 'system::menu_title',
			'url_history' => 'url_history::menu_title',
			'urls_not_found' => 'urls_not_found::menu_title',
			'usergroups' => 'usergroups::menu_title',
			'users' => 'users::menu_title',
			'widget_templates' => 'widget_templates::menu_title',
			'widgets' => 'widgets::menu_title',
		], $categories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UserConfigurationTable::buildCategories()
	 * @throws \ReflectionException
	 */
	public function testBuildCategoriesWithoutAccess(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('identity', $user);
		Router::setRequest($request);

		$categories = $this->userConfigurationTable->buildCategories();
		$this->assertIsArray($categories);
		$this->assertSame([
			'contents',
			'system',
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
			'DashboardElements',
			'Datatables',
			'EmailTemplates',
			'Employees',
			'Employers',
			'FormElements',
			'FormEntries',
			'Forms',
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
			'WidgetTemplates',
			'Widgets',
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
		$this->assertContains('user_id', $config['blocklistedColumns']);
	}
}
