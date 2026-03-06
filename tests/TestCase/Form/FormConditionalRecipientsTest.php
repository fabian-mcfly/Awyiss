<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Form;


use Awyiss\Form\FormConditionalRecipients;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormConditionalRecipient;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use InvalidArgumentException;


/**
 * FormConditionalRecipients Test Case
 *
 * @see \Awyiss\Form\FormConditionalRecipients
 */
class FormConditionalRecipientsTest extends TestCase {
	/**
	 * @var \Awyiss\Form\FormConditionalRecipients
	 */
	protected FormConditionalRecipients $conditionalRecipients;
	/**
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected Form $form;
	/**
	 * @var \Awyiss\Model\Entity\Page
	 */
	protected Page $page;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->form = new Form(['id' => 1]);
		$this->page = new Page(['id' => 1, 'title' => 'Test Page']);
		$this->conditionalRecipients = new FormConditionalRecipients($this->form, $this->page);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::__construct()
	 */
	public function testConstructor(): void {
		$form = new Form(['id' => 123]);
		$page = new Page(['id' => 456]);

		$instance = new FormConditionalRecipients($form, $page);

		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST, $instance->getProcessStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::__construct()
	 */
	public function testConstructorWithNullPage(): void {
		$form = new Form(['id' => 123]);

		$instance = new FormConditionalRecipients($form, null);

		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST, $instance->getProcessStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getProcessStrategy()
	 * @see \Awyiss\Form\FormConditionalRecipients::setProcessStrategy()
	 */
	public function testSetAndGetProcessStrategy(): void {
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST, $this->conditionalRecipients->getProcessStrategy());

		$result = $this->conditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL);
		$this->assertSame($this->conditionalRecipients, $result);
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL, $this->conditionalRecipients->getProcessStrategy());

