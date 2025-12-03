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
	protected FormConditionalRecipients $formConditionalRecipients;
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
		$this->formConditionalRecipients = new FormConditionalRecipients($this->form, $this->page);
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
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST, $this->formConditionalRecipients->getProcessStrategy());

		$result = $this->formConditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL);
		$this->assertSame($this->formConditionalRecipients, $result);
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL, $this->formConditionalRecipients->getProcessStrategy());

		$this->formConditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST);
		$this->assertSame(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST, $this->formConditionalRecipients->getProcessStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::setProcessStrategy()
	 */
	public function testSetProcessStrategyWithInvalidStrategy(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid process strategy');

		$this->formConditionalRecipients->setProcessStrategy('invalid_strategy');
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchFirst(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field', '=', 'value', 'recipient1@example.com'),
			$this->createConditionalRecipient('field', '=', 'value', 'recipient2@example.com'),
			$this->createConditionalRecipient('field', '=', 'value', 'recipient3@example.com'),
		];
		$requestData = ['field' => 'value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field'),
		]);

		$this->formConditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST);
		$result = $this->formConditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient1@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchLast(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field', '=', 'value', 'recipient1@example.com'),
			$this->createConditionalRecipient('field', '=', 'value', 'recipient2@example.com'),
			$this->createConditionalRecipient('field', '=', 'value', 'recipient3@example.com'),
		];
		$requestData = ['field' => 'value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field'),
		]);

		$this->formConditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST);
		$result = $this->formConditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient3@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchAll(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field2', '=', 'value2', 'recipient2@example.com'),
		];
		$requestData = ['field1' => 'value1', 'field2' => 'value2'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
			$this->createFormElement('text', 'field2'),
		]);

		$this->formConditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL);
		$result = $this->formConditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getMatchingRecipient()
	 */
	public function testGetMatchingRecipientWithMatchAllFailure(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field2', '=', 'wrong_value', 'recipient2@example.com'),
		];
		$requestData = ['field1' => 'value1', 'field2' => 'value2'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
			$this->createFormElement('text', 'field2'),
		]);

		$this->formConditionalRecipients->setProcessStrategy(FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL);
		$result = $this->formConditionalRecipients->getMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getFirstMatchingRecipient()
	 */
	public function testGetFirstMatchingRecipient(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', '=', 'wrong_value', 'recipient1@example.com'),
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient2@example.com'),
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient3@example.com'),
		];
		$requestData = ['field1' => 'value1'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getFirstMatchingRecipient()
	 */
	public function testGetFirstMatchingRecipientNoMatch(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', '=', 'wrong_value', 'recipient1@example.com'),
		];
		$requestData = ['field1' => 'value1'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getLastMatchingRecipient()
	 */
	public function testGetLastMatchingRecipient(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient2@example.com'),
			$this->createConditionalRecipient('field1', '=', 'wrong_value', 'recipient3@example.com'),
		];
		$requestData = ['field1' => 'value1'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
		]);

		$result = $this->formConditionalRecipients->getLastMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::getAllMatchingRecipient()
	 */
	public function testGetAllMatchingRecipient(): void {
		$conditionalRecipients = [
			$this->createConditionalRecipient('field1', '=', 'value1', 'recipient1@example.com'),
			$this->createConditionalRecipient('field2', '=', 'value2', 'recipient2@example.com'),
		];
		$requestData = ['field1' => 'value1', 'field2' => 'value2'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'field1'),
			$this->createFormElement('text', 'field2'),
		]);

		$result = $this->formConditionalRecipients->getAllMatchingRecipient($conditionalRecipients, $requestData);

		$this->assertSame('recipient2@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithElementIdentifier(): void {
		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', 'test_value');
		$requestData = ['test_field' => 'test_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingField(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missing_field', '=', 'test_value');
		$requestData = ['other_field' => 'other_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageProperty(): void {
		$conditionalRecipient = $this->createConditionalRecipient('title', '=', 'Test Page', 'page@example.com', 'current_page');
		$this->page->title = 'Test Page';

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		$this->assertSame('page@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageAttributes(): void {
		$conditionalRecipient = $this->createConditionalRecipient('custom_field', '=', 'custom_value', 'attr@example.com', 'current_page');
		$this->page->attributes = new Entity(['custom_field' => 'custom_value']);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		$this->assertSame('attr@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageNullPage(): void {
		$formConditionalRecipients = new FormConditionalRecipients($this->form, null);
		$conditionalRecipient = $this->createConditionalRecipient('title', '=', 'Test Page', 'page@example.com', 'current_page');

		$result = $formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEqualTo()
	 */
	public function testCompareEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', 'test');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Test']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', '');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', null);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => null]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', '');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => null]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', null);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', 'test');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'other']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEqualTo()
	 */
	public function testCompareNotEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '!=', 'test');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'test']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '!=', '');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '!=', null);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => null]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '!=', '');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => null]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '!=', null);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '!=', 'test');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'other']);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '>=', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThanWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('date', 'date_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', '2025-01-06');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', '2025-01-06');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', new Date('2025-01-06'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', new Date('2025-01-06'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', '2025-01-07');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', '2025-01-07');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-06')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', new Date('2025-01-07'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>', new Date('2025-01-07'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-06')]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareGreaterThanOrEqualWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('date', 'date_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', '2025-01-06');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', '2025-01-06');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', new Date('2025-01-06'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', new Date('2025-01-06'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', '2025-01-07');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', '2025-01-07');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', new Date('2025-01-07'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-07']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', new Date('2025-01-07'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-07')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', '2025-01-07');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', '2025-01-07');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-06')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', new Date('2025-01-07'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => '2025-01-06']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('date_field', '>=', new Date('2025-01-07'));
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['date_field' => new Date('2025-01-06')]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareLessThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareGreaterThan()
	 */
	public function testCompareLessThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', '10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', 10);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 5]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', '5');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', '<=', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareBetween(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 1]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['1', '10']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', '1, 10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 15]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['1', '10']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', '1, 10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 15]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareBetweenWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2021-06-15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2021-06-15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2021-06-15')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', '2020-01-01,2023-12-31');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2021-06-15')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2019-12-31']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2019-12-31']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2019-12-31')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'between', '2020-01-01,2023-12-31');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2019-12-31')]);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareNotBetween(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '5']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 1]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['1', '10']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '10']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', '1, 10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 10]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', [1, 10]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 15]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['1', '10']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '15']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', '1, 10');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 15]);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareBetween()
	 */
	public function testCompareNotBetweenWithDates(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2021-06-15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2021-06-15']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2021-06-15')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', '2020-01-01,2023-12-31');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2021-06-15')]);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2019-12-31']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['2020-01-01', new Date('2023-12-31')]);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => '2019-12-31']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', ['2020-01-01', '2023-12-31']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2019-12-31')]);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_between', '2020-01-01,2023-12-31');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => new Date('2019-12-31')]);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLengthEqualTo()
	 */
	public function testCompareLengthEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'length_equal', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'length_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLengthEqualTo()
	 */
	public function testCompareLengthNotEqualTo(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'length_not_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'length_not_equal', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareLongerThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'longer_than', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'longer_than', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hey']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareLongerThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'longer_than_or_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'longer_than_or_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hey']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'longer_than_or_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hi']);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareShorterThan(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'shorter_than', 5);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hey']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'shorter_than', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hey']);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareLongerThan()
	 */
	public function testCompareShorterThanOrEqual(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'shorter_than_or_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hey']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'shorter_than_or_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hi']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'shorter_than_or_equal', 3);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareIn()
	 */
	public function testCompareIn(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'in', ['apple', 'banana', 'cherry']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Apple']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'in', ['apple', 'banana', 'cherry']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'grape']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareIn()
	 */
	public function testCompareNotIn(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_in', ['apple', 'banana', 'cherry']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'grape']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_in', ['apple', 'banana', 'cherry']);
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'banana']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareContains()
	 */
	public function testCompareContains(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'contains', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Hello World']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'contains', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareContains()
	 */
	public function testCompareNotContains(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_contains', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_contains', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Hello World']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareStartsWith()
	 */
	public function testCompareStartsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'starts_with', 'hello');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Hello World']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'starts_with', 'hello');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'world hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareStartsWith()
	 */
	public function testCompareNotStartsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_starts_with', 'hello');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'world hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_starts_with', 'hello');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Hello World']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEndsWith()
	 */
	public function testCompareEndsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'ends_with', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Hello World']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'ends_with', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'world hello']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareEndsWith()
	 */
	public function testCompareNotEndsWith(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_ends_with', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'world hello']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'not_ends_with', 'world');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'Hello World']);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::compareRegexp()
	 */
	public function testCompareRegexp(): void {
		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'test_field'),
		]);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'regexp', '/\d+/');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello123']);
		$this->assertSame('test@example.com', $result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'regexp', '/\d+/');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'hello']);
		$this->assertNull($result);

		$conditionalRecipient = $this->createConditionalRecipient('test_field', 'regexp', '/^[^@]+@[^@]+\.[^@]+$/');
		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], ['test_field' => 'test@example.com']);
		$this->assertSame('test@example.com', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingFormElements(): void {
		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', 'test_value');
		$requestData = ['test_field' => 'test_value'];

		// Form has no formElements property set
		$this->form->formElements = null;

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because OutOfBoundsException is caught in ruleMatches()
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingFormElement(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missing_field', '=', 'test_value');
		$requestData = ['missing_field' => 'test_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'other_field'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because OutOfBoundsException is caught in ruleMatches()
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithCurrentPageMissingField(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missing_field', '=', 'test_value', 'page@example.com', 'current_page');

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], []);

		// Should return null because OutOfBoundsException is caught in ruleMatches()
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithInvalidFieldType(): void {
		$conditionalRecipient = $this->createConditionalRecipient('test_field', '=', 'test_value', 'test@example.com', 'invalid_type');
		$requestData = ['test_field' => 'test_value'];

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid field type');

		$this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithOutOfBoundsExceptionHandling(): void {
		$conditionalRecipient = $this->createConditionalRecipient('nonexistent_field', '=', 'test_value');
		$requestData = ['different_field' => 'test_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'existing_field'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because rule doesn't match due to OutOfBoundsException
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Form\FormConditionalRecipients::ruleMatches()
	 */
	public function testRuleMatchesWithMissingRequestDataField(): void {
		$conditionalRecipient = $this->createConditionalRecipient('missing_field', '=', 'test_value');
		$requestData = ['other_field' => 'other_value'];

		$this->form->formElements = new Collection([
			$this->createFormElement('text', 'missing_field'),
		]);

		$result = $this->formConditionalRecipients->getFirstMatchingRecipient([$conditionalRecipient], $requestData);

		// Should return null because field is missing from request data (OutOfBoundsException caught)
		$this->assertNull($result);
	}


	/**
	 * @param string $field
	 * @param string $operator
	 * @param mixed $value
	 * @param string $recipient
	 * @param string $type
	 * @return \Awyiss\Model\Entity\FormConditionalRecipient
	 */
	protected function createConditionalRecipient(
		string $field,
		string $operator,
		mixed $value,
		string $recipient = 'test@example.com',
		string $type = 'element_identifier'
	): FormConditionalRecipient {
		$conditionalRecipient = new FormConditionalRecipient();
		$conditionalRecipient->type = $type;
		$conditionalRecipient->field = $field;
		$conditionalRecipient->operator = ComparisonOperator::from($operator);
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
