<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Hasher\Algorithm;
use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\AltchaFormProtection;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;
use Dom\HTMLDocument;


/**
 * AltchaFormProtection Test Case
 *
 * @see \Awyiss\Form\Protection\AltchaFormProtection
 */
class AltchaFormProtectionTest extends TestCase {
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\Form\Protection\AltchaFormProtection
	 */
	protected AltchaFormProtection $altchaFormProtection;
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
			new FormElement(['id' => 1, 'type' => 'text', 'identifier' => 'name']),
			new FormElement(['id' => 2, 'type' => 'email', 'identifier' => 'email']),
		];
		$this->formOptions = new FormOptions($this->form);
		$this->view = new FrontendView();

		$this->altchaFormProtection = new AltchaFormProtection();
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up any test data
		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntriesTable->deleteAll(['id >' => 3]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialize(): void {
		$result = $this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$this->assertSame($this->altchaFormProtection, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::getHtml()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtml(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$result = $this->altchaFormProtection->getHtml('before');
		$this->assertNull($result);

		$result = $this->altchaFormProtection->getHtml('before_submit');
		$this->assertStringContainsString('<altcha-widget', $result);
		$this->assertStringContainsString('auto="onfocus" delay="0" hidefooter="1" name="_altcha"', $result);
		$this->assertStringContainsString('?expires=', $result);
		$this->assertStringContainsString('</altcha-widget>', $result);

		$result = $this->altchaFormProtection->getHtml('after');
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::getHtml()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtmlBeforeAddsScriptToAssetHelper(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		/** @var \Awyiss\View\Helper\AssetHelper $assetHelper */
		$assetHelper = $this->view->helpers()->get('Asset');
		$assetHelper->clearAssets();

		$this->assertEmpty($assetHelper->getAssets()['js']['nonCritical']);

		$result = $this->altchaFormProtection->getHtml('before');
		$this->assertNull($result);

		$this->assertNotEmpty($assetHelper->getAssets()['js']['nonCritical']);
		$this->assertArrayHasKey('Frontend/Captcha/altcha.i18n.js', $assetHelper->getAssets()['js']['nonCritical']);

		$altchaJs = $assetHelper->getAssets()['js']['nonCritical']['Frontend/Captcha/altcha.i18n.js'];
		$this->assertSame([
			'minified' => true,
			'critical' => false,
			'attributes' => [
				'type' => 'module',
			],
			'priority' => 10,
			'realm' => 'Backend',
		], $altchaJs);
	}



	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::getHtml()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHtmlWithCustomComplexityAndMaxNumber(): void {
		// Mock form options with custom complexity and maxNumber
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->method('getProtectionOptions')->with('altcha')->willReturn([
			'maxNumber' => 123456,
		]);

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$result = $this->altchaFormProtection->getHtml('before_submit');

		$this->assertStringContainsString('&quot;maxNumber&quot;:123456', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::validateData()
	 * @throws \JsonException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithValidAltchaResponse(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$altcha = $this->altchaFormProtection->getHtml('before_submit');

		$dom = HTMLDocument::createFromString($altcha, LIBXML_NOERROR, 'UTF-8');
		$widget = $dom->querySelector('altcha-widget');
		$challenge = json_decode($widget->getAttribute('challengejson'), true, 512, JSON_THROW_ON_ERROR);

		$altcha = new Altcha(Security::getSalt());
		$solution = $altcha->solveChallenge($challenge['challenge'], $challenge['salt'], Algorithm::SHA256, $challenge['maxNumber']);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'_altcha' => base64_encode(json_encode([
				'algorithm' => 'SHA-256',
				'challenge' => $challenge['challenge'],
				'number' => $solution->number,
				'salt' => $challenge['salt'],
				'signature' => $challenge['signature'],
				'took' => 1234,
			])),
		];

		$result = $this->altchaFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithInvalidAltchaResponse(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'_altcha' => 'invalid_altcha_response_token',
		];

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$result = $this->altchaFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('form', 'altcha_error'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithMissingAltchaResponse(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			// Missing 'altcha' field
		];

		$result = $this->altchaFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('form', 'altcha_error'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::modifyForm()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModifyForm(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$originalForm = clone $this->form;
		$this->altchaFormProtection->modifyForm($this->form);

		$this->assertEquals($originalForm, $this->form);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::modifyFormEntry()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModifyFormEntry(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$formEntry = new FormEntry([
			'form_id' => $this->form->id,
			'data' => json_encode(['test' => 'data']),
		]);

		$result = $this->altchaFormProtection->modifyFormEntry($formEntry);

		$this->assertSame($formEntry, $result);
	}
}
