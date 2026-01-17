<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Table\ConfigurationTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;


/**
 * ConfigurationTable Test Case
 *
 * @see \Awyiss\Model\Table\ConfigurationTable
 */
class ConfigurationTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ConfigurationTable
	 */
	protected ConfigurationTable $configurationTable;


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
		$this->configurationTable = FactoryLocator::get('Table')->get('Configuration');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->configurationTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('configuration', $this->configurationTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(5, $this->configurationTable->associations()->keys());

		// Test Languages association (BelongsTo)
		$this->assertTrue($this->configurationTable->hasAssociation('Languages'));
		$languagesAssociation = $this->configurationTable->getAssociation('Languages');
		$this->assertInstanceOf(BelongsTo::class, $languagesAssociation);
		$this->assertSame(['realm', 'shortcode'], $languagesAssociation->getBindingKey());
		$this->assertSame(['realm', 'language_shortcode'], $languagesAssociation->getForeignKey());
		$this->assertSame('LEFT', $languagesAssociation->getJoinType());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->configurationTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->configurationTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->configurationTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->configurationTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->configurationTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->configurationTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->configurationTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->configurationTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->configurationTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('configuration', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('realm'));
		$this->assertSame('create', $result->field('realm')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('scope'));
		$this->assertTrue($result->hasField('value'));
		$this->assertTrue($result->hasField('languageShortcode'));
		$this->assertTrue($result->hasField('description'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'System',
			'identifier' => 'test_config',
			'value' => 'test_value',
			'languageShortcode' => 'de',
			'description' => 'Test configuration',
		];

		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'scope' => 'System',
			'value' => 'test_value',
		];

		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'realm' => true,
			'scope' => true,
			'identifier' => true,
			'value' => true,
			'languageShortcode' => true,
			'description' => true,
		];

		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('description', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'realm' => str_repeat('a', 21), // exceeds 20 char limit
			'scope' => str_repeat('b', 51), // exceeds 50 char limit
			'identifier' => str_repeat('c', 101), // exceeds 100 char limit
			'value' => str_repeat('d', 256), // exceeds 255 char limit
			'languageShortcode' => 'abc', // exceeds 2 char limit
			'description' => str_repeat('e', 256), // exceeds 255 char limit
		];

		$entity = $this->configurationTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('description', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationRealmInList(): void {
		// Test valid realm
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'test_config',
		];

		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('realm', $errors);

		// Test invalid realm
		$data['realm'] = 'invalid_realm';
		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('realm', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
			'languageShortcode' => '  ', // only whitespace
		];

		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('notBlank', $errors['scope']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('notBlank', $errors['identifier']);

		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('notBlank', $errors['languageShortcode']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::validationDefault()
	 */
	public function testEntityValidationOptionalFields(): void {
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'test_config',
			'scope' => null, // Should be allowed
			'value' => null, // Should be allowed
			'languageShortcode' => null, // Should be allowed
			'description' => null, // Should be allowed
		];

		$entity = $this->configurationTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('scope', $errors);
		$this->assertArrayNotHasKey('value', $errors);
		$this->assertArrayNotHasKey('languageShortcode', $errors);
		$this->assertArrayNotHasKey('description', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueForRealmScopeLanguage(): void {
		$entity = $this->configurationTable->newEntity([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'System',
			'identifier' => 'unique_config',
			'languageShortcode' => 'de',
			'value' => 'test_value',
		]);

		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);

		// Check that the identifier is unique for the combination
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueForRealmScopeLanguageDuplicate(): void {
		// Get existing configuration from seed data
		/** @var \Awyiss\Model\Entity\Configuration $entity */
		$entity = $this->configurationTable->get(68);
		$entity->unset('id');
		$entity->setNew(true);

		$result = $this->configurationTable->checkRules($entity);

		$this->assertFalse($result, 'Second entity with duplicate combination should fail');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUniqueForScope', $errors['identifier']);
		$this->assertEquals('configuration::error_identifier_unique_for_scope', $errors['identifier']['identifierUniqueForScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueForRealmScopeLanguageDifferentLanguage(): void {
		// Get existing configuration from seed data
		/** @var \Awyiss\Model\Entity\Configuration $entity */
		$entity = $this->configurationTable->get(68);
		$entity->setNew(true);
		$entity->unset('id');
		$entity->languageShortcode = 'es'; // Different language

		$result = $this->configurationTable->checkRules($entity);

		$this->assertTrue($result, 'Same identifier should be allowed for different languages');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidRealm(): void {
		// Test with valid realm
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'test_config',
			'scope' => 'System',
			'value' => 'test_value',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesInvalidRealm(): void {
		// Test with invalid realm
		$data = [
			'realm' => 'invalid_realm',
			'identifier' => 'test_config',
			'scope' => 'System',
			'value' => 'test_value',
		];

		$entity = $this->configurationTable->newEntity($data, ['validate' => false]);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('validRealm', $errors['realm']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidScope(): void {
		// Test with valid scope
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'test_config',
			'scope' => 'System',
			'value' => 'test_value',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesInvalidScope(): void {
		// Test with invalid scope
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'test_config',
			'scope' => 'InvalidScope',
			'value' => 'test_value',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('validScope', $errors['scope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesInaccessibleScope(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('BackendIdentity', $user);
		Router::setRequest($request);

		$data = [
			'userId' => 1,
			'scope' => 'forms',
			'identifier' => 'overview.displayedFields',
			'value' => json_encode(['send_email']),
		];

		$entity = $this->configurationTable->newDefaultEntity();
		$this->configurationTable->patchEntity($entity, $data);

		$result = $this->configurationTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('validScope', $errors['scope']);
		$this->assertSame('configuration::error_valid_scope', $errors['scope']['validScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesNonExistingConfigOptionPassesValidation(): void {
		// Non-existing config identifiers should PASS validation
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'non_existing_config_option',
			'scope' => 'System',
			'value' => 'any_value_should_work',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result, 'Non-existing config options should pass validation');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidValue(): void {
		// Test with valid value
		$data = [
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.field',
			'scope' => 'products',
			'value' => 'id',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesInvalidValue(): void {
		// Test with invalid value
		$data = [
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.field',
			'scope' => 'products',
			'value' => 'invalid_value', // Invalid value for this config option will be cast to null
		];

		$entity = $this->configurationTable->newEntity($data, ['validate' => false]);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('validValue', $errors['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesNullableConfigWithValidValue(): void {
		// Test with valid string value for nullable config (orsApiKey)
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'orsApiKey',
			'scope' => 'System',
			'value' => 'valid-api-key-123',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesNullableConfigWithNullValue(): void {
		// Test with null value for nullable config (orsApiKey)
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'orsApiKey',
			'scope' => 'System',
			'value' => null,
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesNonNullableConfigWithNullValue(): void {
		// Test with null value for non-nullable config (htmlCleaning)
		$data = [
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'htmlCleaning',
			'scope' => 'System',
			'value' => null,
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('validValue', $errors['value']);
		$this->assertEquals('configuration::error_option_not_nullable', $errors['value']['validValue']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesNonNullableConfigWithInvalidValue(): void {
		// Test with null value for non-nullable config (htmlCleaning)
		$data = [
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'htmlCleaning',
			'scope' => 'System',
			'value' => 'invalid', // Invalid value for this config option will be cast to null
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('validValue', $errors['value']);
		$this->assertEquals('configuration::error_valid_value', $errors['value']['validValue']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesValidValueLocalizableConfig(): void {
		// Test with valid value for localizable config (titleAppendix)
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'titleAppendix',
			'scope' => 'System',
			'languageShortcode' => 'de',
			'value' => 'Custom Company Name',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesLanguageExistsForLocalizable(): void {
		// Test with existing language for localizable config
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'titleAppendix',
			'scope' => 'System',
			'languageShortcode' => 'de',
			'value' => 'Firma GmbH',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesLanguageDoesNotExistForLocalizable(): void {
		// Test with non-existing language for localizable config
		// The language validation only checks if languageShortcode is provided
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'titleAppendix',
			'scope' => 'System',
			'languageShortcode' => 'zz', // Non-existing language
			'value' => 'Company Name',
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('_existsIn', $errors['languageShortcode']);
		$this->assertEquals('configuration::error_language_exists', $errors['languageShortcode']['_existsIn']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildRules()
	 */
	public function testBuildRulesLanguageProvidedForNonLocalizable(): void {
		// Test with language provided for non-localizable config
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'editor',
			'scope' => 'System',
			'languageShortcode' => 'de', // Providing language for non-localizable config will fail
			'value' => true,
		];

		$entity = $this->configurationTable->newEntity($data);
		$result = $this->configurationTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('validValue', $errors['value']);
		$this->assertEquals('configuration::error_option_not_localizable', $errors['value']['validValue']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Configuration $entity */
		$entity = $this->configurationTable->newDefaultEntity();

		$this->assertInstanceOf(Configuration::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->id);
		$this->assertSame(Awyiss::REALM_FRONTEND, $entity->realm);
		$this->assertSame('system', $entity->scope);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->value);
		$this->assertNull($entity->languageShortcode);
		$this->assertNull($entity->description);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'realm' => Awyiss::REALM_BACKEND,
			'scope' => 'System',
			'identifier' => 'custom_config',
			'value' => 'custom_value',
			'languageShortcode' => 'en',
			'description' => 'Custom configuration',
		];

		/** @var \Awyiss\Model\Entity\Configuration $entity */
		$entity = $this->configurationTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Configuration::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(Awyiss::REALM_BACKEND, $entity->realm);
		$this->assertSame('System', $entity->scope);
		$this->assertSame('custom_config', $entity->identifier);
		$this->assertSame('custom_value', $entity->value);
		$this->assertSame('en', $entity->languageShortcode);
		$this->assertSame('Custom configuration', $entity->description);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildCategories()
	 * @throws \ReflectionException
	 */
	public function testBuildCategories(): void {
		$categories = $this->configurationTable->buildCategories();

		$this->assertIsArray($categories);
		$this->assertSame([
			'employers' => 'Arbeitgeber',
			'attributes' => 'attributes::menu_title',
			'cars' => 'Autos',
			'content_templates' => 'content_templates::menu_title',
			'contents' => 'contents::menu_title',
			'customer_groups' => 'customer_groups::menu_title',
			'customers' => 'customers::menu_title',
			'dashboard_elements' => 'dashboard_elements::menu_title',
			'datatables' => 'datatables::menu_title',
			'email_templates' => 'email_templates::menu_title',
			'form_elements' => 'form_elements::menu_title',
			'forms' => 'forms::menu_title',
			'global_content_templates' => 'global_content_templates::menu_title',
			'global_contents' => 'global_contents::menu_title',
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
			'pages' => 'pages::menu_title',
			'survey_questions' => 'survey_questions::menu_title',
			'surveys' => 'surveys::menu_title',
			'system' => 'system::menu_title',
			'url_history' => 'url_history::menu_title',
			'urls_not_found' => 'urls_not_found::menu_title',
			'usergroups' => 'usergroups::menu_title',
			'users' => 'users::menu_title',
		], $categories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::buildCategories()
	 * @throws \ReflectionException
	 */
	public function testBuildCategoriesWithoutAccess(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('BackendIdentity', $user);
		Router::setRequest($request);

		$categories = $this->configurationTable->buildCategories();
		$this->assertIsArray($categories);
		$this->assertSame([], array_keys($categories));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ConfigurationTable::getScopes()
	 * @throws \ReflectionException
	 */
	public function testGetScopes(): void {
		$scopes = $this->configurationTable->getScopes();

		$this->assertIsArray($scopes);
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
	 * @see \Awyiss\Model\Table\ConfigurationTable::getScopes()
	 * @throws \ReflectionException
	 */
	public function testGetScopesWithoutAccess(): void {
		$user = $this->login(2); // Simulate a logged-in user with ID 2
		$request = Router::getRequest()->withAttribute('BackendIdentity', $user);
		Router::setRequest($request);

		$scopes = $this->configurationTable->getScopes();
		$this->assertIsArray($scopes);
		$this->assertSame([], array_keys($scopes));
	}
}
