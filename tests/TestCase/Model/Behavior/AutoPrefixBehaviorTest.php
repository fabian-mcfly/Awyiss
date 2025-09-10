<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use Awyiss\Model\Behavior\AutoPrefixBehavior;
use Awyiss\Model\Table;
use Awyiss\Test\TestSuite\TestCase;
use Cake\ORM\TableRegistry;


/**
 * AutoPrefixBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior
 */
class AutoPrefixBehaviorTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentsTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\AutoPrefixBehavior
	 */
	protected AutoPrefixBehavior $behavior;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Contents');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('AutoPrefix');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['beforeFind'], $config['implementedEvents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializationWhenDisabled(): void {
		$table = new class ([
			'table' => 'employers',
			'alias' => 'TestContents',
			'registryAlias' => 'TestContents',
		]) extends Table {
			protected array $autoPrefix = [
				'enabled' => false,
			];
		};

		$behavior = $table->getBehavior('AutoPrefix');

		$config = $behavior->getConfig();

		$this->assertFalse($config['enabled']);
		$this->assertSame(['beforeFind'], $config['implementedEvents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find()->where(['title' => 'Test']);
		$sql = $query->sql();

		$this->assertStringContainsString('title = ', $sql);
		$this->assertStringNotContainsString('.title = ', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithSimpleCondition(): void {
		$query = $this->table->find()->where(['title' => 'Test']);
		$sql = $query->sql();

		$this->assertStringContainsString('Contents.title = ', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithMultipleConditions(): void {
		$query = $this->table->find()->where([
			'title' => 'Test',
			'active' => true,
			'system_order IN' => [1, 2, 3],
		]);
		$sql = $query->sql();

		$this->assertStringContainsString('Contents.title =', $sql);
		$this->assertStringContainsString('Contents.active =', $sql);
		$this->assertStringContainsString('Contents.system_order IN', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithAlreadyPrefixedCondition(): void {
		$query = $this->table->find()->where(['Contents.title' => 'Test']);
		$sql = $query->sql();

		$this->assertStringContainsString('Contents.title = ', $sql);
		$this->assertStringNotContainsString('Contents.Contents.title', $sql);
		$this->assertEquals(1, substr_count($sql, 'Contents.title = '));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithForeignTableCondition(): void {
		$query = $this->table->find()->where(['OtherTable.field' => 'Test']);
		$sql = $query->sql();

		$this->assertStringContainsString('OtherTable.field', $sql);
		$this->assertStringNotContainsString('Contents.OtherTable.field', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithAndConditions(): void {
		$query = $this->table->find()->where([
			'AND' => [
				'title' => 'Test',
				'active' => true,
			],
		]);
		$sql = $query->sql();

		$this->assertStringContainsString('Contents.title = ', $sql);
		$this->assertStringContainsString('Contents.active = ', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithOrConditions(): void {
		$query = $this->table->find()->where([
			'OR' => [
				'title' => 'Test1',
				'active' => true,
			],
		]);
		$sql = $query->sql();


		$this->assertStringContainsString('Contents.title = ', $sql);
		$this->assertStringContainsString('Contents.active = ', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithLikeCondition(): void {
		$query = $this->table->find()->where(['title LIKE' => '%Test%']);
		$sql = $query->sql();

		$this->assertStringContainsString('Contents.title LIKE', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithIsNullCondition(): void {
		$query = $this->table->find()->where(['title IS' => null]);
		$sql = $query->sql();

		$this->assertStringContainsString('(Contents.title) IS NULL', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithNotCondition(): void {
		$query = $this->table->find()->where(['NOT' => ['title' => 'Test']]);
		$sql = $query->sql();

		$this->assertStringContainsString('NOT (Contents.title = :', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithComparisonOperators(): void {
		$query = $this->table->find()->where([
			'system_order >' => 5,
			'system_order <=' => 10,
			'system_order !=' => 7,
		]);
		$sql = $query->sql();

		$this->assertStringContainsString('Contents.system_order > ', $sql);
		$this->assertStringContainsString('Contents.system_order <= ', $sql);
		$this->assertStringContainsString('Contents.system_order != ', $sql);
	}
}
