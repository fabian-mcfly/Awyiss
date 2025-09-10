<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\UrlsNotFound;
use Awyiss\Model\Table\UrlsNotFoundTable;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;


/**
 * UrlsNotFoundTable Test Case
 *
 * @see \Awyiss\Model\Table\UrlsNotFoundTable
 */
class UrlsNotFoundTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\UrlsNotFoundTable
	 */
	protected UrlsNotFoundTable $urlsNotFoundTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->urlsNotFoundTable = FactoryLocator::get('Table')->get('UrlsNotFound');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->urlsNotFoundTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('urls_not_found', $this->urlsNotFoundTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->urlsNotFoundTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('urls_not_found', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('url'));
		$this->assertSame('create', $result->field('url')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('referrer'));
		$this->assertTrue($result->hasField('isRobot'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'url' => 'https://example.com/not-found',
			'referrer' => 'https://example.com/home',
			'isRobot' => false,
			'createdOn' => DateTime::now(),
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'referrer' => 'https://example.com/home',
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('_required', $errors['url']);
		$this->assertSame('urls_not_found::error_required', $errors['url']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'url' => true,
			'referrer' => true,
			'isRobot' => 'not_a_boolean',
			'createdOn' => 'not_a_datetime',
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('referrer', $errors);
		$this->assertArrayHasKey('isRobot', $errors);
		$this->assertArrayHasKey('createdOn', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'url' => str_repeat('a', 2049), // exceeds 2048 char limit
			'referrer' => str_repeat('b', 2049), // exceeds 2048 char limit
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('referrer', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationEmptyUrl(): void {
		$data = [
			'url' => '',
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationEmptyReferrer(): void {
		$data = [
			'url' => 'https://example.com/not-found',
			'referrer' => '',
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('referrer', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNullReferrer(): void {
		$data = [
			'url' => 'https://example.com/not-found',
			'referrer' => null,
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('referrer', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNullIsRobot(): void {
		$data = [
			'url' => 'https://example.com/not-found',
			'isRobot' => null,
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('isRobot', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNullCreatedOn(): void {
		$data = [
			'url' => 'https://example.com/not-found',
			'createdOn' => null,
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('createdOn', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationBooleanValues(): void {
		// Test valid boolean values
		$validBooleans = [true, false, 1, 0, '1', '0'];

		foreach ($validBooleans as $value) {
			$data = [
				'url' => 'https://example.com/not-found',
				'isRobot' => $value,
			];

			$entity = $this->urlsNotFoundTable->newDefaultEntity();
			$this->urlsNotFoundTable->patchEntity($entity, $data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('isRobot', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationDateTimeFormats(): void {
		// Test various valid datetime formats
		$validDateTimes = [
			DateTime::now(),
			new DateTime('2023-10-01 12:00:00'),
			'2023-10-01 12:00:00',
			'2023-10-01T12:00:00',
		];

		foreach ($validDateTimes as $dateTime) {
			$data = [
				'url' => 'https://example.com/not-found',
				'createdOn' => $dateTime,
			];

			$entity = $this->urlsNotFoundTable->newDefaultEntity();
			$this->urlsNotFoundTable->patchEntity($entity, $data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('createdOn', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationLongUrls(): void {
		// Test URLs at maximum length (2048 characters)
		$maxLengthUrl = 'https://example.com/' . str_repeat('a', 2028); // 20 + 2028 = 2048

		$data = [
			'url' => $maxLengthUrl,
			'referrer' => $maxLengthUrl,
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('url', $errors);
		$this->assertArrayNotHasKey('referrer', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSpecialCharacters(): void {
		$data = [
			'url' => 'https://example.com/测试页面?param=值&other=参数',
			'referrer' => 'https://example.com/référence-français',
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity();
		$this->urlsNotFoundTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('url', $errors);
		$this->assertArrayNotHasKey('referrer', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\UrlsNotFound $entity */
		$entity = $this->urlsNotFoundTable->newDefaultEntity();

		$this->assertInstanceOf(UrlsNotFound::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->url);
		$this->assertNull($entity->referrer);
		$this->assertFalse($entity->isRobot);
		$this->assertNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'url' => 'https://example.com/missing-page',
			'referrer' => 'https://example.com/home',
			'isRobot' => true,
			'createdOn' => new DateTime('2023-10-01 12:00:00'),
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UrlsNotFound::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('https://example.com/missing-page', $entity->url);
		$this->assertSame('https://example.com/home', $entity->referrer);
		$this->assertTrue($entity->isRobot);
		$this->assertInstanceOf(DateTime::class, $entity->createdOn);
		$this->assertSame('2023-10-01 12:00:00', $entity->createdOn->format('Y-m-d H:i:s'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlsNotFoundTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithMinimalData(): void {
		$additionalData = [
			'url' => '/404-page',
		];

		$entity = $this->urlsNotFoundTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UrlsNotFound::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('/404-page', $entity->url);
		$this->assertNull($entity->referrer);
		$this->assertFalse($entity->isRobot);
		$this->assertNull($entity->createdOn);
	}
}
