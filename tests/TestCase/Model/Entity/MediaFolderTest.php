<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaFolder Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaFolder
 */
class MediaFolderTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaFolder();

		$this->assertSame([
			'parentId' => true,
			'path' => true,
			'languageShortcode' => true,
			'title' => true,
			'hidden' => true,
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
	 * @see \Awyiss\Model\Entity\MediaFolder::_setPath()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testPathCleaningViaPropertyAssignment(): void {
		$entity = new MediaFolder();

		$entity->path = 'Media Folder';
		$this->assertEquals('media-folder', $entity->path);

		$entity->path = 'MediaFolder';
		$this->assertEquals('mediafolder', $entity->path);

		$entity->path = 'Media-Folder';
		$this->assertEquals('media-folder', $entity->path);

		$entity->path = 'Media Folder!@#$%';
		$this->assertEquals('media-folder', $entity->path);

		$entity->path = 'IMAGES FOLDER';
		$this->assertEquals('images-folder', $entity->path);

		$entity->path = 'parent/child/subfolder';
		$this->assertEquals('subfolder', $entity->path);

		$entity->path = '/leading/and/trailing/slashes/';
		$this->assertEquals('slashes', $entity->path);

		$entity->path = null;
		$this->assertNull($entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::_setPath()
	 */
	public function testPathCleaningViaSetMethod(): void {
		$entity = new MediaFolder();

		$entity->set('path', 'Media Folder');
		$this->assertEquals('media-folder', $entity->path);

		$entity->set('path', 'MediaFolder');
		$this->assertEquals('mediafolder', $entity->path);

		$entity->set('path', 'Media-Folder');
		$this->assertEquals('media-folder', $entity->path);

		$entity->set('path', 'Media Folder!@#$%');
		$this->assertEquals('media-folder', $entity->path);

		$entity->set('path', 'IMAGES FOLDER');
		$this->assertEquals('images-folder', $entity->path);

		$entity->set('path', 'parent/child/subfolder');
		$this->assertEquals('subfolder', $entity->path);

		$entity->set('path', '/leading/and/trailing/slashes/');
		$this->assertEquals('slashes', $entity->path);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('path', null);
		$this->assertNull($entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::_setPath()
	 */
	public function testPathCleaningSkippedWhenDeleted(): void {
		$entity = new MediaFolder(['deleted' => true]);

		$entity->path = 'Deleted Folder With/Special!Characters';
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertEquals('Deleted Folder With/Special!Characters', $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::getChildren()
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $parent */
		$parent = $table->get(2);

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(1, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $parent */
		$parent = $table->get(2);

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(2, $nestedChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::getParent()
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $child */
		$child = $table->get(6);

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(5, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $folder */
		$folder = $table->get(2);

		$parent = $folder->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::getParents()
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $folder */
		$folder = $table->get(6);

		$parents = $folder->getParents();

		$this->assertNotNull($parents);
		$this->assertCount(2, $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder::getParents()
	 */
	public function _testGetParentsWithNoParents(): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = FactoryLocator::get('Table')->get('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $folder */
		$folder = $table->get(2);

		$parents = $folder->getParents();

		$this->assertEmpty($parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaFolder
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'parentId' => 123,
			'path' => 'Test Media/Folder',
			'languageShortcode' => 'de',
			'title' => 'Test Media Folder',
			'hidden' => false,
			'systemOrder' => 10,
			'active' => true,
			'parentsActive' => true,
			'deleted' => false,
		];

		$entity = new MediaFolder($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->parentId);
		$this->assertEquals('folder', $entity->path); // Path cleaning removes path
		$this->assertEquals('de', $entity->languageShortcode);
		$this->assertEquals('Test Media Folder', $entity->title);
		$this->assertFalse($entity->hidden);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertTrue($entity->parentsActive);
		$this->assertFalse($entity->deleted);
	}
}
