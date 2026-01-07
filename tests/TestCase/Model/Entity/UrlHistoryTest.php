<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\UrlHistory;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * UrlHistory Entity Test Case
 *
 * @see \Awyiss\Model\Entity\UrlHistory
 */
class UrlHistoryTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlHistory::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UrlHistoryTable $table */
		$table = FactoryLocator::get('Table')->get('UrlHistory');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlHistory::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new UrlHistory();

		$this->assertSame([
			'url' => true,
			'scope' => true,
			'foreignKey' => true,
			'target' => true,
			'status' => true,
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
	 * @see \Awyiss\Model\Entity\UrlHistory::_setStatus()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testStatusCleaningViaPropertyAssignment(): void {
		$entity = new UrlHistory();

		$entity->status = '301';
		$this->assertEquals(301, $entity->status);

		$entity->status = 404;
		$this->assertEquals(404, $entity->status);

		$entity->status = '0';
		$this->assertNull($entity->status);

		$entity->status = 0;
		$this->assertNull($entity->status);

		$entity->status = '';
		$this->assertNull($entity->status);

		$entity->status = null;
		$this->assertNull($entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlHistory::_setStatus()
	 */
	public function testStatusCleaningViaSetMethod(): void {
		$entity = new UrlHistory();

		$entity->set('status', '301');
		$this->assertEquals(301, $entity->status);

		$entity->set('status', 404);
		$this->assertEquals(404, $entity->status);

		$entity->set('status', '0');
		$this->assertNull($entity->status);

		$entity->set('status', 0);
		$this->assertNull($entity->status);

		$entity->set('status', '');
		$this->assertNull($entity->status);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('status', null);
		$this->assertNull($entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlHistory
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'url' => '/old-page',
			'scope' => 'page',
			'foreign_key' => 123,
			'target' => '/new-page',
			'status' => 301,
		];

		$entity = new UrlHistory($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('/old-page', $entity->url);
		$this->assertEquals('page', $entity->scope);
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals('/new-page', $entity->target);
		$this->assertEquals(301, $entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlHistory::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = ['foreign_key' => 456];
		$entity = new UrlHistory($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
