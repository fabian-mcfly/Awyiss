<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Attribute;


use Awyiss\Attribute\AttributeOption;
use Awyiss\Model\Entity;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\EntityInterface;
use RuntimeException;


/**
 * Test class for Awyiss\Attribute\AttributeOptions
 */
class AttributeOptionsTest extends TestCase {
	/**
	 * Test constructor and getters
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorAndGetters(): void {
		$attributeOptions = new AttributeOption(
			'testIdentifier',
			true,
			['option1', 'option2'],
			true,
			null,
			null,
			'defaultValue'
		);

		$this->assertNotNull($attributeOptions);
		$this->assertEquals('testIdentifier', $attributeOptions->getIdentifier());
		$this->assertTrue($attributeOptions->getDisabled());
		$this->assertEquals(['option1', 'option2'], $attributeOptions->getOptions());
		$this->assertTrue($attributeOptions->getReadonly());
		$this->assertNull($attributeOptions->getToScalar());
		$this->assertEquals('defaultValue', $attributeOptions->getValue());
		$this->assertNull($attributeOptions->getValidate());
	}


	/**
	 * Test constructor with named arguments
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithNamedArguments(): void {
		$attributeOptions = new AttributeOption(
			disabled: [1],
			identifier: 'testIdentifier',
			options: ['option1', 'option2'],
			readonly: true,
			value: 'defaultValue',
		);

		$this->assertNotNull($attributeOptions);
		$this->assertEquals('testIdentifier', $attributeOptions->getIdentifier());
		$this->assertEquals([1], $attributeOptions->getDisabled());
		$this->assertEquals(['option1', 'option2'], $attributeOptions->getOptions());
		$this->assertTrue($attributeOptions->getReadonly());
		$this->assertEquals('defaultValue', $attributeOptions->getValue());
	}


	/**
	 * Test the setters
	 */
	public function testSetters(): void {
		/** @noinspection PhpVariableNamingConventionInspection */
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setIdentifier('new identifier?');
		$this->assertEquals('newIdentifier', $attributeOptions->getIdentifier());

		$attributeOptions->setDisabled(true);
		$this->assertTrue($attributeOptions->getDisabled());

		$attributeOptions->setOptions(function (EntityInterface $entity) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return [
				$entity->id => $entity->title,
				$entity->id + 1 => $entity->title . ' + 1',
				$entity->id + 2 => $entity->title . ' + 2',
			];
		});
		$this->assertIsCallable($attributeOptions->getOptions());

		$attributeOptions->setReadonly(true);
		$this->assertTrue($attributeOptions->getReadonly());

		$attributeOptions->setToScalar(function ($value) {
			return $value;
		});
		$this->assertIsCallable($attributeOptions->getToScalar());

		$attributeOptions->setValue('new value');
		$this->assertEquals('new value', $attributeOptions->getValue());

