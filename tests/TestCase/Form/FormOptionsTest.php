<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form;


use Awyiss\Form\FormConditionalRecipients;
use Awyiss\Form\FormOptions;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Awyiss\View\FrontendView;


/**
 * FormOptions Test Case
 *
 * @see \Awyiss\Form\FormOptions
 */
class FormOptionsTest extends TestCase {
	/**
	 * @var \Awyiss\Form\FormOptions
	 */
	protected FormOptions $formOptions;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$this->formOptions = new FormOptions($form);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorSetsSafeRealSender(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);

		$formOptions = new FormOptions($form);
		$safeRealSender = $formOptions->getSafeRealSender();

		$this->assertSame('noreply@localhost', $safeRealSender);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::getSafeRealSender()
	 * @see \Awyiss\Form\FormOptions::setSafeRealSender()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetSafeRealSender(): void {
		$testEmail = 'test@example.com';
		$result = $this->formOptions->setSafeRealSender($testEmail);

		$this->assertSame($this->formOptions, $result);
		$this->assertSame($testEmail, $this->formOptions->getSafeRealSender());

		$this->formOptions->setSafeRealSender(null);
		$this->assertNull($this->formOptions->getSafeRealSender());
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithRequiredElement(): void {
		$validator = new Validator();
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$result = $form->getFormOptions()->setValidationRules($validator);

		$this->assertSame($validator, $result);
		$this->assertTrue($validator->hasField('anrede'));
		$this->assertFalse($validator->isPresenceRequired('anrede', true));
		$this->assertTrue($validator->hasField('email'));
		$this->assertTrue($validator->isPresenceRequired('email', true));
		$this->assertTrue($validator->hasField('datenschutz_akzeptiert'));
		$this->assertTrue($validator->isPresenceRequired('datenschutz_akzeptiert', true));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithEmailElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('email'));
		$this->assertFalse($validator->field('email')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('email')->rule('email'));
		$this->assertSame('email', $validator->field('email')->rule('email')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithDateElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('datum'));
		$this->assertTrue($validator->field('datum')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('datum')->rule('date'));
		$this->assertSame('date', $validator->field('datum')->rule('date')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithTimeElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('uhrzeit'));
		$this->assertTrue($validator->field('uhrzeit')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('uhrzeit')->rule('time'));
		$this->assertSame('time', $validator->field('uhrzeit')->rule('time')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithDateTimeElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('datum_und_uhrzeit'));
		$this->assertTrue($validator->field('datum_und_uhrzeit')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('datum_und_uhrzeit')->rule('datetime'));
		$this->assertSame('datetime', $validator->field('datum_und_uhrzeit')->rule('datetime')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithRadioElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('multi_radio'));
		$this->assertTrue($validator->field('multi_radio')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('multi_radio')->rule('inList'));
		$this->assertSame('inList', $validator->field('multi_radio')->rule('inList')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithSelectElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('select'));
		$this->assertTrue($validator->field('select')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('select')->rule('inList'));
		$this->assertSame('inList', $validator->field('select')->rule('inList')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithSelectMultipleElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('multi_select'));
		$this->assertTrue($validator->field('multi_select')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('multi_select')->rule('inList'));
		$this->assertIsCallable($validator->field('multi_select')->rule('inList')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithSingleCheckboxElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('datenschutz_akzeptiert'));
		$this->assertTrue($validator->field('datenschutz_akzeptiert')->isPresenceRequired());
		$this->assertFalse($validator->field('datenschutz_akzeptiert')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('datenschutz_akzeptiert')->rule('inList'));
		$this->assertSame('inList', $validator->field('datenschutz_akzeptiert')->rule('inList')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setValidationRules()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetValidationRulesWithMultipleCheckboxElement(): void {
		$validator = new Validator();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->getFormOptions()->setValidationRules($validator);

		$this->assertTrue($validator->hasField('multi_checkbox'));
		$this->assertTrue($validator->field('multi_checkbox')->isEmptyAllowed());
		$this->assertNotEmpty($validator->field('multi_checkbox')->rule('inList'));
		$this->assertIsCallable($validator->field('multi_checkbox')->rule('inList')->get('rule'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::modifyForm()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModifyForm(): void {
		$result = $this->formOptions->modifyForm();

		$this->assertSame($this->formOptions, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::modifyFormElement()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModifyFormElement(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$formElement = $form->formElements->first();

		$result = $form->getFormOptions()->modifyFormElement($formElement);

		$this->assertSame($form->getFormOptions(), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetConditionalRecipientWithFirstMatchingConditionalRecipients(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$form->conditionalRecipientsStrategy = FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST;

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy1@domain.com', $form->ownerEmail);

		$form->setFormData([
			'vorname' => 'Not John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy2@domain.com', $form->ownerEmail);

		$form->setFormData([
			'vorname' => 'Not John',
			'nachname' => 'Not Doe',
			'email' => 'dummy@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy3@domain.com', $form->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetConditionalRecipientWithLastMatchingConditionalRecipients(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->setFormData([
			'vorname' => 'Not John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$form->conditionalRecipientsStrategy = FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST;

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy3@domain.com', $form->ownerEmail);

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'other@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy2@domain.com', $form->ownerEmail);

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy3@domain.com', $form->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetConditionalRecipientWithAllMatchingConditionalRecipients(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$form->conditionalRecipientsStrategy = FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL;

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('johnsdummy3@domain.com', $form->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetConditionalRecipientWithNotMatchingConditionalRecipients(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->setFormData([
			'vorname' => 'Not John',
			'nachname' => 'Not Doe',
			'email' => 'other@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetConditionalRecipientWithNoneMatchingConditionalRecipients(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);
		$form->initialize(new FrontendView());

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'other@domain.com',
		]);

		$form->conditionalRecipientsStrategy = FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL;

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);

		$form->setFormData([
			'vorname' => 'Not John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Not Doe',
			'email' => 'dummy@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertNotEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetConditionalRecipientWithoutConditionalRecipients(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(4);
		$form->initialize(new FrontendView());

		$form->setFormData([
			'vorname' => 'John',
			'nachname' => 'Doe',
			'email' => 'dummy@domain.com',
		]);

		$result = $form->getFormOptions()->setConditionalRecipient();

		$this->assertEmpty($form->formConditionalRecipients);

		$this->assertSame($form->getFormOptions(), $result);
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormOptions::getProtectionOptions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetProtectionOptions(): void {
		$result = $this->formOptions->getProtectionOptions('test_identifier');

		$this->assertNull($result);
	}
}
