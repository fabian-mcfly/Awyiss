<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\AltchaFormProtection;
use Awyiss\Form\Protection\FormProtectionInterface;
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
	 */
	public function testGetHtml(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$result = $this->altchaFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertNull($result);

		$result = $this->altchaFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE_SUBMIT);
		$this->assertStringContainsString('<altcha-widget', $result);
		$this->assertStringContainsString('auto="onfocus" delay="0" hideFooter="1" hideLogo="1" type="checkbox" name="_altcha"', $result);
		$this->assertStringContainsString('&quot;expiresAt&quot;:', $result);
		$this->assertStringContainsString('</altcha-widget>', $result);

		$result = $this->altchaFormProtection->getHtml(FormProtectionInterface::POSITION_AFTER);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::getHtml()
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

		$result = $this->altchaFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
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
	 */
	public function testGetHtmlWithChallengeCost(): void {
		// Mock form options with custom cost value
		// This test verifies the custom cost appears in the challenge
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->expects($this->atLeastOnce())->method('getProtectionOptions')->with('altcha')->willReturn([
			'cost' => 123456,
		]);

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$result = $this->altchaFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE_SUBMIT);

		$this->assertStringContainsString('&quot;cost&quot;:123456', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::validateData()
	 * @throws \JsonException
	 */
	public function testValidateDataWithValidAltchaResponse(): void {
		// Mock form options with lower cost for faster test execution
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->expects($this->atLeastOnce())->method('getProtectionOptions')->with('altcha')->willReturn([
			'cost' => 100,
		]);

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$altchaHtml = $this->altchaFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE_SUBMIT);

		$dom = HTMLDocument::createFromString($altchaHtml, LIBXML_NOERROR, 'UTF-8');
		$widget = $dom->querySelector('altcha-widget');
		$challengeJson = json_decode($widget->getAttribute('challenge'), true, 512, JSON_THROW_ON_ERROR);

		// Reconstruct Challenge object from the JSON
		$challengeParams = ChallengeParameters::fromArray($challengeJson['parameters']);
		$challenge = new Challenge($challengeParams, $challengeJson['signature'] ?? null);

		$altchaInstance = new Altcha(Security::getSalt());

		// Solve the challenge using the new API
		$solveChallengeOptions = new SolveChallengeOptions(
			challenge: $challenge,
			algorithm: new Pbkdf2(),
			timeout: 30.0,
		);

		$solution = $altchaInstance->solveChallenge($solveChallengeOptions);

		$this->assertNotNull($solution, 'Solution should not be null');

		// Create Payload with Challenge and Solution
		$payload = new Payload(
			challenge: $challenge,
			solution: $solution,
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'_altcha' => $payload->toBase64(),
		];

		$result = $this->altchaFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::validateData()
	 */
	public function testValidateDataWithInvalidAltchaResponse(): void {
		// Mock form options with lower cost for faster test execution
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->expects($this->atLeastOnce())->method('getProtectionOptions')->with('altcha')->willReturn([
			'cost' => 100,
		]);

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'_altcha' => 'invalid_altcha_response_token',
		];

		$result = $this->altchaFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'altcha_error'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::validateData()
	 */
	public function testValidateDataWithMissingAltchaResponse(): void {
		// Mock form options with lower cost for faster test execution
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->expects($this->atLeastOnce())->method('getProtectionOptions')->with('altcha')->willReturn([
			'cost' => 100,
		]);

		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$data = [
			'name' => 'Test User',
			'email' => 'test@example.com',
			// Missing 'altcha' field
		];

		$result = $this->altchaFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'altcha_error'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\AltchaFormProtection::modifyForm()
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
	 */
	public function testModifyFormEntry(): void {
		$this->altchaFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$formEntry = new FormEntry([
			'formId' => $this->form->id,
			'data' => json_encode(['test' => 'data']),
		]);

		$result = $this->altchaFormProtection->modifyFormEntry($formEntry);

		$this->assertSame($formEntry, $result);
	}
}
