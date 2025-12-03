<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\I18n;
use Awyiss\Model\Table\I18nTable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * I18nTable Test Case
 *
 * @see \Awyiss\Model\Table\I18nTable
 */
class I18nTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\I18nTable
	 */
	protected I18nTable $i18nTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->i18nTable = FactoryLocator::get('Table')->get('I18n');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\I18nTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->i18nTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\I18nTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('i18n', $this->i18nTable::TABLE);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\I18nTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\I18n $entity */
		$entity = $this->i18nTable->newDefaultEntity();

		$this->assertInstanceOf(I18n::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->id);
		$this->assertNull($entity->content);
		$this->assertNull($entity->locale);
		$this->assertNull($entity->model);
		$this->assertNull($entity->foreignKey);
		$this->assertNull($entity->field);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\I18nTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'content' => 'Test content',
			'locale' => 'en_US',
			'model' => 'TestModel',
			'foreignKey' => 123,
			'field' => 'test_field',
		];

		/** @var \Awyiss\Model\Entity\I18n $entity */
		$entity = $this->i18nTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(I18n::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->id);
		$this->assertEquals('Test content', $entity->content);
		$this->assertEquals('en_US', $entity->locale);
		$this->assertEquals('TestModel', $entity->model);
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals('test_field', $entity->field);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\I18nTable::$audit
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->i18nTable->hasBehavior('Audit'));

		$config = $this->i18nTable->getBehavior('Audit')->getConfig();

		$this->assertFalse($config['enabled']);
	}
}
