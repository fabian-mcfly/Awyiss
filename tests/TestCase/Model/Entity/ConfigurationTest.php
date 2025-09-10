<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Configuration Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Configuration
 */
class ConfigurationTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\ConfigurationTable $table */
		$table = FactoryLocator::get('Table')->get('Configuration');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new Configuration();

		$this->assertSame([
			'realm' => true,
			'scope' => true,
			'identifier' => true,
			'value' => true,
			'languageShortcode' => true,
			'description' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new Configuration();

		$this->assertSame(['printableValue'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::_setScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testScopeCleaningViaPropertyAssignment(): void {
		$entity = new Configuration();

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
	 * @see \Awyiss\Model\Entity\Configuration::_setScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testScopeCleaningViaSetMethod(): void {
		$entity = new Configuration();

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
	 * @see \Awyiss\Model\Entity\Configuration::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Configuration();

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
	 * @see \Awyiss\Model\Entity\Configuration::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Configuration();

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
	 * @see \Awyiss\Model\Entity\Configuration::_setValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testValueCleaningViaPropertyAssignment(): void {
		$entity = new Configuration();

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
	 * @see \Awyiss\Model\Entity\Configuration::_setValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValueCleaningViaSetMethod(): void {
		$entity = new Configuration();

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
	 * @see \Awyiss\Model\Entity\Configuration::_getPrintableValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrintableValueVirtualPropertyWithBasicValues(): void {
		$entity = new Configuration([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'system',
			'identifier' => 'test_setting',
			'value' => 'test_value',
		]);

		$printableValue = $entity->printableValue;

		$this->assertEquals('test_value', $printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::_getPrintableValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrintableValueVirtualPropertyWithBooleanValues(): void {
		$entityTrue = new Configuration([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'system',
			'identifier' => 'test_setting',
			'value' => true,
		]);

		$entityFalse = new Configuration([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'system',
			'identifier' => 'test_setting',
			'value' => false,
		]);

		$this->assertEquals('true', $entityTrue->printableValue);
		$this->assertEquals(0, $entityFalse->printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::_getPrintableValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrintableValueVirtualPropertyWithArrayValues(): void {
		$entity = new Configuration([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'system',
			'identifier' => 'test_setting',
			'value' => ['value1', 'value2', 'value3'],
		]);

		$printableValue = $entity->printableValue;

		$this->assertEquals('value1, value2, value3', $printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::_getPrintableValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrintableValueVirtualPropertyWithNullValue(): void {
		$entity = new Configuration([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => 'system',
			'identifier' => 'test_setting',
			'value' => null,
		]);

		$printableValue = $entity->printableValue;

		$this->assertNull($printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::_getPrintableValue()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrintableValueVirtualPropertyWithMissingRequiredFields(): void {
		$entity = new Configuration([
			'value' => 'test_value',
		]);

		$printableValue = $entity->printableValue;

		$this->assertNull($printableValue);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::$defaultValues
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\ConfigurationTable $table */
		$table = FactoryLocator::get('Table')->get('Configuration');
		$entity = $table->newDefaultEntity();

		$this->assertEquals(Awyiss::REALM_FRONTEND, $entity->realm);
		$this->assertEquals('system', $entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'realm' => Awyiss::REALM_BACKEND,
			'scope' => 'TestScope',
			'identifier' => 'TestIdentifier',
			'value' => 'test_value',
			'language_shortcode' => 'de',
			'description' => 'Test Configuration',
		];

		$entity = new Configuration($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(Awyiss::REALM_BACKEND, $entity->realm);
		// Scope will be pluralized
		$this->assertEquals('test_scopes', $entity->scope);
		$this->assertEquals('test_identifier', $entity->identifier);
		$this->assertEquals('test_value', $entity->value);
		$this->assertEquals('de', $entity->languageShortcode);
		$this->assertEquals('Test Configuration', $entity->description);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Configuration::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'language_shortcode' => 'en',
		];

		$entity = new Configuration($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
