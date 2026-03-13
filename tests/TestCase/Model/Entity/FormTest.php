<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\FormProtectionInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\View\View;
use Customer\Form\Contact4FormOptions;


/**
 * Form Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Form
 */
class FormTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Form();

		$this->assertSame([
			'title' => true,
			'identifier' => true,
			'sendEmail' => true,
			'emailTemplateId' => true,
			'sendConfirmationEmail' => true,
			'confirmationEmailTemplateId' => true,
			'ownerEmail' => true,
			'ownerName' => true,
			'userEmail' => true,
			'userName' => true,
			'cc' => true,
			'bcc' => true,
			'subject' => true,
			'subjectConfirmation' => true,
			'salutation' => true,
			'salutationConfirmation' => true,
			'summarizeErrors' => true,
			'successMessage' => true,
			'multistep' => true,
			'conditionalRecipientsStrategy' => true,
			'transportProfile' => true,
			'active' => true,
			'conditionalRecipients' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		$entity = $table->newDefaultEntity();

		$this->assertEquals('default', $entity->transportProfile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Form();

		$entity->identifier = 'TestForm';
		$this->assertEquals('testForm', $entity->identifier);

		$entity->identifier = 'Test Form';
		$this->assertEquals('testForm', $entity->identifier);

		$entity->identifier = 'Test-Form';
		$this->assertEquals('testForm', $entity->identifier);

		$entity->identifier = 'Test Form!@#$%';
		$this->assertEquals('testForm', $entity->identifier);

		$entity->identifier = 'UPPERCASE FORM';
		$this->assertEquals('uPPERCASEFORM', $entity->identifier);

		$entity->identifier = 'testHTMLForm';
		$this->assertEquals('testHTMLForm', $entity->identifier);

		$entity->identifier = 'is_underscored';
		$this->assertEquals('isUnderscored', $entity->identifier);

		$entity->identifier = 'alreadyVariableLike';
		$this->assertEquals('alreadyVariableLike', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Form();

		$entity->set('identifier', 'TestForm');
		$this->assertEquals('testForm', $entity->identifier);

		$entity->set('identifier', 'Test Form');
		$this->assertEquals('testForm', $entity->identifier);

		$entity->set('identifier', 'Test-Form');
		$this->assertEquals('testForm', $entity->identifier);

		$entity->set('identifier', 'Test Form!@#$%');
		$this->assertEquals('testForm', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE FORM');
		$this->assertEquals('uPPERCASEFORM', $entity->identifier);

		$entity->set('identifier', 'testHTMLForm');
		$this->assertEquals('testHTMLForm', $entity->identifier);

		$entity->set('identifier', 'is_underscored');
		$this->assertEquals('isUnderscored', $entity->identifier);

		$entity->set('identifier', 'alreadyVariableLike');
		$this->assertEquals('alreadyVariableLike', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Contact Form',
			'identifier' => 'TestForm',
			'sendEmail' => true,
			'emailTemplateId' => 123,
			'sendConfirmationEmail' => false,
			'confirmationEmailTemplateId' => 456,
			'ownerEmail' => 'owner@example.com',
			'ownerName' => 'Form Owner',
			'userEmail' => '$email',
			'userName' => '$vorname $nachname',
			'cc' => '[]',
			'bcc' => '[]',
			'subject' => 'Form Subject',
			'subjectConfirmation' => 'Confirmation Subject',
			'salutation' => 'Dear Sir/Madam',
			'salutationConfirmation' => 'Thank you',
			'summarizeErrors' => false,
			'successMessage' => 'Form submitted successfully',
			'multistep' => false,
			'conditionalRecipientsStrategy' => 'firstMatch',
			'transportProfile' => 'smtp',
			'active' => true,
			'deleted' => false,
		];

		$entity = new Form($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Contact Form', $entity->title);
		$this->assertEquals('testForm', $entity->identifier); // Should be cleaned by setter
		$this->assertTrue($entity->sendEmail);
		$this->assertEquals(123, $entity->emailTemplateId);
		$this->assertFalse($entity->sendConfirmationEmail);
		$this->assertEquals(456, $entity->confirmationEmailTemplateId);
		$this->assertEquals('owner@example.com', $entity->ownerEmail);
		$this->assertEquals('Form Owner', $entity->ownerName);
		$this->assertEquals('$email', $entity->userEmail);
		$this->assertEquals('$vorname $nachname', $entity->userName);
		$this->assertEquals('[]', $entity->cc);
		$this->assertEquals('[]', $entity->bcc);
		$this->assertEquals('Form Subject', $entity->subject);
		$this->assertEquals('Confirmation Subject', $entity->subjectConfirmation);
		$this->assertEquals('Dear Sir/Madam', $entity->salutation);
		$this->assertEquals('Thank you', $entity->salutationConfirmation);
		$this->assertFalse($entity->summarizeErrors);
		$this->assertEquals('Form submitted successfully', $entity->successMessage);
		$this->assertFalse($entity->multistep);
		$this->assertEquals('firstMatch', $entity->conditionalRecipientsStrategy);
		$this->assertEquals('smtp', $entity->transportProfile);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::setFormData()
	 * @see \Awyiss\Model\Entity\Form::getFormData()
	 */
	public function testFormDataHandling(): void {
		$entity = new Form();
		$formData = [
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'john@example.com',
		];

		$entity->setFormData($formData);

		$this->assertEquals($formData, $entity->getFormData());
		$this->assertEquals('John', $entity->getFormData('vorname'));
		$this->assertEquals('john@example.com', $entity->getFormData('email'));
		$this->assertNull($entity->getFormData('nonexistent'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::submitted()
	 * @see \Awyiss\Model\Entity\Form::isSubmitted()
	 */
	public function testFormSubmissionState(): void {
		$entity = new Form();

		$this->assertFalse($entity->isSubmitted());

		$entity->submitted();
		$this->assertTrue($entity->isSubmitted());

		$entity->submitted(false);
		$this->assertFalse($entity->isSubmitted());

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->submitted(true);
		$this->assertTrue($entity->isSubmitted());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::getValidator()
	 */
	public function testGetValidator(): void {
		$entity = new Form();
		$validator = $entity->getValidator();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(Validator::class, $validator);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::isValid()
	 */
	public function testIsValidWithNoErrors(): void {
		$entity = new Form();

		$this->assertTrue($entity->isValid());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::isValid()
	 */
	public function testIsValidWithErrors(): void {
		$entity = new Form();
		$entity->setError('identifier', 'Test error');

		$this->assertFalse($entity->isValid());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @see \Awyiss\Model\Entity\Form::getFormOptions()
	 */
	public function testLoadFormOptionsWithDefaultFormOptions(): void {
		$entity = new Form(['identifier' => 'nonexistentForm']);
		$entity->loadFormOptions();

		$formOptions = $entity->getFormOptions();
		$this->assertInstanceOf(FormOptions::class, $formOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @see \Awyiss\Model\Entity\Form::getFormOptions()
	 */
	public function testLoadFormOptionsWithCustomFormOptions(): void {
		$entity = new Form(['identifier' => 'contact4']);
		$entity->loadFormOptions();

		$formOptions = $entity->getFormOptions();
		$this->assertInstanceOf(Contact4FormOptions::class, $formOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormElements()
	 * @see \Awyiss\Model\Entity\Form::getFormElements()
	 * @see \Awyiss\Model\Entity\Form::getFormElementsChecksum()
	 * @throws \Exception
	 */
	public function testLoadFormElementsWithRealData(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1); // Contact form from seed data

		$view = new View();
		$entity->initialize($view);

		$formElements = $entity->getFormElements();
		$this->assertNotNull($formElements);
		$this->assertGreaterThan(0, $formElements->count());

		$checksum = $entity->getFormElementsChecksum();
		$this->assertIsString($checksum);
		$this->assertEquals(32, strlen($checksum)); // MD5 hash length
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormElements()
	 * @throws \Exception
	 */
	public function testLoadFormElementsWithNoElements(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(3); // Form without elements

		$view = new View();
		$entity->initialize($view);

		$formElements = $entity->getFormElements();
		$this->assertNull($formElements);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormElements()
	 * @throws \Exception
	 */
	public function testLoadFormElementsInPreviewMode(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view, null, true); // Preview mode

		$formElements = $entity->getFormElements();
		$this->assertNotNull($formElements);
		$this->assertGreaterThan(0, $formElements->count());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::initialize()
	 * @throws \Exception
	 */
	public function testInitialize(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$result = $entity->initialize($view);

		$this->assertSame($entity, $result);
		$this->assertNotNull($entity->getFormOptions());
		$this->assertNotNull($entity->getFormElements());
		$this->assertIsArray($entity->getProtectionMethods());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::initialize()
	 * @throws \Exception
	 */
	public function testInitializeWithPageObject(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		$view = new View();
		$entity->initialize($view, $page);

		$this->assertNotNull($entity->getFormOptions());
		$this->assertNotNull($entity->getFormElements());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormElements()
	 * @throws \Exception
	 */
	public function testLoadFormElementsWithLanguageSpecificOptions(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		$view = new View();
		$entity->initialize($view, $page);

		$formElements = $entity->getFormElements();
		$this->assertNotNull($formElements);

		$formElements = $formElements->listNested();

		// Check that select element options are processed with language
		$selectElement = $formElements->filter(function ($element) {
			return $element->type === 'select' && $element->identifier === 'anrede';
		})->first();

		$this->assertNotNull($selectElement);
		$this->assertIsArray($selectElement->options);
		// Should contain German translations
		$this->assertArrayHasKey('Frau', $selectElement->options);
		$this->assertArrayHasKey('Herr', $selectElement->options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 */
	public function testValidateWithFormSubmissionFlow(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		// Test complete submission flow
		$formData = [
			'anrede' => 'Herr',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'email' => 'max@example.com',
			'telefon' => '+49 123 456789',
			'nachricht' => 'Test message',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$entity->setFormData($formData);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->submitted(true);
		$entity->validate($formData);

		$this->assertTrue($entity->isSubmitted());
		$this->assertTrue($entity->isValid());
		$this->assertEquals($formData, $entity->getFormData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::getFormData()
	 */
	public function testFormDataRetrievalWithComplexData(): void {
		$entity = new Form();
		$complexFormData = [
			'personal' => [
				'vorname' => 'John',
				'nachname' => 'Doe',
			],
			'contact' => [
				'email' => 'john@example.com',
				'telefon' => '+49 123 456789',
			],
			'multiCheckbox' => ['a', 'b'],
			'files' => ['upload1.pdf', 'upload2.jpg'],
		];

		$entity->setFormData($complexFormData);

		$this->assertEquals($complexFormData, $entity->getFormData());
		$this->assertEquals(['vorname' => 'John', 'nachname' => 'Doe'], $entity->getFormData('personal'));
		$this->assertEquals(['email' => 'john@example.com', 'telefon' => '+49 123 456789'], $entity->getFormData('contact'));
		$this->assertEquals(['a', 'b'], $entity->getFormData('multiCheckbox'));
		$this->assertNull($entity->getFormData('nonexistent'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @throws \Exception
	 */
	public function testFormOptionsModifyFormElement(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(4); // contact4 form with custom FormOptions

		$view = new View();
		$entity->initialize($view);

		$formElements = $entity->getFormElements();
		$this->assertNotNull($formElements);

		// Check if FormOptions modified form elements (like setting default values)
		$emailElement = $formElements->filter(function ($element) {
			return $element->identifier === 'email';
		})->first();

		$this->assertNotNull($emailElement);
		// Contact4FormOptions should set email default value to 'foo@bar.com'
		$this->assertEquals('foo@bar.com', $emailElement->value ?? null);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 */
	public function testValidateWithMissingRequiredFields(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		$incompleteFormData = [
			'vorname' => 'John',
			// Missing required fields: nachname, email, nachricht, datenschutzAkzeptiert
		];

		$entity->setFormData($incompleteFormData);
		$entity->validate($incompleteFormData);

		$this->assertFalse($entity->isValid());
		$errors = $entity->getErrors();
		$this->assertNotEmpty($errors);

		// Should have errors for required fields
		$this->assertArrayHasKey('nachname', $errors);
		$this->assertArrayHasKey('_required', $errors['nachname']);
		$this->assertSame('Form::error_required', $errors['nachname']['_required']);

		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('_required', $errors['email']);
		$this->assertSame('Form::error_required', $errors['email']['_required']);

		$this->assertArrayHasKey('nachricht', $errors);
		$this->assertArrayHasKey('_required', $errors['nachricht']);
		$this->assertSame('Form::error_required', $errors['nachricht']['_required']);

		$this->assertArrayHasKey('datenschutzAkzeptiert', $errors);
		$this->assertArrayHasKey('_required', $errors['datenschutzAkzeptiert']);
		$this->assertSame('Form::error_required', $errors['datenschutzAkzeptiert']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 */
	public function testValidateWithInvalidEmailFormat(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		$invalidFormData = [
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'not-an-email',
			'nachricht' => 'Test message',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$entity->setFormData($invalidFormData);
		$entity->validate($invalidFormData);

		$this->assertFalse($entity->isValid());
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('email', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::getFormElementsChecksum()
	 * @throws \Exception
	 */
	public function testFormElementsChecksumConsistency(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity1 */
		$entity1 = $table->get(1);
		/** @var \Awyiss\Model\Entity\Form $entity2 */
		$entity2 = $table->get(1);

		$view = new View();
		$entity1->initialize($view);
		$entity2->initialize($view);

		$checksum1 = $entity1->getFormElementsChecksum();
		$checksum2 = $entity2->getFormElementsChecksum();

		$this->assertEquals($checksum1, $checksum2);
		$this->assertEquals(32, strlen($checksum1)); // MD5 hash length
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 */
	public function testFormWithMultistepConfiguration(): void {
		// Create a form entity to test multistep functionality
		$entity = new Form([
			'title' => 'Multistep Form',
			'identifier' => 'multistepTest',
			'multistep' => true,
			'active' => true,
		]);

		$this->assertTrue($entity->multistep);
		$this->assertEquals('multistepTest', $entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 */
	public function testFormWithConditionalRecipientsStrategy(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$this->assertSame('matchFirst', $entity->conditionalRecipientsStrategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 */
	public function testFormWithDifferentTransportProfiles(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');

		// Test form with debug transport profile
		/** @var \Awyiss\Model\Entity\Form $entity1 */
		$entity1 = $table->get(1);
		$this->assertEquals('debug', $entity1->transportProfile);

		// Test form with default transport profile through default values
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		$newEntity = $table->newDefaultEntity();
		$this->assertEquals('default', $newEntity->transportProfile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 */
	public function testValidateWithMultipleChoiceFields(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		$formData = [
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'john@example.com',
			'multiCheckbox' => ['a', 'b'], // Multiple selections
			'multiSelect' => ['a'],
			'nachricht' => 'Test message',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$entity->setFormData($formData);
		$entity->validate($formData);

		$this->assertTrue($entity->isValid());
		$this->assertEquals(['a', 'b'], $entity->getFormData('multiCheckbox'));
		$this->assertEquals(['a'], $entity->getFormData('multiSelect'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 */
	public function testValidateWithProtectionMethod(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', ['hiddenInput']);

		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		// Simulate protection method failure by providing invalid data
		$formData = [
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'john@example.com',
			'nachricht' => 'Test message',
			'datenschutzAkzeptiert' => 'Ja',
			'emailConfirmation' => '',
		];

		$entity->setFormData($formData);
		$entity->validate($formData, null, true); // Force protection validation

		$this->assertTrue($entity->isValid());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 */
	public function testValidateWithProtectionMethodFailure(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', ['hiddenInput']);

		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		// Simulate protection method failure by providing invalid data
		$formData = [
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'john@example.com',
			'nachricht' => 'Test message',
			'datenschutzAkzeptiert' => 'Ja',
			'emailConfirmation' => 'this-should-be-empty-for-honeypot', // This will trigger HiddenInputFormProtection failure
		];

		$entity->setFormData($formData);
		$entity->validate($formData, null, true); // Force protection validation

		$this->assertFalse($entity->isValid());
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('hiddenInput', $errors['_general']);
		$this->assertEquals('Form::protection_method_hidden_input_error_field_empty', $errors['_general']['hiddenInput']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new Form();

		$this->assertSame(['label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::setProgress()
	 */
	public function testFormProgressDataHandling(): void {
		$entity = new Form();
		$progressData = [
			'step1' => 'completed',
			'step2' => 'in_progress',
			'step3' => 'pending',
		];

		// Test that Form entity can handle progress data (for multistep forms)
		$entity->setFormData($progressData);

		$this->assertEquals($progressData, $entity->getFormData());
		$this->assertEquals('completed', $entity->getFormData('step1'));
		$this->assertEquals('in_progress', $entity->getFormData('step2'));
		$this->assertEquals('pending', $entity->getFormData('step3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @throws \Exception
	 */
	public function testFormOptionsModifyFormIntegration(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(4); // contact4 form with custom FormOptions

		$originalIdentifier = $entity->identifier;
		$this->assertEquals('contact4', $originalIdentifier);

		$view = new View();
		$entity->initialize($view);

		$entity->getFormOptions()->modifyForm();

		// Check if Contact4FormOptions modified the form identifier
		$this->assertEquals('newContact4', $entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @throws \Exception
	 */
	public function testFormOptionsSetConditionalRecipient(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(4); // contact4 form with custom FormOptions

		$view = new View();
		$entity->initialize($view);

		// Test conditional recipient modification
		$entity->setFormData(['email' => 'importantclient@example.com']);
		$entity->getFormOptions()->setConditionalRecipient();

		$this->assertEquals('importantclient@cms.de', $entity->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 */
	public function testFormWithConditionalRecipientsConfiguration(): void {
		// Test various conditional recipients strategies
		$formData = [
			'title' => 'Test Form',
			'identifier' => 'testConditional',
			'conditionalRecipientsStrategy' => 'firstMatch',
		];

		$entity = new Form($formData);
		$this->assertEquals('firstMatch', $entity->conditionalRecipientsStrategy);

		$entity->conditionalRecipientsStrategy = 'all';
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertEquals('all', $entity->conditionalRecipientsStrategy);

		$entity->conditionalRecipientsStrategy = 'none';
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertEquals('none', $entity->conditionalRecipientsStrategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::initialize()
	 * @throws \Exception
	 */
	public function testInitializeWithProtectionMethodsConfiguration(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', [
			'hiddenInput',
			'altcha',
			'duplicateCheck',
			'ipCheck',
		]);

		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		$protectionMethods = $entity->getProtectionMethods();
		$this->assertIsArray($protectionMethods);
		$this->assertSame([
			'hiddenInput',
			'altcha',
			'duplicateCheck',
			'ipCheck',
		], array_keys($protectionMethods));

		foreach ($protectionMethods as $method) {
			$this->assertInstanceOf(FormProtectionInterface::class, $method);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormElements()
	 * @throws \Exception
	 */
	public function testLoadFormElementsWithThreadedStructure(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$view = new View();
		$entity->initialize($view);

		$formElements = $entity->getFormElements();
		$this->assertCount(4, $formElements);

		$formElements = $formElements->listNested()->toArray();
		$this->assertCount(14, $formElements);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::getValidator()
	 */
	public function testGetValidatorConfiguration(): void {
		$entity = new Form();
		$validator = $entity->getValidator();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(Validator::class, $validator);

		// Should have 'form' as I18n domain
		$this->assertEquals('Form', $validator->getI18nDomain());

		$this->assertFalse($validator->__debugInfo()['_stopOnFailure']);
	}
}
