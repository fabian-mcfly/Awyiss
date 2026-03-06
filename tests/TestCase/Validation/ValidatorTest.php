<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Validation;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Validation\ValidationSet;


/**
 * ValidatorTest class
 *
 * @see \Awyiss\Validation\Validator
 */
class ValidatorTest extends TestCase {
	/**
	 * @var \Awyiss\Validation\Validator
	 */
	protected Validator $validator;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);

		$this->validator = new Validator();
		$this->validator->setI18nDomain('test_domain');
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::getI18nDomain()
	 * @see \Awyiss\Validation\Validator::setI18nDomain()
	 */
	public function testSetAndGetI18nDomain(): void {
		$this->assertSame('TestDomain', $this->validator->getI18nDomain());

		$this->validator->setI18nDomain('AnotherTestDomain');
		$this->assertSame('AnotherTestDomain', $this->validator->getI18nDomain());
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidate(): void {
		$data = [
			'fieldName' => '',
		];

		$this->validator->requirePresence([
			'fieldName',
		]);

		$this->validator->notEmptyString('fieldName');

		$this->validator->add('fieldName', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 100]],
		]);

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_not_empty', $result['fieldName']['_empty']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidateWithValue(): void {
		$data = [
			'fieldName' => 'foo',
		];

		$this->validator->add('fieldName', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 100]],
		]);

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_min_length', $result['fieldName']['minLength']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidateWithBoolValue(): void {
		$data = [
			'fieldName' => true,
		];

		$this->validator->add('fieldName', [
			'isScalar' => ['rule' => 'isScalar'],
		]);

		$result = $this->validator->validate($data);

		$this->assertEmpty($result);

		$data = [
			'fieldName' => false,
		];

		$result = $this->validator->validate($data);

		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidateNotBooleanWithBoolValue(): void {
		$data = [
			'fieldName' => true,
		];

		/** @uses \Awyiss\Validation\Validation::notBoolean() */
		$this->validator->add('fieldName', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
		]);

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_not_boolean', $result['fieldName']['notBoolean']);

		$data = [
			'fieldName' => false,
		];

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_not_boolean', $result['fieldName']['notBoolean']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidateWithValueAndCompareWith(): void {
		$data = [
			'fieldName' => 'fooooooooooooooobar',
			'anotherFieldName' => 'bar',
		];

		$this->validator->add('fieldName', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 10]],
		]);

		$this->validator->add('anotherFieldName', [
			'compareWith' => ['rule' => ['compareWith', 'fieldName']],
		]);

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_max_length', $result['fieldName']['maxLength']);
		$this->assertArrayHasKey('anotherFieldName', $result);
		$this->assertSame('test_domain::error_compare_with', $result['anotherFieldName']['compareWith']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidateWithRuleInList(): void {
		$data = [
			'fieldName' => 'fooooooooooooooobar',
			'anotherFieldName' => 'bar',
		];

		$this->validator->add('fieldName', [
			'inList' => ['rule' => ['inList', Awyiss::getRealms()]],
		]);

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_in_list', $result['fieldName']['inList']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 */
	public function testValidateWithRuleDateTime(): void {
		$data = [
			'fieldName' => 'fooooooooooooooobar',
			'anotherFieldName' => 'bar',
		];

		$this->validator->add('fieldName', [
			'dateTime' => ['rule' => ['dateTime', 'Y-m-d H:i:s']],
		]);

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('fieldName', $result);
		$this->assertSame('test_domain::error_date_time', $result['fieldName']['dateTime']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::allowEmptyFor()
	 */
	public function testAllowEmptyFor(): void {
		$this->validator->allowEmptyFor('fieldName');

		$this->assertTrue($this->validator->hasField('fieldName'));
		$this->assertTrue($this->validator->hasField('fieldName'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::field()
	 */
	public function testField(): void {
		$validationSet = $this->validator->field('fieldName');
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(ValidationSet::class, $validationSet);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::hasField()
	 */
	public function testHasField(): void {
		$this->validator->field('fieldName');
		$this->assertTrue($this->validator->hasField('fieldName'));
		$this->assertTrue($this->validator->hasField('fieldName'));

		$this->validator->field('anotherFieldName');
		$this->assertTrue($this->validator->hasField('anotherFieldName'));
		$this->assertTrue($this->validator->hasField('anotherFieldName'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::remove()
	 */
	public function testRemoveField(): void {
		$this->validator->field('fieldName');
		$this->validator->remove('fieldName');
		$this->assertFalse($this->validator->hasField('fieldName'));
		$this->assertFalse($this->validator->hasField('fieldName'));

		$this->validator->field('anotherFieldName');
		$this->validator->remove('anotherFieldName');
		$this->assertFalse($this->validator->hasField('anotherFieldName'));
		$this->assertFalse($this->validator->hasField('anotherFieldName'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::getRequiredMessage()
	 */
	public function testGetRequiredMessage(): void {
		$message = $this->validator->getRequiredMessage('fieldName');
		$this->assertNull($message);

		$this->validator->field('fieldName');
		$message = $this->validator->getRequiredMessage('fieldName');
		$this->assertSame('test_domain::error_required', $message);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::getNotEmptyMessage()
	 */
	public function testGetNotEmptyMessage(): void {
		$this->validator->field('fieldName');
		$message = $this->validator->getNotEmptyMessage('fieldName');
		$this->assertSame('test_domain::error_not_empty', $message);
	}
}
