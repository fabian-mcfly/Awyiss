<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration;


use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionCollection;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;
use RuntimeException;


/**
 * Tests for `ConfigOptionCollection`
 */
class ConfigOptionCollectionTest extends TestCase {
	/**
	 * Test constructor and the scope
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorAndScope(): void {
		$dummyOptionsCollection = new ConfigOptionCollection('dummy identifier');

		$this->assertEquals('dummyIdentifier', $dummyOptionsCollection->getIdentifier());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddConfigOptionSuccessfully() {
		$configOption = new ConfigOption(
			defaultValue: true,
			identifier: 'enabled',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);

		$collection = new ConfigOptionCollection('root');
		$collection->add($configOption);

		$this->assertTrue($collection->offsetExists('enabled'));
		$this->assertSame($configOption, $collection->offsetGet('enabled'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddConfigOptionThrowsExceptionWhenIdentifierIsDuplicate(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The identifier `option1` is already in use.');

		$collection = new ConfigOptionCollection('root');

		$collection->add(new ConfigOption(
			defaultValue: true,
			identifier: 'option1',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		));

		$collection->add(new ConfigOption(
			defaultValue: true,
			identifier: 'option1',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddArrayWithStringKeyAddsSubCollection() {
		$configOption = $this->createMock(ConfigOption::class);
		$configOption->method('getIdentifier')->willReturn('option1');

		$collection = new ConfigOptionCollection('root');
		$collection->add(['sub' => [$configOption]]);

		$this->assertTrue($collection->offsetExists('sub'));
		$this->assertInstanceOf(ConfigOptionCollection::class, $collection->offsetGet('sub'));
		$this->assertTrue($collection->offsetGet('sub')->offsetExists('option1'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddArrayWithNumericKeyAddsConfigOption() {
		$configOption = $this->createMock(ConfigOption::class);
		$configOption->method('getIdentifier')->willReturn('option1');

		$collection = new ConfigOptionCollection('root');
		$collection->add([$configOption]);

		$this->assertTrue($collection->offsetExists('option1'));
		$this->assertSame($configOption, $collection->offsetGet('option1'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddArrayWithNestedArrayCreatesConfigOption() {
		$collection = new ConfigOptionCollection('root');

		$configOptionData = ['identifier' => 'option1', 'defaultValue' => 'test'];

		$configOption = $this->createMock(ConfigOption::class);
		$configOption->method('getIdentifier')->willReturn('option1');
		$configOption->method('getDefaultValue')->willReturn('test');

		$collection->add([$configOptionData]);

		$this->assertTrue($collection->offsetExists('option1'));
		$this->assertInstanceOf(ConfigOption::class, $collection->offsetGet('option1'));
		$this->assertEquals('test', $collection->offsetGet('option1')->getDefaultValue());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddCollectionAddsNewCollectionSuccessfully() {
		$collection = new ConfigOptionCollection('root');
		$subCollection = new ConfigOptionCollection('sub');
		$collection->addCollection($subCollection);

		$this->assertTrue($collection->offsetExists('sub'));
		$this->assertSame($subCollection, $collection->offsetGet('sub'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddCollectionMergesNestedCollectionsSuccessfully() {
		$collection = new ConfigOptionCollection('root');
		$subCollection1 = new ConfigOptionCollection('sub');

		$configOption = $this->createMock(ConfigOption::class);
		$configOption->method('getIdentifier')->willReturn('option1');

		$subCollection2 = new ConfigOptionCollection('sub');
		$subCollection2->add($configOption);

		$collection->addCollection($subCollection1);
		$collection->addCollection($subCollection2);

		$this->assertTrue($collection->offsetExists('sub'));
		$this->assertTrue($collection->offsetGet('sub')->offsetExists('option1'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddCollectionThrowsNoExceptionWhenCollectionIdentifierIsDuplicate() {
		$collection = new ConfigOptionCollection('root');

		$subCollection1 = new ConfigOptionCollection('sub');
		$configOption1 = new ConfigOption(
			defaultValue: true,
			identifier: 'option1',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);
		$subCollection1->add($configOption1);

		$subCollection2 = new ConfigOptionCollection('sub');
		$configOption2 = new ConfigOption(
			defaultValue: true,
			identifier: 'option2',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);
		$subCollection2->add($configOption2);

		$collection->addCollection($subCollection1);
		$collection->addCollection($subCollection2);

		$this->assertTrue($collection->offsetExists('sub'));

		$this->assertSame([
			'option1' => $configOption1,
			'option2' => $configOption2,
		], $collection->offsetGet('sub')->getArrayCopy());
	}

	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddCollectionThrowsExceptionWhenIdentifierIsOption() {
		$collection = new ConfigOptionCollection('root');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The identifier `sub` is already in use.');

		$configOption1 = new ConfigOption(
			defaultValue: true,
			identifier: 'sub',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);

		$subCollection1 = new ConfigOptionCollection('sub');
		$configOption2 = new ConfigOption(
			defaultValue: true,
			identifier: 'option1',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);
		$subCollection1->add($configOption2);

		$collection->add($configOption1);
		$collection->addCollection($subCollection1);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetConfigOptionsPrependsPathPartsToIdentifier(): void {
		$collection = new ConfigOptionCollection('root');
		$collection->add(new ConfigOption(
			defaultValue: true,
			identifier: 'option1',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		));

		$subCollection = new ConfigOptionCollection('sub');
		$subCollection->add(new ConfigOption(
			defaultValue: true,
			identifier: 'option2',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		));

		$collection->addCollection($subCollection);

		$configOptions = $collection->getConfigOptions('prepended.Path');

		$this->assertCount(2, $configOptions);

		$this->assertArrayHasKey('prepended.Path.option1', $configOptions);
		$this->assertArrayHasKey('prepended.Path.sub.option2', $configOptions);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testDeepArrayCopy(): void {
		$collection = new ConfigOptionCollection('root');
		$option1 = new ConfigOption(
			defaultValue: true,
			identifier: 'option1',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);
		$collection->add($option1);

		$subCollection = new ConfigOptionCollection('sub');
		$option2 = new ConfigOption(
			defaultValue: true,
			identifier: 'option2',
			localizable: false,
			nullable: false,
			personalizable: true,
			type: ConfigOptionType::Bool,
		);
		$subCollection->add($option2);

		$collection->addCollection($subCollection);

		$deepCopy = $collection->toArray();

		$this->assertNotSame($collection, $deepCopy);

		$this->assertSame([
			'option1' => $option1,
			'sub' => [
				'option2' => $option2,
			],
		], $deepCopy);
	}
}
