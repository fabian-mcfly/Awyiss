<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table\UsersTable;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\AuditHelper;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use ReflectionClass;


/**
 * AuditHelperTest class
 */
class AuditHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\AuditHelper
	 */
	protected AuditHelper $helper;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$view = new BackendView();
		$this->helper = new AuditHelper($view);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		// Delete the static::$userCache of the AuditHelper to prevent side effects between tests
		$reflection = new ReflectionClass(AuditHelper::class);
		$property = $reflection->getProperty('userCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, null);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUser()
	 */
	public function testGetUserReturnsUserWhenExists(): void {
		$result = $this->helper->getUser(1);

		$this->assertInstanceOf(User::class, $result);
		$this->assertSame(1, $result->id);
		$this->assertSame('awyiss', $result->username);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUser()
	 */
	public function testGetUserReturnsNullWhenNotExists(): void {
		$result = $this->helper->getUser(999);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUsername()
	 */
	public function testGetUsernameReturnsUsernameWhenUserExists(): void {
		$result = $this->helper->getUsername(2);

		$this->assertSame('awyiss-undecided-access', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUsername()
	 */
	public function testGetUsernameReturnsUnknownWhenUserNotExists(): void {
		$result = $this->helper->getUsername(999);

		$this->assertSame('user_unknown', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUser()
	 */
	public function testGetUserCachesUsersTableResults(): void {
		// Reset the cache
		$reflection = new ReflectionClass(AuditHelper::class);
		$property = $reflection->getProperty('userCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, null);

		$user1 = new User(['id' => 123, 'username' => 'testuser1']);
		$user2 = new User(['id' => 124, 'username' => 'testuser2']);

		$resultSet = $this->createStub(ResultSetInterface::class);
		$resultSet->method('indexBy')->willReturnSelf();
		$resultSet->method('toArray')->willReturn([123 => $user1, 124 => $user2]);

		$query = $this->createStub(SelectQuery::class);
		$query->method('all')->willReturn($resultSet);

		$usersTable = $this->getMockBuilder(UsersTable::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		// Ensure that 'find' is called only once
		$usersTable->expects($this->once())->method('find')->willReturn($query);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Users', $usersTable);

		$this->helper->getUser(123);
		$this->helper->getUser(124);
		$this->helper->getUser(123);
	}


	/**
	 * Test formatUsergroupPermissions
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatUsergroupPermissions()
	 */
	public function testFormatUsergroupPermissionsGroupsByScope(): void {
		$usergroupPermissionsTable = $this->fetchTable('UsergroupPermissions');

		// Create mock permission entities with different scopes
		$permission1 = $usergroupPermissionsTable->newEntity([
			'id' => 1,
			'scope' => 'Pages',
			'identifier' => 'pages_edit',
			'access' => PermissionAccess::Granted,
		]);
		// Set virtual property by mocking labelData - scope gets translated
		$permission1->set('labelData', ['scope' => 'Seite', 'identifier' => 'pages.edit', 'id' => 1]);
		$permission1->set('label', 'Edit Pages');

		$permission2 = $usergroupPermissionsTable->newEntity([
			'id' => 2,
			'scope' => 'Pages',
			'identifier' => 'pages_delete',
			'access' => PermissionAccess::Denied,
		]);
		$permission2->set('labelData', ['scope' => 'Seite', 'identifier' => 'pages.delete', 'id' => 2]);
		$permission2->set('label', 'Delete Pages');

		$permission3 = $usergroupPermissionsTable->newEntity([
			'id' => 3,
			'scope' => 'Media',
			'identifier' => 'media_upload',
			'access' => PermissionAccess::Granted,
		]);
		$permission3->set('labelData', ['scope' => 'Media', 'identifier' => 'media.upload', 'id' => 3]);
		$permission3->set('label', 'Upload Media');

		$result = $this->helper->formatUsergroupPermissions([$permission1, $permission2, $permission3]);

		$this->assertStringContainsString('<strong>Media::headline_overview:</strong>', $result);
		$this->assertStringContainsString('<li class="PermissionAccess-Granted">Media::permission_media_upload', $result);
		$this->assertStringContainsString('<br><hr><br><strong>Seite:</strong>', $result);
		$this->assertStringContainsString('<li class="PermissionAccess-Denied">Pages::permission_pages_delete', $result);
	}


	/**
	 * Test formatUsergroupPermissions with empty array
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatUsergroupPermissions()
	 */
	public function testFormatUsergroupPermissionsHandlesEmptyArray(): void {
		$result = $this->helper->formatUsergroupPermissions([]);

		$this->assertSame('', $result);
	}


	/**
	 * Test formatMediaEntities with single ID
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatMediaEntities()
	 */
	public function testFormatMediaEntitiesHandlesSingleId(): void {
		$mediaTable = $this->fetchTable('Media');
		$media = $mediaTable->newEntity([
			'id' => 5,
			'path' => '/uploads/test-image.jpg',
		]);

		$settings = [
			'media' => [5 => $media],
			'baseUrl' => 'https://example.com',
		];

		$result = $this->helper->formatMediaEntities(5, $settings);

		$this->assertStringContainsString('https://example.com/uploads/test-image.jpg', $result);
		$this->assertStringContainsString('(ID: 5)', $result);
		$this->assertStringContainsString('target="_blank"', $result);
	}


	/**
	 * Test formatMediaEntities with array of IDs
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatMediaEntities()
	 */
	public function testFormatMediaEntitiesHandlesArrayOfIds(): void {
		$mediaTable = $this->fetchTable('Media');
		$media1 = $mediaTable->newEntity(['id' => 1, 'path' => '/uploads/image1.jpg']);
		$media2 = $mediaTable->newEntity(['id' => 2, 'path' => '/uploads/image2.jpg']);

		$settings = [
			'media' => [
				1 => $media1,
				2 => $media2,
			],
			'baseUrl' => 'https://example.com',
		];

		$result = $this->helper->formatMediaEntities([1, 2], $settings);

		$this->assertStringContainsString('/uploads/image1.jpg', $result);
		$this->assertStringContainsString('/uploads/image2.jpg', $result);
		$this->assertStringContainsString('(ID: 1)', $result);
		$this->assertStringContainsString('(ID: 2)', $result);
		$this->assertStringContainsString('<br>', $result);
	}


	/**
	 * Test formatMediaEntities with unknown media ID
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatMediaEntities()
	 */
	public function testFormatMediaEntitiesHandlesUnknownMediaId(): void {
		$settings = [
			'media' => [],
			'baseUrl' => 'https://example.com',
		];

		$result = $this->helper->formatMediaEntities(999, $settings);

		$this->assertStringContainsString('unknown_file', $result);
		$this->assertStringContainsString('(ID: 999)', $result);
	}


	/**
	 * Test formatMediaEntities with null value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatMediaEntities()
	 */
	public function testFormatMediaEntitiesHandlesNullValue(): void {
		$result = $this->helper->formatMediaEntities(null, []);

		$this->assertSame('', $result);
	}


	/**
	 * Test attributeValuesDiffer with both empty
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::attributeValuesDiffer()
	 */
	public function testAttributeValuesDifferReturnsFalseWhenBothEmpty(): void {
		$result = $this->helper->attributeValuesDiffer(null, null);
		$this->assertFalse($result);

		$result = $this->helper->attributeValuesDiffer('', '');
		$this->assertFalse($result);

		$result = $this->helper->attributeValuesDiffer(0, 0);
		$this->assertFalse($result);
	}


	/**
	 * Test attributeValuesDiffer with different values
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::attributeValuesDiffer()
	 */
	public function testAttributeValuesDifferReturnsTrueWhenDifferent(): void {
		$result = $this->helper->attributeValuesDiffer('foo', 'bar');
		$this->assertTrue($result);

		$result = $this->helper->attributeValuesDiffer(1, 2);
		$this->assertTrue($result);

		$result = $this->helper->attributeValuesDiffer(null, 'something');
		$this->assertTrue($result);
	}


	/**
	 * Test valuesDiffer with equal strings
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferReturnsFalseForEqualStrings(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['username' => 'test']);

		$result = $this->helper->valuesDiffer('test', 'test', 'username', $entity, []);

		$this->assertFalse($result);
	}


	/**
	 * Test valuesDiffer with different strings
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferReturnsTrueForDifferentStrings(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['username' => 'test']);

		$result = $this->helper->valuesDiffer('test1', 'test2', 'username', $entity, []);

		$this->assertTrue($result);
	}


	/**
	 * Test valuesDiffer with enum values
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferHandlesEqualEnumValues(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newEntity([]);

		$enum1 = ProcessStatus::Success;
		$enum2 = ProcessStatus::Success;

		$result = $this->helper->valuesDiffer($enum1, $enum2, 'preview', $entity, []);

		$this->assertFalse($result);
	}


	/**
	 * Test valuesDiffer with different enum values
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferHandlesDifferentEnumValues(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newEntity([]);

		$enum1 = ProcessStatus::Success;
		$enum2 = ProcessStatus::Fail;

		$result = $this->helper->valuesDiffer($enum1, $enum2, 'preview', $entity, []);

		$this->assertTrue($result);
	}


	/**
	 * Test valuesDiffer with enum and scalar
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferHandlesEnumAndScalar(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newEntity([]);

		$enum = ProcessStatus::Success;

		$result = $this->helper->valuesDiffer($enum, 1, 'preview', $entity, []);
		$this->assertFalse($result); // Success has value 1

		$result = $this->helper->valuesDiffer($enum, 2, 'preview', $entity, []);
		$this->assertTrue($result);
	}


	/**
	 * Test valuesDiffer with DateTime values
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferHandlesDateTimeValues(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity([]);

		$date1 = new DateTime('2024-03-15 14:30:00');
		$date2 = new DateTime('2024-03-15 14:30:00');
		$date3 = new DateTime('2024-03-16 14:30:00');

		$result = $this->helper->valuesDiffer($date1, $date2, 'lastLogin', $entity, []);
		$this->assertFalse($result);

		$result = $this->helper->valuesDiffer($date1, $date3, 'lastLogin', $entity, []);
		$this->assertTrue($result);
	}


	/**
	 * Test valuesDiffer with manyToMany associations
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::valuesDiffer()
	 * @throws \Exception
	 */
	public function testValuesDifferHandlesManyToManyAssociations(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity([]);

		$usergroupsTable = $this->fetchTable('Usergroups');
		$group1 = $usergroupsTable->newEntity([]);
		$group1->set('id', 1);
		$group2 = $usergroupsTable->newEntity([]);
		$group2->set('id', 2);
		$group3 = $usergroupsTable->newEntity([]);
		$group3->set('id', 3);

		$associations = [
			'usergroups' => [
				'name' => 'Usergroups',
				'property' => 'usergroups',
				'type' => 'manyToMany',
			],
		];

		// Same groups, same order
		$result = $this->helper->valuesDiffer([$group1, $group2], [$group1, $group2], 'usergroups', $entity, $associations);
		$this->assertFalse($result);

		// Same groups, different order (should be sorted and equal)
		$result = $this->helper->valuesDiffer([$group2, $group1], [$group1, $group2], 'usergroups', $entity, $associations);
		$this->assertFalse($result);

		// Different groups - actually different IDs
		$result = $this->helper->valuesDiffer([$group1, $group2], [$group2, $group3], 'usergroups', $entity, $associations);
		$this->assertTrue($result);
	}


	/**
	 * Test formatOldValue with simple field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueHandlesSimpleField(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['username' => 'newuser']);

		$data = ['username' => 'olduser'];

		$result = $this->helper->formatOldValue('username', $data, $entity, [], false);

		$this->assertSame('olduser', $result);
	}


	/**
	 * Test formatOldValue with boolean field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueHandlesBooleanField(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['active' => false]);

		$data = ['active' => true];

		$result = $this->helper->formatOldValue('active', $data, $entity, [], false);

		$this->assertStringContainsString('<i class="las la-check">', $result);
	}


	/**
	 * Test formatOldValue with password field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueMasksPassword(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity([]);

		$data = ['password' => 'secret123'];

		$result = $this->helper->formatOldValue('password', $data, $entity, [], false);

		$this->assertSame('********', $result);
	}


	/**
	 * Test formatOldValue with HTML field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueWrapsHtmlFieldInCode(): void {
		$contentsTable = $this->fetchTable('Contents');
		$entity = $contentsTable->newEntity([]);

		$data = ['textHtml' => '<p>Some HTML content</p>'];

		$result = $this->helper->formatOldValue('textHtml', $data, $entity, [], false);

		$this->assertStringContainsString('<code>', $result);
		$this->assertStringContainsString('&lt;p&gt;', $result);
	}


	/**
	 * Test formatOldValue with CSS field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueWrapssCssFieldInTextarea(): void {
		$contentsTable = $this->fetchTable('Contents');
		$entity = $contentsTable->newEntity([]);

		$data = ['css' => '.class { color: red; }'];

		$result = $this->helper->formatOldValue('css', $data, $entity, [], false);

		$this->assertStringContainsString('<textarea', $result);
		$this->assertStringContainsString('data-readonly="1"', $result);
		$this->assertStringContainsString('data-css-editor="1"', $result);
	}


	/**
	 * Test formatCurrentValue with simple field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatCurrentValue()
	 * @throws \Exception
	 */
	public function testFormatCurrentValueHandlesSimpleField(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['username' => 'currentuser']);

		$result = $this->helper->formatCurrentValue('username', $entity, []);

		$this->assertSame('currentuser', $result);
	}


	/**
	 * Test formatCurrentValue with association
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatCurrentValue()
	 * @throws \Exception
	 */
	public function testFormatCurrentValueHandlesBelongsToAssociation(): void {
		$pagesTable = $this->fetchTable('Pages');
		$pageTemplatesTable = $this->fetchTable('PageTemplates');

		$template = $pageTemplatesTable->newEntity([
			'id' => 1,
			'title' => 'Template Title',
		]);
		// Ensure ID is actually set (not in _accessible by default)
		$template->set('id', 1);

		$page = $pagesTable->newEntity([
			'pageTemplateId' => 1,
		]);
		// Set the association property that formatCurrentValue will access
		$page->set('pageTemplate', $template);

		$associations = [
			'pageTemplateId' => [
				'name' => 'PageTemplates',
				'property' => 'pageTemplate',
				'type' => 'belongsTo',
			],
		];

		$result = $this->helper->formatCurrentValue('pageTemplateId', $page, $associations);

		$this->assertStringContainsString('Template Title', $result);
		$this->assertStringContainsString('(ID: 1)', $result);
	}


	/**
	 * Test formatOldValue with belongsTo association using current entity
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueUsesCurrentEntityWhenNotDifferent(): void {
		$pagesTable = $this->fetchTable('Pages');
		$pageTemplatesTable = $this->fetchTable('PageTemplates');

		$template = $pageTemplatesTable->newEntity([
			'id' => 1,
			'title' => 'Current Template',
		]);
		// Ensure ID is actually set
		$template->set('id', 1);

		$page = $pagesTable->newEntity([
			'pageTemplateId' => 1,
		]);
		// Set the association property that formatOldValue will access
		$page->set('pageTemplate', $template);

		$associations = [
			'pageTemplateId' => [
				'name' => 'PageTemplates',
				'property' => 'pageTemplate',
				'type' => 'belongsTo',
			],
		];

		$data = ['pageTemplateId' => 1];

		$result = $this->helper->formatOldValue('pageTemplateId', $data, $page, $associations, false);

		$this->assertStringContainsString('Current Template', $result);
		$this->assertStringContainsString('(ID: 1)', $result);
	}


	/**
	 * Test formatOldValue with association using audit data
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueUsesAuditDataWhenDifferent(): void {
		$pagesTable = $this->fetchTable('Pages');
		$pageTemplatesTable = $this->fetchTable('PageTemplates');

		$oldTemplate = $pageTemplatesTable->newEntity([
			'id' => 1,
			'title' => 'Old Template',
		]);
		// Ensure ID is actually set
		$oldTemplate->set('id', 1);

		$currentTemplate = $pageTemplatesTable->newEntity([
			'id' => 2,
			'title' => 'Current Template',
		]);
		// Ensure ID is actually set
		$currentTemplate->set('id', 2);

		$page = $pagesTable->newEntity([
			'pageTemplateId' => 2,
		]);
		// Set the association property
		$page->set('pageTemplate', $currentTemplate);

		$associations = [
			'pageTemplateId' => [
				'name' => 'PageTemplates',
				'property' => 'pageTemplate',
				'type' => 'belongsTo',
			],
		];

		$data = [
			'pageTemplateId' => 1,
			'pageTemplate' => $oldTemplate,
		];

		$result = $this->helper->formatOldValue('pageTemplateId', $data, $page, $associations, true);

		$this->assertStringContainsString('Old Template', $result);
		$this->assertStringContainsString('(ID: 1)', $result);
	}


	/**
	 * Test formatOldValue with unknown association entity
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::formatOldValue()
	 * @throws \Exception
	 */
	public function testFormatOldValueHandlesUnknownAssociationEntity(): void {
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity([]);

		$associations = [
			'pageTemplateId' => [
				'name' => 'PageTemplates',
				'property' => 'pageTemplate',
				'type' => 'belongsTo',
			],
		];

		$data = ['pageTemplateId' => 999];

		$result = $this->helper->formatOldValue('pageTemplateId', $data, $page, $associations, true);

		$this->assertStringContainsString('unknown_entity', $result);
		$this->assertStringContainsString('(ID: 999)', $result);
	}
}
