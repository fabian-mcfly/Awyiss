<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\HiddenInputFormProtection;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;


/**
 * HiddenInputFormProtection Test Case
 *
 * @see \Awyiss\Form\Protection\HiddenInputFormProtection
 */
class HiddenInputFormProtectionTest extends TestCase {
	/**
	 * @var \Awyiss\Form\Protection\HiddenInputFormProtection
	 */
	protected HiddenInputFormProtection $hiddenInputFormProtection;
	/**
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected Form $form;
	/**
	 * @var array<\Awyiss\Model\Entity\FormElement>
	 */
	protected array $formElements;
	/**
	 * @var \Awyiss\Form\FormOptions
	 */
	protected FormOptions $formOptions;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->form = new Form(['id' => 1]);
		$this->formElements = [
			'name' => new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'name']),
			'email' => new FormElement(['id' => 2, 'type' => 'email', 'identifier' => 'email']),
		];
		$this->formOptions = new FormOptions($this->form);
		$this->view = new FrontendView();

		$this->hiddenInputFormProtection = new HiddenInputFormProtection();
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialize(): void {
		$result = $this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$this->assertSame($this->hiddenInputFormProtection, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getHtml()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtml(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ls_nonce = $this->view->helpers()->get('Asset')->getStyleNonce();

		$result = $this->hiddenInputFormProtection->getHtml('before');
		$this->assertStringContainsString('<style nonce="' . $ls_nonce . '">', $result);
		$this->assertStringContainsString('{ position:absolute; visibility:hidden; }</style><input type="email" name="email_confirmation" value=""', $result);

		$result = $this->hiddenInputFormProtection->getHtml('before_submit');
		$this->assertNull($result);

		$result = $this->hiddenInputFormProtection->getHtml('after');
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getFieldName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtmlWithConflictingFormElements(): void {
		$conflictingFormElements = [
			'email_confirmation' => new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'email_confirmation']),
			'mail_confirmation' => new FormElement(['id' => 2, 'type' => 'text', 'identifier' => 'mail_confirmation']),
			'e_mail_confirmation' => new FormElement(['id' => 3, 'type' => 'text', 'identifier' => 'e_mail_confirmation']),
		];

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$conflictingFormElements,
			$this->formOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml('before');
		$this->assertStringContainsString('name="mail"', $html);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getFieldName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtmlWithAllAlternativesConflicting(): void {
		$conflictingFormElements = [
			'email_confirmation' => new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'email_confirmation']),
			'mail_confirmation' => new FormElement(['id' => 2, 'type' => 'text', 'identifier' => 'mail_confirmation']),
			'e_mail_confirmation' => new FormElement(['id' => 3, 'type' => 'text', 'identifier' => 'e_mail_confirmation']),
			'mail' => new FormElement(['id' => 4, 'type' => 'text', 'identifier' => 'mail']),
			'e_mail' => new FormElement(['id' => 5, 'type' => 'text', 'identifier' => 'e_mail']),
		];

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$conflictingFormElements,
			$this->formOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml('before');
		$this->assertStringContainsString('name="emailConfirmation"', $html);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getFieldName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtmlWithCustomElementNameConflicting(): void {
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->method('getProtectionOptions')->with('hiddenInput')->willReturn(['elementName' => 'name']);

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml('before');
		$this->assertStringContainsString('name="email_confirmation"', $html);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithEmptyHiddenField(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'email_confirmation' => '',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithMissingHiddenField(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithFilledHiddenField(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'email_confirmation' => 'spam@bot.com',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertIsString($result);
		$this->assertEquals(__d('form', 'protection_method_hidden_input_error_field_empty'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithCustomFieldName(): void {
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->method('getProtectionOptions')->with('hiddenInput')->willReturn(['elementName' => 'custom_field']);

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'custom_field' => 'filled',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('form', 'protection_method_hidden_input_error_field_empty'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithWhitespaceValue(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'email_confirmation' => '   ',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertIsString($result);
		$this->assertEquals(__d('form', 'protection_method_hidden_input_error_field_empty'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::modifyForm()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModifyForm(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$originalForm = clone $this->form;
		$this->hiddenInputFormProtection->modifyForm($this->form);

		$this->assertEquals($originalForm, $this->form);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::modifyFormEntry()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModifyFormEntry(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$formEntry = new FormEntry([
			'form_id' => $this->form->id,
			'data' => json_encode(['test' => 'data']),
		]);

		$result = $this->hiddenInputFormProtection->modifyFormEntry($formEntry);

		$this->assertSame($formEntry, $result);
	}
}
