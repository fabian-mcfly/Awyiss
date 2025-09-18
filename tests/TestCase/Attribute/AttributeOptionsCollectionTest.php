<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Attribute;


use Awyiss\Attribute\AttributeOption;
use Awyiss\Test\TestSuite\TestCase;
use Customer\Attribute\AttributeOptions\EmptyAttributeOptions;
use RuntimeException;


/**
 * Test class for Awyiss\Attribute\AttributeOptions
 */
class AttributeOptionsCollectionTest extends TestCase {
	/**
	 * Test if adding AttributeOptions instances works
	 * Either by passing an array or an instance
	 *
	 * @return void
	 * @see \Awyiss\Attribute\AttributeOptionsCollection::add()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddAttributeOptions(): void {
		$attributeOptions = new EmptyAttributeOptions();

		// Adding a new AttributeOptions instance by passing an array
		$attributeOptions->add([
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
		$this->assertNotNull($attributeOptions['backgroundColor']);
		$this->assertInstanceOf(AttributeOption::class, $attributeOptions['backgroundColor']);

		// Adding will sanitize the identifier
		// Adding a new AttributeOptions instance by passing an instance is also possible
		$attributeOptions->add(new AttributeOption('text_color', true, ['dark', 'light'], true, null, null, 'dark'));
		$this->assertNotNull($attributeOptions['textColor']);
		$this->assertInstanceOf(AttributeOption::class, $attributeOptions['textColor']);

		// Adding the same identifier again should throw an exception
		$this->expectException(RuntimeException::class);
		$attributeOptions->add(new AttributeOption('text__color'));
	}


	/**
	 * Test getAttributeOption
	 *
	 * @return void
	 * @see \Awyiss\Attribute\AttributeOptionsCollection::getAttributeOption()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOption(): void {
		$attributeOptions = new EmptyAttributeOptions();

		$attributeOptions->add([
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

		$this->assertNotNull($attributeOptions->getAttributeOption('backgroundColor'));
		$this->assertInstanceOf(AttributeOption::class, $attributeOptions->getAttributeOption('backgroundColor'));

		$this->assertNull($attributeOptions->getAttributeOption('textColor'));
	}


	/**
	 * Test getAttributeOptionsAttributes
	 *
	 * @return void
	 * @see \Awyiss\Attribute\AttributeOptionsCollection::getAttributeOptionsAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAttributeOptionsAttributes(): void {
		$attributeOptions = new EmptyAttributeOptions();

		$attributeOptions->add([
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
		], $attributeOptions->getAttributeOptionsAttributes('backgroundColor'));
	}
}
