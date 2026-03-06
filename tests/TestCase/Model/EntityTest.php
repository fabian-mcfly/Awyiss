<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model;


use Awyiss\Model\Entity;
use Awyiss\Test\TestSuite\TestCase;
use Cake\I18n\DateTime;


/**
 * General tests for the Entity class
 *
 * @see \Awyiss\Model\Entity
 */
class EntityTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::__construct()
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
	 */
	public function testConstructorSetsAccessIfNotSet(): void {
		$entity = new Entity([]);

		$this->assertTrue($entity->isAccessible('_translations'));
		$this->assertTrue($entity->isAccessible('_publicationData'));
		$this->assertTrue($entity->isAccessible('mediaAssignments'));
		$this->assertTrue($entity->isAccessible('mediaElementAssignments'));

		$entity = new class extends Entity {
			protected array $_accessible = [ // phpcs:ignore
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
	 * @see \Awyiss\Model\Entity::getFromAttribute()
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
	 * @see \Awyiss\Model\Entity::defaultValues()
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
	 */
	public function testAllowsAuditDefaultTrue(): void {
		$entity = new Entity([]);
		$this->assertTrue($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::allowsAudit()
	 */
	public function testDisableAuditPreventsAudit(): void {
		$entity = new Entity([]);
		$entity->disableAudit();
		$this->assertFalse($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::allowsAudit()
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
	 */
	public function testEnableAuditWithFalse(): void {
		$entity = new Entity([]);
		$entity->enableAudit(false);
		$this->assertFalse($entity->allowsAudit());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::enableAudit()
	 */
	public function testEnableAuditReturnsSelf(): void {
		$entity = new Entity([]);
		$result = $entity->enableAudit();
		$this->assertSame($entity, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::disableAudit()
	 */
	public function testDisableAuditReturnsSelf(): void {
		$entity = new Entity([]);
		$result = $entity->disableAudit();
		$this->assertSame($entity, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isPublished()
	 */
	public function testIsPublishedReturnsNullWhenNoPublicationData(): void {
		$entity = new Entity([]);
		$entity->setSource('TestTable');

		$this->assertNull($entity->isPublished());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::isPublished()
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
	 */
	public function testGetLabelWithTitle(): void {
		$entity = new Entity(['title' => 'Test Title']);
		$entity->setSource('TestTable');

		$this->assertEquals('Test Title', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelWithName(): void {
		$entity = new Entity(['name' => 'Test Name']);
		$entity->setSource('TestTable');

		$this->assertEquals('Test Name', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelWithIdentifier(): void {
		$entity = new Entity(['identifier' => 'testIdentifier']);
		$entity->setSource('TestTable');

		$this->assertEquals('test_table::title_test_identifier', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelWithFileName(): void {
		$entity = new Entity(['fileName' => 'test-file.jpg']);
		$entity->setSource('TestTable');

		$this->assertEquals('test-file.jpg', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelFallbackWithId(): void {
		$entity = new Entity(['id' => 123]);
		$entity->setSource('TestTables');

		$this->assertEquals('TestTable123', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelWithInactiveEntity(): void {
		$entity = new Entity(['title' => 'Test Title', 'active' => 0]);
		$entity->setSource('TestTable');

		$this->assertEquals('test_table::inactive Test Title', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelWithActiveEntity(): void {
		$entity = new Entity(['title' => 'Test Title', 'active' => 1]);
		$entity->setSource('TestTable');

		$this->assertEquals('Test Title', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity::_getLabel()
	 */
	public function testGetLabelWithoutSource(): void {
		$entity = new Entity(['title' => 'Test Title']);

		$this->assertEquals('Test Title', $entity->label);
	}
}
