<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\FormConditionalRecipient;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * FormConditionalRecipient Entity Test Case
 *
 * @see \Awyiss\Model\Entity\FormConditionalRecipient
 */
class FormConditionalRecipientTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\FormConditionalRecipientsTable $table */
		$table = FactoryLocator::get('Table')->get('FormConditionalRecipients');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new FormConditionalRecipient();

		$this->assertSame([
			'formId' => true,
			'type' => true,
			'field' => true,
			'operator' => true,
			'value' => true,
			'recipient' => true,
			'systemOrder' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\FormConditionalRecipientsTable $table */
		$table = FactoryLocator::get('Table')->get('FormConditionalRecipients');
		$entity = $table->newDefaultEntity();

		$this->assertNull($entity->formId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'formId' => 123,
			'type' => 'email',
			'field' => 'anrede',
			'operator' => 'equals',
			'value' => 'Frau',
			'recipient' => 'female@example.com',
			'systemOrder' => 10,
			'createdBy' => 456,
			'createdOn' => '2025-01-06 12:00:00',
			'changedBy' => 789,
			'changedOn' => '2025-01-06 13:00:00',
		];

		$entity = new FormConditionalRecipient($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->formId);
		$this->assertEquals('email', $entity->type);
		$this->assertEquals('anrede', $entity->field);
		$this->assertEquals('equals', $entity->operator);
		$this->assertEquals('Frau', $entity->value);
		$this->assertEquals('female@example.com', $entity->recipient);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertEquals(456, $entity->createdBy);
		$this->assertNotNull($entity->createdOn);
		$this->assertEquals(789, $entity->changedBy);
		$this->assertNotNull($entity->changedOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient
	 */
	public function testEntityConstructionWithDifferentOperators(): void {
		$properties = [
			'id' => 2,
			'formId' => 456,
			'type' => 'cc',
			'field' => 'multi_select',
			'operator' => 'contains',
			'value' => 'option_b',
			'recipient' => 'manager@example.com',
			'systemOrder' => 5,
		];

		$entity = new FormConditionalRecipient($properties);

		$this->assertEquals(2, $entity->id);
		$this->assertEquals(456, $entity->formId);
		$this->assertEquals('cc', $entity->type);
		$this->assertEquals('multi_select', $entity->field);
		$this->assertEquals('contains', $entity->operator);
		$this->assertEquals('option_b', $entity->value);
		$this->assertEquals('manager@example.com', $entity->recipient);
		$this->assertEquals(5, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient
	 */
	public function testEntityConstructionWithBccType(): void {
		$properties = [
			'id' => 3,
			'formId' => 789,
			'type' => 'bcc',
			'field' => 'telefon',
			'operator' => 'not_empty',
			'value' => '',
			'recipient' => 'audit@example.com',
			'systemOrder' => 1,
		];

		$entity = new FormConditionalRecipient($properties);

		$this->assertEquals(3, $entity->id);
		$this->assertEquals(789, $entity->formId);
		$this->assertEquals('bcc', $entity->type);
		$this->assertEquals('telefon', $entity->field);
		$this->assertEquals('not_empty', $entity->operator);
		$this->assertEquals('', $entity->value);
		$this->assertEquals('audit@example.com', $entity->recipient);
		$this->assertEquals(1, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormConditionalRecipient
	 */
	public function testEntityConstructionWithNotEqualsOperator(): void {
		$properties = [
			'id' => 4,
			'formId' => 101,
			'type' => 'email',
			'field' => 'datenschutzAkzeptiert',
			'operator' => 'notEquals',
			'value' => 'Ja',
			'recipient' => 'legal@example.com',
			'systemOrder' => 15,
		];

		$entity = new FormConditionalRecipient($properties);

		$this->assertEquals(4, $entity->id);
		$this->assertEquals(101, $entity->formId);
		$this->assertEquals('email', $entity->type);
		$this->assertEquals('datenschutzAkzeptiert', $entity->field);
		$this->assertEquals('notEquals', $entity->operator);
		$this->assertEquals('Ja', $entity->value);
		$this->assertEquals('legal@example.com', $entity->recipient);
		$this->assertEquals(15, $entity->systemOrder);
	}
}
