<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Attribute;


use Awyiss\Attribute\AttributeOptions;
use Awyiss\Test\TestSuite\TestCase;
use Customer\Attribute\AttributeOptionsCollection\EmptyAttributeOptionsCollection;
use RuntimeException;


/**
 * Test class for Awyiss\Attribute\AttributeOptions
 */
class AttributeOptionsCollectionTest extends TestCase {
	/**
	 * Test constructor and the scope
	 *
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorAndScope(): void {
		$attributeOptionsCollection = new EmptyAttributeOptionsCollection();

		$this->assertNotNull($attributeOptionsCollection);

		// The scope will be sanitized and not 'Empty', but 'Empties'
		$this->assertEquals('Empties', $attributeOptionsCollection::getScope());
	}


	/**
	 * Test if adding AttributeOptions instances works
	 * Either by passing an array or an instance
	 *
	 * @return void
	 * @see AttributeOptionsCollection::add()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddAttributeOptions(): void {
		$attributeOptionsCollection = new EmptyAttributeOptionsCollection();

		// Adding a new AttributeOptions instance by passing an array
		$attributeOptionsCollection->add([
			'backgroundColor' => [
				'disabled' => function () {
					return ['dark', 'light'];
				},
				'options' => function () {
					return [
						'text' => 'Text',
						'dark' => 'Dunkel',
						'medium' => 'Mittel',
						'light' => 'Hell',
						'main' => 'Hauptfarbe',
						'contrast' => 'Kontrastfarbe',
					];
				},
				'validate' => function (mixed $value) {
					return $value === null || $value === 'main';
				},
			],
		]);
		$this->assertNotNull($attributeOptionsCollection['backgroundColor']);
		$this->assertInstanceOf(AttributeOptions::class, $attributeOptionsCollection['backgroundColor']);

		// Adding will sanitize the identifier
		// Adding a new AttributeOptions instance by passing an instance is also possible
		$attributeOptionsCollection->add(new AttributeOptions('text_color', true, ['dark', 'light'], true, null, null, 'dark'));
		$this->assertNotNull($attributeOptionsCollection['textColor']);
		$this->assertInstanceOf(AttributeOptions::class, $attributeOptionsCollection['textColor']);

		// Adding the same identifier again should throw an exception
		$this->expectException(RuntimeException::class);
		$attributeOptionsCollection->add(new AttributeOptions('text__color'));
	}


	/**
	 * Test getAttributeOption
	 *
	 * @return void
	 * @see AttributeOptionsCollection::getAttributeOption()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOption(): void {
		$attributeOptionsCollection = new EmptyAttributeOptionsCollection();

		$attributeOptionsCollection->add([
			'background__color' => [
				'options' => [
					'text' => 'Text',
					'dark' => 'Dunkel',
					'medium' => 'Mittel',
					'light' => 'Hell',
					'main' => 'Hauptfarbe',
					'contrast' => 'Kontrastfarbe',
				],
			],
		]);

		$this->assertNotNull($attributeOptionsCollection->getAttributeOption('backgroundColor'));
		$this->assertInstanceOf(AttributeOptions::class, $attributeOptionsCollection->getAttributeOption('backgroundColor'));

		$this->assertNull($attributeOptionsCollection->getAttributeOption('textColor'));
	}


	/**
	 * Test getAttributeOptionsAttributes
	 *
	 * @return void
	 * @see AttributeOptionsCollection::getAttributeOptionsAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOptionsAttributes(): void {
		$attributeOptionsCollection = new EmptyAttributeOptionsCollection();

		$attributeOptionsCollection->add([
			'background__color' => [
				'options' => [
					'text' => 'Text',
					'dark' => 'Dunkel',
					'medium' => 'Mittel',
					'light' => 'Hell',
					'main' => 'Hauptfarbe',
					'contrast' => 'Kontrastfarbe',
				],
				'value' => 'main',
			],
		]);

		$this->assertEquals([
			'options' => [
				'text' => 'Text',
				'dark' => 'Dunkel',
				'medium' => 'Mittel',
				'light' => 'Hell',
				'main' => 'Hauptfarbe',
				'contrast' => 'Kontrastfarbe',
			],
			'val' => 'main',
		], $attributeOptionsCollection->getAttributeOptionsAttributes('backgroundColor'));
	}
}
