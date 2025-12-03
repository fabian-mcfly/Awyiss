<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Language;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Language Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Language
 */
class LanguageTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Language::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\LanguagesTable $table */
		$table = FactoryLocator::get('Table')->get('Languages');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Language::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Language();

		$this->assertSame([
			'realm' => true,
			'shortcode' => true,
			'timezone' => true,
			'locale' => true,
			'dateFormat' => true,
			'timeFormat' => true,
			'title' => true,
			'systemOrder' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Language
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'realm' => 'frontend',
			'shortcode' => 'de',
			'timezone' => 'Europe/Berlin',
			'locale' => 'de_DE',
			'date_format' => 'd.m.Y',
			'time_format' => 'H:i',
			'title' => 'Deutsch',
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new Language($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('frontend', $entity->realm);
		$this->assertEquals('de', $entity->shortcode);
		$this->assertEquals('Europe/Berlin', $entity->timezone);
		$this->assertEquals('de_DE', $entity->locale);
		$this->assertEquals('d.m.Y', $entity->dateFormat);
		$this->assertEquals('H:i', $entity->timeFormat);
		$this->assertEquals('Deutsch', $entity->title);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Language::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'date_format' => 'Y-m-d',
			'time_format' => 'H:i:s',
			'system_order' => 5,
		];

		$entity = new Language($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Language::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\LanguagesTable $table */
		$table = FactoryLocator::get('Table')->get('Languages');
		$entity = $table->newDefaultEntity();

		$this->assertEquals(Awyiss::REALM_FRONTEND, $entity->realm);
	}
}
