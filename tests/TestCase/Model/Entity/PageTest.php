<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Page;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Customer\Model\Enum\PageRole;
use ReflectionClass;


/**
 * Page Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Page
 */
class PageTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Page();

		$this->assertSame([
			'parentId' => true,
			'slug' => true,
			'languageShortcode' => true,
			'title' => true,
			'redirectLink' => true,
			'metaTitle' => true,
			'metaDescription' => true,
			'robotsIndex' => true,
			'robotsFollow' => true,
			'pageRoleId' => true,
			'pageTemplateId' => true,
			'duplicateOf' => true,
			'formId' => true,
			'surveyId' => true,
			'systemOrder' => true,
			'active' => true,
			'addMenuEntry' => true,
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
	 * @see \Awyiss\Model\Entity\Page::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new Page();

		$this->assertSame(['link', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setSlug()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testSlugCleaningViaPropertyAssignment(): void {
		$entity = new Page();

		$entity->slug = 'Test Page Title';
		$this->assertEquals('test-page-title', $entity->slug);

		$entity->slug = 'TestPageTitle';
		$this->assertEquals('testpagetitle', $entity->slug);

		$entity->slug = 'Test-Page-Title';
		$this->assertEquals('test-page-title', $entity->slug);

		$entity->slug = 'Test Page Title!@#$%';
		$this->assertEquals('test-page-title', $entity->slug);

		$entity->slug = 'UPPERCASE PAGE TITLE';
		$this->assertEquals('uppercase-page-title', $entity->slug);

		$entity->slug = 'parent/child/grandchild';
		$this->assertEquals('grandchild', $entity->slug);

		$entity->slug = '/leading/and/trailing/slashes/';
		$this->assertEquals('slashes', $entity->slug);

		$entity->slug = null;
		$this->assertNull($entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setSlug()
	 */
	public function testSlugCleaningViaSetMethod(): void {
		$entity = new Page();

		$entity->set('slug', 'Test Page Title');
		$this->assertEquals('test-page-title', $entity->slug);

		$entity->set('slug', 'TestPageTitle');
		$this->assertEquals('testpagetitle', $entity->slug);

		$entity->set('slug', 'Test-Page-Title');
		$this->assertEquals('test-page-title', $entity->slug);

		$entity->set('slug', 'Test Page Title!@#$%');
		$this->assertEquals('test-page-title', $entity->slug);

		$entity->set('slug', 'UPPERCASE PAGE TITLE');
		$this->assertEquals('uppercase-page-title', $entity->slug);

		$entity->set('slug', 'parent/child/grandchild');
		$this->assertEquals('grandchild', $entity->slug);

		$entity->set('slug', '/leading/and/trailing/slashes/');
		$this->assertEquals('slashes', $entity->slug);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('slug', null);
		$this->assertNull($entity->slug);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setPageRoleId()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testPageRoleIdCleaningViaPropertyAssignment(): void {
		$entity = new Page();

		$entity->pageRoleId = '123';
		$this->assertEquals(123, $entity->pageRoleId);

		$entity->pageRoleId = 456;
		$this->assertEquals(456, $entity->pageRoleId);

		$entity->pageRoleId = null;
		$this->assertNull($entity->pageRoleId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setPageRoleId()
	 */
	public function testPageRoleIdCleaningViaSetMethod(): void {
		$entity = new Page();

		$entity->set('pageRoleId', '123');
		$this->assertEquals(123, $entity->pageRoleId);

		$entity->set('pageRoleId', 456);
		$this->assertEquals(456, $entity->pageRoleId);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('pageRoleId', null);
		$this->assertNull($entity->pageRoleId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setPageRoleId()
	 */
	public function testPageRoleIdWithEnumInterface(): void {
		$entity = new Page();

		$entity->pageRoleId = PageRole::Newscategory;

		$this->assertSame(PageRole::Newscategory, $entity->pageRoleId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setAddMenuEntry()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testAddMenuEntryCleaningViaPropertyAssignment(): void {
		$entity = new Page();

		$entity->addMenuEntry = ['1', '2', '3'];
		$this->assertEquals([1, 2, 3], $entity->addMenuEntry);

		$entity->addMenuEntry = [4, 5, 6];
		$this->assertEquals([4, 5, 6], $entity->addMenuEntry);

		$entity->addMenuEntry = [];
		$this->assertNull($entity->addMenuEntry);

		$entity->addMenuEntry = null;
		$this->assertNull($entity->addMenuEntry);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_setAddMenuEntry()
	 */
	public function testAddMenuEntryCleaningViaSetMethod(): void {
		$entity = new Page();

		$entity->set('addMenuEntry', ['1', '2', '3']);
		$this->assertEquals([1, 2, 3], $entity->addMenuEntry);

		$entity->set('addMenuEntry', [4, 5, 6]);
		$this->assertEquals([4, 5, 6], $entity->addMenuEntry);

		$entity->set('addMenuEntry', []);
		$this->assertNull($entity->addMenuEntry);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('addMenuEntry', null);
		$this->assertNull($entity->addMenuEntry);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_getLink()
	 */
	public function testLinkVirtualPropertyWithLanguageShortcode(): void {
		Configure::write('Route.includeLanguageShortcode', true);
		$reflection = new ReflectionClass(Page::class);
		$property = $reflection->getProperty('includeLanguageShortcode');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, true);

		$entity = new Page([
			'slug' => 'test-page',
			'languageShortcode' => 'de',
		]);

		$link = $entity->link;

		$this->assertIsString($link);
		$this->assertSame('de/test-page', $link);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::_getLink()
	 */
	public function testLinkVirtualPropertyWithoutLanguageShortcode(): void {
		$reflection = new ReflectionClass(Page::class);
		$property = $reflection->getProperty('includeLanguageShortcode');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, false);

		$entity = new Page([
			'slug' => 'test-page',
			'languageShortcode' => 'de',
		]);

		$link = $entity->link;

		$property->setValue(null, true);

		$this->assertIsString($link);
		$this->assertSame('test-page', $link);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::hasContentTemplate()
	 */
	public function testHasContentTemplate(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $table->get(3, contain: [
			'PageTemplates' => [
				'ContentAreas' => [
					'ContentTemplates',
				],
			],
		]);

		$result = $entity->hasContentTemplate();

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::hasContentTemplate()
	 */
	public function testHasContentTemplateWithNoPageTemplate(): void {
		$entity = new Page();

		$result = $entity->hasContentTemplate();

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::hasContentTemplate()
	 */
	public function testHasContentTemplateWithNoContentAreas(): void {
		$entity = new Page();
		$entity->pageTemplate = null;

		$result = $entity->hasContentTemplate();

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::getChildren()
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $parent */
		$parent = $table->get(2); // "Über uns" page which has children

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(5, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $parent */
		$parent = $table->get(2); // "Über uns" page which has nested children

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(5, $nestedChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::getParent()
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $child */
		$child = $table->get(3); // "Unternehmensgeschichte" page with parent_id = 2

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(2, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $table->get(1); // "Startseite" page with no parent

		$parent = $page->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::getParents()
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $deepChild */
		$deepChild = $table->get(40, skipPageRoleCheck: true); // "Nicht noch ne Katze" with multiple parents

		$parents = $deepChild->getParents(['finder' => ['all' => ['skipPageRoleCheck' => true]]]);

		$this->assertNotNull($parents);
		$this->assertCount(3, $parents); // Based on hierarchy: 36 -> 7 -> 2
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::getParents()
	 */
	public function testGetParentsWithNoParents(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $table->get(1); // "Startseite" page with no parent

		$parents = $page->getParents();

		$this->assertEmpty($parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'pageRoleId' => '1',
			'pageTemplateId' => 1,
			'parentId' => null,
			'languageShortcode' => 'de',
			'slug' => 'Test Page/Title',
			'title' => 'Test Page',
			'redirectLink' => '/redirect',
			'metaTitle' => 'Test Meta Title',
			'metaDescription' => 'Test Meta Description',
			'robotsIndex' => true,
			'robotsFollow' => false,
			'duplicateOf' => 2,
			'formId' => 3,
			'surveyId' => 4,
			'systemOrder' => 10,
			'active' => true,
			'parentsActive' => true,
			'addMenuEntry' => ['1', '2', '3'],
		];

		$entity = new Page($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(1, $entity->pageRoleId);
		$this->assertEquals(1, $entity->pageTemplateId);
		$this->assertNull($entity->parentId);
		$this->assertEquals('de', $entity->languageShortcode);
		$this->assertEquals('title', $entity->slug); // Slug cleaning removes path
		$this->assertEquals('Test Page', $entity->title);
		$this->assertEquals('/redirect', $entity->redirectLink);
		$this->assertEquals('Test Meta Title', $entity->metaTitle);
		$this->assertEquals('Test Meta Description', $entity->metaDescription);
		$this->assertTrue($entity->robotsIndex);
		$this->assertFalse($entity->robotsFollow);
		$this->assertEquals(2, $entity->duplicateOf);
		$this->assertEquals(3, $entity->formId);
		$this->assertEquals(4, $entity->surveyId);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertTrue($entity->parentsActive);
		$this->assertEquals([1, 2, 3], $entity->addMenuEntry);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Page::defaultValues()
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get('Pages');
		$entity = $table->newDefaultEntity();

		$this->assertSame(PageRole::Page, $entity->pageRoleId);
	}
}
