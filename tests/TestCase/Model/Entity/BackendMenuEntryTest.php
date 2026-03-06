<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * BackendMenuEntry Entity Test Case
 *
 * @see \Awyiss\Model\Entity\BackendMenuEntry
 */
class BackendMenuEntryTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var string|false
	 */
	protected string|false $locale;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->locale = ini_get('intl.default_locale');
		ini_set('intl.default_locale', 'de_DE');
		I18n::setLocale('de_DE');
		setlocale(LC_ALL, 'de_DE.utf8');
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		ini_set('intl.default_locale', $this->locale);
		I18n::setLocale($this->locale);
		setlocale(LC_ALL, $this->locale . '.utf8');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new BackendMenuEntry();

		$this->assertSame([
			'parentId' => true,
			'insertAfterId' => true,
			'title' => true,
			'link' => true,
			'access' => true,
			'external' => true,
			'systemOrder' => true,
			'active' => true,
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
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::_getTitle()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testTitleCleaningViaPropertyAssignment(): void {
		$entity = new BackendMenuEntry();

		$entity->title = 'Simple Title';
		$this->assertEquals('Simple Title', $entity->title);

		$entity->title = 'system::meta_title_overview';
		$this->assertEquals('Systemübersicht', $entity->title);

		$entity->title = 'customers::menu_overview';
		$this->assertEquals('Übersicht', $entity->title);

		$entity->title = '';
		$this->assertEquals('', $entity->title);

		$entity->title = null;
		$this->assertEquals('', $entity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::_getTitle()
	 */
	public function testTitleCleaningViaSetMethod(): void {
		$entity = new BackendMenuEntry();

		$entity->set('title', 'Simple Title');
		$this->assertEquals('Simple Title', $entity->title);

		$entity->set('title', 'system::meta_title_overview');
		$this->assertEquals('Systemübersicht', $entity->title);

		$entity->set('title', 'customers::menu_overview');
		$this->assertEquals('Übersicht', $entity->title);

		$entity->set('title', '');
		$this->assertEquals('', $entity->title);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('title', null);
		$this->assertEquals('', $entity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::_setAccess()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testAccessCleaningViaPropertyAssignment(): void {
		$entity = new BackendMenuEntry();

		$entity->access = ['admin', 'moderator'];
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->access = '["admin", "moderator"]';
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->access = [];
		$this->assertNull($entity->access);

		$entity->access = '';
		$this->assertNull($entity->access);

		$entity->access = null;
		$this->assertNull($entity->access);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::_setAccess()
	 */
	public function testAccessCleaningViaSetMethod(): void {
		$entity = new BackendMenuEntry();

		$entity->set('access', ['admin', 'moderator']);
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->set('access', '["admin", "moderator"]');
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->set('access', []);
		$this->assertNull($entity->access);

		$entity->set('access', '');
		$this->assertNull($entity->access);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('access', null);
		$this->assertNull($entity->access);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::getChildren()
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $parent */
		$parent = $table->get(1);

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(1, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $parent */
		$parent = $table->get(1);

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(3, $nestedChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::getParent()
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $child */
		$child = $table->get(7);

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(6, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $entry */
		$entry = $table->get(3);

		$parent = $entry->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::getParents()
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $child */
		$child = $table->get(7);

		$parents = $child->getParents();

		$this->assertNotNull($parents);
		$this->assertCount(3, $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry::getParents()
	 */
	public function _testGetParentsWithNoParents(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('BackendMenuEntries');
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $entry */
		$entry = $table->get(3);

		$parents = $entry->getParents();

		$this->assertEmpty($parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\BackendMenuEntry
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'parentId' => 'media',
			'insertAfterId' => 'some_entry',
			'title' => 'dashboard::meta_title_overview',
			'link' => 'https://example.com/test',
			'access' => '["admin", "moderator"]',
			'external' => true,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new BackendMenuEntry($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('media', $entity->parentId);
		$this->assertEquals('some_entry', $entity->insertAfterId);
		$this->assertEquals('Dashboard', $entity->title);
		$this->assertEquals('https://example.com/test', $entity->link);
		$this->assertEquals(['admin', 'moderator'], $entity->access);
		$this->assertTrue($entity->external);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
