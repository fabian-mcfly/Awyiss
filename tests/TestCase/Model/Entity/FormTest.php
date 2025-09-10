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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
			'formConditionalRecipients' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$defaultValues
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Form();

		$entity->identifier = 'TestForm';
		$this->assertEquals('testform', $entity->identifier);

		$entity->identifier = 'Test Form';
		$this->assertEquals('test_form', $entity->identifier);

		$entity->identifier = 'Test-Form';
		$this->assertEquals('test_form', $entity->identifier);

		$entity->identifier = 'Test Form!@#$%';
		$this->assertEquals('test_form', $entity->identifier);

		$entity->identifier = 'UPPERCASE FORM';
		$this->assertEquals('uppercase_form', $entity->identifier);

		$entity->identifier = 'testHTMLForm';
		$this->assertEquals('testhtmlform', $entity->identifier);

		$entity->identifier = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Form();

		$entity->set('identifier', 'TestForm');
		$this->assertEquals('testform', $entity->identifier);

		$entity->set('identifier', 'Test Form');
		$this->assertEquals('test_form', $entity->identifier);

		$entity->set('identifier', 'Test-Form');
		$this->assertEquals('test_form', $entity->identifier);

		$entity->set('identifier', 'Test Form!@#$%');
		$this->assertEquals('test_form', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE FORM');
		$this->assertEquals('uppercase_form', $entity->identifier);

		$entity->set('identifier', 'testHTMLForm');
		$this->assertEquals('testhtmlform', $entity->identifier);

		$entity->set('identifier', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Contact Form',
			'identifier' => 'TestForm',
			'send_email' => true,
			'email_template_id' => 123,
			'send_confirmation_email' => false,
			'confirmation_email_template_id' => 456,
			'owner_email' => 'owner@example.com',
			'owner_name' => 'Form Owner',
			'user_email' => '$email',
			'user_name' => '$vorname $nachname',
			'cc' => '[]',
			'bcc' => '[]',
			'subject' => 'Form Subject',
			'subject_confirmation' => 'Confirmation Subject',
			'salutation' => 'Dear Sir/Madam',
			'salutation_confirmation' => 'Thank you',
			'summarize_errors' => false,
			'success_message' => 'Form submitted successfully',
			'multistep' => false,
			'conditional_recipients_strategy' => 'first_match',
			'transport_profile' => 'smtp',
			'active' => true,
			'deleted' => false,
		];

		$entity = new Form($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Contact Form', $entity->title);
		$this->assertEquals('testform', $entity->identifier); // Should be cleaned by setter
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
		$this->assertEquals('first_match', $entity->conditionalRecipientsStrategy);
		$this->assertEquals('smtp', $entity->transportProfile);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'send_email' => true,
			'email_template_id' => 123,
			'send_confirmation_email' => false,
			'confirmation_email_template_id' => 456,
			'owner_email' => 'test@example.com',
			'owner_name' => 'Test Owner',
			'user_email' => '$email',
			'user_name' => '$name',
			'subject_confirmation' => 'Test Confirmation',
			'salutation_confirmation' => 'Thank you',
			'summarize_errors' => true,
			'success_message' => 'Success!',
			'conditional_recipients_strategy' => 'all',
			'transport_profile' => 'debug',
		];

		$entity = new Form($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::setFormData()
	 * @see \Awyiss\Model\Entity\Form::getFormData()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsValidWithNoErrors(): void {
		$entity = new Form();

		$this->assertTrue($entity->isValid());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::isValid()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadFormOptionsWithDefaultFormOptions(): void {
		$entity = new Form(['identifier' => 'nonexistent_form']);
		$entity->loadFormOptions();

		$formOptions = $entity->getFormOptions();
		$this->assertInstanceOf(FormOptions::class, $formOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @see \Awyiss\Model\Entity\Form::getFormOptions()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
			'datenschutz_akzeptiert' => 'Ja',
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
	 * @noinspection PhpVariableNamingConventionInspection
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
			'multi_checkbox' => ['a', 'b'],
			'files' => ['upload1.pdf', 'upload2.jpg'],
		];

		$entity->setFormData($complexFormData);

		$this->assertEquals($complexFormData, $entity->getFormData());
		$this->assertEquals(['vorname' => 'John', 'nachname' => 'Doe'], $entity->getFormData('personal'));
		$this->assertEquals(['email' => 'john@example.com', 'telefon' => '+49 123 456789'], $entity->getFormData('contact'));
		$this->assertEquals(['a', 'b'], $entity->getFormData('multi_checkbox'));
		$this->assertNull($entity->getFormData('nonexistent'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
			// Missing required fields: nachname, email, nachricht, datenschutz_akzeptiert
		];

		$entity->setFormData($incompleteFormData);
		$entity->validate($incompleteFormData);

		$this->assertFalse($entity->isValid());
		$errors = $entity->getErrors();
		$this->assertNotEmpty($errors);

		// Should have errors for required fields
		$this->assertArrayHasKey('nachname', $errors);
		$this->assertArrayHasKey('_required', $errors['nachname']);
		$this->assertSame('form::error_required', $errors['nachname']['_required']);

		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('_required', $errors['email']);
		$this->assertSame('form::error_required', $errors['email']['_required']);

		$this->assertArrayHasKey('nachricht', $errors);
		$this->assertArrayHasKey('_required', $errors['nachricht']);
		$this->assertSame('form::error_required', $errors['nachricht']['_required']);

		$this->assertArrayHasKey('datenschutz_akzeptiert', $errors);
		$this->assertArrayHasKey('_required', $errors['datenschutz_akzeptiert']);
		$this->assertSame('form::error_required', $errors['datenschutz_akzeptiert']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
			'datenschutz_akzeptiert' => 'Ja',
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFormWithMultistepConfiguration(): void {
		// Create a form entity to test multistep functionality
		$entity = new Form([
			'title' => 'Multistep Form',
			'identifier' => 'multistep_test',
			'multistep' => true,
			'active' => true,
		]);

		$this->assertTrue($entity->multistep);
		$this->assertEquals('multistep_test', $entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFormWithConditionalRecipientsStrategy(): void {
		/** @var \Awyiss\Model\Table\FormsTable $table */
		$table = FactoryLocator::get('Table')->get('Forms');
		/** @var \Awyiss\Model\Entity\Form $entity */
		$entity = $table->get(1);

		$this->assertSame('match_first', $entity->conditionalRecipientsStrategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
			'multi_checkbox' => ['a', 'b'], // Multiple selections
			'multi_select' => ['a'],
			'nachricht' => 'Test message',
			'datenschutz_akzeptiert' => 'Ja',
		];

		$entity->setFormData($formData);
		$entity->validate($formData);

		$this->assertTrue($entity->isValid());
		$this->assertEquals(['a', 'b'], $entity->getFormData('multi_checkbox'));
		$this->assertEquals(['a'], $entity->getFormData('multi_select'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateWithProtectionMethod(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', ['hidden_input']);

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
			'datenschutz_akzeptiert' => 'Ja',
			'email_confirmation' => '',
		];

		$entity->setFormData($formData);
		$entity->validate($formData, null, true); // Force protection validation

		$this->assertTrue($entity->isValid());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::validate()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateWithProtectionMethodFailure(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', ['hidden_input']);

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
			'datenschutz_akzeptiert' => 'Ja',
			'email_confirmation' => 'this-should-be-empty-for-honeypot', // This will trigger HiddenInputFormProtection failure
		];

		$entity->setFormData($formData);
		$entity->validate($formData, null, true); // Force protection validation

		$this->assertFalse($entity->isValid());
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('hidden_input', $errors['_general']);
		$this->assertEquals('form::protection_method_hidden_input_error_field_empty', $errors['_general']['hidden_input']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new Form();

		$this->assertSame(['label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::setProgress()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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

		$entity->getFormOptions()->modifyForm($entity);

		// Check if Contact4FormOptions modified the form identifier
		$this->assertEquals('new_contact4', $entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormOptions()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
		$entity->getFormOptions()->setConditionalRecipient($entity);

		$this->assertEquals('importantclient@cms.de', $entity->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFormWithConditionalRecipientsConfiguration(): void {
		// Test various conditional recipients strategies
		$formData = [
			'title' => 'Test Form',
			'identifier' => 'test_conditional',
			'conditionalRecipientsStrategy' => 'first_match',
		];

		$entity = new Form($formData);
		$this->assertEquals('first_match', $entity->conditionalRecipientsStrategy);

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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeWithProtectionMethodsConfiguration(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', [
			'hidden_input',
			'altcha',
			'duplicate_check',
			'ip_check',
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
			'hidden_input',
			'altcha',
			'duplicate_check',
			'ip_check',
		], array_keys($protectionMethods));

		foreach ($protectionMethods as $method) {
			$this->assertInstanceOf(FormProtectionInterface::class, $method);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Form::loadFormElements()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetValidatorConfiguration(): void {
		$entity = new Form();
		$validator = $entity->getValidator();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(Validator::class, $validator);

		// Should have 'form' as I18n domain
		$this->assertEquals('form', $validator->getI18nDomain());

		$this->assertFalse($validator->__debugInfo()['_stopOnFailure']);
	}
}
