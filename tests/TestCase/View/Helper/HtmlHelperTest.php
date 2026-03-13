<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\HtmlHelper;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;


/**
 * HtmlHelperTest class
 */
class HtmlHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\HtmlHelper
	 */
	protected HtmlHelper $helper;
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm('Backend');

		$this->view = new BackendView();

		// Set up default view variables that will be cached by static variables in HtmlHelper
		// This ensures consistent behavior across all tests
		$this->view->set('languages', [
			'Frontend' => [
				'de' => ['title' => 'Deutsch'],
				'en' => ['title' => 'English'],
			],
			'Backend' => [
				'de' => ['title' => 'German'],
				'en' => ['title' => 'English'],
				'fr' => ['title' => 'French Backend'], // Backend-only language for fallback test
			],
		]);

		$this->view->set('dateFormat', 'Y-m-d');
		$this->view->set('timeFormat', 'H:i:s');

		$this->helper = new HtmlHelper($this->view);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * Test formatValue with null value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 */
	public function testFormatValueReturnsDefaultForNull(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1]);
		$result = $this->helper->formatValue(null, $entity, 'username');

		$this->assertSame('-', $result);
	}


	/**
	 * Test formatValue with custom empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 */
	public function testFormatValueReturnsCustomEmptyValue(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1]);
		$result = $this->helper->formatValue(null, $entity, 'username', ['emptyValue' => 'N/A']);

		$this->assertSame('N/A', $result);
	}


	/**
	 * Test formatValue with empty string
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 */
	public function testFormatValueReturnsDefaultForEmptyString(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1]);
		$result = $this->helper->formatValue('', $entity, 'username');

		$this->assertSame('-', $result);
	}


	/**
	 * Test formatValue with password field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 */
	public function testFormatValueMasksPasswordField(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1, 'password' => 'secret123']);
		$result = $this->helper->formatValue('secret123', $entity, 'password');

		$this->assertSame('********', $result);
	}


	/**
	 * Test formatValue with field ending in Password
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 */
	public function testFormatValueMasksFieldEndingWithPassword(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1]);
		$result = $this->helper->formatValue('mypassword', $entity, 'userPassword');

		$this->assertSame('********', $result);
	}


	/**
	 * Test formatValue with boolean true
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeReturnsTrueIcon(): void {
		$result = $this->helper->formatValueByType(true, 'boolean');

		$this->assertStringContainsString('<i class="las la-check">', $result);
		$this->assertStringContainsString('true', $result);
	}


	/**
	 * Test formatValue with boolean false
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeReturnsFalseIcon(): void {
		$result = $this->helper->formatValueByType(false, 'boolean');

		$this->assertStringContainsString('<i class="las la-times">', $result);
		$this->assertStringContainsString('false', $result);
	}


	/**
	 * Test formatValue with string 'true'
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesStringTrue(): void {
		$result = $this->helper->formatValueByType('true', 'string');

		$this->assertStringContainsString('<i class="las la-check">', $result);
	}


	/**
	 * Test formatValue with string 'false'
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesStringFalse(): void {
		$result = $this->helper->formatValueByType('false', 'string');

		$this->assertStringContainsString('<i class="las la-times">', $result);
	}


	/**
	 * Test formatValue with boolean column type and non-empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesBooleanColumnWithNonEmptyValue(): void {
		$result = $this->helper->formatValueByType(1, 'boolean');

		$this->assertStringContainsString('<i class="las la-check">', $result);
	}


	/**
	 * Test formatValue with boolean column type and empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesBooleanColumnWithEmptyValue(): void {
		$result = $this->helper->formatValueByType(0, 'boolean');

		$this->assertStringContainsString('<i class="las la-times">', $result);
	}


	/**
	 * Test formatValue with DateTime
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsDateTime(): void {
		$dateTime = new DateTime('2024-03-15 14:30:00');
		$result = $this->helper->formatValueByType($dateTime, 'datetime');

		// Should use Time helper's nice() method
		$this->assertNotEmpty($result);
		$this->assertStringContainsString('2024', $result);
	}


	/**
	 * Test formatValue with Date
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsDate(): void {
		$date = new Date('2024-03-15');
		$result = $this->helper->formatValueByType($date, 'date');

		$this->assertNotEmpty($result);
		$this->assertStringContainsString('2024', $result);
	}


	/**
	 * Test formatValue with Time
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsTime(): void {
		$time = new Time('14:30:00');
		$result = $this->helper->formatValueByType($time, 'time');

		$this->assertNotEmpty($result);
		$this->assertStringContainsString('14', $result);
	}


	/**
	 * Test formatValue with integer
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsInteger(): void {
		$result = $this->helper->formatValueByType(42, 'integer');

		$this->assertSame('42', $result);
	}


	/**
	 * Test formatValue with biginteger
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsBigInteger(): void {
		$result = $this->helper->formatValueByType(9999999999, 'biginteger');

		$this->assertSame('9999999999', $result);
	}


	/**
	 * Test formatValue with smallinteger
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsSmallInteger(): void {
		$result = $this->helper->formatValueByType(123, 'smallinteger');

		$this->assertSame('123', $result);
	}


	/**
	 * Test formatValue with float
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsFloat(): void {
		$result = $this->helper->formatValueByType(3.14159, 'float');

		$this->assertSame('3.14', $result);
	}


	/**
	 * Test formatValue with float and custom decimals
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsFloatWithCustomDecimals(): void {
		$result = $this->helper->formatValueByType(3.14159, 'float', ['decimals' => 4]);

		$this->assertSame('3.1416', $result);
	}


	/**
	 * Test formatValue with decimal
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsDecimal(): void {
		$result = $this->helper->formatValueByType(99.99, 'decimal');

		$this->assertSame('99.99', $result);
	}


	/**
	 * Test formatValue with JSON
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsJson(): void {
		$data = ['foo' => 'bar', 'baz' => 123];
		$result = $this->helper->formatValueByType($data, 'json');

		$this->assertSame('{"foo":"bar","baz":123}', $result);
	}


	/**
	 * Test formatValue with empty JSON
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsEmptyJson(): void {
		$result = $this->helper->formatValueByType([], 'json');

		$this->assertSame('', $result);
	}


	/**
	 * Test formatValue with array
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsArray(): void {
		$data = ['foo', 'bar', 'baz'];
		$result = $this->helper->formatValueByType($data, 'string');

		$this->assertSame('["foo","bar","baz"]', $result);
	}


	/**
	 * Test formatValue with object
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsObject(): void {
		$object = (object)['foo' => 'bar'];
		$result = $this->helper->formatValueByType($object, 'string');

		$this->assertSame('{"foo":"bar"}', $result);
	}


	/**
	 * Test formatValue with string
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsString(): void {
		$result = $this->helper->formatValueByType('Hello World', 'string');

		$this->assertSame('Hello World', $result);
	}


	/**
	 * Test formatValue strips HTML tags
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeStripsHtmlTags(): void {
		$result = $this->helper->formatValueByType('<script>alert("xss")</script>Hello', 'string');

		$this->assertSame('alert("xss")Hello', $result);
	}


	/**
	 * Test formatValue with enum value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsEnum(): void {
		$enum = ProcessStatus::InProgress;
		$result = $this->helper->formatValueByType($enum, 'string');

		$this->assertSame('InProgress', $result);
	}


	/**
	 * Test formatFieldValue method
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatFieldValue()
	 * @throws \Exception
	 */
	public function testFormatFieldValueGetsValueFromEntity(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1, 'username' => 'testuser']);
		$result = $this->helper->formatFieldValue($entity, 'username');

		$this->assertSame('testuser', $result);
	}


	/**
	 * Test formatFieldValue with missing field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatFieldValue()
	 * @throws \Exception
	 */
	public function testFormatFieldValueHandlesMissingField(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1]);
		$result = $this->helper->formatFieldValue($entity, 'nonexistent');

		$this->assertSame('-', $result);
	}


	/**
	 * Test formatSpecialField with languageShortcode
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesLanguageShortcode(): void {
		// Languages are already set in setUp()
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity(['languageShortcode' => 'de']);
		$result = $this->helper->formatValue('de', $page, 'languageShortcode');

		$this->assertSame('Deutsch', $result);
	}


	/**
	 * Test formatSpecialField with languageShortcode fallback to Backend
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesLanguageShortcodeFallbackToBackend(): void {
		// Use 'fr' which only exists in Backend, not Frontend
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity(['languageShortcode' => 'fr']);
		$result = $this->helper->formatValue('fr', $page, 'languageShortcode');

		$this->assertSame('French Backend', $result);
	}


	/**
	 * Test formatSpecialField with createdBy field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesCreatedBy(): void {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->newEntity(['id' => 2]);
		$entity->createdBy = 2;

		$result = $this->helper->formatValue(1, $entity, 'createdBy');

		$this->assertSame('awyiss-undecided-access', $result);
	}


	/**
	 * Test formatSpecialField with changedBy field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesChangedBy(): void {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $this->fetchTable('Pages');
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $pagesTable->newEntity(['id' => 1]);
		$entity->changedBy = 3;

		$result = $this->helper->formatValue(2, $entity, 'changedBy');

		$this->assertSame('awyiss-no-access', $result);
	}


	/**
	 * Test formatSpecialField with createdBy field and null value
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesCreatedByWithNullValue(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1]);
		$result = $this->helper->formatValue(null, $entity, 'createdBy');

		$this->assertSame('-', $result);
	}


	/**
	 * Test formatValue with real User entity
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueWithRealUserEntity(): void {
		$usersTable = $this->fetchTable('Users');
		$user = $usersTable->newEntity([
			'username' => 'testuser',
			'active' => true,
		]);

		$result = $this->helper->formatValue('testuser', $user, 'username');
		$this->assertSame('testuser', $result);

		$result = $this->helper->formatValue(true, $user, 'active');
		$this->assertStringContainsString('<i class="las la-check">', $result);
	}


	/**
	 * Test formatValue with real Media entity
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueWithRealMediaEntity(): void {
		$mediaTable = $this->fetchTable('Media');
		$media = $mediaTable->newEntity([
			'name' => 'test-image.jpg',
			'width' => 1920.0,
			'height' => 1080.0,
		]);

		$result = $this->helper->formatValue('test-image.jpg', $media, 'name');
		$this->assertSame('test-image.jpg', $result);

		$result = $this->helper->formatValue(1920.0, $media, 'width');
		$this->assertSame('1920.00', $result);

		$result = $this->helper->formatValue(1080.0, $media, 'height');
		$this->assertSame('1080.00', $result);
	}


	/**
	 * Test formatValue with real Page entity
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueWithRealPageEntity(): void {
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity([
			'title' => 'Test Page',
			'slug' => 'test-page',
			'active' => true,
			'robotsIndex' => true,
			'robotsFollow' => false,
		]);

		$result = $this->helper->formatValue('Test Page', $page, 'title');
		$this->assertSame('Test Page', $result);

		$result = $this->helper->formatValue(true, $page, 'active');
		$this->assertStringContainsString('<i class="las la-check">', $result);

		$result = $this->helper->formatValue(false, $page, 'robotsFollow');
		$this->assertStringContainsString('<i class="las la-times">', $result);
	}


	/**
	 * Test formatValue with DateTime on real entity
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValue()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueWithDateTimeOnRealEntity(): void {
		$usersTable = $this->fetchTable('Users');
		$dateTime = new DateTime('2024-03-15 14:30:00');
		$user = $usersTable->newEntity([
			'username' => 'testuser',
			'lastLogin' => $dateTime,
		]);

		$result = $this->helper->formatValue($dateTime, $user, 'lastLogin');
		$this->assertNotEmpty($result);
		$this->assertStringContainsString('2024', $result);
	}


	/**
	 * Test getTemplateLabel with association
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::getTemplateLabel()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testGetTemplateLabelWithAssociation(): void {
		$pagesTable = $this->fetchTable('Pages');
		$pageTemplatesTable = $this->fetchTable('PageTemplates');

		// Create a mock template entity
		$template = $pageTemplatesTable->newEntity([
			'id' => 1,
			'title' => 'Standard Template',
		]);

		// Create a page with the template association loaded
		$page = $pagesTable->newDefaultEntity([
			'pageTemplateId' => 1,
			'pageTemplate' => $template,
		]);

		$result = $this->helper->formatValue(1, $page, 'pageTemplateId');
		$this->assertSame('Standard Template', $result);
	}


	/**
	 * Test getTemplateLabel without association falls back to view variable
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::getTemplateLabel()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testGetTemplateLabelFallsBackToViewVariable(): void {
		$pageTemplatesTable = $this->fetchTable('PageTemplates');
		$template = $pageTemplatesTable->newEntity([
			'id' => 1,
			'title' => 'Homepage Template',
		]);

		// Set view variable
		$this->view->set('pageTemplates', [1 => $template]);

		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity([
			'pageTemplateId' => 1,
		]);

		$result = $this->helper->formatValue(1, $page, 'pageTemplateId');
		$this->assertSame('Homepage Template', $result);
	}


	/**
	 * Test getTemplateLabel returns dash when not found
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::getTemplateLabel()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testGetTemplateLabelReturnsDashWhenNotFound(): void {
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity([
			'pageTemplateId' => 999,
		]);

		$result = $this->helper->formatValue(999, $page, 'pageTemplateId');
		$this->assertSame('-', $result);
	}


	/**
	 * Test formatValue with timestamp column type
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsTimestamp(): void {
		$dateTime = new DateTime('2024-03-15 14:30:00');
		$result = $this->helper->formatValueByType($dateTime, 'timestamp');

		$this->assertNotEmpty($result);
		$this->assertStringContainsString('2024', $result);
	}


	/**
	 * Test formatValue with DateTime object but unknown column type
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsDateTimeObjectWithUnknownType(): void {
		$dateTime = new DateTime('2024-03-15 14:30:00');
		$result = $this->helper->formatValueByType($dateTime, 'string');

		// Should fallback to nice() method
		$this->assertNotEmpty($result);
		$this->assertStringContainsString('2024', $result);
	}


	/**
	 * Test formatValue with category field using _categoriesIdentifier
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesCategoriesIdentifier(): void {
		$mediaTable = $this->fetchTable('Media');
		$media = $mediaTable->newEntity([
			'mediaFolderId' => 1,
		]);

		$this->view->set('_categoriesIdentifier', 'mediaFolderId');
		$this->view->set('categories', [
			'simple' => [
				1 => 'Folder 1',
				2 => 'Folder 2',
			],
		]);

		$result = $this->helper->formatValue(1, $media, 'mediaFolderId');
		$this->assertSame('Folder 1', $result);
	}


	/**
	 * Test formatValue with category field using _categories config
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesCategoriesConfig(): void {
		$mediaTable = $this->fetchTable('Media');
		$media = $mediaTable->newEntity([
			'mediaFolderId' => 2,
		]);

		$this->view->set('_categories', [
			'categories' => [
				'config' => [
					'field' => 'mediaFolderId',
				],
				'simple' => [
					1 => 'Category A',
					2 => 'Category B',
				],
			],
		]);

		$result = $this->helper->formatValue(2, $media, 'mediaFolderId');
		$this->assertSame('Category B', $result);
	}


	/**
	 * Test formatValue with contentTemplateId field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesContentTemplateId(): void {
		$contentsTable = $this->fetchTable('Contents');
		$content = $contentsTable->newEntity([
			'contentTemplateId' => 5,
		]);

		$contentTemplatesTable = $this->fetchTable('ContentTemplates');
		$template = $contentTemplatesTable->newEntity([
			'id' => 5,
			'title' => 'Text Content Template',
		]);

		$this->view->set('contentTemplates', [5 => $template]);

		$result = $this->helper->formatValue(5, $content, 'contentTemplateId');
		$this->assertSame('Text Content Template', $result);
	}


	/**
	 * Test formatValue with unknown column type falls back to string handling
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesUnknownColumnType(): void {
		$result = $this->helper->formatValueByType('simple text', 'unknown');

		$this->assertSame('simple text', $result);
	}


	/**
	 * Test formatValue with negative numbers
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesNegativeNumbers(): void {
		$result = $this->helper->formatValueByType(-42, 'integer');
		$this->assertSame('-42', $result);

		$result = $this->helper->formatValueByType(-3.14159, 'float');
		$this->assertSame('-3.14', $result);
	}


	/**
	 * Test formatValue with zero values
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesZeroValues(): void {
		$result = $this->helper->formatValueByType(0, 'integer');
		$this->assertSame('0', $result);

		$result = $this->helper->formatValueByType(0.0, 'float');
		$this->assertSame('0.00', $result);
	}


	/**
	 * Test formatValue with very large numbers
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesLargeNumbers(): void {
		$result = $this->helper->formatValueByType(9223372036854775807, 'biginteger');
		$this->assertSame('9223372036854775807', $result);
	}


	/**
	 * Test formatValue with nested JSON
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsNestedJson(): void {
		$data = [
			'user' => [
				'name' => 'John',
				'email' => 'john@example.com',
				'settings' => [
					'theme' => 'dark',
				],
			],
		];
		$result = $this->helper->formatValueByType($data, 'json');

		$this->assertStringContainsString('"user"', $result);
		$this->assertStringContainsString('"settings"', $result);
		$this->assertStringContainsString('"theme"', $result);
	}


	/**
	 * Test formatValue with Unicode characters
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesUnicodeCharacters(): void {
		$result = $this->helper->formatValueByType('Hällö Wörld 🌍', 'string');

		$this->assertSame('Hällö Wörld 🌍', $result);
	}


	/**
	 * Test formatValue with special HTML entities
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeHandlesHtmlEntities(): void {
		$result = $this->helper->formatValueByType('Test &amp; &lt;more&gt;', 'string');

		// strip_tags should not affect entities, only tags
		$this->assertSame('Test &amp; &lt;more&gt;', $result);
	}


	/**
	 * Test formatValue with multiple HTML tags
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeStripsMultipleHtmlTags(): void {
		$result = $this->helper->formatValueByType('<div><p>Text</p></div>', 'string');

		$this->assertSame('Text', $result);
	}


	/**
	 * Test formatFieldValue with existing field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatFieldValue()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatFieldValueWithExistingField(): void {
		$usersTable = $this->fetchTable('Users');
		$user = $usersTable->newEntity([
			'username' => 'john_doe',
			'active' => true,
		]);

		$result = $this->helper->formatFieldValue($user, 'username');
		$this->assertSame('john_doe', $result);

		$result = $this->helper->formatFieldValue($user, 'active');
		$this->assertStringContainsString('<i class="las la-check">', $result);
	}


	/**
	 * Test formatValue with deletedBy field
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldDoesNotHandleDeletedBy(): void {
		$usersTable = $this->fetchTable('Users');
		$entity = $usersTable->newEntity(['id' => 1, 'deletedBy' => 3]);

		// deletedBy is NOT a special field, so it should be formatted normally
		$result = $this->helper->formatValue(3, $entity, 'deletedBy');

		// It should return the integer as string, not a username
		$this->assertSame('3', $result);
	}


	/**
	 * Test formatValue with complex nested array
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatValueByType()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testFormatValueByTypeFormatsComplexArray(): void {
		$data = [
			['id' => 1, 'name' => 'Item 1'],
			['id' => 2, 'name' => 'Item 2'],
		];
		$result = $this->helper->formatValueByType($data, 'string');

		$this->assertStringContainsString('"id"', $result);
		$this->assertStringContainsString('"name"', $result);
	}


	/**
	 * Test getTemplateLabel with missing template ID
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::getTemplateLabel()
	 * @throws \Exception
	 * @throws \Exception
	 */
	public function testGetTemplateLabelWithNullTemplateId(): void {
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity([
			'pageTemplateId' => null,
		]);

		$result = $this->helper->formatValue(null, $page, 'pageTemplateId');
		$this->assertSame('-', $result);
	}


	/**
	 * Test formatValue with languageShortcode and missing language
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\HtmlHelper::formatSpecialField()
	 * @throws \Exception
	 */
	public function testFormatSpecialFieldHandlesLanguageShortcodeWithMissingLanguage(): void {
		// Use 'es' which doesn't exist in either Frontend or Backend
		$pagesTable = $this->fetchTable('Pages');
		$page = $pagesTable->newEntity(['languageShortcode' => 'es']);
		$result = $this->helper->formatValue('es', $page, 'languageShortcode');

		$this->assertSame('-', $result);
	}
}
