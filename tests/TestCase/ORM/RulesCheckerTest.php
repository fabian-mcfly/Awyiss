<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\ORM;


use Awyiss\Model\Entity\PageRole;
use Awyiss\ORM\Rule\ExistsIn;
use Awyiss\ORM\RulesChecker;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RuleInvoker;
use Cake\ORM\Association;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Rule\ValidCount;
use Cake\ORM\Table;
use ReflectionClass;
use RuntimeException;


/**
 * Test case for RulesChecker
 *
 * @see \Awyiss\ORM\RulesChecker
 */
class RulesCheckerTest extends TestCase {
	/**
	 * @var \Awyiss\ORM\RulesChecker
	 */
	protected RulesChecker $rulesChecker;
	/**
	 * @var \Cake\ORM\Table
	 */
	protected Table $mockTable;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockTable = $this->createMock(Table::class);
		$this->rulesChecker = new RulesChecker(['repository' => $this->mockTable]);
	}


	/**
	 * Test add method with string name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::add()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddWithStringName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->add($rule, 'testRule', ['message' => 'Test error']);

		$this->assertTrue($this->rulesChecker->ruleExists('testRule'));
	}


	/**
	 * Test add method with array options containing name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::add()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddWithArrayName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->add($rule, ['name' => 'arrayRule', 'message' => 'Array error']);

		$this->assertTrue($this->rulesChecker->ruleExists('arrayRule'));
	}


	/**
	 * Test add method throws exception for duplicate rule names
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::add()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddThrowsExceptionForDuplicateRule(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->add($rule, 'duplicateRule');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Rule `duplicateRule` already exists in `Awyiss\ORM\RulesChecker`');

		$this->rulesChecker->add($rule, 'duplicateRule');
	}


	/**
	 * Test addCreate method with string name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addCreate()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddCreateWithStringName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addCreate($rule, 'createRule', ['message' => 'Create error']);

		$this->assertTrue($this->rulesChecker->ruleExists('createRule'));
	}


	/**
	 * Test addCreate method with array options containing name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addCreate()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddCreateWithArrayName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addCreate($rule, ['name' => 'arrayCreateRule', 'message' => 'Array create error']);

		$this->assertTrue($this->rulesChecker->ruleExists('arrayCreateRule'));
	}


	/**
	 * Test addCreate method throws exception for duplicate rule names
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addCreate()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddCreateThrowsExceptionForDuplicateRule(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addCreate($rule, 'duplicateCreateRule');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Rule `duplicateCreateRule` already exists in `Awyiss\ORM\RulesChecker`');

		$this->rulesChecker->addCreate($rule, 'duplicateCreateRule');
	}


	/**
	 * Test addUpdate method with string name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addUpdate()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddUpdateWithStringName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addUpdate($rule, 'updateRule', ['message' => 'Update error']);

		$this->assertTrue($this->rulesChecker->ruleExists('updateRule'));
	}


	/**
	 * Test addUpdate method with array options containing name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addUpdate()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddUpdateWithArrayName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addUpdate($rule, ['name' => 'arrayUpdateRule', 'message' => 'Array update error']);

		$this->assertTrue($this->rulesChecker->ruleExists('arrayUpdateRule'));
	}


	/**
	 * Test addUpdate method throws exception for duplicate rule names
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addUpdate()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddUpdateThrowsExceptionForDuplicateRule(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addUpdate($rule, 'duplicateUpdateRule');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Rule `duplicateUpdateRule` already exists in `Awyiss\ORM\RulesChecker`');

		$this->rulesChecker->addUpdate($rule, 'duplicateUpdateRule');
	}


	/**
	 * Test addDelete method with string name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addDelete()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddDeleteWithStringName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addDelete($rule, 'deleteRule', ['message' => 'Delete error']);

		$this->assertTrue($this->rulesChecker->ruleExists('deleteRule'));
	}


	/**
	 * Test addDelete method with array options containing name
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addDelete()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddDeleteWithArrayName(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addDelete($rule, ['name' => 'arrayDeleteRule', 'message' => 'Array delete error']);

		$this->assertTrue($this->rulesChecker->ruleExists('arrayDeleteRule'));
	}


	/**
	 * Test addDelete method throws exception for duplicate rule names
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::addDelete()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testAddDeleteThrowsExceptionForDuplicateRule(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->addDelete($rule, 'duplicateDeleteRule');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Rule `duplicateDeleteRule` already exists in `Awyiss\ORM\RulesChecker`');

		$this->rulesChecker->addDelete($rule, 'duplicateDeleteRule');
	}


	/**
	 * Test existsIn method with string field
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInWithStringField(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (ExistsIn $existsIn): bool {
				$reflection = new ReflectionClass($existsIn);
				$fieldsProperty = $reflection->getProperty('_fields');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$fieldsProperty->setAccessible(true);

				$fields = $fieldsProperty->getValue($existsIn);
				$this->assertSame(['user_id'], $fields);

				return true;
			}),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options) {
				$this->assertSame('user_id', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn('user_id', $mockTargetTable, 'Custom error message');
	}


	/**
	 * Test existsIn method with array field
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInWithArrayField(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (ExistsIn $existsIn): bool {
				$reflection = new ReflectionClass($existsIn);
				$fieldsProperty = $reflection->getProperty('_fields');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$fieldsProperty->setAccessible(true);

				$fields = $fieldsProperty->getValue($existsIn);
				$this->assertSame(['user_id', 'dummy_id'], $fields);

				return true;
			}),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options): bool {
				$this->assertSame('user_id', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn(['user_id', 'dummy_id'], $mockTargetTable, 'Custom error message');
	}


	/**
	 * Test existsIn method with association
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInWithAssociation(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$association = $this->createMock(Association::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (ExistsIn $existsIn) use ($association): bool {
				$reflection = new ReflectionClass($existsIn);
				$fieldsProperty = $reflection->getProperty('_fields');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$fieldsProperty->setAccessible(true);

				$fields = $fieldsProperty->getValue($existsIn);
				$this->assertSame(['user_id'], $fields);

				$repositoryProperty = $reflection->getProperty('_repository');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$repositoryProperty->setAccessible(true);

				$repository = $repositoryProperty->getValue($existsIn);
				$this->assertSame($association, $repository);

				return true;
			}),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options): bool {
				$this->assertSame('user_id', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn('user_id', $association, 'Custom error message');
	}


	/**
	 * Test existsIn method with association
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInWithTableString(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (ExistsIn $existsIn): bool {
				$reflection = new ReflectionClass($existsIn);
				$repositoryProperty = $reflection->getProperty('_repository');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$repositoryProperty->setAccessible(true);

				$repository = $repositoryProperty->getValue($existsIn);
				$this->assertSame('Users', $repository);

				return true;
			}),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options): bool {
				$this->assertSame('user_id', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn('user_id', 'Users', 'Custom error message');
	}


	/**
	 * Test existsIn method with array options including errorField
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInWithErrorField(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->isInstanceOf(ExistsIn::class),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options): bool {
				$this->assertSame('dummy_id', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn('user_id', $mockTargetTable, ['errorField' => 'dummy_id', 'message' => 'Custom error message']);
	}


	/**
	 * Test existsIn method without a custom error message
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInWithDefaultMessage(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->isInstanceOf(ExistsIn::class),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options): bool {
				$this->assertSame('user_id', $options['errorField']);
				$this->assertSame('validation::error_exists_in', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn('user_id', $mockTargetTable);
	}


	/**
	 * Test existsIn unmaps fields correctly
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::existsIn()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExistsInUnmapsFields(): void {
		$mockTargetTable = $this->createMock(Table::class);
		$mockTargetTable->method('getEntityClass')->willReturn(PageRole::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (ExistsIn $existsIn): bool {
				$reflection = new ReflectionClass($existsIn);
				$fieldsProperty = $reflection->getProperty('_fields');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$fieldsProperty->setAccessible(true);

				$fields = $fieldsProperty->getValue($existsIn);
				// Field must be unmapped to database field
				$this->assertSame(['include_in_linklist', 'system_order'], $fields);

				return true;
			}),
			$this->equalTo('_existsIn'),
			$this->callback(function (array $options): bool {
				// Error field stays unchanged
				$this->assertSame('includeInLinklist', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->existsIn(['includeInLinklist', 'systemOrder'], 'PageRoles', 'Custom error message');
	}


	/**
	 * Test isUnique method with array options including errorField
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::isUnique()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsUniqueWithErrorField(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->isInstanceOf(IsUnique::class),
			$this->equalTo('_isUnique'),
			$this->callback(function (array $options): bool {
				$this->assertSame('dummy_username', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->isUnique(['username'], ['errorField' => 'dummy_username', 'message' => 'Custom error message']);
	}


	/**
	 * Test isUnique method without a custom error message
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::isUnique()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsUniqueWithDefaultMessage(): void {
		$mockTargetTable = $this->createMock(Table::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->isInstanceOf(IsUnique::class),
			$this->equalTo('_isUnique'),
			$this->callback(function (array $options): bool {
				$this->assertSame('username', $options['errorField']);
				$this->assertSame('validation::error_unique', $options['message']);

				return true;
			})
		);

		$rulesChecker->isUnique(['username']);
	}


	/**
	 * Test isUnique method without a custom error message
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::isUnique()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsUniqueUnmapsFields(): void {
		$mockTargetTable = $this->createMock(Table::class);
		$mockTargetTable->method('getEntityClass')->willReturn(PageRole::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (IsUnique $isUnique): bool {
				$reflection = new ReflectionClass($isUnique);
				$fieldsProperty = $reflection->getProperty('_fields');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$fieldsProperty->setAccessible(true);

				$fields = $fieldsProperty->getValue($isUnique);
				// Field must be unmapped to database field
				$this->assertSame(['include_in_linklist', 'system_order'], $fields);

				return true;
			}),
			$this->equalTo('_isUnique'),
			$this->callback(function (array $options): bool {
				// Error field stays unchanged
				$this->assertSame('includeInLinklist', $options['errorField']);
				$this->assertSame('Custom error message', $options['message']);

				return true;
			})
		);

		$rulesChecker->isUnique(['includeInLinklist', 'systemOrder'], 'Custom error message');
	}


	/**
	 * Test validCount method with default parameters
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::validCount()
	 * @throws \ReflectionException
	 */
	public function testValidCount(): void {
		$result = $this->rulesChecker->validCount('items');

		$reflection = new ReflectionClass($result);
		$optionsProperty = $reflection->getProperty('options');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$optionsProperty->setAccessible(true);
		$options = $optionsProperty->getValue($result);

		$this->assertSame(0, $options['count']);
		$this->assertSame('>', $options['operator']);
		$this->assertSame('validation::error_valid_count', $options['message']);
	}


	/**
	 * Test validCount method with custom parameters
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::validCount()
	 * @throws \ReflectionException
	 */
	public function testValidCountWithCustomParameters(): void {
		$result = $this->rulesChecker->validCount('tags', 5, '>=', 'Must have at least 5 tags');

		$reflection = new ReflectionClass($result);
		$optionsProperty = $reflection->getProperty('options');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$optionsProperty->setAccessible(true);
		$options = $optionsProperty->getValue($result);

		$this->assertSame(5, $options['count']);
		$this->assertSame('>=', $options['operator']);
		$this->assertSame('Must have at least 5 tags', $options['message']);
	}


	/**
	 * Test validCount method with different operators
	 *
	 * @dataProvider validCountOperatorProvider
	 * @param string $operator
	 * @param int $count
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::validCount()
	 * @throws \ReflectionException
	 */
	public function testValidCountWithDifferentOperators(string $operator, int $count): void {
		$result = $this->rulesChecker->validCount('field', $count, $operator);

		$reflection = new ReflectionClass($result);
		$optionsProperty = $reflection->getProperty('options');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$optionsProperty->setAccessible(true);
		$options = $optionsProperty->getValue($result);

		$this->assertSame($count, $options['count']);
		$this->assertSame($operator, $options['operator']);
	}


	/**
	 * Test validCount method unmaps field correctly
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::validCount()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testValidCountUnmapsFields(): void {
		$mockTargetTable = $this->createMock(Table::class);
		$mockTargetTable->method('getEntityClass')->willReturn(PageRole::class);

		$rulesChecker = $this->getMockBuilder(RulesChecker::class)->setConstructorArgs([['repository' => $mockTargetTable]])->onlyMethods(['_addError'])->getMock();

		$rulesChecker->expects($this->once())->method('_addError')->with(
			$this->callback(function (ValidCount $validCount): bool {
				$reflection = new ReflectionClass($validCount);
				$fieldProperty = $reflection->getProperty('_field');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$fieldProperty->setAccessible(true);
				$field = $fieldProperty->getValue($validCount);

				// Field must be unmapped to database field
				$this->assertSame('include_in_linklist', $field);

				return true;
			}),
			$this->equalTo('_validCount'),
			$this->callback(function (array $options): bool {
				// Error field stays unchanged
				$this->assertSame('includeInLinklist', $options['errorField']);
				$this->assertSame('validation::error_valid_count', $options['message']);

				return true;
			})
		);

		$rulesChecker->validCount('includeInLinklist');
	}


	/**
	 * Test ruleExists method returns true for existing rules
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::ruleExists()
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testRuleExistsReturnsTrueForExistingRules(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		// Add rules to different rule sets
		$this->rulesChecker->add($rule, 'generalRule');
		$this->rulesChecker->addCreate($rule, 'createRule');
		$this->rulesChecker->addUpdate($rule, 'updateRule');
		$this->rulesChecker->addDelete($rule, 'deleteRule');

		$this->assertTrue($this->rulesChecker->ruleExists('generalRule'));
		$this->assertTrue($this->rulesChecker->ruleExists('createRule'));
		$this->assertTrue($this->rulesChecker->ruleExists('updateRule'));
		$this->assertTrue($this->rulesChecker->ruleExists('deleteRule'));
	}


	/**
	 * Test ruleExists method returns false for non-existing rules
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::ruleExists()
	 */
	public function testRuleExistsReturnsFalseForNonExistingRules(): void {
		$this->assertFalse($this->rulesChecker->ruleExists('nonExistentRule'));
	}


	/**
	 * Test extractName method throws exception when name is missing
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::extractName()
	 * @throws \ReflectionException
	 */
	public function testExtractNameThrowsExceptionWhenNameMissing(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing option `name` in `Awyiss\ORM\RulesChecker`');

		$this->callProtectedMethod($this->rulesChecker, 'extractName', []);
	}


	/**
	 * Test extractName method throws exception when name is null
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::extractName()
	 * @throws \ReflectionException
	 */
	public function testExtractNameThrowsExceptionWhenNameIsNull(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing option `name` in `Awyiss\ORM\RulesChecker`');

		$this->callProtectedMethod($this->rulesChecker, 'extractName', null);
	}


	/**
	 * Test extractName method throws exception when name is empty
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::extractName()
	 * @throws \ReflectionException
	 */
	public function testExtractNameThrowsExceptionWhenNameIsEmpty(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing option `name` in `Awyiss\ORM\RulesChecker`');

		$this->callProtectedMethod($this->rulesChecker, 'extractName', ['name' => '']);
	}


	/**
	 * Test extractName method returns correct name when provided
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::extractName()
	 * @throws \ReflectionException
	 */
	public function testExtractNameReturnsCorrectName(): void {
		$result = $this->callProtectedMethod($this->rulesChecker, 'extractName', ['name' => 'testRule']);

		$this->assertSame('testRule', $result);
	}


	/**
	 * Test _addLinkConstraintRule method with Association object
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::_addLinkConstraintRule()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testAddLinkConstraintRuleWithAssociation(): void {
		$mockAssociation = $this->createMock(Association::class);
		$mockAssociation->expects($this->any())->method('getName')->willReturn('Users');

		$result = $this->callProtectedMethod(
			$this->rulesChecker,
			'_addLinkConstraintRule',
			$mockAssociation,
			'user_id',
			'Custom message',
			'linked',
			'linkRule'
		);

		$this->assertInstanceOf(RuleInvoker::class, $result);

		$reflection = new ReflectionClass($result);
		$optionsProperty = $reflection->getProperty('options');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$optionsProperty->setAccessible(true);
		$options = $optionsProperty->getValue($result);

		$this->assertSame('user_id', $options['errorField']);
		$this->assertSame('Custom message', $options['message']);
	}


	/**
	 * Test _addLinkConstraintRule method with string association
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::_addLinkConstraintRule()
	 * @throws \ReflectionException
	 */
	public function testAddLinkConstraintRuleWithStringAssociation(): void {
		$result = $this->callProtectedMethod(
			$this->rulesChecker,
			'_addLinkConstraintRule',
			'Users',
			'user_id',
			'Custom message',
			'linked',
			'linkRule'
		);

		$this->assertInstanceOf(RuleInvoker::class, $result);

		$reflection = new ReflectionClass($result);
		$optionsProperty = $reflection->getProperty('options');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$optionsProperty->setAccessible(true);
		$options = $optionsProperty->getValue($result);

		$this->assertSame('user_id', $options['errorField']);
		$this->assertSame('Custom message', $options['message']);
	}


	/**
	 * Test _addLinkConstraintRule method with string association
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker::_addLinkConstraintRule()
	 * @throws \ReflectionException
	 */
	public function testAddLinkConstraintRuleWithDefaultMessage(): void {
		$result = $this->callProtectedMethod(
			$this->rulesChecker,
			'_addLinkConstraintRule',
			'Users',
			'user_id',
			null,
			'linked',
			'linkRule'
		);

		$this->assertInstanceOf(RuleInvoker::class, $result);

		$reflection = new ReflectionClass($result);
		$optionsProperty = $reflection->getProperty('options');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$optionsProperty->setAccessible(true);
		$options = $optionsProperty->getValue($result);

		$this->assertSame('user_id', $options['errorField']);
		$this->assertSame('validation::error_link_constraint_rule', $options['message']);
	}


	/**
	 * Test that duplicate rules across different rule sets throw exceptions
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testDuplicateRulesAcrossRuleSets(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		$this->rulesChecker->add($rule, 'crossSetRule');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Rule `crossSetRule` already exists in `Awyiss\ORM\RulesChecker`');

		$this->rulesChecker->addCreate($rule, 'crossSetRule');
	}


	/**
	 * Data provider for validCount operator testing
	 *
	 * @return array
	 */
	public static function validCountOperatorProvider(): array {
		return [
			['>', 0],
			['>=', 1],
			['<', 10],
			['<=', 5],
			['==', 3],
			['!=', 0],
		];
	}


	/**
	 * Test multiple rule additions and rule existence checking
	 *
	 * @return void
	 * @see \Awyiss\ORM\RulesChecker
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testMultipleRuleAdditionsAndExistence(): void {
		$rule = function (EntityInterface $entity) {
			return true;
		};

		// Add multiple rules to different rule sets
		$this->rulesChecker->add($rule, 'rule1');
		$this->rulesChecker->add($rule, 'rule2');
		$this->rulesChecker->addCreate($rule, 'createRule1');
		$this->rulesChecker->addUpdate($rule, 'updateRule1');
		$this->rulesChecker->addDelete($rule, 'deleteRule1');

		// Verify all rules exist
		$this->assertTrue($this->rulesChecker->ruleExists('rule1'));
		$this->assertTrue($this->rulesChecker->ruleExists('rule2'));
		$this->assertTrue($this->rulesChecker->ruleExists('createRule1'));
		$this->assertTrue($this->rulesChecker->ruleExists('updateRule1'));
		$this->assertTrue($this->rulesChecker->ruleExists('deleteRule1'));

		// Verify non-existent rule returns false
		$this->assertFalse($this->rulesChecker->ruleExists('nonExistentRule'));
	}
}
