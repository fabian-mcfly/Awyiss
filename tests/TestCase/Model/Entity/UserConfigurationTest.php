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
		$this->assertEquals('test_scopes', $entity->scope);

		$entity->scope = 'TestScope';
		$this->assertEquals('test_scopes', $entity->scope);

		$entity->scope = 'testHTMLScope';
		$this->assertEquals('test_h_t_m_l_scopes', $entity->scope);

		$entity->scope = 'test_scope';
		$this->assertEquals('test_scopes', $entity->scope);

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
		$this->assertEquals('test_scopes', $entity->scope);

		$entity->set('scope', 'TestScope');
		$this->assertEquals('test_scopes', $entity->scope);

		$entity->set('scope', 'testHTMLScope');
		$this->assertEquals('test_h_t_m_l_scopes', $entity->scope);

		$entity->set('scope', 'test_scope');
		$this->assertEquals('test_scopes', $entity->scope);

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
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'testHTMLElement';
		$this->assertEquals('test_h_t_m_l_element', $entity->identifier);

		$entity->identifier = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->identifier);

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
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'testHTMLElement');
		$this->assertEquals('test_h_t_m_l_element', $entity->identifier);

		$entity->set('identifier', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->identifier);

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
			'scope' => 'contents',
			'identifier' => 'overview.displayedFields',
			'value' => ['id', 'title', 'active'],
		]);

		$printableValue = $entity->printableValue;

		$this->assertIsString($printableValue);
		$this->assertSame('contents::title, contents::active', $printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\UserConfigurationTable $table */
		$table = FactoryLocator::get('Table')->get('UserConfiguration');
		$entity = $table->newDefaultEntity();

		$this->assertEquals('system', $entity->scope);
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
			'user_id' => 123,
		];

		$entity = new UserConfiguration($properties);

		$this->assertEquals(1, $entity->id);
		// Scope will be pluralized
		$this->assertEquals('test_scopes', $entity->scope);
		$this->assertEquals('test_identifier', $entity->identifier);
		$this->assertEquals('test_value', $entity->value);
		$this->assertEquals(123, $entity->userId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UserConfiguration::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'user_id' => 456,
		];

		$entity = new UserConfiguration($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
