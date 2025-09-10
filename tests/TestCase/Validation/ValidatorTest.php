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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetI18nDomain(): void {
		$this->assertSame('test_domain', $this->validator->getI18nDomain());

		$this->validator->setI18nDomain('another_test_domain');
		$this->assertSame('another_test_domain', $this->validator->getI18nDomain());
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_not_empty', $result['field_name']['_empty']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_min_length', $result['field_name']['minLength']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_not_boolean', $result['field_name']['notBoolean']);

		$data = [
			'fieldName' => false,
		];

		$result = $this->validator->validate($data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_not_boolean', $result['field_name']['notBoolean']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_max_length', $result['field_name']['maxLength']);
		$this->assertArrayHasKey('another_field_name', $result);
		$this->assertSame('test_domain::error_compare_with', $result['another_field_name']['compareWith']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_in_list', $result['field_name']['inList']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::validate()
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertArrayHasKey('field_name', $result);
		$this->assertSame('test_domain::error_date_time', $result['field_name']['dateTime']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::allowEmptyFor()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAllowEmptyFor(): void {
		$this->validator->allowEmptyFor('fieldName');

		$this->assertTrue($this->validator->hasField('fieldName'));
		$this->assertTrue($this->validator->hasField('field_name'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::field()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testField(): void {
		$validationSet = $this->validator->field('fieldName');
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(ValidationSet::class, $validationSet);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::hasField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testHasField(): void {
		$this->validator->field('fieldName');
		$this->assertTrue($this->validator->hasField('fieldName'));
		$this->assertTrue($this->validator->hasField('field_name'));

		$this->validator->field('another_field_name');
		$this->assertTrue($this->validator->hasField('anotherFieldName'));
		$this->assertTrue($this->validator->hasField('another_field_name'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::remove()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveField(): void {
		$this->validator->field('fieldName');
		$this->validator->remove('fieldName');
		$this->assertFalse($this->validator->hasField('fieldName'));
		$this->assertFalse($this->validator->hasField('field_name'));

		$this->validator->field('another_field_name');
		$this->validator->remove('another_field_name');
		$this->assertFalse($this->validator->hasField('anotherFieldName'));
		$this->assertFalse($this->validator->hasField('another_field_name'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::getRequiredMessage()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNotEmptyMessage(): void {
		$this->validator->field('fieldName');
		$message = $this->validator->getNotEmptyMessage('fieldName');
		$this->assertSame('test_domain::error_not_empty', $message);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::underscoreFields()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUnderscoreFields(): void {
		$fields = [
			'fieldName' => 'value',
			'anotherFieldName' => 'anotherValue',
		];

		$expectedUnderscoredKeys = [
			'field_name' => 'value',
			'another_field_name' => 'anotherValue',
		];

		$expectedUnderscoredValues = [
			'fieldName' => 'value',
			'anotherFieldName' => 'another_value',
		];

		// Test with underscoreKeys = true
		$result = $this->callProtectedMethod($this->validator, 'underscoreFields', $fields, true);
		$this->assertSame($expectedUnderscoredKeys, $result);

		// Test with underscoreKeys = false
		$result = $this->callProtectedMethod($this->validator, 'underscoreFields', $fields, false);
		$this->assertSame($expectedUnderscoredValues, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Validation\Validator::underscoreField()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUnderscoreField(): void {
		// Test simple field name
		$result = $this->callProtectedMethod($this->validator, 'underscoreField', 'fieldName');
		$this->assertSame('field_name', $result);

		// Test field name with prefix
		$result = $this->callProtectedMethod($this->validator, 'underscoreField', 'prefix.fieldName');
		$this->assertSame('prefix.field_name', $result);

		// Test field name starting with underscore
		$result = $this->callProtectedMethod($this->validator, 'underscoreField', '_fieldName');
		$this->assertSame('_fieldName', $result);

		// Test non-string field
		$result = $this->callProtectedMethod($this->validator, 'underscoreField', 123);
		$this->assertSame(123, $result);

		// Test empty field
		$result = $this->callProtectedMethod($this->validator, 'underscoreField', '');
		$this->assertSame('', $result);
	}
}
