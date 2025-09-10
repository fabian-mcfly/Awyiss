<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM;


use Awyiss\Model\Table;
use Awyiss\ORM\BehaviorRegistry;
use Awyiss\Test\TestSuite\TestCase;


/**
 * BehaviorRegistry Test Case
 *
 * @see \Awyiss\ORM\BehaviorRegistry
 */
class BehaviorRegistryTest extends TestCase {
	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testClassNamePrefersCustomerClass(): void {
		$table = new Table([
			'table' => 'contents',
			'alias' => 'DummyContents',
		]);
		$behaviorRegistry = new BehaviorRegistry($table);

		$className = $behaviorRegistry::className('Attributes');
		$this->assertSame('\Customer\Model\Behavior\AttributesBehavior', $className);

		$className = $behaviorRegistry::className('Translate');
		$this->assertSame('\Awyiss\Model\Behavior\TranslateBehavior', $className);
	}
}
