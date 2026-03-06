<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Table;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Customer\Model\Entity\AttributesContent;
use Customer\Model\Entity\Employer;
use RuntimeException;


/**
 * DefaultValuesBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior
 */
class DefaultValuesBehaviorTest extends TestCase {
	/**
	 * @return void
	 */
	public function testInitialization(): void {
		$table = new Table([
			'table' => 'employers',
			'alias' => 'DummyEmployers',
			'registryAlias' => 'DummyEmployers',
		]);
		$behavior = $table->getBehavior('DefaultValues');
		$config = $behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['beforeMarshal'], $config['implementedEvents']);
		$this->assertSame(['newDefaultEntity' => 'newDefaultEntity'], $config['implementedMethods']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Customer\Model\Table\EmployersTable $table */
		$table = $this->fetchTable('Employers');
		$entity = $table->newDefaultEntity();

		$entity->setVirtual([]);

		$this->assertInstanceOf(Employer::class, $entity);
		$this->assertSame([
			'parentId' => null,
			'languageShortcode' => null,
			'title' => null,
			'systemOrder' => 0,
			'active' => true,
			'deleted' => false,
			'createdBy' => null,
			'createdOn' => null,
			'changedBy' => null,
			'changedOn' => null,
			'deletedBy' => null,
			'deletedOn' => null,
		], $entity->toArray());

		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = $this->fetchTable('Contents');
		$entity = $table->newDefaultEntity();

		$entity->setVirtual([]);
		$this->assertTrue($entity->has('attributes'));
		$this->assertInstanceOf(AttributesContent::class, $entity->attributes);

		$entity->attributes->setVirtual([]);

		$this->assertInstanceOf(Content::class, $entity);
		$this->assertEquals([
			'parentId' => null,
			'title' => null,
			'titleTag' => null,
			'subtitle' => null,
			'subtitleTag' => null,
			'text' => null,
			'link' => null,
			'columnWidth' => '1/1',
			'columnIndent' => null,
			'columnLast' => false,
			'columnRtl' => false,
			'cssClass' => null,
			'css' => null,
			'duplicateOf' => null,
			'data' => null,
			'formId' => null,
			'surveyId' => null,
			'systemOrder' => 0,
			'active' => true,
			'deleted' => false,
			'createdBy' => null,
			'createdOn' => null,
			'changedBy' => null,
			'changedOn' => null,
			'deletedBy' => null,
			'deletedOn' => null,
			'pageId' => null,
			'contentAreaId' => null,
			'contentTemplateId' => null,
			'attributes' => [
				'teaser' => null,
				'freeText' => null,
				'backgroundColor' => null,
				'contentId' => null,
			],
		], $entity->toArray());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithAdditionalData(): void {
		$additionalData = [
			'title' => 'Test Entity',
			'systemOrder' => 5,
			'columnWidth' => '2/3',
		];

		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = $this->fetchTable('Contents');
		$entity = $table->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Content::class, $entity);
		$this->assertSame('Test Entity', $entity->title);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame('2/3', $entity->columnWidth);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithAttribute(): void {
		$additionalData = [
			'backgroundColor' => '#FF00FF',
		];

		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = $this->fetchTable('Contents');
		$entity = $table->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Content::class, $entity);

		$this->assertFalse($entity->has('backgroundColor'));
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->assertSame('#FF00FF', $entity->attributes->backgroundColor);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithOptions(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = $this->fetchTable('MenuEntries');

		$table->getBehavior('Categories')->setConfig('selectedCategory', 123);

		$entity = $table->newDefaultEntity([], ['includeCategory' => true]);
		$this->assertInstanceOf('Awyiss\Model\Entity\MenuEntry', $entity);
		$this->assertSame(123, $entity->menuId);

		$entity = $table->newDefaultEntity([], ['includeCategory' => false]);
		$this->assertInstanceOf('Awyiss\Model\Entity\MenuEntry', $entity);
		$this->assertNull($entity->menuId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntityWhenDisabled(): void {
		/** @var \Customer\Model\Table\EmployersTable $table */
		$table = $this->fetchTable('Employers');
		$behavior = $table->getBehavior('DefaultValues');

		$behavior->setConfig('enabled', false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The method `newDefaultEntity()` is not available since the `Awyiss\Model\Behavior\DefaultValuesBehavior` Behavior is not enabled');

		$table->newDefaultEntity();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithUnmappedFields(): void {
		$additionalData = [
			'title' => 'Test Entity',
			'systemOrder' => 5,
			'columnWidth' => '2/3',
		];

		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = $this->fetchTable('Contents');
		$entity = $table->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Content::class, $entity);
		$this->assertSame('Test Entity', $entity->title);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame('2/3', $entity->columnWidth);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntitySetsDefaultBooleanValues(): void {
		/** @var \Customer\Model\Table\NewsTable $table */
		$table = $this->fetchTable('News');
		$entity = $table->newDefaultEntity();

		$this->assertIsBool($entity->active);
		$this->assertIsBool($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::newDefaultEntity()
	 */
	public function testNewDefaultEntityTypeCastsDateValues(): void {
		/** @var \Customer\Model\Table\NewsTable $table */
		$table = $this->fetchTable('News');
		$entity = $table->newDefaultEntity([
			'createdOn' => '2023-01-01 12:00:00',
		]);

		$this->assertInstanceOf(DateTime::class, $entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::beforeMarshal()
	 */
	public function testBeforeMarshalUnsetsEmptyNullables(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = $this->fetchTable('Contents');
		$behavior = $table->getBehavior('DefaultValues');

		$data = new ArrayObject([
			'title' => 'Test Entity',
			'titleTag' => '',
			'formId' => '',
		]);
		$options = new ArrayObject([]);

		$event = new Event('Model.beforeMarshal', $table, [$data, $options]);
		$behavior->beforeMarshal($event, $data, $options);

		$data = $data->getArrayCopy();

		$this->assertSame('Test Entity', $data['title']);
		$this->assertNull($data['titleTag']);
		$this->assertNull($data['formId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::beforeMarshal()
	 */
	public function testBeforeMarshalWithNonNullableFields(): void {
		$table = $this->fetchTable('ContentTemplates');
		$behavior = $table->getBehavior('DefaultValues');

		$data = new ArrayObject([
			'title' => '',
		]);
		$options = new ArrayObject([]);

		$event = new Event('Model.beforeMarshal', $table, [$data, $options]);
		$behavior->beforeMarshal($event, $data, $options);

		$this->assertSame('', $data['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior::beforeMarshal()
	 */
	public function testBeforeMarshalWithNonEmptyValues(): void {
		$table = $this->fetchTable('ContentTemplates');
		$behavior = $table->getBehavior('DefaultValues');

		$data = new ArrayObject([
			'title' => 'NonEmpty',
		]);
		$options = new ArrayObject([]);

		$event = new Event('Model.beforeMarshal', $table, [$data, $options]);
		$behavior->beforeMarshal($event, $data, $options);

		$this->assertSame('NonEmpty', $data['title']);
	}
}
