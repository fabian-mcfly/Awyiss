<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Form\FormConditionalRecipients;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Table\FormsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * FormsTable Test Case
 *
 * @see \Awyiss\Model\Table\FormsTable
 */
class FormsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\FormsTable
	 */
	protected FormsTable $formsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->formsTable = FactoryLocator::get('Table')->get('Forms');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->formsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('forms', $this->formsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(19, $this->formsTable->associations()->keys());

		// Test Contents association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('Contents'));
		$contentsAssociation = $this->formsTable->getAssociation('Contents');
		$this->assertInstanceOf(HasMany::class, $contentsAssociation);

		// Test EmailTemplates association (BelongsTo)
		$this->assertTrue($this->formsTable->hasAssociation('EmailTemplates'));
		$emailTemplatesAssociation = $this->formsTable->getAssociation('EmailTemplates');
		$this->assertInstanceOf(BelongsTo::class, $emailTemplatesAssociation);
		$this->assertFalse($emailTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($emailTemplatesAssociation->getDependent());
		$this->assertSame('email_template_id', $emailTemplatesAssociation->getForeignKey());

		// Test ConfirmationEmailTemplates association (BelongsTo)
		$this->assertTrue($this->formsTable->hasAssociation('ConfirmationEmailTemplates'));
		$confirmationEmailTemplatesAssociation = $this->formsTable->getAssociation('ConfirmationEmailTemplates');
		$this->assertInstanceOf(BelongsTo::class, $confirmationEmailTemplatesAssociation);
		$this->assertFalse($confirmationEmailTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($confirmationEmailTemplatesAssociation->getDependent());
		$this->assertSame('confirmation_email_template_id', $confirmationEmailTemplatesAssociation->getForeignKey());

		// Test FormConditionalRecipients association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('FormConditionalRecipients'));
		$formConditionalRecipientsAssociation = $this->formsTable->getAssociation('FormConditionalRecipients');
		$this->assertInstanceOf(HasMany::class, $formConditionalRecipientsAssociation);
		$this->assertTrue($formConditionalRecipientsAssociation->getCascadeCallbacks());
		$this->assertTrue($formConditionalRecipientsAssociation->getDependent());
		$this->assertSame('form_id', $formConditionalRecipientsAssociation->getForeignKey());
		$this->assertSame('replace', $formConditionalRecipientsAssociation->getSaveStrategy());

		// Test FormElements association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('FormElements'));
		$formElementsAssociation = $this->formsTable->getAssociation('FormElements');
		$this->assertInstanceOf(HasMany::class, $formElementsAssociation);
		$this->assertTrue($formElementsAssociation->getCascadeCallbacks());
		$this->assertTrue($formElementsAssociation->getDependent());
		$this->assertSame('form_id', $formElementsAssociation->getForeignKey());

		// Test FormEntries association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('FormEntries'));
		$formEntriesAssociation = $this->formsTable->getAssociation('FormEntries');
		$this->assertInstanceOf(HasMany::class, $formEntriesAssociation);
		$this->assertTrue($formEntriesAssociation->getCascadeCallbacks());
		$this->assertTrue($formEntriesAssociation->getDependent());
		$this->assertSame('form_id', $formEntriesAssociation->getForeignKey());

		// Test Pages association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('Pages'));
		$pagesAssociation = $this->formsTable->getAssociation('Pages');
		$this->assertInstanceOf(HasMany::class, $pagesAssociation);

		// Test Surveys association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->formsTable->getAssociation('Surveys');
		$this->assertInstanceOf(HasMany::class, $surveysAssociation);

		// Test Widgets association (HasMany)
		$this->assertTrue($this->formsTable->hasAssociation('Widgets'));
		$widgetsAssociation = $this->formsTable->getAssociation('Widgets');
		$this->assertInstanceOf(HasMany::class, $widgetsAssociation);

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->formsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->formsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->formsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->formsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->formsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->formsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->formsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Forms_title_translation' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('Forms_title_translation'));
		$titleTranslationAssociation = $this->formsTable->getAssociation('Forms_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'Forms_subject_translation' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('Forms_subject_translation'));
		$subjectTranslationAssociation = $this->formsTable->getAssociation('Forms_subject_translation');
		$this->assertInstanceOf(HasOne::class, $subjectTranslationAssociation);
		$this->assertFalse($subjectTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subjectTranslationAssociation->getDependent());

		// 'Forms_subject_confirmation_translation' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('Forms_subjectConfirmation_translation'));
		$subjectConfirmationTranslationAssociation = $this->formsTable->getAssociation('Forms_subjectConfirmation_translation');
		$this->assertInstanceOf(HasOne::class, $subjectConfirmationTranslationAssociation);
		$this->assertFalse($subjectConfirmationTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subjectConfirmationTranslationAssociation->getDependent());

		// 'Forms_salutation_confirmation_translation' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('Forms_salutationConfirmation_translation'));
		$salutationConfirmationTranslationAssociation = $this->formsTable->getAssociation('Forms_salutationConfirmation_translation');
		$this->assertInstanceOf(HasOne::class, $salutationConfirmationTranslationAssociation);
		$this->assertFalse($salutationConfirmationTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($salutationConfirmationTranslationAssociation->getDependent());

		// 'Forms_success_message_translation' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('Forms_successMessage_translation'));
		$successMessageTranslationAssociation = $this->formsTable->getAssociation('Forms_successMessage_translation');
		$this->assertInstanceOf(HasOne::class, $successMessageTranslationAssociation);
		$this->assertFalse($successMessageTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($successMessageTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->formsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->formsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->formsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('forms', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('sendEmail'));
		$this->assertTrue($result->hasField('emailTemplateId'));
		$this->assertTrue($result->hasField('sendConfirmationEmail'));
		$this->assertTrue($result->hasField('confirmationEmailTemplateId'));
		$this->assertTrue($result->hasField('ownerEmail'));
		$this->assertTrue($result->hasField('ownerName'));
		$this->assertTrue($result->hasField('userEmail'));
		$this->assertTrue($result->hasField('userName'));
		$this->assertTrue($result->hasField('cc'));
		$this->assertTrue($result->hasField('bcc'));
		$this->assertTrue($result->hasField('subject'));
		$this->assertTrue($result->hasField('subjectConfirmation'));
		$this->assertTrue($result->hasField('salutationConfirmation'));
		$this->assertTrue($result->hasField('summarizeErrors'));
		$this->assertTrue($result->hasField('successMessage'));
		$this->assertTrue($result->hasField('multistep'));
		$this->assertTrue($result->hasField('transportProfile'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
		$this->assertTrue($result->hasField('conditionalRecipientsStrategy'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'sendEmail' => false,
			'sendConfirmationEmail' => false,
			'summarizeErrors' => true,
			'successMessage' => 'Thank you for your submission',
			'multistep' => false,
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
			'transportProfile' => 'default',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'sendEmail' => false,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'identifier' => true,
			'sendEmail' => 'not_a_boolean',
			'emailTemplateId' => 'not_an_integer',
			'sendConfirmationEmail' => 'not_a_boolean',
			'confirmationEmailTemplateId' => 'not_an_integer',
			'ownerEmail' => true,
			'ownerName' => true,
			'userEmail' => true,
			'userName' => true,
			'cc' => 'not_an_array',
			'bcc' => 'not_an_array',
			'subject' => true,
			'subjectConfirmation' => true,
			'salutationConfirmation' => true,
			'summarizeErrors' => 'not_a_boolean',
			'successMessage' => true,
			'multistep' => 'not_a_boolean',
			'conditionalRecipientsStrategy' => true,
			'transportProfile' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('sendEmail', $errors);
		$this->assertArrayHasKey('emailTemplateId', $errors);
		$this->assertArrayHasKey('sendConfirmationEmail', $errors);
		$this->assertArrayHasKey('confirmationEmailTemplateId', $errors);
		$this->assertArrayHasKey('ownerEmail', $errors);
		$this->assertArrayHasKey('ownerName', $errors);
		$this->assertArrayHasKey('userEmail', $errors);
		$this->assertArrayHasKey('userName', $errors);
		$this->assertArrayHasKey('cc', $errors);
		$this->assertArrayHasKey('bcc', $errors);
		$this->assertArrayHasKey('subject', $errors);
		$this->assertArrayHasKey('subjectConfirmation', $errors);
		$this->assertArrayHasKey('salutationConfirmation', $errors);
		$this->assertArrayHasKey('summarizeErrors', $errors);
		$this->assertArrayHasKey('successMessage', $errors);
		$this->assertArrayHasKey('multistep', $errors);
		$this->assertArrayHasKey('conditionalRecipientsStrategy', $errors);
		$this->assertArrayHasKey('transportProfile', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
			'emailTemplateId' => 123456789123, // exceeds 11 char limit
			'confirmationEmailTemplateId' => 123456789123, // exceeds 11 char limit
			'ownerEmail' => str_repeat('c', 256), // exceeds 255 char limit
			'ownerName' => str_repeat('d', 256), // exceeds 255 char limit
			'userEmail' => str_repeat('e', 256), // exceeds 255 char limit
			'userName' => str_repeat('f', 256), // exceeds 255 char limit
			'subject' => str_repeat('g', 256), // exceeds 255 char limit
			'subjectConfirmation' => str_repeat('h', 256), // exceeds 255 char limit
			'salutationConfirmation' => str_repeat('i', 256), // exceeds 255 char limit
			'successMessage' => str_repeat('j', 65536), // exceeds 65535 byte limit
			'conditionalRecipientsStrategy' => str_repeat('k', 21), // exceeds 20 char limit
			'transportProfile' => str_repeat('l', 51), // exceeds 50 char limit
		];

		$entity = $this->formsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('emailTemplateId', $errors);
		$this->assertArrayHasKey('confirmationEmailTemplateId', $errors);
		$this->assertArrayHasKey('ownerEmail', $errors);
		$this->assertArrayHasKey('ownerName', $errors);
		$this->assertArrayHasKey('userEmail', $errors);
		$this->assertArrayHasKey('userName', $errors);
		$this->assertArrayHasKey('subject', $errors);
		$this->assertArrayHasKey('subjectConfirmation', $errors);
		$this->assertArrayHasKey('salutationConfirmation', $errors);
		$this->assertArrayHasKey('successMessage', $errors);
		$this->assertArrayHasKey('conditionalRecipientsStrategy', $errors);
		$this->assertArrayHasKey('transportProfile', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationEmailFields(): void {
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'ownerEmail' => 'invalid-email',
			'userEmail' => 'invalid-email',
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('ownerEmail', $errors);
		$this->assertArrayHasKey('userEmail', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationValidEmailPlaceholders(): void {
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'userEmail' => '$email_field',
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('userEmail', $errors);

		// Test with curly brace syntax
		$data['userEmail'] = '{{$email_field}}';
		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('userEmail', $errors);

		// Test with alternative text
		$data['userEmail'] = '{{$email_field|fallback@example.com}}';
		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('userEmail', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidEmailPlaceholders(): void {
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'userEmail' => '{{$email_field|invalid-fallback}}',
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('userEmail', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationCcBccArraysValid(): void {
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'cc' => [
				['email' => 'cc1@example.com'],
				['email' => 'cc2@example.com'],
			],
			'bcc' => [
				['email' => 'bcc1@example.com'],
				['email' => 'bcc2@example.com'],
			],
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('cc', $errors);
		$this->assertArrayNotHasKey('bcc', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationCcBccArraysInvalid(): void {
		// Test invalid CC/BCC
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'cc' => [
				['email' => 'invalid-email'],
			],
			'bcc' => [
				['email' => 'invalid-email'],
			],
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('cc', $errors);
		$this->assertArrayHasKey('bcc', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationConditionalRequired(): void {
		// Test that email template is required when send_email is true
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'sendEmail' => true,
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('emailTemplateId', $errors);
		$this->assertArrayHasKey('ownerEmail', $errors);
		$this->assertArrayHasKey('userEmail', $errors);
		$this->assertArrayHasKey('subject', $errors);

		// Test that confirmation email fields are required when send_confirmation_email is true
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'sendConfirmationEmail' => true,
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('confirmationEmailTemplateId', $errors);
		$this->assertArrayHasKey('ownerEmail', $errors);
		$this->assertArrayHasKey('userEmail', $errors);
		$this->assertArrayHasKey('subjectConfirmation', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationConditionalRecipientsStrategy(): void {
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form',
			'conditionalRecipientsStrategy' => 'invalid_strategy',
		];

		$entity = $this->formsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('conditionalRecipientsStrategy', $errors);
		$this->assertArrayHasKey('inList', $errors['conditionalRecipientsStrategy']);

		// Test valid strategies
		$validStrategies = [
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL,
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST,
		];

		foreach ($validStrategies as $strategy) {
			$data['conditionalRecipientsStrategy'] = $strategy;
			$entity = $this->formsTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('conditionalRecipientsStrategy', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidEmailTemplate(): void {
		// Test with existing email template
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form_unique_1',
			'emailTemplateId' => 1,
			'transportProfile' => 'default',
		];

		$entity = $this->formsTable->newEntity($data);
		$result = $this->formsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidEmailTemplate(): void {
		// Test with non-existing email template
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form_unique_2',
			'emailTemplateId' => 99999,
		];

		$entity = $this->formsTable->newEntity($data);
		$result = $this->formsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('emailTemplateId', $errors);
		$this->assertArrayHasKey('emailTemplateExists', $errors['emailTemplateId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidConfirmationEmailTemplate(): void {
		// Test with existing confirmation email template
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form_unique_3',
			'confirmationEmailTemplateId' => 1,
			'transportProfile' => 'default',
		];

		$entity = $this->formsTable->newEntity($data);
		$result = $this->formsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidConfirmationEmailTemplate(): void {
		// Test with non-existing confirmation email template
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form_unique_4',
			'confirmationEmailTemplateId' => 99999,
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$result = $this->formsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('confirmationEmailTemplateId', $errors);
		$this->assertArrayHasKey('confirmationEmailTemplateExists', $errors['confirmationEmailTemplateId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesUniqueIdentifier(): void {
		// Test with existing identifier (should fail)
		$data = [
			'title' => 'Test Form',
			'identifier' => 'contact', // This identifier already exists in fixtures
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$result = $this->formsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidTransportProfile(): void {
		// Test with valid transport profile - we need to check what's available
		$transportProfiles = $this->formsTable->getTransportProfiles();
		$validProfile = array_key_first($transportProfiles);

		if ($validProfile) {
			$data = [
				'title' => 'Test Form',
				'identifier' => 'test_form_unique_5',
				'transportProfile' => $validProfile,
				'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
			];

			$entity = $this->formsTable->newEntity($data);
			$result = $this->formsTable->checkRules($entity);

			$this->assertTrue($result);
		}
		else {
			$this->markTestSkipped('No transport profiles configured for testing');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidTransportProfile(): void {
		// Test with non-existing transport profile
		$data = [
			'title' => 'Test Form',
			'identifier' => 'test_form_unique_6',
			'transportProfile' => 'non_existing_profile',
			'conditionalRecipientsStrategy' => FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
		];

		$entity = $this->formsTable->newEntity($data);
		$result = $this->formsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('transportProfile', $errors);
		$this->assertArrayHasKey('transportProfileExists', $errors['transportProfile']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildDeleteRulesNoLinkedContents(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->formsTable->get(1); // Form that has linked contents

		$result = $this->formsTable->checkRules($form, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $form->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedContents', $errors['_general']);
		$this->assertSame('forms::error_linked_contents', $errors['_general']['noLinkedContents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildDeleteRulesNoLinkedPages(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->formsTable->get(2); // Form that has linked pages

		$result = $this->formsTable->checkRules($form, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $form->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedPages', $errors['_general']);
		$this->assertSame('forms::error_linked_pages', $errors['_general']['noLinkedPages']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildDeleteRulesNoLinkedSurveys(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->formsTable->get(1); // Form that has linked surveys

		$result = $this->formsTable->checkRules($form, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $form->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedSurveys', $errors['_general']);
		$this->assertSame('forms::error_linked_surveys', $errors['_general']['noLinkedSurveys']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildDeleteRulesNoLinkedWidgets(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->formsTable->get(2); // Form that has linked widgets

		$result = $this->formsTable->checkRules($form, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $form->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedWidgets', $errors['_general']);
		$this->assertSame('forms::error_linked_widgets', $errors['_general']['noLinkedWidgets']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::getFormTemplates()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFormTemplates(): void {
		$templates = $this->formsTable->getFormTemplates();

		$this->assertIsArray($templates);

		$this->assertSame([
			'AppointmentFormTemplate' => 'forms::form_template_appointment_form',
			'CallbackFormTemplate' => 'forms::form_template_callback_form',
			'ContactFormTemplate' => 'forms::form_template_contact_form',
			'JobApplicationFormTemplate' => 'forms::form_template_job_application_form',
		], $templates);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::getTransportProfiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTransportProfiles(): void {
		$profiles = $this->formsTable->getTransportProfiles();

		$this->assertIsArray($profiles);
		$this->assertSame([
			'default' => 'default',
			'smtp' => 'smtp',
			'debug' => 'debug',
		], $profiles);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->formsTable->newDefaultEntity();

		$this->assertInstanceOf(Form::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values - these should match the schema defaults
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
		$this->assertTrue($entity->sendEmail);
		$this->assertNull($entity->emailTemplateId);
		$this->assertTrue($entity->sendConfirmationEmail);
		$this->assertNull($entity->confirmationEmailTemplateId);
		$this->assertNull($entity->ownerEmail);
		$this->assertNull($entity->ownerName);
		$this->assertNull($entity->userEmail);
		$this->assertNull($entity->userName);
		$this->assertNull($entity->cc);
		$this->assertNull($entity->bcc);
		$this->assertNull($entity->subject);
		$this->assertNull($entity->subjectConfirmation);
		$this->assertNull($entity->salutationConfirmation);
		$this->assertFalse($entity->summarizeErrors);
		$this->assertNull($entity->successMessage);
		$this->assertFalse($entity->multistep);
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST, $entity->conditionalRecipientsStrategy);
		$this->assertSame('default', $entity->transportProfile);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Form',
			'identifier' => 'custom_form',
			'sendEmail' => true,
			'emailTemplateId' => 1,
			'ownerEmail' => 'owner@example.com',
			'userEmail' => 'user@example.com',
			'subject' => 'Custom Subject',
			'active' => false,
		];

		$entity = $this->formsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Form::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Form', $entity->title);
		$this->assertSame('custom_form', $entity->identifier);
		$this->assertTrue($entity->sendEmail);
		$this->assertSame(1, $entity->emailTemplateId);
		$this->assertSame('owner@example.com', $entity->ownerEmail);
		$this->assertSame('user@example.com', $entity->userEmail);
		$this->assertSame('Custom Subject', $entity->subject);
		$this->assertFalse($entity->active);

		// Check that defaults are preserved
		$this->assertFalse($entity->deleted);
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST, $entity->conditionalRecipientsStrategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->formsTable->hasBehavior('Translate'));

		$config = $this->formsTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame([
			'title',
			'subject',
			'subjectConfirmation',
			'salutationConfirmation',
			'successMessage',
		], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormsTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchemaJsonColumns(): void {
		$schema = $this->formsTable->getSchema();

		// Test that cc and bcc columns are configured as JSON types
		$this->assertSame('json', $schema->getColumnType('cc'));
		$this->assertSame('json', $schema->getColumnType('bcc'));
	}
}
