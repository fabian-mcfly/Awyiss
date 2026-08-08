<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\View\FrontendView;
use Cake\Http\Exception\RedirectException;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;


/**
 * Test case for FormRenderer class
 *
 * @see \Awyiss\Utility\Form\FormRenderer
 */
class FormRendererTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->loadRoutes();

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		$request = new ServerRequest([
			'url' => '/de/contact',
			'params' => [
				'lang' => 'de',
				'slug' => 'contact',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->view = new FrontendView($request);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormByIdentifier()
	 */
	public function testGetFormByIdentifierWithIdReturnsForm(): void {
		$renderer = new FormRenderer($this->view);
		$result = $renderer->getFormByIdentifier(1);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals('contact', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormByIdentifier()
	 */
	public function testGetFormByIdentifierWithStringIdentifierReturnsForm(): void {
		$renderer = new FormRenderer($this->view);
		$result = $renderer->getFormByIdentifier('contact');

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals('contact', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormByIdentifier()
	 */
	public function testGetFormByIdentifierReturnsNullIfInactive(): void {
		$renderer = new FormRenderer($this->view);
		$result = $renderer->getFormByIdentifier('contact3');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see\Awyiss\Utility\Form\FormRenderer::getFormByIdentifier()
	 */
	public function testGetFormByIdentifierReturnsFormInPreviewModeIfInactive(): void {
		/** @var \Awyiss\Utility\Form\FormRenderer|\PHPUnit\Framework\MockObject\Stub $renderer */
		$renderer = $this->getStubBuilder(FormRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getStub();
		$renderer->method('isPreview')->willReturn(true);

		$result = $renderer->getFormByIdentifier('contact3');

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals('contact3', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormByIdentifier()
	 */
	public function testGetFormByIdentifierReturnsNullIfNotFound(): void {
		$renderer = new FormRenderer($this->view);
		$result = $renderer->getFormByIdentifier('unknown_form');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 */
	public function testInitFormInitializesForm(): void {
		$renderer = new FormRenderer($this->view);

		// Test with an integer identifier
		$result = $renderer->initForm(1, []);

		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Form::class, $renderer->getForm());
		$this->assertEquals('contact', $renderer->getForm()->identifier);

		// Test with an invalid identifier
		$renderer->initForm('unknown_form', []);

		$this->assertNull($renderer->getForm());

		// Test with a string identifier
		$result = $renderer->initForm('contact', []);

		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Form::class, $renderer->getForm());
		$this->assertEquals('contact', $renderer->getForm()->identifier);

		// Test with a form entity
		$form = $this->fetchTable('Forms')->get(1);
		$renderer->initForm($form, []);

		$this->assertInstanceOf(Form::class, $renderer->getForm());
		$this->assertEquals('contact', $renderer->getForm()->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	public function testInitFormInitializesFormWithValidIdentifier(): void {
		$renderer = new FormRenderer($this->view);

		$result = $renderer->initForm('contact', []);

		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Form::class, $renderer->getForm());
		$this->assertEquals('contact', $renderer->getForm()->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	public function testInitFormWithInvalidIdentifier(): void {
		$renderer = new FormRenderer($this->view);

		$result = $renderer->initForm('nonexistent_form', []);

		$this->assertSame($renderer, $result);
		$this->assertNull($renderer->getForm());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	public function testInitFormWithInactiveFormReturnsNull(): void {
		$renderer = new FormRenderer($this->view);
		$result = $renderer->initForm('contact3', []);
		$this->assertSame($renderer, $result);
		$this->assertNull($renderer->getForm());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	public function testInitFormWithInactiveFormInPreviewMode(): void {
		/** @var \Awyiss\Utility\Form\FormRenderer|\PHPUnit\Framework\MockObject\Stub $renderer */
		$renderer = $this->getStubBuilder(FormRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getStub();
		$renderer->method('isPreview')->willReturn(true);

		$result = $renderer->initForm('contact3', []);
		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Form::class, $renderer->getForm());
		$this->assertEquals('contact3', $renderer->getForm()->identifier);
	}


	/**
	 * @param bool $previewMode
	 * @param int $expectedQuestions
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	#[TestWith([false, 18])]
	#[TestWith([true, 19])]
	public function testInitFormPassesPreviewMode(bool $previewMode, int $expectedQuestions): void {
		/** @var \Awyiss\Utility\Form\FormRenderer|\PHPUnit\Framework\MockObject\Stub $renderer */
		$renderer = $this->getStubBuilder(FormRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getStub();
		$renderer->method('isPreview')->willReturn($previewMode);

		$renderer->initForm('contact', []);

		$form = $renderer->getForm();
		$formElements = $form->getFormElements()->listNested();

		$this->assertCount($expectedQuestions, $formElements);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	public function testInitFormSetsFormDataWhenIdentifierMatches(): void {
		$renderer = new FormRenderer($this->view);

		$requestData = [
			'_formIdentifier' => 'contact',
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		];

		$renderer->initForm(1, $requestData);

		$this->assertEquals($requestData, $renderer->getForm()->getFormData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::initForm()
	 * @throws \Exception
	 */
	public function testInitFormNotSetsFormDataWhenIdentifierNotMatches(): void {
		$renderer = new FormRenderer($this->view);

		$requestData = [
			'_formIdentifier' => 'contact2',
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		];

		$renderer->initForm(1, $requestData);

		$this->assertEquals([], $renderer->getForm()->getFormData());
	}


	/**
	 * @return void
	 * @see \Customer\Form\Contact4FormOptions::modifyForm()
	 * @throws \Exception
	 */
	public function testInitFormCallsModifyFormOnFormOptions(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(4);
		$this->assertSame('contact4', $form->identifier);

		$renderer = new FormRenderer($this->view);

		$renderer->initForm(4, []);
		$form = $renderer->getForm();

		$this->assertSame('newContact4', $form->identifier);
	}


	/**
	 * @return void
	 * @see \Customer\Form\Contact4FormOptions::setConditionalRecipient()
	 * @throws \Exception
	 */
	public function testInitFormSetsConditionalRecipientOnlyWhenSubmitted(): void {
		$renderer = new FormRenderer($this->view);

		// Simulate a form submission with data that matches the conditional recipient
		$requestData = [
			'_formIdentifier' => 'contact4',
			'email' => 'importantclient@example.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		];

		$renderer->initForm('contact4', $requestData);
		$form = $renderer->getForm();

		$this->assertTrue($form->isSubmitted());
		$this->assertSame('importantclient@cms.de', $form->ownerEmail);

		$renderer = new FormRenderer($this->view);

		// Simulate a form submission with data that matches the conditional recipient
		$requestData = [
			'_formIdentifier' => 'contact3',
			'email' => 'importantclient@example.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		];

		$renderer->initForm('contact4', $requestData);
		$form = $renderer->getForm();

		// Assert that the form is not submitted
		$this->assertFalse($form->isSubmitted());
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(4);
		// Assert that the form's ownerEmail is unchanged
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);

		$renderer = new FormRenderer($this->view);

		// Simulate a form submission with data that matches the conditional recipient
		$requestData = [
			'_formIdentifier' => 'contact4',
			'email' => 'domain@example.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		];

		$renderer->initForm('contact4', $requestData);
		$form = $renderer->getForm();

		$this->assertTrue($form->isSubmitted());
		// If the condition is not met, the ownerEmail should remain unchanged
		$this->assertSame('awyiss@cms.de', $form->ownerEmail);
	}


	/**
	 * Test process with no form initialized
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::process()
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function testProcessWithNoForm(): void {
		$renderer = new FormRenderer($this->view);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form was initialized.');

		$renderer->process();
	}


	/**
	 * Test process with form not submitted
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::process()
	 * @throws \Exception
	 */
	public function testProcessFormNotSubmitted(): void {
		// Mock sendAndRedirect to avoid RedirectException
		$renderer = $this->getMockBuilder(FormRenderer::class)->setConstructorArgs([$this->view])->onlyMethods(['processFormEntryFromHash', 'sendAndRedirect'])->getMock();

		$renderer->expects($this->never())->method('processFormEntryFromHash');
		$renderer->expects($this->never())->method('sendAndRedirect');

		$renderer->initForm(1, [
			'_formIdentifier' => 'contact2', // Other Form's identifier to simulate not submitted
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		]);

		$renderer->process();
	}


	/**
	 * Test process with form submitted but invalid
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::process()
	 * @throws \Exception
	 */
	public function testProcessInvalidSubmission(): void {
		// Mock sendAndRedirect to avoid RedirectException
		$renderer = $this->getMockBuilder(FormRenderer::class)->setConstructorArgs([$this->view])->onlyMethods(['processFormEntryFromHash', 'sendAndRedirect'])->getMock();

		$renderer->expects($this->never())->method('processFormEntryFromHash');
		$renderer->expects($this->never())->method('sendAndRedirect');

		$renderer->initForm(1, [
			'_formIdentifier' => 'contact',
			'email' => 'no-email',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		]);

		$renderer->process();

		$this->assertFalse($renderer->getForm()->isValid());
	}


	/**
	 * Test process with form submitted but invalid
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::process()
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 * @throws \Exception
	 */
	public function testProcessValidSubmission(): void {
		// Mock sendAndRedirect to avoid RedirectException
		$renderer = $this->getMockBuilder(FormRenderer::class)->setConstructorArgs([$this->view])->onlyMethods(['sendAndRedirect'])->getMock();

		$renderer->expects($this->once())->method('sendAndRedirect');

		$renderer->initForm(1, [
			'_formIdentifier' => 'contact',
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		]);

		$renderer->process();

		$this->assertTrue($renderer->getForm()->isValid());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testGetFormBodyIsEmptyForEmptyElements(): void {
		$renderer = new FormRenderer($this->view);
		$renderer->initForm(5, [
			'_formIdentifier' => 'contact',
		]);

		$renderer->process();

		$formBody = $renderer->getFormBody([]);

		$this->assertSame('', $formBody);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testGetFormBody(): void {
		$renderer = new FormRenderer($this->view);

		$renderer->initForm(1, []);

		$renderer->process();

		$formBody = $renderer->getFormBody([]);

		$formBody = trim(preg_replace('/\s+/', ' ', $formBody));
		$formBody = str_replace('> ', '>' . PHP_EOL, $formBody);
		$formBody = str_replace('> ', '>' . PHP_EOL, $formBody);
		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'FormBody-Contact.txt', $formBody);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testGetFormBodyRepopulatesInputs(): void {
		$renderer = new FormRenderer($this->view);

		$renderer->initForm(1, [
			'_formIdentifier' => 'contact',
			'email' => 'dummy@example.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
		]);

		$renderer->process();

		$formBody = $renderer->getFormBody([]);
		$formBody = trim(preg_replace('/\s+/', ' ', $formBody));
		$this->assertStringContainsString('<input id="FormInput2" type="text" name="vorname" value="Max" required class="FormInputType-Input">', $formBody);
		$this->assertStringContainsString('<input id="FormInput3" type="text" name="nachname" value="Mustermann" required class="FormInputType-Input">', $formBody);
		$this->assertStringContainsString('<input id="FormInput6" type="email" name="email" value="dummy@example.com" required class="FormInputType-Input">', $formBody);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testGetFormBodyErrorFields(): void {
		$renderer = new FormRenderer($this->view);

		$renderer->initForm(1, [
			'_formIdentifier' => 'contact',
			'email' => 'dummy@example.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
		]);

		$renderer->process();

		$formBody = $renderer->getFormBody([]);

		$this->assertStringContainsString(
			'<div class="FormElement Column-100 FormElementType-Checkbox FormElement-DatenschutzAkzeptiert FormElement-IsInvalid" id="FormElement9">',
			$formBody
		);
		$this->assertStringContainsString('<div class="FormElement-Error">Form::error_required</div>', $formBody);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testGetFormBodySetsEnctypeIfFileInputExists(): void {
		$renderer = new FormRenderer($this->view);

		$renderer->initForm(1, []);

		$renderer->process();

		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertNull($renderer->getForm()->enctype);

		$renderer->getFormBody([]);

		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame('multipart/form-data', $renderer->getForm()->enctype);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @see \Awyiss\Utility\Form\FormRenderer::parseAwyissImageTags()
	 * @throws \Exception
	 * @throws \ReflectionException
	 * @noinspection HtmlUnknownTarget
	 */
	public function testGetFormBodyReplacesAwyissImageTagInFreeTextElement(): void {
		// Free text in form 1 is inactive. So force preview mode
		$renderer = $this->getStubBuilder(FormRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getStub();
		$renderer->method('isPreview')->willReturn(true);

		$renderer->initForm(1, []);

		$renderer->process();

		$formBody = $renderer->getFormBody([]);

		$this->assertStringContainsString('<div class="FormInputType-FreeText"><p>Form element with inline img tag</p><p><picture>', $formBody);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1536].avif" alt="logo-awyiss.png"', $formBody);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1536].avif" alt="logo-awyiss.png"', $formBody);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p></div>', $formBody);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @see \Awyiss\Utility\Form\FormRenderer::parseAwyissImageTags()
	 * @throws \Exception
	 * @throws \ReflectionException
	 * @noinspection HtmlUnknownTarget
	 */
	public function testGetFormBodyReplacesAwyissImageTagInFreeTextElementWithBaseWidth(): void {
		// Free text in form 1 is inactive. So force preview mode
		$renderer = $this->getStubBuilder(FormRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getStub();
		$renderer->method('isPreview')->willReturn(true);

		$renderer->initForm(1, []);

		$renderer->process();

		$formBody = $renderer->getFormBody([
			'fullWidth' => 1440.00,
		]);

		$this->assertStringContainsString(
			'<img data-src="_resized/dummypath/logo-awyiss-[w1152].avif" alt="logo-awyiss.png"',
			$formBody
		);
		$this->assertStringContainsString(
			'<noscript><img src="_resized/dummypath/logo-awyiss-[w1152].avif" alt="logo-awyiss.png"',
			$formBody
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @see \Awyiss\Utility\Form\FormRenderer::parseAwyissImageTags()
	 * @throws \Exception
	 * @throws \ReflectionException
	 * @noinspection HtmlUnknownTarget
	 */
	public function testGetFormBodyReplacesAwyissImageTagInFreeTextElementWithColumnWidth(): void {
		// Free text in form 1 is inactive. So force preview mode
		$renderer = $this->getStubBuilder(FormRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getStub();
		$renderer->method('isPreview')->willReturn(true);

		$renderer->initForm(1, []);

		$renderer->process();

		$formBody = $renderer->getFormBody([
			'fullWidth' => 1440.00,
			'columnWidth' => 50.00,
		]);

		$this->assertStringContainsString(
			'<img data-src="_resized/dummypath/logo-awyiss-[w576].avif" data-srcset="_resized/dummypath/logo-awyiss-[w1152].avif 2x" alt="logo-awyiss.png"',
			$formBody
		);
		$this->assertStringContainsString(
			'<noscript><img src="_resized/dummypath/logo-awyiss-[w576].avif" srcset="_resized/dummypath/logo-awyiss-[w1152].avif 2x" alt="logo-awyiss.png"',
			$formBody
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::getFormBody()
	 * @see \Awyiss\Utility\Form\FormRenderer::parseWidgets()
	 * @throws \Exception
	 * @throws \ReflectionException
	 * @noinspection HtmlUnknownTarget
	 */
	public function testGetFormBodyParsesWidgetInFreeTextElement(): void {
		// Free text in form 1 is inactive. So force preview mode
		$renderer = new FormRenderer($this->view);

		$renderer->initForm(2, []);

		$form = $renderer->getForm();
		$formElements = $form->getFormElements();
		$freeText = $formElements->first();
		$freeText->text = '<p>Content with widget:</p><widget class="mceNonEditable" data-identifier="test" data-label="Testwidget">{"key":"value"}</widget>';

		$renderer->process();

		$formBody = $renderer->getFormBody([]);

		$this->assertStringNotContainsString('<widget', $formBody);
		$this->assertStringContainsString('Rendered Output (and key is `value`)', $formBody);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::loadFormEntryFromHash()
	 * @throws \Exception
	 */
	public function testLoadFormEntryFromHash(): void {
		$renderer = new FormRenderer($this->view);

		// Initialize the form with a valid identifier
		$renderer->initForm(1, []);

		// Use an invalid form entry hash
		$result = $renderer->loadFormEntryFromHash('invalid-hash-1234567890abcdef');

		// Assert that the result is null since the hash is invalid
		$this->assertNull($result);

		// Use a valid form entry hash
		$result = $renderer->loadFormEntryFromHash('aa43b23308dd6bdff9edb15deb2b3b41');

		// Assert that the result is an instance of FormEntry
		$this->assertInstanceOf(FormEntry::class, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::processFormEntry()
	 * @throws \Exception
	 */
	public function testProcessFormEntry(): void {
		$renderer = new FormRenderer($this->view);

		// Initialize the form with a valid identifier
		$renderer->initForm(1, []);

		$this->assertStringContainsString('Wir werden uns zeitnah mit Ihnen, {{$vorname $nachname|lieber Kunde oder Kunde-to-be}},', $renderer->getForm()->successMessage);

		// Load a valid form entry
		$entry = $renderer->loadFormEntryFromHash('aa43b23308dd6bdff9edb15deb2b3b41');

		// Process the form entry
		$result = $renderer->processFormEntry($entry);

		// Assert that the result is the renderer itself
		$this->assertSame($renderer, $result);

		// Assert that the form is marked as sent
		$this->assertTrue($renderer->isSent());

		// Assert that the form data is set correctly
		$this->assertSame([
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'email' => 'dummy@domain.tld',
		], $renderer->getForm()->getFormData());

		$this->assertStringNotContainsString('Wir werden uns zeitnah mit Ihnen, {{$vorname $nachname|lieber Kunde oder Kunde-to-be}},', $renderer->getForm()->successMessage);
		$this->assertStringContainsString('Wir werden uns zeitnah mit Ihnen, Max Mustermann,', $renderer->getForm()->successMessage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::processFormEntryFromHash()
	 * @throws \Exception
	 */
	public function testProcessFormEntryFromHash(): void {
		$renderer = new FormRenderer($this->view);

		// Initialize the form with a valid identifier
		$renderer->initForm(1, []);

		// Use an invalid form entry hash
		$result = $renderer->processFormEntryFromHash('invalid-hash-1234567890abcdef');

		// Assert that the form is not marked as sent
		$this->assertNull($renderer->isSent());

		// Assert that the result is the renderer itself
		$this->assertSame($renderer, $result);

		// Use a valid form entry hash
		$result = $renderer->processFormEntryFromHash('aa43b23308dd6bdff9edb15deb2b3b41');

		// Assert that the result is the renderer itself
		$this->assertSame($renderer, $result);

		// Assert that the form is marked as sent
		$this->assertTrue($renderer->isSent());

		// Assert that the form data is set correctly
		$this->assertSame([
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'email' => 'dummy@domain.tld',
		], $renderer->getForm()->getFormData());

		$this->assertStringNotContainsString('Wir werden uns zeitnah mit Ihnen, {{$vorname $nachname|lieber Kunde oder Kunde-to-be}},', $renderer->getForm()->successMessage);
		$this->assertStringContainsString('Wir werden uns zeitnah mit Ihnen, Max Mustermann,', $renderer->getForm()->successMessage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendForm()
	 */
	public function testSendFormWithNoForm(): void {
		$renderer = new FormRenderer($this->view);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form was initialized.');

		$renderer->sendForm();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendForm()
	 * @throws \Exception
	 */
	public function testSendFormWithNoSubmission(): void {
		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, []);

		$result = $renderer->sendForm();

		$this->assertFalse($result);
		$this->assertNull($renderer->isSent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendForm()
	 * @throws \Exception
	 */
	public function testSendForm(): void {
		$formData = [
			'_formIdentifier' => 'contact',
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, $formData);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$renderer->getForm()->submitted(true);

		$result = $renderer->sendForm();

		$this->assertIsString($result);
		$this->assertEquals(32, strlen($result));
		$this->assertTrue($renderer->isSent());

		// Getting the form entry by hash should return the same entry
		$entry = $renderer->loadFormEntryFromHash($result);
		$this->assertInstanceOf(FormEntry::class, $entry);
		$this->assertIsString($entry->data);

		$data = json_decode(gzuncompress(base64_decode($entry->data)), true);
		$this->assertIsArray($data);

		unset($formData['_formIdentifier']);
		$this->assertSame($formData, $data);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 */
	public function testSendAndRedirectWithNoForm(): void {
		$renderer = new FormRenderer($this->view);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form was initialized.');

		$renderer->sendAndRedirect();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 * @throws \Exception
	 */
	public function testSendAndRedirectWithNoSubmission(): void {
		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, []);

		$renderer->sendAndRedirect();

		$this->assertNull($renderer->isSent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 * @throws \Exception
	 */
	public function testSendAndRedirect(): void {
		$formData = [
			'_formIdentifier' => 'contact',
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, $formData);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$renderer->getForm()->submitted(true);

		// Expect the RedirectException to be thrown
		$this->expectException(RedirectException::class);
		$this->expectExceptionCode(302);
		// Must match the redirect URL in the form of `http://localhost/de/contact/form-entry:<hash>/#Form-Contact`
		$this->expectExceptionMessageMatches('/^http:\/\/localhost\/de\/contact\/form-entry:[a-f0-9]{32}\/#Form-Contact$/');
		$renderer->sendAndRedirect();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 * @throws \Exception
	 */
	public function testSendAndRedirectForRouteFrontendLanguageRoot(): void {
		$request = new ServerRequest([
			'url' => '/de',
			'params' => [
				'lang' => 'de',
				'slug' => '',
				'_name' => 'FrontendLanguageRoot',
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$formData = [
			'_formIdentifier' => 'contact',
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, $formData);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$renderer->getForm()->submitted(true);

		// Expect the RedirectException to be thrown
		$this->expectException(RedirectException::class);
		$this->expectExceptionCode(302);
		// Must match the redirect URL in the form of `http://localhost/de/form-entry:<hash>/#Form-Contact`
		$this->expectExceptionMessageMatches('/^http:\/\/localhost\/de\/form-entry:[a-f0-9]{32}\/#Form-Contact$/');
		$renderer->sendAndRedirect();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 * @throws \Exception
	 */
	public function testSendAndRedirectForRouteFrontendRoot(): void {
		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => '',
				'_name' => 'FrontendRoot',
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$formData = [
			'_formIdentifier' => 'contact',
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, $formData);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$renderer->getForm()->submitted(true);

		// Expect the RedirectException to be thrown
		$this->expectException(RedirectException::class);
		$this->expectExceptionCode(302);
		// Must match the redirect URL in the form of `http://localhost/form-entry:<hash>/#Form-Contact`
		$this->expectExceptionMessageMatches('/^http:\/\/localhost\/de\/form-entry:[a-f0-9]{32}\/#Form-Contact$/');
		$renderer->sendAndRedirect();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormRenderer::sendAndRedirect()
	 * @throws \Exception
	 */
	public function testSendAndRedirectForRouteFrontendFormAntiSpamPost(): void {
		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => '',
				'_name' => 'FrontendFormAntiSpamPost',
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$formData = [
			'_formIdentifier' => 'contact',
			'email' => 'awyiss@cms.de',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'datenschutzAkzeptiert' => 'Ja',
		];

		$renderer = new FormRenderer($this->view);
		$renderer->initForm(1, $formData);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$renderer->getForm()->submitted(true);

		// Expect the RedirectException to be thrown
		$this->expectException(RedirectException::class);
		$this->expectExceptionCode(302);
		// Must match the redirect URL in the form of `http://localhost/de/_form/<hash>/#Form-Contact`
		$this->expectExceptionMessageMatches('/^http:\/\/localhost\/de\/_form\/[a-f0-9]{32}\/#Form-Contact$/');
		$renderer->sendAndRedirect();
	}
}
