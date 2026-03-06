<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Model\Table\EmailTemplatesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * EmailTemplatesTable Test Case
 *
 * @see \Awyiss\Model\Table\EmailTemplatesTable
 */
class EmailTemplatesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\EmailTemplatesTable
	 */
	protected EmailTemplatesTable $emailTemplatesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->emailTemplatesTable = FactoryLocator::get('Table')->get('EmailTemplates');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->emailTemplatesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('email_templates', $this->emailTemplatesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(10, $this->emailTemplatesTable->associations()->keys());

		// Test FormEmails association (HasMany to Forms)
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('FormEmails'));
		$formEmailsAssociation = $this->emailTemplatesTable->getAssociation('FormEmails');
		$this->assertInstanceOf(HasMany::class, $formEmailsAssociation);
		$this->assertSame('Forms', $formEmailsAssociation->getClassName());
		$this->assertSame('emailTemplateId', $formEmailsAssociation->getForeignKey());

		// Test FormConfirmationEmails association (HasMany to Forms)
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('FormConfirmationEmails'));
		$formConfirmationEmailsAssociation = $this->emailTemplatesTable->getAssociation('FormConfirmationEmails');
		$this->assertInstanceOf(HasMany::class, $formConfirmationEmailsAssociation);
		$this->assertSame('Forms', $formConfirmationEmailsAssociation->getClassName());
		$this->assertSame('confirmationEmailTemplateId', $formConfirmationEmailsAssociation->getForeignKey());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->emailTemplatesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->emailTemplatesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->emailTemplatesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->emailTemplatesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->emailTemplatesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->emailTemplatesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'EmailTemplates_title_translation' must also exist
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('EmailTemplates_title_translation'));
		$titleTranslationAssociation = $this->emailTemplatesTable->getAssociation('EmailTemplates_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'EmailTemplates_textHtml_translation' must also exist
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('EmailTemplates_textHtml_translation'));
		$textHtmlTranslationAssociation = $this->emailTemplatesTable->getAssociation('EmailTemplates_textHtml_translation');
		$this->assertInstanceOf(HasOne::class, $textHtmlTranslationAssociation);
		$this->assertFalse($textHtmlTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textHtmlTranslationAssociation->getDependent());

		// 'EmailTemplates_textPlain_translation' must also exist
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('EmailTemplates_textPlain_translation'));
		$textPlainTranslationAssociation = $this->emailTemplatesTable->getAssociation('EmailTemplates_textPlain_translation');
		$this->assertInstanceOf(HasOne::class, $textPlainTranslationAssociation);
		$this->assertFalse($textPlainTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textPlainTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->emailTemplatesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->emailTemplatesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::findWithUsages()
	 */
	public function testFindWithUsages(): void {
		$query = $this->emailTemplatesTable->find('all');
		$result = $this->emailTemplatesTable->findWithUsages($query);

		$this->assertSame($query, $result);

		// Execute query to test the actual functionality
		$emailTemplates = $result->toArray();

		$this->assertNotEmpty($emailTemplates);

		foreach ($emailTemplates as $emailTemplate) {
			$this->assertInstanceOf(EmailTemplate::class, $emailTemplate);
			$this->assertIsInt($emailTemplate->usedForEmails);
			$this->assertIsInt($emailTemplate->usedForConfirmationEmails);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::getAvailableLayouts()
	 */
	public function testGetAvailableLayouts(): void {
		$layouts = $this->emailTemplatesTable->getAvailableLayouts();

		$this->assertIsArray($layouts);

		// Check that all values end with .twig
		foreach ($layouts as $key => $value) {
			$this->assertIsString($key);
			$this->assertIsString($value);
			$this->assertSame($key, $value);
			$this->assertStringEndsWith('.twig', $value);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->emailTemplatesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('EmailTemplates', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('fileName'));
		$this->assertSame('create', $result->field('fileName')->isPresenceRequired());

		$this->assertTrue($result->hasField('layout'));
		$this->assertSame('create', $result->field('layout')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('textHtml'));
		$this->assertTrue($result->hasField('textPlain'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Email Template',
			'textHtml' => '<p>Test HTML content</p>',
			'textPlain' => 'Test plain text content',
			'fileName' => 'test_template',
			'layout' => 'default.twig',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'textHtml' => '<p>Test HTML content</p>',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('layout', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'textHtml' => true,
			'textPlain' => true,
			'fileName' => true,
			'layout' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('textHtml', $errors);
		$this->assertArrayHasKey('textPlain', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('layout', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'textHtml' => str_repeat('b', 65536), // exceeds 65535 byte limit
			'textPlain' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'fileName' => str_repeat('d', 101), // exceeds 100 char limit
			'layout' => str_repeat('e', 101), // exceeds 100 char limit
		];

		$entity = $this->emailTemplatesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('textHtml', $errors);
		$this->assertArrayHasKey('textPlain', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('layout', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'fileName' => '   ', // only whitespace
			'layout' => '   ', // only whitespace
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('layout', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationNotEmptyString(): void {
		$data = [
			'title' => '',
			'fileName' => '',
			'layout' => '',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('layout', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFileNameAscii(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'tëst_fîlé_nämé', // non-ASCII characters
			'layout' => 'default.twig',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('ascii', $errors['fileName']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::validationDefault()
	 */
	public function testEntityValidationAllowEmptyTextFields(): void {
		$data = [
			'title' => 'Test Template',
			'textHtml' => null, // textHtml allows empty
			'textPlain' => null, // textPlain allows empty
			'fileName' => 'test_template',
			'layout' => 'default.twig',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('textHtml', $errors);
		$this->assertArrayNotHasKey('textPlain', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::buildRules()
	 */
	public function testBuildRulesUniqueFileName(): void {
		// Test with existing fileName (should fail)
		$data = [
			'title' => 'Test Template',
			'fileName' => 'dummy', // This fileName already exists in fixtures
			'layout' => 'default.twig',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$result = $this->emailTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('fileNameUnique', $errors['fileName']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::buildRules()
	 */
	public function testBuildRulesValidLayout(): void {
		$availableLayouts = $this->emailTemplatesTable->getAvailableLayouts();
		$validLayout = array_key_first($availableLayouts);

		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_template_unique',
			'layout' => $validLayout,
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$result = $this->emailTemplatesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::buildRules()
	 */
	public function testBuildRulesInvalidLayout(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_template_unique_2',
			'layout' => 'non_existing_layout.twig',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$result = $this->emailTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('layout', $errors);
		$this->assertArrayHasKey('validLayout', $errors['layout']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::buildRules()
	 */
	public function testBuildDeleteRulesNoLinkedFormEmailsValid(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_template_unique',
			'layout' => 'default.twig',
		];

		$entity = $this->emailTemplatesTable->newEntity($data);
		$entity->set('id', 9999);
		$entity->setNew(false);

		$result = $this->emailTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::buildRules()
	 */
	public function testBuildDeleteRulesNoLinkedFormEmailsInvalid(): void {
		/** @var \Awyiss\Model\Entity\EmailTemplate $emailTemplate */
		$emailTemplate = $this->emailTemplatesTable->get(1); // Email template that has linked forms

		$result = $this->emailTemplatesTable->checkRules($emailTemplate, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $emailTemplate->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedFormEmails', $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::buildRules()
	 */
	public function testBuildDeleteRulesNoLinkedFormConfirmationEmails(): void {
		/** @var \Awyiss\Model\Entity\EmailTemplate $emailTemplate */
		$emailTemplate = $this->emailTemplatesTable->get(3); // Email template that has linked forms

		$result = $this->emailTemplatesTable->checkRules($emailTemplate, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $emailTemplate->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedFormConfirmationEmails', $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->emailTemplatesTable->newDefaultEntity();

		$this->assertInstanceOf(EmailTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->title);
		$this->assertNull($entity->textHtml);
		$this->assertNull($entity->textPlain);
		$this->assertNull($entity->fileName);
		$this->assertNull($entity->layout);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Email Template',
			'textHtml' => '<p>Custom HTML content</p>',
			'textPlain' => 'Custom plain text content',
			'fileName' => 'custom_template',
			'layout' => 'custom.twig',
			'active' => false,
		];

		$entity = $this->emailTemplatesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(EmailTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Email Template', $entity->title);
		$this->assertSame('<p>Custom HTML content</p>', $entity->textHtml);
		$this->assertSame('Custom plain text content', $entity->textPlain);
		$this->assertSame('custom_template', $entity->fileName);
		$this->assertSame('custom.twig', $entity->layout);
		$this->assertFalse($entity->active);

		// Check that defaults are preserved
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\EmailTemplatesTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->emailTemplatesTable->hasBehavior('Translate'));

		$config = $this->emailTemplatesTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame([
			'title',
			'textHtml',
			'textPlain',
		], $config['fields']);
	}
}
