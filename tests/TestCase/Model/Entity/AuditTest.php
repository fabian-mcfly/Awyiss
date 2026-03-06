<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Audit;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Audit Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Audit
 */
class AuditTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Audit::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\AuditTable $table */
		$table = FactoryLocator::get('Table')->get('Audit');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Audit::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Audit();

		$this->assertSame([
			'scope' => true,
			'foreignKey' => true,
			'subjectLeftTable' => true,
			'subjectLeftForeignKey' => true,
			'subjectRightTable' => true,
			'subjectRightForeignKey' => true,
			'transactionId' => true,
			'type' => true,
			'dataOld' => true,
			'dataNew' => true,
			'diff' => true,
			'createdBy' => true,
			'createdOn' => true,
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
	 * @see \Awyiss\Model\Entity\Audit
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'Users',
			'foreignKey' => 123,
			'transactionId' => 'abc-123-def',
			'type' => 'update',
			'dataOld' => '{"name":"John","email":"john@example.com"}',
			'dataNew' => '{"name":"John Smith","email":"john.smith@example.com"}',
			'diff' => ['name' => ['old' => 'John', 'new' => 'John Smith'], 'email' => ['old' => 'john@example.com', 'new' => 'john.smith@example.com']],
			'createdBy' => 456,
			'createdOn' => '2023-01-01 12:00:00',
		];

		$entity = new Audit($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Users', $entity->scope);
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals('abc-123-def', $entity->transactionId);
		$this->assertEquals('update', $entity->type);
		$this->assertEquals('{"name":"John","email":"john@example.com"}', $entity->dataOld);
		$this->assertEquals('{"name":"John Smith","email":"john.smith@example.com"}', $entity->dataNew);
		$this->assertEquals(['name' => ['old' => 'John', 'new' => 'John Smith'], 'email' => ['old' => 'john@example.com', 'new' => 'john.smith@example.com']], $entity->diff);
		$this->assertEquals(456, $entity->createdBy);
		$this->assertEquals('2023-01-01 12:00:00', $entity->createdOn);
	}
}
