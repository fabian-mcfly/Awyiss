<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model;


use Awyiss\Model\Entity;
use Awyiss\Test\TestSuite\TestCase;
use Cake\I18n\DateTime;
use InvalidArgumentException;


/**
 * General tests for the Entity class
 *
 * @see \Awyiss\Model\Entity
 */
class EntityTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorMapsFields(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('testColumn1', $entityArray);
		$this->assertEquals('Value 1', $entityArray['testColumn1']);
		$this->assertArrayHasKey('testColumn2', $entityArray);
		$this->assertEquals('Value 2', $entityArray['testColumn2']);
		$this->assertArrayHasKey('publicationStart', $entityArray);
		$this->assertInstanceOf(DateTime::class, $entityArray['publicationStart']);
		$this->assertArrayHasKey('publicationEnd', $entityArray);
		$this->assertInstanceOf(DateTime::class, $entityArray['publicationEnd']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorSetsPropertiesAsOriginal(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
		];

		$entity = new Entity($properties);
		$entity->set('otherField', 'Other Value');

		$originals = $entity->getOriginalFields();

		$this->assertSame([
			'id',
			'title',
			'publicationStart',
			'publicationEnd',
		], $originals);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::__construct()
	 * @see \Awyiss\Model\Entity::setAccess()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorSetsAccessIfNotSet(): void {
		$entity = new Entity([]);

		$this->assertTrue($entity->isAccessible('_translations'));
		$this->assertTrue($entity->isAccessible('_publicationData'));
		$this->assertTrue($entity->isAccessible('mediaAssignments'));
		$this->assertTrue($entity->isAccessible('mediaElementAssignments'));

		$entity = new class extends Entity {
			protected array $_accessible = [
				'_translations' => false,
				'_publicationData' => false,
				'mediaAssignments' => false,
				'mediaElementAssignments' => false,
			];
		};

		$this->assertFalse($entity->isAccessible('_translations'));
		$this->assertFalse($entity->isAccessible('_publicationData'));
		$this->assertFalse($entity->isAccessible('mediaAssignments'));
		$this->assertFalse($entity->isAccessible('mediaElementAssignments'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::get()
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetWithUnmappedField(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$this->assertEquals('Value 1', $entity->get('test_column_1'));
		$this->assertEquals('Value 2', $entity->get('test_column_2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::get()
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetWithMappedField(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$this->assertEquals('Value 1', $entity->get('testColumn1'));
		$this->assertEquals('Value 2', $entity->get('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::get()
	 * @see \Awyiss\Model\Entity::getFromAttribute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFromAttributeEntity(): void {
		$attributes = new Entity(['color' => 'red']);
		$entity = new class (['attributes' => $attributes]) extends Entity {
		};

		$this->assertEquals('red', $entity->get('color'));
		$this->assertNull($entity->get('size'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::get()
	 * @see \Awyiss\Model\Entity::getFromAttribute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetReturnsByReferenceForSelf(): void {
		$entity = new Entity(['title' => 'Original']);
		$value = &$entity->get('title');
		$value = 'Changed';
		$this->assertEquals('Changed', $entity->get('title'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::get()
	 * @see \Awyiss\Model\Entity::getFromAttribute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetReturnsByReferenceForAttribute(): void {
		$attributes = new Entity(['color' => 'red']);
		$entity = new class (['attributes' => $attributes]) extends Entity {
		};

		$value = &$entity->get('color');
		$value = 'blue';
		$this->assertEquals('blue', $entity->get('color'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::set()
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetWithUnmappedField(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$entity->set('test_column_1', 'New Value 1');
		$entity->set('test_column_2', 'New Value 2');

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('testColumn1', $entityArray);
		$this->assertEquals('New Value 1', $entityArray['testColumn1']);
		$this->assertArrayHasKey('testColumn2', $entityArray);
		$this->assertEquals('New Value 2', $entityArray['testColumn2']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::set()
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetWithMappedField(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$entity->set('testColumn1', 'New Value 1');
		$entity->set('testColumn2', 'New Value 2');

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('testColumn1', $entityArray);
		$this->assertEquals('New Value 1', $entityArray['testColumn1']);
		$this->assertArrayHasKey('testColumn2', $entityArray);
		$this->assertEquals('New Value 2', $entityArray['testColumn2']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::set()
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetWithArrayEmitsDeprecationWarning(): void {
		$entity = new Entity();

		$this->deprecated(
			function () use ($entity): void {
				$entity->set(['test_column_1' => 'New Value 1', 'test_column_2' => 'New Value 2']);
			}
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::set()
	 * @see \Awyiss\Model\Entity::setFromAttribute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetInAttributeEntity(): void {
		$attributes = new Entity(['color' => 'red']);
		$entity = new class (['attributes' => $attributes]) extends Entity {
		};

		$entity->set('color', 'blue');
		$this->assertEquals('blue', $entity->get('color'));
		$this->assertEquals('blue', $entity->attributes->get('color'));
		$this->assertNull($entity->get('size'));

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('attributes', $entityArray);
		$this->assertIsArray($entityArray['attributes']);
		$this->assertArrayHasKey('color', $entityArray['attributes']);
		$this->assertArrayNotHasKey('color', $entityArray);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::patch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPatchWithUnmappedFields(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$entity->patch([
			'test_column_1' => 'New Value 1',
			'test_column_2' => 'New Value 2',
		]);

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('testColumn1', $entityArray);
		$this->assertEquals('New Value 1', $entityArray['testColumn1']);
		$this->assertArrayHasKey('testColumn2', $entityArray);
		$this->assertEquals('New Value 2', $entityArray['testColumn2']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::patch()
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPatchWithMappedFields(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Title',
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
			'test_column_1' => 'Value 1',
			'test_column_2' => 'Value 2',
		];

		$entity = new class ($properties) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
				'publication_start' => 'publicationStart',
				'publication_end' => 'publicationEnd',
			];
		};

		$entity->patch([
			'testColumn1' => 'New Value 1',
			'testColumn2' => 'New Value 2',
		]);

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('testColumn1', $entityArray);
		$this->assertEquals('New Value 1', $entityArray['testColumn1']);
		$this->assertArrayHasKey('testColumn2', $entityArray);
		$this->assertEquals('New Value 2', $entityArray['testColumn2']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::patch()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPatchInAttributeEntity(): void {
		$attributes = new Entity(['color' => 'red']);
		$entity = new class (['attributes' => $attributes]) extends Entity {
		};

		$entity->patch([
			'color' => 'blue',
			'size' => 'large',
		]);

		$this->assertEquals('blue', $entity->get('color'));
		$this->assertEquals('blue', $entity->attributes->get('color'));
		$this->assertEquals('large', $entity->get('size'));
		$this->assertNull($entity->attributes->get('size'));

		$entityArray = $entity->toArray();

		$this->assertArrayHasKey('attributes', $entityArray);
		$this->assertIsArray($entityArray['attributes']);
		$this->assertArrayHasKey('color', $entityArray['attributes']);
		$this->assertEquals('blue', $entityArray['attributes']['color']);
		$this->assertArrayNotHasKey('size', $entityArray['attributes']);

		$this->assertArrayNotHasKey('color', $entityArray);
		$this->assertArrayHasKey('size', $entityArray);
		$this->assertEquals('large', $entityArray['size']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::__isset()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testIssetWithUnmappedField(): void {
		$entity = new Entity();
		$entity->test_column_1 = 'Test Value';

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue(isset($entity->test_column_1));
		$this->assertFalse(isset($entity->testColumn1));
		$this->assertFalse(isset($entity->nonExistentField));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::__isset()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testIssetWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
			];
		};
		$entity->testColumn1 = 'Test Value';

		$this->assertTrue(isset($entity->test_column_1));
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue(isset($entity->testColumn1));
		$this->assertFalse(isset($entity->nonExistentField));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::unset()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testUnsetWithUnmappedField(): void {
		$entity = new Entity();
		$entity->test_column_1 = 'Test Value';

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue(isset($entity->test_column_1));
		$this->assertFalse(isset($entity->testColumn1));
		unset($entity->test_column_1);
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse(isset($entity->test_column_1));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::unset()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testUnsetWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
			];
		};
		$entity->testColumn1 = 'Test Value';

		$this->assertTrue(isset($entity->test_column_1));
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue(isset($entity->testColumn1));
		unset($entity->testColumn1);
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse(isset($entity->test_column_1));
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse(isset($entity->testColumn1));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setHidden()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetHiddenFieldsWithUnmappedField(): void {
		$entity = new Entity();
		$entity->setHidden(['test_column_1', 'test_column_2']);

		$this->assertSame(['test_column_1', 'test_column_2'], $entity->getHidden());

		$entity->setHidden(['testColumn1', 'testColumn2']);

		$this->assertSame(['testColumn1', 'testColumn2'], $entity->getHidden());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setHidden()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetHiddenFieldsWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->setHidden(['test_column_1', 'test_column_2']);

		$this->assertSame(['testColumn1', 'testColumn2'], $entity->getHidden());

		$entity->setHidden(['testColumn1', 'testColumn2']);

		$this->assertSame(['testColumn1', 'testColumn2'], $entity->getHidden());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setVirtual()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetVirtualFieldsWithUnmappedField(): void {
		$entity = new Entity();
		$entity->setVirtual(['test_column_1', 'test_column_2']);

		$this->assertSame(['test_column_1', 'test_column_2'], $entity->getVirtual());

		$entity->setVirtual(['testColumn1', 'testColumn2']);

		$this->assertSame(['testColumn1', 'testColumn2'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setVirtual()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetVirtualFieldsWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->setVirtual(['test_column_1', 'test_column_2']);

		$this->assertSame(['testColumn1', 'testColumn2'], $entity->getVirtual());

		$entity->setVirtual(['testColumn1', 'testColumn2']);

		$this->assertSame(['testColumn1', 'testColumn2'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::has()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testHasWithUnmappedField(): void {
		$entity = new Entity();
		$entity->test_column_1 = 'Test Value';
		$entity->test_column_2 = 'Another Value';

		$this->assertTrue($entity->has('test_column_1'));
		$this->assertFalse($entity->has('testColumn1'));
		$this->assertTrue($entity->has('test_column_2'));
		$this->assertFalse($entity->has('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::has()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testHasWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->testColumn1 = 'Test Value';
		$entity->testColumn2 = 'Another Value';

		$this->assertTrue($entity->has('test_column_1'));
		$this->assertTrue($entity->has('testColumn1'));
		$this->assertTrue($entity->has('test_column_2'));
		$this->assertTrue($entity->has('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::hasOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testHasOriginalWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->test_column_1 = 'New Test Value';
		$entity->test_column_2 = 'New Another Value';

		$this->assertTrue($entity->hasOriginal('test_column_1'));
		$this->assertFalse($entity->hasOriginal('testColumn1'));
		$this->assertTrue($entity->hasOriginal('test_column_2'));
		$this->assertFalse($entity->hasOriginal('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::hasOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testHasOriginalWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->testColumn1 = 'New Test Value';
		$entity->testColumn2 = 'New Another Value';

		$this->assertTrue($entity->hasOriginal('test_column_1'));
		$this->assertTrue($entity->hasOriginal('testColumn1'));
		$this->assertTrue($entity->hasOriginal('test_column_2'));
		$this->assertTrue($entity->hasOriginal('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::getOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testGetOriginalWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->test_column_1 = 'New Test Value';
		$entity->test_column_2 = 'New Another Value';

		$this->assertEquals('Test Value', $entity->getOriginal('test_column_1'));
		$this->assertEquals('Another Value', $entity->getOriginal('test_column_2'));

		$this->expectException(InvalidArgumentException::class);
		$this->assertNull($entity->getOriginal('testColumn1'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::getOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function testGetOriginalWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->testColumn1 = 'New Test Value';
		$entity->testColumn2 = 'New Another Value';

		$this->assertEquals('Test Value', $entity->getOriginal('test_column_1'));
		$this->assertEquals('Test Value', $entity->getOriginal('testColumn1'));
		$this->assertEquals('Another Value', $entity->getOriginal('test_column_2'));
		$this->assertEquals('Another Value', $entity->getOriginal('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extract()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);

		$extracted = $entity->extract(['test_column_1', 'test_column_2', 'test_column_3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
			'test_column_3' => null,
		], $extracted);

		$extracted = $entity->extract(['testColumn1', 'testColumn2', 'testColumn3']);
		$this->assertSame(['testColumn1' => null, 'testColumn2' => null, 'testColumn3' => null], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extract()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$extracted = $entity->extract(['test_column_1', 'test_column_2', 'test_column_3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
			'test_column_3' => null,
		], $extracted);

		$extracted = $entity->extract(['testColumn1', 'testColumn2', 'testColumn3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
			'testColumn3' => null,
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extract()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractWithMappedFieldReturnMapped(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$extracted = $entity->extract(['test_column_1', 'test_column_2', 'test_column_3'], false, false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
			'testColumn2' => 'Another Value',
			'test_column_3' => null,
		], $extracted);

		$extracted = $entity->extract(['testColumn1', 'testColumn2', 'testColumn3'], false, false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
			'testColumn2' => 'Another Value',
			'testColumn3' => null,
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extract()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractWithUnmappedFieldOnlyDirty(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->clean();
		$entity->set('test_column_1', 'New Test Value');

		$extracted = $entity->extract(['test_column_1', 'test_column_2', 'test_column_3'], true);
		$this->assertSame([
			'test_column_1' => 'New Test Value',
		], $extracted);

		$extracted = $entity->extract(['testColumn1', 'testColumn2', 'test_column_3'], true);
		$this->assertSame([], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extract()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractWithMappedFieldOnlyDirty(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extract(['test_column_1', 'test_column_2', 'test_column_3'], true);
		$this->assertSame([
			'test_column_1' => 'New Test Value',
		], $extracted);

		$extracted = $entity->extract(['testColumn1', 'testColumn2', 'testColumn3'], true);
		$this->assertSame([
			'test_column_1' => 'New Test Value',
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extract()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractWithMappedFieldOnlyDirtyReturnMapped(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extract(['test_column_1', 'test_column_2', 'test_column_3'], true, false);
		$this->assertSame([
			'testColumn1' => 'New Test Value',
		], $extracted);

		$extracted = $entity->extract(['testColumn1', 'testColumn2', 'test_column_3'], true, false);
		$this->assertSame([
			'testColumn1' => 'New Test Value',
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->clean();
		$entity->set('test_column_1', 'New Test Value');

		$extracted = $entity->extractOriginal(['test_column_1', 'test_column_2', 'test_column_3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		], $extracted);

		$extracted = $entity->extractOriginal(['testColumn1', 'testColumn2', 'test_column_3']);
		$this->assertSame([], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extractOriginal(['test_column_1', 'test_column_2', 'test_column_3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		], $extracted);

		$extracted = $entity->extractOriginal(['testColumn1', 'testColumn2', 'testColumn3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalWithMappedFieldReturnMapped(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extractOriginal(['test_column_1', 'test_column_2', 'test_column_3'], false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
			'testColumn2' => 'Another Value',
		], $extracted);

		$extracted = $entity->extractOriginal(['testColumn1', 'testColumn2', 'testColumn3'], false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
			'testColumn2' => 'Another Value',
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginalChanged()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalChangedWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->clean();
		$entity->set('test_column_1', 'New Test Value');

		$extracted = $entity->extractOriginalChanged(['test_column_1', 'test_column_2', 'test_column_3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
		], $extracted);

		$extracted = $entity->extractOriginalChanged(['testColumn1', 'testColumn2', 'testColumn3']);
		$this->assertSame([], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginalChanged()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalChangedWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extractOriginalChanged(['test_column_1', 'test_column_2', 'test_column_3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
		], $extracted);

		$extracted = $entity->extractOriginalChanged(['testColumn1', 'testColumn2', 'testColumn3']);
		$this->assertSame([
			'test_column_1' => 'Test Value',
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginalChanged()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalChangedWithUnmappedFieldIncludingUnknownFields(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->clean();
		$entity->set('test_column_1', 'New Test Value');

		$extracted = $entity->extractOriginalChanged(['test_column_1', 'test_column_2', 'test_column_3'], true);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_3' => null,
		], $extracted);

		$extracted = $entity->extractOriginalChanged(['testColumn1', 'testColumn2', 'testColumn3'], true);
		$this->assertSame(['testColumn1' => null, 'testColumn2' => null, 'testColumn3' => null], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginalChanged()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalChangedWithMappedFieldIncludingUnknownFields(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extractOriginalChanged(['test_column_1', 'test_column_2', 'test_column_3'], true);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'test_column_3' => null,
		], $extracted);

		$extracted = $entity->extractOriginalChanged(['testColumn1', 'testColumn2', 'testColumn3'], true);
		$this->assertSame([
			'test_column_1' => 'Test Value',
			'testColumn3' => null,
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginalChanged()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalChangedWithMappedFieldIncludingUnknownFieldsReturnUnapped(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extractOriginalChanged(['test_column_1', 'test_column_2', 'test_column_3'], true, false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
			'test_column_3' => null,
		], $extracted);

		$extracted = $entity->extractOriginalChanged(['testColumn1', 'testColumn2', 'testColumn3'], true, false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
			'testColumn3' => null,
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::extractOriginalChanged()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtractOriginalChangedWithMappedFieldReturnMapped(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();
		$entity->set('testColumn1', 'New Test Value');

		$extracted = $entity->extractOriginalChanged(['test_column_1', 'test_column_2', 'test_column_3'], false, false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
		], $extracted);

		$extracted = $entity->extractOriginalChanged(['testColumn1', 'testColumn2', 'testColumn3'], false, false);
		$this->assertSame([
			'testColumn1' => 'Test Value',
		], $extracted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isDirty()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsDirtyWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->clean();

		$this->assertFalse($entity->isDirty('test_column_1'));
		$this->assertFalse($entity->isDirty('testColumn1'));

		$entity->set('test_column_1', 'New Test Value');
		$this->assertTrue($entity->isDirty('test_column_1'));
		$this->assertFalse($entity->isDirty('testColumn1'));

		$this->assertFalse($entity->isDirty('test_column_2'));
		$this->assertFalse($entity->isDirty('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isDirty()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsDirtyWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();

		$this->assertFalse($entity->isDirty('test_column_1'));
		$this->assertFalse($entity->isDirty('testColumn1'));

		$entity->set('testColumn1', 'New Test Value');
		$this->assertTrue($entity->isDirty('test_column_1'));
		$this->assertTrue($entity->isDirty('testColumn1'));

		$this->assertFalse($entity->isDirty('test_column_2'));
		$this->assertFalse($entity->isDirty('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setDirty()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetDirtyWithUnmappedField(): void {
		$entity = new Entity([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]);
		$entity->clean();

		$entity->setDirty('test_column_1');
		$entity->setDirty('testColumn2');

		$this->assertTrue($entity->isDirty('test_column_1'));
		$this->assertFalse($entity->isDirty('testColumn1'));

		$this->assertFalse($entity->isDirty('test_column_2'));
		$this->assertTrue($entity->isDirty('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setDirty()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetDirtyWithMappedField(): void {
		$entity = new class ([
			'test_column_1' => 'Test Value',
			'test_column_2' => 'Another Value',
		]) extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->clean();

		$entity->setDirty('test_column_1');
		$entity->setDirty('testColumn2');

		$this->assertTrue($entity->isDirty('test_column_1'));
		$this->assertTrue($entity->isDirty('testColumn1'));

		$this->assertTrue($entity->isDirty('test_column_2'));
		$this->assertTrue($entity->isDirty('testColumn2'));
	}



	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::getError()
	 * @see \Awyiss\Model\Entity::setError()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetErrorWithUnmappedField(): void {
		$entity = new Entity();
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$this->assertEmpty($entity->getError('test_column_1'));
		$this->assertEmpty($entity->getError('testColumn1'));

		$entity->setError('test_column_1', 'Error for test_column_1');
		$this->assertEquals(['Error for test_column_1'], $entity->getError('test_column_1'));
		$this->assertEmpty($entity->getError('testColumn1'));

		$entity->setError('testColumn2', 'Error for testColumn2');
		$this->assertEmpty($entity->getError('test_column_2'));
		$this->assertEquals(['Error for testColumn2'], $entity->getError('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::getError()
	 * @see \Awyiss\Model\Entity::setError()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetErrorWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->set('testColumn1', 'Test Value');
		$entity->set('testColumn2', 'Another Value');

		$this->assertEmpty($entity->getError('test_column_1'));
		$this->assertEmpty($entity->getError('testColumn1'));

		$entity->setError('test_column_1', 'Error for test_column_1');
		$this->assertEquals(['Error for test_column_1'], $entity->getError('test_column_1'));
		$this->assertEquals(['Error for test_column_1'], $entity->getError('testColumn1'));

		$entity->setError('testColumn2', 'Error for testColumn2');
		$this->assertEquals(['Error for testColumn2'], $entity->getError('test_column_2'));
		$this->assertEquals(['Error for testColumn2'], $entity->getError('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setErrors()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetErrorsWithUnmappedField(): void {
		$entity = new Entity();
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$entity->setErrors([
			'test_column_1' => ['Error for test_column_1'],
			'testColumn1' => ['Error for testColumn1'],
			'test_column_2' => ['Error for test_column_2'],
			'testColumn2' => ['Error for testColumn2'],
			'test_column_3' => ['Error for test_column_3'],
			'testColumn3' => ['Error for testColumn3'],
		]);

		$this->assertEquals(['Error for test_column_1'], $entity->getError('test_column_1'));
		$this->assertEquals(['Error for testColumn1'], $entity->getError('testColumn1'));

		$this->assertEquals(['Error for test_column_2'], $entity->getError('test_column_2'));
		$this->assertEquals(['Error for testColumn2'], $entity->getError('testColumn2'));

		$this->assertEquals(['Error for test_column_3'], $entity->getError('test_column_3'));
		$this->assertEquals(['Error for testColumn3'], $entity->getError('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setErrors()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetErrorsWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->set('testColumn1', 'Test Value');
		$entity->set('testColumn2', 'Another Value');

		$entity->setErrors([
			'test_column_1' => ['Error for test_column_1'],
			'testColumn1' => ['Error for testColumn1'],
			'test_column_2' => ['Error for test_column_2'],
			'testColumn2' => ['Error for testColumn2'],
			'test_column_3' => ['Error for test_column_3'],
			'testColumn3' => ['Error for testColumn3'],
		]);

		$this->assertEquals(['Error for testColumn1'], $entity->getError('test_column_1'));
		$this->assertEquals(['Error for testColumn1'], $entity->getError('testColumn1'));

		$this->assertEquals(['Error for testColumn2'], $entity->getError('test_column_2'));
		$this->assertEquals(['Error for testColumn2'], $entity->getError('testColumn2'));

		$this->assertEquals(['Error for test_column_3'], $entity->getError('test_column_3'));
		$this->assertEquals(['Error for testColumn3'], $entity->getError('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::getInvalidField()
	 * @see \Awyiss\Model\Entity::setInvalidField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetInvalidFieldWithUnmappedField(): void {
		$entity = new Entity();
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$entity->setInvalidField('test_column_1', 'InvalidField for test_column_1');
		$entity->setInvalidField('testColumn2', 'InvalidField for testColumn2');

		$this->assertEquals('InvalidField for test_column_1', $entity->getInvalidField('test_column_1'));
		$this->assertNull($entity->getInvalidField('testColumn1'));

		$this->assertNull($entity->getInvalidField('test_column_2'));
		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::getInvalidField()
	 * @see \Awyiss\Model\Entity::setInvalidField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetInvalidFieldWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->set('testColumn1', 'Test Value');
		$entity->set('testColumn2', 'Another Value');

		$entity->setInvalidField('test_column_1', 'InvalidField for test_column_1');
		$entity->setInvalidField('testColumn2', 'InvalidField for testColumn2');

		$this->assertEquals('InvalidField for test_column_1', $entity->getInvalidField('test_column_1'));
		$this->assertEquals('InvalidField for test_column_1', $entity->getInvalidField('testColumn1'));

		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('test_column_2'));
		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setInvalid()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetInvalidWithUnmappedField(): void {
		$entity = new Entity();
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$entity->setInvalid([
			'test_column_1' => 'InvalidField for test_column_1',
			'testColumn1' => 'InvalidField for testColumn1',
			'test_column_2' => 'InvalidField for test_column_2',
			'testColumn2' => 'InvalidField for testColumn2',
			'test_column_3' => 'InvalidField for test_column_3',
			'testColumn3' => 'InvalidField for testColumn3',
		]);

		$this->assertEquals('InvalidField for test_column_1', $entity->getInvalidField('test_column_1'));
		$this->assertEquals('InvalidField for testColumn1', $entity->getInvalidField('testColumn1'));

		$this->assertEquals('InvalidField for test_column_2', $entity->getInvalidField('test_column_2'));
		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('testColumn2'));

		$this->assertEquals('InvalidField for test_column_3', $entity->getInvalidField('test_column_3'));
		$this->assertEquals('InvalidField for testColumn3', $entity->getInvalidField('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setInvalid()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetInvalidWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$entity->setInvalid([
			'test_column_1' => 'InvalidField for test_column_1',
			'testColumn1' => 'InvalidField for testColumn1',
			'test_column_2' => 'InvalidField for test_column_2',
			'testColumn2' => 'InvalidField for testColumn2',
			'test_column_3' => 'InvalidField for test_column_3',
			'testColumn3' => 'InvalidField for testColumn3',
		]);

		$this->assertEquals('InvalidField for testColumn1', $entity->getInvalidField('test_column_1'));
		$this->assertEquals('InvalidField for testColumn1', $entity->getInvalidField('testColumn1'));

		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('test_column_2'));
		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('testColumn2'));

		$this->assertEquals('InvalidField for test_column_3', $entity->getInvalidField('test_column_3'));
		$this->assertEquals('InvalidField for testColumn3', $entity->getInvalidField('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setInvalid()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetInvalidWithMappedFieldWithoutOverwrite(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$entity->setInvalid([
			'test_column_1' => 'InvalidField for test_column_1',
			'test_column_2' => 'InvalidField for test_column_2',
			'test_column_3' => 'InvalidField for test_column_3',
		]);

		$entity->setInvalid([
			'testColumn1' => 'InvalidField for testColumn1',
			'testColumn2' => 'InvalidField for testColumn2',
			'testColumn3' => 'InvalidField for testColumn3',
		]);

		$this->assertEquals('InvalidField for test_column_1', $entity->getInvalidField('test_column_1'));
		$this->assertEquals('InvalidField for test_column_1', $entity->getInvalidField('testColumn1'));

		$this->assertEquals('InvalidField for test_column_2', $entity->getInvalidField('test_column_2'));
		$this->assertEquals('InvalidField for test_column_2', $entity->getInvalidField('testColumn2'));

		$this->assertEquals('InvalidField for test_column_3', $entity->getInvalidField('test_column_3'));
		$this->assertEquals('InvalidField for testColumn3', $entity->getInvalidField('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::setInvalid()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetInvalidWithMappedFieldWithOverwrite(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};
		$entity->set('test_column_1', 'Test Value');
		$entity->set('test_column_2', 'Another Value');

		$entity->setInvalid([
			'test_column_1' => 'InvalidField for test_column_1',
			'test_column_2' => 'InvalidField for test_column_2',
			'test_column_3' => 'InvalidField for test_column_3',
		]);

		$entity->setInvalid([
			'testColumn1' => 'InvalidField for testColumn1',
			'testColumn2' => 'InvalidField for testColumn2',
			'testColumn3' => 'InvalidField for testColumn3',
		], true);

		$this->assertEquals('InvalidField for testColumn1', $entity->getInvalidField('test_column_1'));
		$this->assertEquals('InvalidField for testColumn1', $entity->getInvalidField('testColumn1'));

		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('test_column_2'));
		$this->assertEquals('InvalidField for testColumn2', $entity->getInvalidField('testColumn2'));

		$this->assertEquals('InvalidField for test_column_3', $entity->getInvalidField('test_column_3'));
		$this->assertEquals('InvalidField for testColumn3', $entity->getInvalidField('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isAccessible()
	 * @see \Awyiss\Model\Entity::setAccess()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsAccessibleWithUnmappedField(): void {
		$entity = new Entity();

		$entity->setAccess('test_column_1', true);
		$entity->setAccess('testColumn1', false);
		$entity->setAccess('test_column_2', false);
		$entity->setAccess('testColumn2', true);

		$this->assertTrue($entity->isAccessible('test_column_1'));
		$this->assertFalse($entity->isAccessible('testColumn1'));
		$this->assertFalse($entity->isAccessible('test_column_2'));
		$this->assertTrue($entity->isAccessible('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isAccessible()
	 * @see \Awyiss\Model\Entity::setAccess()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsAccessibleWithMappedField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$entity->setAccess('test_column_1', true);
		$entity->setAccess('testColumn1', false);
		$entity->setAccess('test_column_2', false);
		$entity->setAccess('testColumn2', true);

		$this->assertFalse($entity->isAccessible('test_column_1'));
		$this->assertFalse($entity->isAccessible('testColumn1'));
		$this->assertTrue($entity->isAccessible('test_column_2'));
		$this->assertTrue($entity->isAccessible('testColumn2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::addFieldMapping()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddFieldMapping(): void {
		$entity = new Entity([]);
		$entity->set('test_column_1', 'Test Value');

		$this->assertEquals('Test Value', $entity->get('test_column_1'));
		$this->assertNull($entity->get('testColumn1'));

		$entity->addFieldMapping('test_column_1', 'testColumn1');

		// After adding the mapping, the old value is no longer accessible
		$this->assertNull($entity->get('test_column_1'));
		$this->assertNull($entity->get('testColumn1'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::mapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMapField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$this->assertSame('testColumn1', $entity::mapField('test_column_1'));
		$this->assertSame('testColumn2', $entity::mapField('test_column_2'));
		$this->assertSame('test_column_3', $entity::mapField('test_column_3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::mapFields()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMapFields(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$fields = ['test_column_1' => 'test_column_1', 'test_column_2' => 'test_column_2', 'test_column_3' => 'test_column_3'];

		$mappedFields = $entity::mapFields($fields);

		$this->assertSame(['test_column_1' => 'testColumn1', 'test_column_2' => 'testColumn2', 'test_column_3' => 'test_column_3'], $mappedFields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::mapFields()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMapFieldsMapsKeys(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$fields = ['test_column_1' => 'test_column_1', 'test_column_2' => 'test_column_2', 'test_column_3' => 'test_column_3'];

		$mappedFields = $entity::mapFields($fields, true);

		$this->assertSame(['testColumn1' => 'test_column_1', 'testColumn2' => 'test_column_2', 'test_column_3' => 'test_column_3'], $mappedFields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::unmapField()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUnmapField(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$this->assertSame('test_column_1', $entity::unmapField('testColumn1'));
		$this->assertSame('test_column_2', $entity::unmapField('testColumn2'));
		$this->assertSame('testColumn3', $entity::unmapField('testColumn3'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::unmapFields()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUnmapFields(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$fields = ['testColumn1' => 'testColumn1', 'testColumn2' => 'testColumn2', 'testColumn3' => 'testColumn3'];

		$unmappedFields = $entity::unmapFields($fields);

		$this->assertSame(['testColumn1' => 'test_column_1', 'testColumn2' => 'test_column_2', 'testColumn3' => 'testColumn3'], $unmappedFields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::unmapFields()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUnmapFieldsUnmapsKeys(): void {
		$entity = new class extends Entity {
			protected static array $fieldMap = [
				'test_column_1' => 'testColumn1',
				'test_column_2' => 'testColumn2',
			];
		};

		$fields = ['testColumn1' => 'testColumn1', 'testColumn2' => 'testColumn2', 'testColumn3' => 'testColumn3'];

		$unmappedFields = $entity::unmapFields($fields, true);

		$this->assertSame(['test_column_1' => 'testColumn1', 'test_column_2' => 'testColumn2', 'testColumn3' => 'testColumn3'], $unmappedFields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::defaultValues()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDefaultValues(): void {
		$entity = new Entity([]);
		$this->assertSame([], $entity->defaultValues());

		$entity = new class extends Entity {
			protected array $defaultValues = ['key1' => 'value1', 'key2' => 'value2'];
		};
		$this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $entity->defaultValues());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::allowsAudit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAllowsAuditDefaultTrue(): void {
		$entity = new Entity([]);
		$this->assertTrue($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::allowsAudit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisableAuditPreventsAudit(): void {
		$entity = new Entity([]);
		$entity->disableAudit();
		$this->assertFalse($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::allowsAudit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnableAuditTogglesAuditFlag(): void {
		$entity = new Entity([]);
		$entity->disableAudit();
		$entity->enableAudit();
		$this->assertTrue($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::enableAudit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnableAuditWithFalse(): void {
		$entity = new Entity([]);
		$entity->enableAudit(false);
		$this->assertFalse($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::enableAudit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnableAuditReturnsSelf(): void {
		$entity = new Entity([]);
		$result = $entity->enableAudit();
		$this->assertSame($entity, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::disableAudit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisableAuditReturnsSelf(): void {
		$entity = new Entity([]);
		$result = $entity->disableAudit();
		$this->assertSame($entity, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isPublished()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsPublishedReturnsNullWhenNoPublicationData(): void {
		$entity = new Entity([]);
		$entity->setSource('TestTable');

		$this->assertNull($entity->isPublished());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isPublished()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsPublishedReturnsFalseWhenStartInFuture(): void {
		$entity = new Entity([
			'publicationStart' => new DateTime('+1 day'),
		]);
		$entity->setSource('TestTable');
		$entity->set('_publicationData', [1]);

		$this->assertFalse($entity->isPublished());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isPublished()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsPublishedReturnsFalseWhenEndInPast(): void {
		$entity = new Entity([
			'publicationEnd' => new DateTime('-1 day'),
		]);
		$entity->setSource('TestTable');
		$entity->set('_publicationData', [1]);

		$this->assertFalse($entity->isPublished());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isPublished()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsPublishedReturnsTrueWithinPublicationWindow(): void {
		$entity = new Entity([
			'publicationStart' => new DateTime('-1 day'),
			'publicationEnd' => new DateTime('+1 day'),
		]);
		$entity->setSource('TestTable');
		$entity->set('_publicationData', [1]);

		$this->assertTrue($entity->isPublished());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithTitle(): void {
		$entity = new Entity(['title' => 'Test Title']);
		$entity->setSource('TestTable');

		$this->assertEquals('Test Title', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithName(): void {
		$entity = new Entity(['name' => 'Test Name']);
		$entity->setSource('TestTable');

		$this->assertEquals('Test Name', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithIdentifier(): void {
		$entity = new Entity(['identifier' => 'test_identifier']);
		$entity->setSource('TestTable');

		$this->assertEquals('test_table::title_test_identifier', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithFileName(): void {
		$entity = new Entity(['fileName' => 'test-file.jpg']);
		$entity->setSource('TestTable');

		$this->assertEquals('test-file.jpg', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelFallbackWithId(): void {
		$entity = new Entity(['id' => 123]);
		$entity->setSource('TestTables');

		$this->assertEquals('TestTable123', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithInactiveEntity(): void {
		$entity = new Entity(['title' => 'Test Title', 'active' => 0]);
		$entity->setSource('TestTable');

		$this->assertEquals('test_table::inactive Test Title', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithActiveEntity(): void {
		$entity = new Entity(['title' => 'Test Title', 'active' => 1]);
		$entity->setSource('TestTable');

		$this->assertEquals('Test Title', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithoutSource(): void {
		$entity = new Entity(['title' => 'Test Title']);

		$this->assertEquals('Test Title', $entity->label);
	}
}
