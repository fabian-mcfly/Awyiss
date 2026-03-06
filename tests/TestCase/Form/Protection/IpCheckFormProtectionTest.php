<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\FormProtectionInterface;
use Awyiss\Form\Protection\IpCheckFormProtection;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;


/**
 * IpCheckFormProtection Test Case
 *
 * @see \Awyiss\Form\Protection\IpCheckFormProtection
 */
class IpCheckFormProtectionTest extends TestCase {
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\Form\Protection\IpCheckFormProtection
	 */
	protected IpCheckFormProtection $ipCheckFormProtection;
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

		$this->ipCheckFormProtection = new IpCheckFormProtection();

		$request = new ServerRequest([
			'environment' => [
				'REMOTE_ADDR' => '192.168.1.100',
			],
		]);
		Router::setRequest($request);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntriesTable->deleteAll(['id >' => 3]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::initialize()
	 */
	public function testInitialize(): void {
		$result = $this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$this->assertSame($this->ipCheckFormProtection, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::getHtml()
	 */
	public function testGetHtml(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$result = $this->ipCheckFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertNull($result);

		$result = $this->ipCheckFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE_SUBMIT);
		$this->assertNull($result);

		$result = $this->ipCheckFormProtection->getHtml(FormProtectionInterface::POSITION_AFTER);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::validateData()
	 */
	public function testValidateDataWithNoExistingEntries(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->ipCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::validateData()
	 */
	public function testValidateDataWithExistingRecentEntry(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$request = Router::getRequest();
		$clientIp = $request->clientIp();
		$ipHash = Security::hash($clientIp . Security::getSalt());

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'ipHash' => $ipHash,
			'data' => json_encode(['test' => 'data']),
			'postHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$result = $formEntriesTable->save($formEntry);

		$this->assertNotFalse($result);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->ipCheckFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'protection_method_ip_check_error_duplicate_found'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::validateData()
	 */
	public function testValidateDataWithExistingEntryAndCustomTimeout(): void {
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions->method('getProtectionOptions')->with('ipCheck')->willReturn(['checkTimeout' => 1200]);

		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$request = Router::getRequest();
		$clientIp = $request->clientIp();
		$ipHash = Security::hash($clientIp . Security::getSalt());

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'ipHash' => $ipHash,
			'data' => json_encode(['test' => 'data']),
			'postHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$formEntry->createdOn = new DateTime()->subSeconds(600);
		$result = $formEntriesTable->save($formEntry, ['audit' => ['skip' => true]]);
		$this->assertNotFalse($result);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->ipCheckFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'protection_method_ip_check_error_duplicate_found'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::validateData()
	 */
	public function testValidateDataWithExistingOldEntry(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$request = Router::getRequest();
		$clientIp = $request->clientIp();
		$ipHash = Security::hash($clientIp . Security::getSalt());

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'ipHash' => $ipHash,
			'data' => json_encode(['test' => 'data']),
			'postHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$formEntry->createdOn = new DateTime()->subSeconds(600);
		$result = $formEntriesTable->save($formEntry, ['audit' => ['skip' => true]]);
		$this->assertNotFalse($result);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->ipCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::validateData()
	 */
	public function _testValidateDataWithDifferentIpAddress(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$ipHash = Security::hash('192.168.1.101' . Security::getSalt());

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'ipHash' => $ipHash,
			'data' => json_encode(['test' => 'data']),
			'postHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$result = $formEntriesTable->save($formEntry);

		$this->assertNotFalse($result);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->ipCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::modifyForm()
	 */
	public function testModifyForm(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$originalForm = clone $this->form;
		$this->ipCheckFormProtection->modifyForm($this->form);

		$this->assertEquals($originalForm, $this->form);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\IpCheckFormProtection::modifyFormEntry()
	 */
	public function testModifyFormEntry(): void {
		$this->ipCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$formEntry = new FormEntry([
			'formId' => $this->form->id,
			'data' => json_encode(['test' => 'data']),
		]);

		$result = $this->ipCheckFormProtection->modifyFormEntry($formEntry);

		$this->assertSame($formEntry, $result);
	}
}
