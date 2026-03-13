<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * UserConfiguration Entity Test Case
 *
 * @see \Awyiss\Model\Entity\UserConfiguration
 */
class UserConfigurationTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UserConfigurationTable $table */
		$table = FactoryLocator::get('Table')->get('UserConfiguration');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new UserConfiguration();

		$this->assertSame([
			'scope' => true,
			'identifier' => true,
			'value' => true,
			'userId' => true,
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
	 * @see \Awyiss\Model\Entity\UserConfiguration::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new UserConfiguration();

		$this->assertSame(['printableValue'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_setScope()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testScopeCleaningViaPropertyAssignment(): void {
		$entity = new UserConfiguration();

		$entity->scope = 'testScope';
		$this->assertEquals('TestScopes', $entity->scope);

		$entity->scope = 'TestScope';
		$this->assertEquals('TestScopes', $entity->scope);

		$entity->scope = 'testHTMLScope';
		$this->assertEquals('TestHTMLScopes', $entity->scope);

		$entity->scope = 'test_scope';
		$this->assertEquals('TestScopes', $entity->scope);

		$entity->scope = null;
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_setScope()
	 */
	public function testScopeCleaningViaSetMethod(): void {
		$entity = new UserConfiguration();

		$entity->set('scope', 'testScope');
		$this->assertEquals('TestScopes', $entity->scope);

		$entity->set('scope', 'TestScope');
		$this->assertEquals('TestScopes', $entity->scope);

		$entity->set('scope', 'testHTMLScope');
		$this->assertEquals('TestHTMLScopes', $entity->scope);

		$entity->set('scope', 'test_scope');
		$this->assertEquals('TestScopes', $entity->scope);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('scope', null);
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new UserConfiguration();

		$entity->identifier = 'testIdentifier';
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->identifier = 'testHTMLElement';
		$this->assertEquals('testHTMLElement', $entity->identifier);

		$entity->identifier = 'is_underscored';
		$this->assertEquals('isUnderscored', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new UserConfiguration();

		$entity->set('identifier', 'testIdentifier');
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->set('identifier', 'testHTMLElement');
		$this->assertEquals('testHTMLElement', $entity->identifier);

		$entity->set('identifier', 'is_underscored');
		$this->assertEquals('isUnderscored', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_setValue()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testValueCleaningViaPropertyAssignment(): void {
		$entity = new UserConfiguration();

		$entity->value = 'test_value';
		$this->assertEquals('test_value', $entity->value);

		$entity->value = true;
		$this->assertTrue($entity->value);

		$entity->value = false;
		$this->assertEquals(0, $entity->value);

		$entity->value = 0;
		$this->assertEquals(0, $entity->value);

		$entity->value = 1;
		$this->assertEquals(1, $entity->value);

		$entity->value = null;
		$this->assertNull($entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_setValue()
	 */
	public function testValueCleaningViaSetMethod(): void {
		$entity = new UserConfiguration();

		$entity->set('value', 'test_value');
		$this->assertEquals('test_value', $entity->value);

		$entity->set('value', true);
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($entity->value);

		$entity->set('value', false);
		$this->assertEquals(0, $entity->value);

		$entity->set('value', 0);
		$this->assertEquals(0, $entity->value);

		$entity->set('value', 1);
		$this->assertEquals(1, $entity->value);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('value', null);
		$this->assertNull($entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::_getPrintableValue()
	 */
	public function testPrintableValueVirtualProperty(): void {
		$entity = new UserConfiguration([
			'scope' => 'Contents',
			'identifier' => 'overview.displayedFields',
			'value' => ['id', 'title', 'active'],
		]);

		$printableValue = $entity->printableValue;

		$this->assertIsString($printableValue);
		$this->assertSame('Contents::title, Contents::active', $printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\UserConfigurationTable $table */
		$table = FactoryLocator::get('Table')->get('UserConfiguration');
		$entity = $table->newDefaultEntity();

		$this->assertEquals('System', $entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'TestScope',
			'identifier' => 'TestIdentifier',
			'value' => 'test_value',
			'userId' => 123,
		];

		$entity = new UserConfiguration($properties);

		$this->assertEquals(1, $entity->id);
		// Scope will be pluralized
		$this->assertEquals('TestScopes', $entity->scope);
		$this->assertEquals('testIdentifier', $entity->identifier);
		$this->assertEquals('test_value', $entity->value);
		$this->assertEquals(123, $entity->userId);
	}
}
