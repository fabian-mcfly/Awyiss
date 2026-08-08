<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\FormProtectionInterface;
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
	 */
	public function testGetHtml(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$nonce = $this->view->helpers()->get('Asset')->getStyleNonce();

		$result = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertStringContainsString('<style nonce="' . $nonce . '">', $result);
		$this->assertStringContainsString('{ position:absolute; visibility:hidden; }</style><input type="email" name="emailConfirmation" value=""', $result);

		$result = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE_SUBMIT);
		$this->assertNull($result);

		$result = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_AFTER);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getFieldName()
	 */
	public function testGetHtmlWithConflictingFormElements(): void {
		$conflictingFormElements = [
			'emailConfirmation' => new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'emailConfirmation']),
			'mailConfirmation' => new FormElement(['id' => 2, 'type' => 'text', 'identifier' => 'mailConfirmation']),
			'eMailConfirmation' => new FormElement(['id' => 3, 'type' => 'text', 'identifier' => 'eMailConfirmation']),
		];

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$conflictingFormElements,
			$this->formOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertStringContainsString('name="mail"', $html);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getFieldName()
	 */
	public function testGetHtmlWithAllAlternativesConflicting(): void {
		$conflictingFormElements = [
			'emailConfirmation' => new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'emailConfirmation']),
			'mailConfirmation' => new FormElement(['id' => 2, 'type' => 'text', 'identifier' => 'mailConfirmation']),
			'eMailConfirmation' => new FormElement(['id' => 3, 'type' => 'text', 'identifier' => 'eMailConfirmation']),
			'mail' => new FormElement(['id' => 4, 'type' => 'text', 'identifier' => 'mail']),
		];

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$conflictingFormElements,
			$this->formOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertStringContainsString('name="eMail"', $html);

		$conflictingFormElements = [
			'emailConfirmation' => new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'emailConfirmation']),
			'mailConfirmation' => new FormElement(['id' => 2, 'type' => 'text', 'identifier' => 'mailConfirmation']),
			'eMailConfirmation' => new FormElement(['id' => 3, 'type' => 'text', 'identifier' => 'eMailConfirmation']),
			'mail' => new FormElement(['id' => 4, 'type' => 'text', 'identifier' => 'mail']),
			'eMail' => new FormElement(['id' => 5, 'type' => 'text', 'identifier' => 'eMail']),
		];

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$conflictingFormElements,
			$this->formOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertStringContainsString('name="_email"', $html);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::getFieldName()
	 */
	public function testGetHtmlWithCustomElementNameConflicting(): void {
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->expects($this->atLeastOnce())->method('getProtectionOptions')->with('hiddenInput')->willReturn(['elementName' => 'name']);

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$html = $this->hiddenInputFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertStringContainsString('name="emailConfirmation"', $html);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
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
			'emailConfirmation' => '',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
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
			'emailConfirmation' => 'spam@bot.com',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'protection_method_hidden_input_error_field_empty'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
	 */
	public function testValidateDataWithCustomFieldName(): void {
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->expects($this->atLeastOnce())->method('getProtectionOptions')->with('hiddenInput')->willReturn(['elementName' => 'customField']);

		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'customField' => 'filled',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'protection_method_hidden_input_error_field_empty'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::validateData()
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
			'emailConfirmation' => '   ',
		];

		$result = $this->hiddenInputFormProtection->validateData($data);
		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'protection_method_hidden_input_error_field_empty'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\HiddenInputFormProtection::modifyForm()
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
	 */
	public function testModifyFormEntry(): void {
		$this->hiddenInputFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$formEntry = new FormEntry([
			'formId' => $this->form->id,
			'data' => json_encode(['test' => 'data']),
		]);

		$result = $this->hiddenInputFormProtection->modifyFormEntry($formEntry);

		$this->assertSame($formEntry, $result);
	}
}
