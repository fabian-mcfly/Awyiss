<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\I18n;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * I18n Entity Test Case
 *
 * @see \Awyiss\Model\Entity\I18n
 */
class I18nTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\I18n::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\I18nTable $table */
		$table = FactoryLocator::get('Table')->get('I18n');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\I18n::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new I18n();

		$this->assertSame([
			'locale' => true,
			'model' => true,
			'foreignKey' => true,
			'field' => true,
			'content' => true,
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
	 * @see \Awyiss\Model\Entity\I18n
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'locale' => 'de_DE',
			'model' => 'Contents',
			'foreignKey' => 123,
			'field' => 'title',
			'content' => 'Deutscher Titel',
		];

		$entity = new I18n($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('de_DE', $entity->locale);
		$this->assertEquals('Contents', $entity->model);
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals('title', $entity->field);
		$this->assertEquals('Deutscher Titel', $entity->content);
	}
}
