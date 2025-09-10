<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Table\LanguagesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * LanguagesTable Test Case
 *
 * @see \Awyiss\Model\Table\LanguagesTable
 */
class LanguagesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\LanguagesTable
	 */
	protected LanguagesTable $languagesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->languagesTable = FactoryLocator::get('Table')->get('Languages');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->languagesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('languages', $this->languagesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(7, $this->languagesTable->associations()->keys());

		// Test Configuration association (HasMany)
		$this->assertTrue($this->languagesTable->hasAssociation('Configuration'));
		$configurationAssociation = $this->languagesTable->getAssociation('Configuration');
		$this->assertInstanceOf(HasMany::class, $configurationAssociation);
		$this->assertTrue($configurationAssociation->getCascadeCallbacks());
		$this->assertTrue($configurationAssociation->getDependent());
		$this->assertSame(['realm', 'shortcode'], $configurationAssociation->getBindingKey());
		$this->assertSame(['realm', 'language_shortcode'], $configurationAssociation->getForeignKey());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->languagesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->languagesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test MenuEntries association (HasMany)
		$this->assertTrue($this->languagesTable->hasAssociation('MenuEntries'));
		$menuEntriesAssociation = $this->languagesTable->getAssociation('MenuEntries');
		$this->assertInstanceOf(HasMany::class, $menuEntriesAssociation);
		$this->assertTrue($menuEntriesAssociation->getCascadeCallbacks());
		$this->assertFalse($menuEntriesAssociation->getDependent());
		$this->assertSame('shortcode', $menuEntriesAssociation->getBindingKey());
		$this->assertSame('language_shortcode', $menuEntriesAssociation->getForeignKey());

		// Test Pages association (HasMany)
		$this->assertTrue($this->languagesTable->hasAssociation('Pages'));
		$pagesAssociation = $this->languagesTable->getAssociation('Pages');
		$this->assertInstanceOf(HasMany::class, $pagesAssociation);
		$this->assertTrue($pagesAssociation->getCascadeCallbacks());
		$this->assertFalse($pagesAssociation->getDependent());
		$this->assertSame('shortcode', $pagesAssociation->getBindingKey());
		$this->assertSame('language_shortcode', $pagesAssociation->getForeignKey());
		$this->assertSame(['all' => ['skipPageRoleCheck' => true]], $pagesAssociation->getFinder());

		// Test user tracking associations
		$this->assertTrue($this->languagesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->languagesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->languagesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->languagesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->languagesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->languagesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->languagesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('languages', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('shortcode'));
		$this->assertSame('create', $result->field('shortcode')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('timezone'));
		$this->assertSame('create', $result->field('timezone')->isPresenceRequired());

		$this->assertTrue($result->hasField('locale'));
		$this->assertSame('create', $result->field('locale')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('realm'));
		$this->assertTrue($result->hasField('dateFormat'));
		$this->assertTrue($result->hasField('timeFormat'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'shortcode' => 'en',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'English',
			'timezone' => 'Europe/Vienna',
			'locale' => 'en_US',
			'dateFormat' => 'Y-m-d',
			'timeFormat' => 'H:i:s',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'realm' => Awyiss::REALM_FRONTEND,
		];

		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('shortcode', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('timezone', $errors);
		$this->assertArrayHasKey('locale', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'shortcode' => true,
			'realm' => true,
			'title' => true,
			'timezone' => true,
			'locale' => true,
			'dateFormat' => true,
			'timeFormat' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('shortcode', $errors);
		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('timezone', $errors);
		$this->assertArrayHasKey('locale', $errors);
		$this->assertArrayHasKey('dateFormat', $errors);
		$this->assertArrayHasKey('timeFormat', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'shortcode' => 'en', // valid 2 char length
			'realm' => str_repeat('a', 21), // exceeds 20 char limit
			'title' => str_repeat('b', 51), // exceeds 50 char limit
			'timezone' => str_repeat('c', 51), // exceeds 50 char limit
			'locale' => str_repeat('d', 6), // exceeds 5 char limit
			'dateFormat' => str_repeat('e', 31), // exceeds 30 char limit
			'timeFormat' => str_repeat('f', 31), // exceeds 30 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->languagesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayNotHasKey('shortcode', $errors); // Should be valid
		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('timezone', $errors);
		$this->assertArrayHasKey('locale', $errors);
		$this->assertArrayHasKey('dateFormat', $errors);
		$this->assertArrayHasKey('timeFormat', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationShortcodeExactLengthValid(): void {
		// Test valid shortcodes
		$codes = ['de', 'en', 'fr', 'es'];
		foreach ($codes as $code) {
			$data = [
				'shortcode' => $code,
				'realm' => Awyiss::REALM_FRONTEND,
				'title' => 'Test',
				'timezone' => 'Europe/Vienna',
				'locale' => 'en_US',
			];

			$entity = $this->languagesTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayNotHasKey('shortcode', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationShortcodeExactLengthInvalid(): void {
		// Test invalid shortcodes
		$codes = ['d', 'deu', 'english', ''];
		foreach ($codes as $code) {
			$data = [
				'shortcode' => $code,
				'realm' => Awyiss::REALM_FRONTEND,
				'title' => 'Test',
				'timezone' => 'Europe/Vienna',
				'locale' => 'en_US',
			];

			$entity = $this->languagesTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayHasKey('shortcode', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationRealmInList(): void {
		// Test valid realm
		$data = [
			'shortcode' => 'en',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'Test',
			'timezone' => 'Europe/Vienna',
			'locale' => 'en_US',
		];

		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('realm', $errors);

		// Test invalid realm
		$data['realm'] = 'invalid_realm';
		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('realm', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'shortcode' => 'en',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => '   ', // only whitespace
			'timezone' => '   ', // only whitespace
			'locale' => '   ', // only whitespace
		];

		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('timezone', $errors);
		$this->assertArrayHasKey('locale', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationDateTimeFormatsAllowEmpty(): void {
		$data = [
			'shortcode' => 'en',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'Test',
			'timezone' => 'Europe/Vienna',
			'locale' => 'en_US',
			'dateFormat' => null, // Should be allowed
			'timeFormat' => null, // Should be allowed
		];

		$entity = $this->languagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('dateFormat', $errors);
		$this->assertArrayNotHasKey('timeFormat', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesShortcodeUniqueForRealm(): void {
		/** @var \Awyiss\Model\Entity\Language $entity */
		$entity = $this->languagesTable->get(1); // Frontend German from seed
		$entity->unset('id'); // Clear ID to create a new entity
		$entity->setNew(true);

		$result = $this->languagesTable->checkRules($entity);
		$this->assertFalse($result, 'Second entity should fail due to duplicate shortcode for realm');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('shortcode', $errors);
		$this->assertArrayHasKey('shortcodeUniqueForRealm', $errors['shortcode']);
		$this->assertSame('languages::error_shortcode_unique_for_realm', $errors['shortcode']['shortcodeUniqueForRealm']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesShortcodeUniqueForRealmDifferentRealms(): void {
		/** @var \Awyiss\Model\Entity\Language $entity */
		$entity = $this->languagesTable->get(3); // Backend English from seed
		$entity->unset('id'); // Clear ID to create a new entity
		$entity->setNew(true);
		$entity->realm = Awyiss::REALM_FRONTEND; // Change realm to frontend

		$result = $this->languagesTable->checkRules($entity);

		$this->assertTrue($result, 'Same shortcode should be allowed for different realms');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidRealm(): void {
		// Test with valid realm
		$data = [
			'shortcode' => 'fr',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'French',
			'timezone' => 'Europe/Paris',
			'locale' => 'fr_FR',
		];

		$entity = $this->languagesTable->newEntity($data);
		$result = $this->languagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidRealm(): void {
		// Test with invalid realm
		$data = [
			'shortcode' => 'fr',
			'realm' => 'invalid_realm',
			'title' => 'French',
			'timezone' => 'Europe/Paris',
			'locale' => 'fr_FR',
		];

		$entity = $this->languagesTable->newEntity($data);
		$result = $this->languagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('realm', $errors);
		$this->assertArrayHasKey('validRealm', $errors['realm']);
		$this->assertSame('languages::error_valid_realm', $errors['realm']['validRealm']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidTimezone(): void {
		// Test with valid timezone
		$data = [
			'shortcode' => 'fr',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'French',
			'timezone' => 'Europe/Paris',
			'locale' => 'fr_FR',
		];

		$entity = $this->languagesTable->newEntity($data);
		$result = $this->languagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidTimezone(): void {
		// Test with invalid timezone
		$data = [
			'shortcode' => 'fr',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'French',
			'timezone' => 'Invalid/Timezone',
			'locale' => 'fr_FR',
		];

		$entity = $this->languagesTable->newEntity($data);
		$result = $this->languagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('timezone', $errors);
		$this->assertArrayHasKey('validTimezone', $errors['timezone']);
		$this->assertSame('languages::error_valid_timezone', $errors['timezone']['validTimezone']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidLocale(): void {
		// Test with valid locale
		$data = [
			'shortcode' => 'fr',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'French',
			'timezone' => 'Europe/Paris',
			'locale' => 'fr_FR',
		];

		$entity = $this->languagesTable->newEntity($data);
		$result = $this->languagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidLocale(): void {
		// Test with invalid locale
		$data = [
			'shortcode' => 'fr',
			'realm' => Awyiss::REALM_FRONTEND,
			'title' => 'French',
			'timezone' => 'Europe/Paris',
			'locale' => 'invalid_locale',
		];

		$entity = $this->languagesTable->newEntity($data);
		$result = $this->languagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('locale', $errors);
		$this->assertArrayHasKey('validLocale', $errors['locale']);
		$this->assertSame('languages::error_valid_locale', $errors['locale']['validLocale']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNotLastLanguageInRealmCanDelete(): void {
		// Test that non-last language can be deleted (assuming there are multiple frontend languages)
		/** @var \Awyiss\Model\Entity\Language $entity */
		$entity = $this->languagesTable->get(3); // Backend English from seed

		$result = $this->languagesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result, 'Non-last language should be deletable');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function _testBuildRulesNotLastLanguageInRealmCannotDeleteLast(): void {
		// Get the only backend language (assuming there's only one)
		/** @var \Awyiss\Model\Entity\Language $entity */
		$entity = $this->languagesTable->get(101); // Dummy xy from seed

		$result = $this->languagesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($result, 'Last language in realm should not be deletable');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('notLastLanguageInRealm', $errors['_general']);
		$this->assertSame('languages::error_not_last_language_in_realm', $errors['_general']['notLastLanguageInRealm']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Language $entity */
		$entity = $this->languagesTable->newDefaultEntity();

		$this->assertInstanceOf(Language::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertSame(Awyiss::REALM_FRONTEND, $entity->realm);
		$this->assertNull($entity->shortcode);
		$this->assertNull($entity->title);
		$this->assertNull($entity->timezone);
		$this->assertNull($entity->locale);
		$this->assertNull($entity->dateFormat);
		$this->assertNull($entity->timeFormat);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'realm' => Awyiss::REALM_BACKEND,
			'shortcode' => 'es',
			'title' => 'Spanish',
			'timezone' => 'Europe/Madrid',
			'locale' => 'es_ES',
			'dateFormat' => 'd/m/Y',
			'timeFormat' => 'H:i',
			'systemOrder' => 5,
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\Language $entity */
		$entity = $this->languagesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Language::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(Awyiss::REALM_BACKEND, $entity->realm);
		$this->assertSame('es', $entity->shortcode);
		$this->assertSame('Spanish', $entity->title);
		$this->assertSame('Europe/Madrid', $entity->timezone);
		$this->assertSame('es_ES', $entity->locale);
		$this->assertSame('d/m/Y', $entity->dateFormat);
		$this->assertSame('H:i', $entity->timeFormat);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted); // Should remain default
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::$systemOrder
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->languagesTable->hasBehavior('SystemOrder'));

		$config = $this->languagesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['realm'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LanguagesTable::$translate
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertFalse($this->languagesTable->hasBehavior('Translate'));

		/** @var \Awyiss\Model\Entity\Language $language */
		$language = $this->languagesTable->get(1);
		$this->languagesTable->addTranslateBehavior($language);

		$this->assertTrue($this->languagesTable->hasBehavior('Translate'));

		$config = $this->languagesTable->getBehavior('Translate')->getConfig();

		$this->assertSame(['title'], $config['fields']);
	}
}
