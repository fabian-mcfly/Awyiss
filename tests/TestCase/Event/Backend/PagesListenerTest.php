<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Event\Backend\PagesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Customer\Model\Enum\PageRole;


/**
 * PagesListener Test Case
 *
 * @see \Awyiss\Event\Backend\PagesListener
 */
class PagesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\PagesListener
	 */
	protected PagesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new PagesListener();
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Pages.beforeCopy' => 'beforeCopy',
			'Model.Pages.afterCopy' => 'afterCopy',
			'Model.Pages.beforeSave' => 'beforeSave',
			'Model.Pages.afterSave' => 'afterSave',
			'Model.Pages.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Pages.beforeDelete' => 'beforeDelete',
			'Model.Pages.afterSoftDelete' => 'afterSoftDelete',
			'Model.Pages.afterDelete' => 'afterDelete',
			'Model.Newscategories.beforeCopy' => 'beforeCopy',
			'Model.Newscategories.afterCopy' => 'afterCopy',
			'Model.Newscategories.beforeSave' => 'beforeSave',
			'Model.Newscategories.afterSave' => 'afterSave',
			'Model.Newscategories.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Newscategories.beforeDelete' => 'beforeDelete',
			'Model.Newscategories.afterSoftDelete' => 'afterSoftDelete',
			'Model.Newscategories.afterDelete' => 'afterDelete',
			'Model.News.beforeCopy' => 'beforeCopy',
			'Model.News.afterCopy' => 'afterCopy',
			'Model.News.beforeSave' => 'beforeSave',
			'Model.News.afterSave' => 'afterSave',
			'Model.News.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.News.beforeDelete' => 'beforeDelete',
			'Model.News.afterSoftDelete' => 'afterSoftDelete',
			'Model.News.afterDelete' => 'afterDelete',
			'Model.Products.beforeCopy' => 'beforeCopy',
			'Model.Products.afterCopy' => 'afterCopy',
			'Model.Products.beforeSave' => 'beforeSave',
			'Model.Products.afterSave' => 'afterSave',
			'Model.Products.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Products.beforeDelete' => 'beforeDelete',
			'Model.Products.afterSoftDelete' => 'afterSoftDelete',
			'Model.Products.afterDelete' => 'afterDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyLoadsChildren(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$this->assertNotNull($page->get('childPages'));
		$this->assertCount(5, $page->get('childPages'));

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();
		$this->assertSame([
			'Unternehmensgeschichte',
			'Mission und Vision',
			'Teamvorstellung',
			'Zertifikate und Auszeichnungen',
			'Aktuelles',
		], array_column($children, 'title'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyLoadsChildrenOfDifferentPageRoleWhenCopyDescendantsWithDifferentPageRole(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$this->assertNotNull($page->get('childPages'));
		$this->assertCount(5, $page->get('childPages'));

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();
		$this->assertSame([
			'Unternehmensgeschichte',
			'Mission und Vision',
			'Teamvorstellung',
			'Zertifikate und Auszeichnungen',
			'Aktuelles',
			'Branchennews',
			'Nicht noch ne Katze',
			'Neues CMS auf CakePHP-Basis revolutioniert Webentwicklung',
			'asdf',
			'Dummynews #2',
			'Dummynews #1',
			'Fachartikel',
			'Unternehmensnews',
		], array_column($children, 'title'));

		/** @var \Awyiss\Model\Entity\Page $child */
		foreach ($children as $child) {
			if ($child->title === 'Dummynews #1') {
				$this->assertNotEmpty($child->mediaAssignments);
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyRemovesPrimaryKeyOfNestedChildren(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();

		/** @var \Awyiss\Model\Entity\Page $child */
		foreach ($children as $child) {
			$this->assertFalse($child->has('id'));
			/** @noinspection PhpUndefinedFieldInspection */
			$this->assertNotEmpty($child->originalPrimaryKeyValues);
		}

		$originalPrimaryKeyValues = array_column($children, 'originalPrimaryKeyValues');
		$this->assertSame([
			['id' => 3],
			['id' => 4],
			['id' => 5],
			['id' => 6],
			['id' => 7],
			['id' => 36],
			['id' => 40],
			['id' => 37],
			['id' => 41],
			['id' => 39],
			['id' => 38],
			['id' => 34],
			['id' => 35],
		], $originalPrimaryKeyValues);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyPropagatesRelatedColumnChangesToNestedChildren(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		$page->languageShortcode = 'it';
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();

		/** @var \Awyiss\Model\Entity\Page $child */
		foreach ($children as $child) {
			$this->assertSame('it', $child->languageShortcode);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyRemovesAttributesOfNestedChildrenOfNotSamePageRole(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();

		/** @var \Awyiss\Model\Entity\Page $child */
		foreach ($children as $child) {
			if ($child->pageRoleId !== PageRole::Page) {
				$this->assertFalse($child->has('attributes'));
			}
			else {
				$this->assertTrue($child->has('attributes'));
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyDisablesBuildRulesOnChildAssociation(): void {
		$newsTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $newsTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $newsTable);

		$association = $newsTable->ChildPages;

		$this->assertTrue($association->getBehavior('Nest')->getConfig('buildRules'));
		$this->assertTrue($association->getBehavior('Categories')->getConfig('buildRules'));

		$this->listener->beforeCopy($event, $page, $options);

		$this->assertFalse($association->getBehavior('Nest')->getConfig('buildRules'));
		$this->assertFalse($association->getBehavior('Categories')->getConfig('buildRules'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopySkipsWhenNotPrimary(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => false]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$this->assertNull($page->get('childPages'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyWithNoChildren(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$this->assertNull($page->get('childPages'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyWithoutOriginalEntity(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$this->assertNull($page->get('childPages'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveSetsSlugFromTitleWhenEmpty(): void {
		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'title' => 'Test Page Title',
			'slug' => '',
			'languageShortcode' => 'de',
		]);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('test-page-title', $entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveSetsParentsActiveDependingOnParent(): void {
		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'title' => 'Test Page',
			'slug' => 'test-page',
			'languageShortcode' => 'de',
			'parentId' => 36,
			'parentsActive' => true,
		]);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$pagesTable->updateAll(['active' => false], ['id' => 36]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$pagesTable->updateAll(['active' => true], ['id' => 36]);

		$this->assertFalse($entity->parentsActive);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertTrue($entity->parentsActive);

		$pagesTable->updateAll(['parents_active' => false], ['id' => 36]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertFalse($entity->parentsActive);

		$pagesTable->updateAll(['parents_active' => true], ['id' => 36]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertTrue($entity->parentsActive);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveSetsParentsActiveTrueWhenNoParent(): void {
		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'title' => 'Test Page',
			'slug' => 'test-page',
			'languageShortcode' => 'de',
			'parentId' => null,
			'parentsActive' => false,
		]);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertTrue($entity->parentsActive);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEnsuresUniqueSlugForNewEntity(): void {
		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'title' => 'Test Page',
			'slug' => 'dummynews-1',
			'languageShortcode' => 'de',
			'parentId' => 36,
		]);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('ueber-uns/aktuelles/branchennews/dummynews-1-2', $entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveNotEnsuresUniqueSlugForExistingEntityWhenPathChanged(): void {
		$pagesTable = $this->fetchTable('News');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(38);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$entity->slug = 'ueber-uns/aktuelles/branchennews/dummynews-2';

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('ueber-uns/aktuelles/branchennews/dummynews-2-2', $entity->slug);

		$entity->languageShortcode = 'it';
		$entity->slug = 'ueber-uns/aktuelles/branchennews/dummynews-2';

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('ueber-uns/aktuelles/branchennews/dummynews-2', $entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveNotEnsuresUniqueSlugForExistingEntityWhenPathUnchanged(): void {
		$pagesTable = $this->fetchTable('News');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(38);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('ueber-uns/aktuelles/branchennews/dummynews-1', $entity->slug);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->slug = 'ueber-uns/aktuelles/branchennews/dummynews-2';
		$entity->slug = 'ueber-uns/aktuelles/branchennews/dummynews-1';

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('ueber-uns/aktuelles/branchennews/dummynews-1', $entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEnsuresUniqueSlugForExistingEntityWhenPathUnchangedAndLanguageChanged(): void {
		$pagesTable = $this->fetchTable('News');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->get(38);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$entity->slug = 'inicio';
		$entity->parentId = null;
		$entity->clean();
		$entity->languageShortcode = 'es';

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('inicio-2', $entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEnsuringUniqueSlugNeverExceedsMaxLength(): void {
		$longSlug = 'ueber-uns/aktuelles/branchennews/' . str_repeat('dummynews', 111);
		$longSlug = substr($longSlug, 0, 1024);

		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'title' => 'Test Page',
			'slug' => $longSlug,
			'languageShortcode' => 'de',
			'parentId' => 36,
		]);

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$pagesTable->updateAll(['slug' => $longSlug], ['id' => 38]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$pagesTable->updateAll(['slug' => 'ueber-uns/aktuelles/branchennews/dummynews-1'], ['id' => 38]);

		$this->assertEquals(1024, strlen($entity->slug));
		$this->assertStringStartsWith('ueber-uns/aktuelles/branchennews/', $entity->slug);
		$this->assertStringEndsWith('dummynew-2', $entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveMarksSlugAsNotDirtyWhenUnchanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$entity = $pagesTable->newDefaultEntity([
			'id' => 123,
			'title' => 'Test Page',
			'slug' => 'test-page',
			'languageShortcode' => 'de',
		]);
		$entity->setNew(false);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->slug = 'new-page';
		$entity->slug = 'test-page';

		$this->assertTrue($entity->isDirty('slug'));

		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertFalse($entity->isDirty('slug'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterCopy()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCopyTransfersAttributesOfDifferentPageRoles(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$attributesPagesTable = $this->fetchTable('AttributesPages');
		$attributesNewsTable = $this->fetchTable('AttributesNews');

		$this->assertCount(1, $attributesPagesTable->find()->all());
		$this->assertCount(1, $attributesNewsTable->find()->all());

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();

		$this->assertCount(13, $children);

		$id = 345;
		foreach ($children as $child) {
			// Make sure an id is set
			$child->id = $id++;
		}

		$event = new Event('Model.Pages.afterCopy', $pagesTable);

		$this->listener->afterCopy($event, $page, $options);

		$this->assertCount(1, $attributesPagesTable->find()->all());
		$this->assertCount(2, $attributesNewsTable->find()->all());

		$attributesNewsTable->deleteAll(['page_id >=' => 345]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterCopy()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCopyNotTransfersAttributesOfDifferentPageRolesWhenNotPrimary(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$attributesPagesTable = $this->fetchTable('AttributesPages');
		$attributesNewsTable = $this->fetchTable('AttributesNews');

		$this->assertCount(1, $attributesPagesTable->find()->all());
		$this->assertCount(1, $attributesNewsTable->find()->all());

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();

		$this->assertCount(13, $children);

		$id = 345;
		foreach ($children as $child) {
			// Make sure an id is set
			$child->id = $id++;
		}

		$options = new ArrayObject(['_primary' => false, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.afterCopy', $pagesTable);

		$this->listener->afterCopy($event, $page, $options);

		$this->assertCount(1, $attributesPagesTable->find()->all());
		$this->assertCount(1, $attributesNewsTable->find()->all());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterCopy()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCopyNotTransfersAttributesOfDifferentPageRolesWhenNotCopyDescendantsWithDifferentPageRole(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(2);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = $page;

		$attributesPagesTable = $this->fetchTable('AttributesPages');
		$attributesNewsTable = $this->fetchTable('AttributesNews');

		$this->assertCount(1, $attributesPagesTable->find()->all());
		$this->assertCount(1, $attributesNewsTable->find()->all());

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$children = collection($page->get('childPages'))->listNested('desc', 'childPages')->toList();

		$this->assertCount(13, $children);

		$id = 345;
		foreach ($children as $child) {
			// Make sure an id is set
			$child->id = $id++;
		}

		$options = new ArrayObject(['_primary' => true, 'copyDescendantsWithDifferentPageRole' => false]);
		$event = new Event('Model.Pages.afterCopy', $pagesTable);

		$this->listener->afterCopy($event, $page, $options);

		$this->assertCount(1, $attributesPagesTable->find()->all());
		$this->assertCount(1, $attributesNewsTable->find()->all());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterCopy()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCopyCopiesContents(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = unserialize(serialize($page));
		$page->unset('id');

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$page->id = 369;

		$contentsTable = $this->fetchTable('Contents');
		$this->assertCount(24, $contentsTable->find()->where(['page_id' => 1])->all());
		$this->assertCount(0, $contentsTable->find()->where(['page_id' => 369])->all());

		$event = new Event('Model.Pages.afterCopy', $pagesTable);

		$this->listener->afterCopy($event, $page, $options);

		$this->assertCount(24, $contentsTable->find()->where(['page_id' => 1])->all());
		$this->assertCount(24, $contentsTable->find()->where(['page_id' => 369])->all());

		$contentsTable->deleteAll(['page_id' => 369]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCopyCopiesMediaAssignmentsOfContents(): void {
		$request = Router::getRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		$this->login();

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$this->assertCount(17, $mediaAssignmentsTable->find()->where(['scope' => 'contents'])->all());

		$page = $pagesTable->save($page, ['asCopy' => true, 'audit' => ['skip' => true], 'systemOrder' => ['skip' => true]]);
		$this->assertNotFalse($page);

		$this->assertCount(21, $mediaAssignmentsTable->find()->where(['scope' => 'contents'])->all());

		$pagesTable->deleteAll(['id' => $page->id]);
		$mediaAssignmentsTable->deleteAll(['id >' => 37]);
		$this->fetchTable('Contents')->deleteAll(['page_id' => $page->id]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterCopy()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCopyNotCreatesAuditForCopiedContents(): void {
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$page->originalEntity = unserialize(serialize($page));
		$page->unset('id');

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Pages.beforeCopy', $pagesTable);

		$this->listener->beforeCopy($event, $page, $options);

		$page->id = 369;

		$auditTable = $this->fetchTable('Audit');
		$auditCount = $auditTable->find()->count();

		$event = new Event('Model.Pages.afterCopy', $pagesTable);

		$this->listener->afterCopy($event, $page, $options);

		$this->assertSame($auditCount, $auditTable->find()->count());

		$contentsTable = $this->fetchTable('Contents');
		$contentsTable->deleteAll(['page_id' => 369]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveAddsMenuEntriesWhenSet(): void {
		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'id' => 123,
			'title' => 'Test Page',
			'slug' => 'new-test-page',
			'languageShortcode' => 'de',
			'addMenuEntry' => [1, 2],
		]);

		$menuEntriesTable = $this->fetchTable('MenuEntries');
		$this->assertCount(35, $menuEntriesTable->find()->where(['menu_id' => 1])->all());
		$this->assertCount(12, $menuEntriesTable->find()->where(['menu_id' => 2])->all());
		$this->assertCount(3, $menuEntriesTable->find()->where(['menu_id' => 3])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/new-test-page'])->all());

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->assertCount(36, $menuEntriesTable->find()->where(['menu_id' => 1])->all());
		$this->assertCount(13, $menuEntriesTable->find()->where(['menu_id' => 2])->all());
		$this->assertCount(3, $menuEntriesTable->find()->where(['menu_id' => 3])->all());
		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/new-test-page'])->all());

		$menuEntriesTable->deleteAll(['link' => 'de/new-test-page']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotAddsMenuEntriesWhenNotSet(): void {
		$pagesTable = $this->fetchTable('Pages');
		$entity = $pagesTable->newDefaultEntity([
			'id' => 123,
			'title' => 'Test Page',
			'slug' => 'new-test-page',
			'languageShortcode' => 'de',
			'addMenuEntry' => [],
		]);

		$menuEntriesTable = $this->fetchTable('MenuEntries');
		$this->assertCount(35, $menuEntriesTable->find()->where(['menu_id' => 1])->all());
		$this->assertCount(12, $menuEntriesTable->find()->where(['menu_id' => 2])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/new-test-page'])->all());

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->assertCount(35, $menuEntriesTable->find()->where(['menu_id' => 1])->all());
		$this->assertCount(12, $menuEntriesTable->find()->where(['menu_id' => 2])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/new-test-page'])->all());

		$menuEntriesTable->deleteAll(['link' => 'de/new-test-page']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCreatesHistoricalSlugsWhenSlugChanged(): void {
		$pagesTable = $this->fetchTable('Pages');
		$urlHistoryTable = $this->fetchTable('UrlHistory');

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		$originalSlug = $page->slug;
		$page->slug = 'new-slug';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$initialCount = $urlHistoryTable->find()->count();

		$this->listener->afterSave($event, $page, $options);

		$this->assertSame($initialCount + 1, $urlHistoryTable->find()->count());

		$historicalSlug = $urlHistoryTable->find()->where([
			'url' => $page->languageShortcode . '/' . $originalSlug,
			'scope' => 'pages',
			'foreign_key' => $page->id,
			'status' => 308,
		]);

		$this->assertCount(1, $historicalSlug);

		$urlHistoryTable->deleteAll(['url' => $page->languageShortcode . '/' . $originalSlug]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotCreatesHistoricalSlugsWhenSlugUnchanged(): void {
		$pagesTable = $this->fetchTable('Pages');
		$urlHistoryTable = $this->fetchTable('UrlHistory');

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		$originalSlug = $page->slug;

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->slug = 'new-slug';
		$page->slug = 'startseite';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$initialCount = $urlHistoryTable->find()->count();

		$this->listener->afterSave($event, $page, $options);

		$this->assertSame($initialCount, $urlHistoryTable->find()->count());

		$historicalSlug = $urlHistoryTable->find()->where([
			'url' => $page->languageShortcode . '/' . $originalSlug,
			'scope' => 'pages',
			'foreign_key' => $page->id,
			'status' => 308,
		]);

		$this->assertCount(0, $historicalSlug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCreatesHistoricalSlugsWhenLanguageShortcodeChanged(): void {
		$pagesTable = $this->fetchTable('Pages');
		$urlHistoryTable = $this->fetchTable('UrlHistory');

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		$originalShortcode = $page->languageShortcode;
		$page->languageShortcode = 'en';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$initialCount = $urlHistoryTable->find()->count();

		$this->listener->afterSave($event, $page, $options);

		$this->assertSame($initialCount + 1, $urlHistoryTable->find()->count());

		$historicalSlug = $urlHistoryTable->find()->where([
			'url' => $originalShortcode . '/' . $page->slug,
			'scope' => 'pages',
			'foreign_key' => $page->id,
			'status' => 308,
		]);

		$this->assertCount(1, $historicalSlug);

		$urlHistoryTable->deleteAll(['url' => $originalShortcode . '/' . $page->slug]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotCreatesHistoricalSlugsWhenLanguageShortcodeUnchanged(): void {
		$pagesTable = $this->fetchTable('Pages');
		$urlHistoryTable = $this->fetchTable('UrlHistory');

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);
		$originalShortcode = $page->languageShortcode;

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->languageShortcode = 'en';
		$page->languageShortcode = $originalShortcode;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$initialCount = $urlHistoryTable->find()->count();

		$this->listener->afterSave($event, $page, $options);

		$this->assertSame($initialCount, $urlHistoryTable->find()->count());

		$historicalSlug = $urlHistoryTable->find()->where([
			'url' => $originalShortcode . '/' . $page->slug,
			'scope' => 'pages',
			'foreign_key' => $page->id,
			'status' => 308,
		]);

		$this->assertCount(0, $historicalSlug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveUpdatesMenuEntriesWhenSlugChanged(): void {
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		$menuEntries = [
			$menuEntry1 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 1,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
			$menuEntry2 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 2,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
		];

		$result = $menuEntriesTable->saveMany($menuEntries);
		$this->assertNotFalse($result);

		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/new-slug'])->all());

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		$page->slug = 'new-slug';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());

		$newMenuEntries = $menuEntriesTable->find()->where(['link' => 'de/new-slug'])->orderByAsc('id')->all();
		$this->assertCount(2, $newMenuEntries);

		$menuEntryIds = $newMenuEntries->extract('id')->toList();

		$this->assertSame([
			$menuEntry1->id,
			$menuEntry2->id,
		], $menuEntryIds);

		$menuEntriesTable->deleteAll(['id IN' => $menuEntryIds]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesMenuEntriesWhenSlugUnchanged(): void {
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		$menuEntries = [
			$menuEntry1 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 1,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
			$menuEntry2 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 2,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
		];

		$result = $menuEntriesTable->saveMany($menuEntries);
		$this->assertNotFalse($result);

		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/new-slug'])->all());

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->slug = 'new-slug';
		$page->slug = 'startseite';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/new-slug'])->all());

		$menuEntryIds = [$menuEntry1->id, $menuEntry2->id];

		$menuEntriesTable->deleteAll(['id IN' => $menuEntryIds]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveUpdatesMenuEntriesWhenLanguageShortcodeChanged(): void {
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		$menuEntries = [
			$menuEntry1 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 1,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
			$menuEntry2 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 2,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
		];

		$result = $menuEntriesTable->saveMany($menuEntries);
		$this->assertNotFalse($result);

		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'en/startseite'])->all());

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		$page->languageShortcode = 'en';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());

		$newMenuEntries = $menuEntriesTable->find()->where(['link' => 'en/startseite'])->orderByAsc('id')->all();
		$this->assertCount(2, $newMenuEntries);

		$menuEntryIds = $newMenuEntries->extract('id')->toList();

		$this->assertSame([
			$menuEntry1->id,
			$menuEntry2->id,
		], $menuEntryIds);

		$menuEntriesTable->deleteAll(['id IN' => $menuEntryIds]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesMenuEntriesWhenLanguageShortcodeUnchanged(): void {
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		$menuEntries = [
			$menuEntry1 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 1,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
			$menuEntry2 = $menuEntriesTable->newDefaultEntity([
				'menu_id' => 2,
				'link' => 'de/startseite',
				'title' => 'Dummy Entry',
				'languageShortcode' => 'de',
				'systemOrder' => 999,
			]),
		];

		$result = $menuEntriesTable->saveMany($menuEntries);
		$this->assertNotFalse($result);

		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'en/startseite'])->all());

		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->get(1);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->languageShortcode = 'en';
		$page->languageShortcode = 'de';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$this->assertCount(2, $menuEntriesTable->find()->where(['link' => 'de/startseite'])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['link' => 'en/startseite'])->all());

		$menuEntryIds = [$menuEntry1->id, $menuEntry2->id];

		$menuEntriesTable->deleteAll(['id IN' => $menuEntryIds]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveUpdatesDescendantSlugsWhenSlugChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Root Employer',
			'language_shortcode' => 'xy',
		])->first();

		$page->slug = 'new-root-slug';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$slugs = $pages->extract('slug')->toList();

		$this->assertSame([
			'root-employer',
			'new-root-slug/child-employer-1',
			'new-root-slug/child-employer-1/grandchild-employer-1',
			'new-root-slug/child-employer-1/grandchild-employer-2',
			'new-root-slug/child-employer-2',
			'new-root-slug/child-employer-3',
			'new-root-slug/child-employer-4',
			'new-root-slug/child-employer-5',
			'new-root-slug/child-employer-5/grandchild-employer-3',
		], $slugs);

		$otherLanguagePages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->orderByAsc('Pages.id')->all();
		$slugs = $otherLanguagePages->extract('slug')->toList();

		$this->assertSame([
			'root-employer',
			'root-employer/child-employer-1',
			'root-employer/child-employer-1/grandchild-employer-1',
		], $slugs);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesParentsSlugsWhenSlugChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Child Employer 1',
			'language_shortcode' => 'xy',
		])->first();

		$page->slug = 'new-child-slug';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$slugs = $pages->extract('slug')->toList();

		$this->assertSame([
			'root-employer',
			'root-employer/child-employer-1',
			'root-employer/new-child-slug/grandchild-employer-1',
			'root-employer/new-child-slug/grandchild-employer-2',
			'root-employer/child-employer-2',
			'root-employer/child-employer-3',
			'root-employer/child-employer-4',
			'root-employer/child-employer-5',
			'root-employer/child-employer-5/grandchild-employer-3',
		], $slugs);

		$otherLanguagePages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->orderByAsc('Pages.id')->all();
		$slugs = $otherLanguagePages->extract('slug')->toList();

		$this->assertSame([
			'root-employer',
			'root-employer/child-employer-1',
			'root-employer/child-employer-1/grandchild-employer-1',
		], $slugs);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesDescendantSlugsWhenSlugUnchanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Root Employer',
			'language_shortcode' => 'xy',
		])->first();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->slug = 'new-root-slug';
		$page->slug = 'root-employer';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$slugs = $pages->extract('slug')->toList();

		$this->assertSame([
			'root-employer',
			'root-employer/child-employer-1',
			'root-employer/child-employer-1/grandchild-employer-1',
			'root-employer/child-employer-1/grandchild-employer-2',
			'root-employer/child-employer-2',
			'root-employer/child-employer-3',
			'root-employer/child-employer-4',
			'root-employer/child-employer-5',
			'root-employer/child-employer-5/grandchild-employer-3',
		], $slugs);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveUpdatesDescendantParentsActiveWhenActiveChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Root Employer',
			'language_shortcode' => 'xy',
		])->first();

		$page->active = false;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
		], $actives);

		$otherLanguagePages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->orderByAsc('Pages.id')->all();
		$actives = $otherLanguagePages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$page->clean();
		$page->active = true;

		$this->listener->beforeSave($event, $page, $options);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesParentsParentsActiveWhenActiveChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Child Employer 1',
			'language_shortcode' => 'xy',
		])->first();

		$page->active = false;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, false],
			[true, false],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$otherLanguagePages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->orderByAsc('Pages.id')->all();
		$actives = $otherLanguagePages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesDescendantParentsActiveWhenActiveUnchanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find()->where([
			'title' => 'Root Employer',
			'language_shortcode' => 'xy',
		])->first();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->active = false;
		$page->active = true;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveUpdatesDescendantParentsActiveWhenParentsActiveChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Root Employer',
			'language_shortcode' => 'xy',
		])->first();

		$page->parentsActive = false;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
		], $actives);

		$otherLanguagePages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->orderByAsc('Pages.id')->all();
		$actives = $otherLanguagePages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$page->clean();
		$page->parentsActive = true;

		$this->listener->beforeSave($event, $page, $options);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesParentsParentsActiveWhenParentsActiveChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find('all', skipPageRoleCheck: true)->where([
			'title' => 'Child Employer 1',
			'language_shortcode' => 'xy',
		])->first();

		$page->parentsActive = false;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, false],
			[true, false],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$otherLanguagePages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->orderByAsc('Pages.id')->all();
		$actives = $otherLanguagePages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesDescendantParentsActiveWhenParentsActiveUnchanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $pagesTable->find()->where([
			'title' => 'Root Employer',
			'language_shortcode' => 'xy',
		])->first();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$page->parentsActive = false;
		$page->parentsActive = true;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $page, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);

		$this->listener->afterSave($event, $page, $options);

		$pages = $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->orderByAsc('Pages.id')->all();
		$actives = $pages->extract(function (Page $page): array {
			return [$page->active, $page->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesDescendantParentsActiveWhenParentsActiveForDescendantsWithInactiveParents(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $root */
		$root = $pagesTable->get(890);
		$root->active = false;
		$pagesTable->save($root, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\Page $child1 */
		$child1 = $pagesTable->get(891, skipPageRoleCheck: true);

		$this->assertFalse($child1->parentsActive);

		$child1->active = false;
		$pagesTable->save($child1, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\Page $grandchild */
		$grandchild = $pagesTable->get(892);
		$this->assertFalse($grandchild->parentsActive);

		$root->active = true;

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $root, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);
		$this->listener->afterSave($event, $root, $options);

		/** @var \Awyiss\Model\Entity\Page $child1 */
		$child1 = $pagesTable->get(891, skipPageRoleCheck: true);
		$this->assertTrue($child1->parentsActive);
		$this->assertFalse($child1->active);

		/** @var \Awyiss\Model\Entity\Page $grandchild */
		$grandchild = $pagesTable->get(892, skipPageRoleCheck: true);
		$this->assertFalse($grandchild->parentsActive);
		$this->assertTrue($grandchild->active);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotUpdatesDescendantParentsActiveWhenParentsActiveForDescendantsWithInactiveParentsAndUpdatesSlugOfAllDescendantsWhenSlugChanged(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->createDummyPages();

		/** @var \Awyiss\Model\Entity\Page $root */
		$root = $pagesTable->get(890);
		$root->active = false;
		$pagesTable->save($root, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\Page $child1 */
		$child1 = $pagesTable->get(891, skipPageRoleCheck: true);

		$this->assertFalse($child1->parentsActive);

		$child1->active = false;
		$pagesTable->save($child1, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\Page $grandchild */
		$grandchild = $pagesTable->get(892);
		$this->assertFalse($grandchild->parentsActive);

		$root->active = true;
		$root->slug = 'new-root-slug';

		$options = new ArrayObject();
		$event = new Event('Model.Pages.beforeSave', $pagesTable);

		$this->listener->beforeSave($event, $root, $options);

		$event = new Event('Model.Pages.afterSave', $pagesTable);
		$this->listener->afterSave($event, $root, $options);

		/** @var \Awyiss\Model\Entity\Page $child1 */
		$child1 = $pagesTable->get(891, skipPageRoleCheck: true);
		$this->assertTrue($child1->parentsActive);
		$this->assertFalse($child1->active);
		$this->assertSame('new-root-slug/child-employer-1', $child1->slug);

		/** @var \Awyiss\Model\Entity\Page $grandchild */
		$grandchild = $pagesTable->get(892, skipPageRoleCheck: true);
		$this->assertFalse($grandchild->parentsActive);
		$this->assertTrue($grandchild->active);
		$this->assertSame('new-root-slug/child-employer-1/grandchild-employer-1', $grandchild->slug);

		$this->deleteDummyPages();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSoftDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSoftDeleteDisabledCascadingOnChildContents(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->assertTrue($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertTrue($pagesTable->Contents->ChildContents->getDependent());

		$event = new Event('Model.Pages.beforeSoftDelete', $pagesTable);

		$this->listener->beforeSoftDelete($event);

		$this->assertFalse($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertFalse($pagesTable->Contents->ChildContents->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeDeleteDisabledCascadingOnChildContents(): void {
		$pagesTable = $this->fetchTable('Pages');

		$this->assertTrue($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertTrue($pagesTable->Contents->ChildContents->getDependent());

		$event = new Event('Model.Pages.beforeDelete', $pagesTable);

		$this->listener->beforeDelete($event);

		$this->assertFalse($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertFalse($pagesTable->Contents->ChildContents->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterSoftDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteEnabledCascadingOnChildContents(): void {
		$pagesTable = $this->fetchTable('Pages');

		$event = new Event('Model.Pages.beforeSoftDelete', $pagesTable);

		$this->listener->beforeSoftDelete($event);

		$this->assertFalse($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertFalse($pagesTable->Contents->ChildContents->getDependent());

		$event = new Event('Model.Pages.afterSoftDelete', $pagesTable);
		$this->listener->afterSoftDelete($event);

		$this->assertTrue($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertTrue($pagesTable->Contents->ChildContents->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteEnabledCascadingOnChildContents(): void {
		$pagesTable = $this->fetchTable('Pages');

		$event = new Event('Model.Pages.beforeDelete', $pagesTable);

		$this->listener->beforeDelete($event);

		$this->assertFalse($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertFalse($pagesTable->Contents->ChildContents->getDependent());

		$event = new Event('Model.Pages.afterDelete', $pagesTable);
		$this->listener->afterDelete($event);

		$this->assertTrue($pagesTable->Contents->ChildContents->getCascadeCallbacks());
		$this->assertTrue($pagesTable->Contents->ChildContents->getDependent());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function createDummyPages(): void {
		$pagesTable = $this->fetchTable('Pages');

		$pagesTable->deleteAll(['language_shortcode' => 'xy']);
		$pagesTable->deleteAll(['language_shortcode' => 'yx']);

		$insertQuery = $pagesTable->insertQuery();
		$insertQuery->insert([
			'id',
			'title',
			'slug',
			'page_role_id',
			'page_template_id',
			'active',
			'language_shortcode',
			'parent_id',
			'system_order',
		]);

		$insertQuery->values([
			'id' => 890,
			'title' => 'Root Employer',
			'slug' => 'root-employer',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => null,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 891,
			'title' => 'Child Employer 1',
			'slug' => 'root-employer/child-employer-1',
			'page_role_id' => 2,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 890,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 892,
			'title' => 'Grandchild Employer 1',
			'slug' => 'root-employer/child-employer-1/grandchild-employer-1',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 891,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 893,
			'title' => 'Grandchild Employer 2',
			'slug' => 'root-employer/child-employer-1/grandchild-employer-2',
			'page_role_id' => 2,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 891,
			'system_order' => 2,
		]);

		$insertQuery->values([
			'id' => 894,
			'title' => 'Child Employer 2',
			'slug' => 'root-employer/child-employer-2',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 890,
			'system_order' => 2,
		]);

		$insertQuery->values([
			'id' => 895,
			'title' => 'Child Employer 3',
			'slug' => 'root-employer/child-employer-3',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 890,
			'system_order' => 3,
		]);

		$insertQuery->values([
			'id' => 896,
			'title' => 'Child Employer 4',
			'slug' => 'root-employer/child-employer-4',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 890,
			'system_order' => 4,
		]);

		$insertQuery->values([
			'id' => 897,
			'title' => 'Child Employer 5',
			'slug' => 'root-employer/child-employer-5',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 890,
			'system_order' => 5,
		]);

		$insertQuery->values([
			'id' => 898,
			'title' => 'Grandchild Employer 3',
			'slug' => 'root-employer/child-employer-5/grandchild-employer-3',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'xy',
			'parent_id' => 897,
			'system_order' => 3,
		]);

		$insertQuery->values([
			'id' => 899,
			'title' => 'Root in Different Language',
			'slug' => 'root-employer',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'yx',
			'parent_id' => null,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 900,
			'title' => 'Child Employer 1',
			'slug' => 'root-employer/child-employer-1',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'yx',
			'parent_id' => 899,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 901,
			'title' => 'Grandchild Employer 1',
			'slug' => 'root-employer/child-employer-1/grandchild-employer-1',
			'page_role_id' => 1,
			'page_template_id' => 1,
			'active' => true,
			'language_shortcode' => 'yx',
			'parent_id' => 900,
			'system_order' => 1,
		]);

		$this->assertNotFalse($insertQuery->execute());

		$this->assertCount(9, $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->all());
		$this->assertCount(3, $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->all());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function deleteDummyPages(): void {
		$pagesTable = $this->fetchTable('Pages');

		$pagesTable->deleteAll(['language_shortcode' => 'xy']);
		$pagesTable->deleteAll(['language_shortcode' => 'yx']);

		$this->assertCount(0, $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'xy'])->all());
		$this->assertCount(0, $pagesTable->find('all', skipPageRoleCheck: true)->where(['language_shortcode' => 'yx'])->all());
	}
}