		$this->conditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST);
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST, $this->conditionalRecipients->getProcessStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::setProcessStrategy()
	 */
	public function testSetProcessStrategyWithInvalidStrategy(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid process strategy');

		$this->conditionalRecipients->setProcessStrategy('invalid_strategy');
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchFirst(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field', ComparisonOperator::Equal, 'value', 'recipient1@example.com'),
			$this->createConditionalRecipient('field', ComparisonOperator::Equal, 'value', 'recipient2@example.com'),
			$this->createConditionalRecipient('field', ComparisonOperator::Equal, 'value', 'recipient3@example.com'),
		];
		$requestData = ['field' => 'value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field'),
		]);

		$this->conditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST);
		$result = $this->conditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient1@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchLast(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field', ComparisonOperator::Equal, 'value', 'recipient1@example.com'),
			$this->createConditionalRecipient('field', ComparisonOperator::Equal, 'value', 'recipient2@example.com'),
			$this->createConditionalRecipient('field', ComparisonOperator::Equal, 'value', 'recipient3@example.com'),
		];
		$requestData = ['field' => 'value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field'),
		]);

		$this->conditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST);
		$result = $this->conditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient3@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchAll(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field2', ComparisonOperator::Equal, 'value2', 'recipient2@example.com'),
		];
		$requestData = ['field1' => 'value1', 'field2' => 'value2'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
			$this->createFormElement('text', 'field2'),
		]);

		$this->conditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL);
		$result = $this->conditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchAllFailure(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field2', ComparisonOperator::Equal, 'wrong_value', 'recipient2@example.com'),
		];
		$requestData = ['field1' => 'value1', 'field2' => 'value2'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
			$this->createFormElement('text', 'field2'),
		]);

		$this->conditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL);
		$result = $this->conditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getFirstMatchingRecipient()
	 */
	public function testGetFirstMatchingRecipient(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'wrong_value', 'recipient1@example.com'),
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient2@example.com'),
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient3@example.com'),
		];
		$requestData = ['field1' => 'value1'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getFirstMatchingRecipient()
	 */
	public function testGetFirstMatchingRecipientNoMatch(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'wrong_value', 'recipient1@example.com'),
		];
		$requestData = ['field1' => 'value1'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getLastMatchingRecipient()
	 */
	public function testGetLastMatchingRecipient(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient2@example.com'),
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'wrong_value', 'recipient3@example.com'),
		];
		$requestData = ['field1' => 'value1'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
		]);

		$result = $this->conditionalRecipients->getLastMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getAllMatchingRecipient()
	 */
	public function testGetAllMatchingRecipient(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', ComparisonOperator::Equal, 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field2', ComparisonOperator::Equal, 'value2', 'recipient2@example.com'),
		];
		$requestData = ['field1' => 'value1', 'field2' => 'value2'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
			$this->createFormElement('text', 'field2'),
		]);

		$result = $this->conditionalRecipients->getAllMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithElementIdentifier(): void {
		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, 'test_value');
		$requestData = ['testField' => 'test_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingField(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missingField', ComparisonOperator::Equal, 'test_value');
		$requestData = ['otherField' => 'other_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageProperty(): void {
		$conditionalRecipient = $this->createConditionalRecipient('title', ComparisonOperator::Equal, 'Test Page', 'page@example.com', 'currentPage');
		$this->page->title = 'Test Page';

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		$this->assertSame('page@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageAttributes(): void {
		$conditionalRecipient = $this->createConditionalRecipient('customField', ComparisonOperator::Equal, 'custom_value', 'attr@example.com', 'currentPage');
		$this->page->attributes = new Entity(['customField' => 'custom_value']);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		$this->assertSame('attr@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageNullPage(): void {
		$conditionalRecipients = new FormConditionalRecipients($this->form, null);
		$conditionalRecipient = $this->createConditionalRecipient('title', ComparisonOperator::Equal, 'Test Page', 'page@example.com', 'currentPage');

		$result = $conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEqualTo()
	 */
	public function testCompareEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, 'test');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Test']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, '');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, null);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => null]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, '');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => null]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, null);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, 'test');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'other']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEqualTo()
	 */
	public function testCompareNotEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEqual, 'test');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'test']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEqual, '');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEqual, null);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => null]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEqual, '');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => null]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEqual, null);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEqual, 'test');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'other']);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThan, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::GreaterThanOrEqual, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThanWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('date', 'dateField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, '2025-01-06');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, '2025-01-06');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, new Date('2025-01-06'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, new Date('2025-01-06'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, '2025-01-07');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, '2025-01-07');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-06')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, new Date('2025-01-07'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThan, new Date('2025-01-07'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-06')]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThanOrEqualWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('date', 'dateField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, '2025-01-06');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, '2025-01-06');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, new Date('2025-01-06'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, new Date('2025-01-06'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, '2025-01-07');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, '2025-01-07');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, new Date('2025-01-07'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, new Date('2025-01-07'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, '2025-01-07');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, '2025-01-07');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-06')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, new Date('2025-01-07'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('dateField', ComparisonOperator::GreaterThanOrEqual, new Date('2025-01-07'));
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['dateField' => new Date('2025-01-06')]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareLessThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThan, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareLessThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, '10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, 10);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, '5');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LessThanOrEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareBetween(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 1]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['1', '10']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, '1, 10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 15]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['1', '10']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, '1, 10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 15]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareBetweenWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2021-06-15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2021-06-15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2021-06-15')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, '2020-01-01,2023-12-31');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2021-06-15')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2019-12-31']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2019-12-31']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2019-12-31')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Between, '2020-01-01,2023-12-31');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2019-12-31')]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareNotBetween(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 1]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['1', '10']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, '1, 10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 10]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, [1, 10]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 15]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['1', '10']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, '1, 10');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 15]);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareNotBetweenWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2021-06-15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2021-06-15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2021-06-15')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, '2020-01-01,2023-12-31');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2021-06-15')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2019-12-31']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => '2019-12-31']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, ['2020-01-01', '2023-12-31']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2019-12-31')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotBetween, '2020-01-01,2023-12-31');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => new Date('2019-12-31')]);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLengthEqualTo()
	 */
	public function testCompareLengthEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LengthEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LengthEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLengthEqualTo()
	 */
	public function testCompareLengthNotEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LengthNotEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LengthNotEqual, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareLongerThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LongerThan, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LongerThan, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hey']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareLongerThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LongerThanOrEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LongerThanOrEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hey']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::LongerThanOrEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hi']);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareShorterThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::ShorterThan, 5);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hey']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::ShorterThan, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hey']);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareShorterThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::ShorterThanOrEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hey']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::ShorterThanOrEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hi']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::ShorterThanOrEqual, 3);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareIn()
	 */
	public function testCompareIn(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::In, ['apple', 'banana', 'cherry']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Apple']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::In, ['apple', 'banana', 'cherry']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'grape']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareIn()
	 */
	public function testCompareNotIn(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotIn, ['apple', 'banana', 'cherry']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'grape']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotIn, ['apple', 'banana', 'cherry']);
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'banana']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareContains()
	 */
	public function testCompareContains(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Contains, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Hello World']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Contains, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareContains()
	 */
	public function testCompareNotContains(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotContains, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotContains, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Hello World']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareStartsWith()
	 */
	public function testCompareStartsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::StartsWith, 'hello');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Hello World']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::StartsWith, 'hello');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'world hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareStartsWith()
	 */
	public function testCompareNotStartsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotStartsWith, 'hello');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'world hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotStartsWith, 'hello');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Hello World']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEndsWith()
	 */
	public function testCompareEndsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::EndsWith, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Hello World']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::EndsWith, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'world hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEndsWith()
	 */
	public function testCompareNotEndsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEndsWith, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'world hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::NotEndsWith, 'world');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'Hello World']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareRegexp()
	 */
	public function testCompareRegexp(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'testField'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Regexp, '/\d+/');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello123']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Regexp, '/\d+/');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'hello']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Regexp, '/^[^@]+@[^@]+\.[^@]+$/');
		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['testField' => 'test@example.com']);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingFormElements(): void {
		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, 'test_value');
		$requestData = ['testField' => 'test_value'];

		// Form has no formElements property set
		$this->form->formElements = null;

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because OutOfBoundsException is caught in ruleMatches()
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingFormElement(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missingField', ComparisonOperator::Equal, 'test_value');
		$requestData = ['missingField' => 'test_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'otherField'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because OutOfBoundsException is caught in ruleMatches()
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageMissingField(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missingField', ComparisonOperator::Equal, 'test_value', 'page@example.com', 'currentPage');

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		// Should return null because OutOfBoundsException is caught in ruleMatches()
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithInvalidFieldType(): void {
		$conditionalRecipient = $this->createConditionalRecipient('testField', ComparisonOperator::Equal, 'test_value', 'test@example.com', 'invalid_type');
		$requestData = ['testField' => 'test_value'];

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid field type');

		$this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithOutOfBoundsExceptionHandling(): void {
		$conditionalRecipient = $this->createConditionalRecipient('nonexistentField', ComparisonOperator::Equal, 'test_value');
		$requestData = ['differentField' => 'test_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'existing_field'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because rule doesn't match due to OutOfBoundsException
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingRequestDataField(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missingField', ComparisonOperator::Equal, 'test_value');
		$requestData = ['otherField' => 'other_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'missingField'),
		]);

		$result = $this->conditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because field is missing from request data (OutOfBoundsException caught)
		$this->assertNull($result);
	}


	/**
	 * @param string $field
	 * @param ComparisonOperator|string $operator
	 * @param mixed $value
	 * @param string $recipient
	 * @param string $type
	 * @return \Awyiss\Model\Entity\FormConditionalRecipient
	 */
	protected function createConditionalRecipient(
		string $field,
		string|ComparisonOperator $operator,
		mixed $value,
		string $recipient = 'test@example.com',
		string $type = 'elementIdentifier'
	): FormConditionalRecipient {
		$conditionalRecipient = new FormConditionalRecipient();
		$conditionalRecipient->type = $type;
		$conditionalRecipient->field = $field;
		$conditionalRecipient->operator = $operator instanceof ComparisonOperator ? $operator : ComparisonOperator::from($operator);
		$conditionalRecipient->value = $value;
		$conditionalRecipient->recipient = $recipient;

		return $conditionalRecipient;
	}


	/**
	 * @param string $type
	 * @param string $identifier
	 * @return \Awyiss\Model\Entity\FormElement
	 */
	protected function createFormElement(string $type, string $identifier): FormElement {
		$formElement = new FormElement();
		$formElement->type = $type;
		$formElement->identifier = $identifier;

		return $formElement;
	}
}
