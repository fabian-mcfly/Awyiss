<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\UrlsNotFound;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * UrlsNotFound Entity Test Case
 *
 * @see \Awyiss\Model\Entity\UrlsNotFound
 */
class UrlsNotFoundTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlsNotFound::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UrlsNotFoundTable $table */
		$table = FactoryLocator::get('Table')->get('UrlsNotFound');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UrlsNotFound::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new UrlsNotFound();

		$this->assertSame([
			'url' => true,
			'referrer' => true,
			'isRobot' => true,
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
	 * @see \Awyiss\Model\Entity\UrlsNotFound
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'url' => 'https://example.com/missing-page',
			'referrer' => 'https://example.com/home',
			'isRobot' => true,
			'createdOn' => '2025-01-06 12:00:00',
		];

		$entity = new UrlsNotFound($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('https://example.com/missing-page', $entity->url);
		$this->assertEquals('https://example.com/home', $entity->referrer);
		$this->assertTrue($entity->isRobot);
		$this->assertNotNull($entity->createdOn);
	}
}
