<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form\Protection;


use Awyiss\Form\FormOptions;
use Awyiss\Form\Protection\DuplicateCheckFormProtection;
use Awyiss\Form\Protection\FormProtectionInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;


/**
 * DuplicateCheckFormProtection Test Case
 *
 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection
 */
class DuplicateCheckFormProtectionTest extends TestCase {
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\Form\Protection\DuplicateCheckFormProtection
	 */
	protected DuplicateCheckFormProtection $duplicateCheckFormProtection;
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

		$this->duplicateCheckFormProtection = new DuplicateCheckFormProtection();
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
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::initialize()
	 */
	public function testInitialize(): void {
		$result = $this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$this->assertSame($this->duplicateCheckFormProtection, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::getHtml()
	 */
	public function testGetHtml(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$result = $this->duplicateCheckFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE);
		$this->assertNull($result);

		$result = $this->duplicateCheckFormProtection->getHtml(FormProtectionInterface::POSITION_BEFORE_SUBMIT);
		$this->assertNull($result);

		$result = $this->duplicateCheckFormProtection->getHtml(FormProtectionInterface::POSITION_AFTER);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::validateData()
	 */
	public function testValidateDataWithNoExistingEntries(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->duplicateCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::validateData()
	 */
	public function testValidateDataWithExistingRecentDuplicate(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$dataHash = Security::hash(serialize($data));

		// Create a recent form entry with the same data hash
		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'postHash' => $dataHash,
			'data' => json_encode($data),
			'ipHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$result = $formEntriesTable->save($formEntry);

		$this->assertNotFalse($result);

		$result = $this->duplicateCheckFormProtection->validateData($data);

		$this->assertIsString($result);
		$this->assertEquals(__d('Form', 'protection_method_duplicate_check_error_duplicate_found'), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::validateData()
	 */
	public function testValidateDataWithExistingOldDuplicate(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$dataHash = Security::hash(serialize($data));

		// Create an old form entry with the same data hash (older than default 24h timeout)
		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'postHash' => $dataHash,
			'data' => json_encode($data),
			'ipHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$formEntry->createdOn = new DateTime()->subSeconds(90000); // 25 hours
		$result = $formEntriesTable->save($formEntry, ['audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->duplicateCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::validateData()
	 */
	public function testValidateDataWithDifferentData(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		// Create a form entry with different data
		$differentData = ['name' => 'Other User', 'email' => 'other@example.com'];
		$differentDataHash = Security::hash(serialize($differentData));

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'postHash' => $differentDataHash,
			'data' => json_encode($differentData),
			'ipHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$result = $formEntriesTable->save($formEntry);

		$this->assertNotFalse($result);

		// Test with new data
		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$result = $this->duplicateCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::validateData()
	 */
	public function testValidateDataWithCustomTimeout(): void {
		// Mock form options with custom timeout
		$mockFormOptions = $this->createMock(FormOptions::class);
		$mockFormOptions
			->expects($this->atLeastOnce())
			->method('getProtectionOptions')
			->with('duplicateCheck')
			->willReturn(['checkTimeout' => 300]); // 5 minutes timeout

		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$mockFormOptions,
			$this->view
		);

		$data = ['name' => 'Test User', 'email' => 'test@example.com'];
		$dataHash = Security::hash(serialize($data));

		// Create a form entry that's 10 minutes old (older than custom timeout)
		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntry = $formEntriesTable->newDefaultEntity([
			'formId' => $this->form->id,
			'postHash' => $dataHash,
			'data' => json_encode($data),
			'ipHash' => '',
			'identifier' => '4b3123d582a34f028a8470c6443baddd1e79a239',
		]);
		$formEntry->createdOn = new DateTime()->subSeconds(600); // 10 minutes
		$result = $formEntriesTable->save($formEntry, ['audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->duplicateCheckFormProtection->validateData($data);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::modifyForm()
	 */
	public function testModifyForm(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$originalForm = clone $this->form;
		$this->duplicateCheckFormProtection->modifyForm($this->form);

		$this->assertEquals($originalForm, $this->form);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\Protection\DuplicateCheckFormProtection::modifyFormEntry()
	 */
	public function testModifyFormEntry(): void {
		$this->duplicateCheckFormProtection->initialize(
			$this->form,
			$this->formElements,
			$this->formOptions,
			$this->view
		);

		$formEntry = new FormEntry([
			'formId' => $this->form->id,
			'data' => json_encode(['test' => 'data']),
		]);

		$result = $this->duplicateCheckFormProtection->modifyFormEntry($formEntry);

		$this->assertSame($formEntry, $result);
	}
}