		$attributeOptions->setValidate(function ($value) {
			return $value === 'valid';
		});
		$this->assertIsCallable($attributeOptions->getValidate());
	}


	/**
	 * Test the evaluated disabled
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEvaluateDisabled(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setDisabled(function (EntityInterface $entity) {
			return $entity->id === 1;
		});

		$testEntity = new Entity();
		$testEntity->set('id', 1);
		$this->assertTrue($attributeOptions->getDisabled(true, $testEntity));

		$testEntity->set('id', 2);
		$this->assertFalse($attributeOptions->getDisabled(true, $testEntity));
	}


	/**
	 * Test the evaluated options
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEvaluateOptions(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setOptions(function (EntityInterface $entity) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return [
				$entity->id => $entity->title,
				$entity->id + 1 => $entity->title . ' + 1',
				$entity->id + 2 => $entity->title . ' + 2',
			];
		});

		$testEntity = new Entity();
		$testEntity->set('id', 1);
		$testEntity->set('title', 'Test-Title');
		$options = $attributeOptions->getOptions(true, $testEntity);

		$this->assertEquals([
			1 => 'Test-Title',
			2 => 'Test-Title + 1',
			3 => 'Test-Title + 2',
		], $options);
	}


	/**
	 * Test the evaluated readonly
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEvaluateReadonly(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setReadonly(function (EntityInterface $entity) {
			return $entity->id === 1;
		});

		$testEntity = new Entity();
		$testEntity->set('id', 1);
		$this->assertTrue($attributeOptions->getReadonly(true, $testEntity));

		$testEntity->set('id', 2);
		$this->assertFalse($attributeOptions->getReadonly(true, $testEntity));
	}


	/**
	 * Test toScalar
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToScalar(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setToScalar(function ($value) {
			return $value . ' + 1';
		});

		$this->assertEquals('test + 1', $attributeOptions->toScalar('test'));
	}


	/**
	 * Test validate
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidate(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		// If validate is set to false, all values are valid
		$attributeOptions->setValidate(false);
		$this->assertTrue($attributeOptions->validateValue('valid'));
		$this->assertTrue($attributeOptions->validateValue('invalid'));

		// If validate is not callable and not null, an exception is thrown
		$attributeOptions->setValidate(true);
		$this->expectException(RuntimeException::class);
		$attributeOptions->validateValue('does not matter');

		$attributeOptions->setValidate(function ($value) {
			return $value === 'valid';
		});

		$this->assertTrue($attributeOptions->validateValue('valid'));
		$this->assertFalse($attributeOptions->validateValue('invalid'));

		// If validate is null, the validation depends on the disabled settings and the options
		$attributeOptions->setValidate();

		// Disabled means no value is allowed
		$attributeOptions->setDisabled(true);
		$this->assertFalse($attributeOptions->validateValue('valid'));
		$attributeOptions->setDisabled(false);

		// If disabled is an array, we need to check if the value is in the array
		$attributeOptions->setDisabled(['invalid']);
		$this->assertFalse($attributeOptions->validateValue('invalid'));

		// If the value is null, it is always valid
		$this->assertTrue($attributeOptions->validateValue(null));

		// If the value is an array, we need to check if all values are valid
		$attributeOptions->setOptions(['valid', 'invalid']);
		$this->assertTrue($attributeOptions->validateValue(['valid']));
		$this->assertTrue($attributeOptions->validateValue(['invalid']));
		$this->assertFalse($attributeOptions->validateValue(['valid', 'invalid']));

		// If the value is not a scalar, we need to convert it
		$attributeOptions->setToScalar(function ($value) {
			return $value;
		});
		$this->assertTrue($attributeOptions->validateValue(function () {
			return 'valid';
		}));
	}


	/**
	 * Test the value
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValue(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$time = time();
		$attributeOptions->setValue(function () use ($time) {
			return $time;
		});

		$this->assertIsCallable($attributeOptions->getValue());

		$this->assertEquals($time, $attributeOptions->getValue(true));
	}


	/**
	 * Test the value with a scalar value
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValueWithScalarValue(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setValue('testValue');

		$this->assertEquals('testValue', $attributeOptions->getValue());
	}


	/**
	 * Test buildOptions method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildOptions(): void {
		$attributeOptions = new AttributeOption('testIdentifier');

		$attributeOptions->setOptions(['option1', 'option2', 'option3', 'option4', 'option5']);
		$attributeOptions->setDisabled(['option2', 'option4']);
		$attributeOptions->setReadonly(true);
		$attributeOptions->setValue(2);

		$options = $attributeOptions->buildOptions([]);

		$this->assertEquals([
			'disabled' => [
				0 => 'option2',
				1 => 'option4',
			],
			'options' => [
				0 => 'option1',
				1 => 'option2',
				2 => 'option3',
				3 => 'option4',
				4 => 'option5',
			],
			'readonly' => true,
			'val' => 2,
		], $options);
	}
}
