<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Model\Behavior\Translate\EavStrategy;
use Awyiss\Model\Behavior\TranslateBehavior;
use Awyiss\Model\Table\ContentTemplatesTable;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;


/**
 * TranslateBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\TranslateBehavior
 */
class TranslateBehaviorTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Behavior\TranslateBehavior
	 */
	protected TranslateBehavior $behavior;
	/**
	 * @var \Cake\ORM\Table
	 */
	protected Table $table;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		// Use ContentTemplates table which has the translate behavior
		$this->table = $this->getTableLocator()->get('ContentTemplates');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('Translate');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::initialize()
	 * @throws \Exception
	 */
	public function testInitialize(): void {
		$this->assertInstanceOf(EavStrategy::class, $this->behavior->getStrategy());
		// Uses the locale of the first realm language (for tests: backend & german)
		$this->assertSame('de', $this->behavior->getStrategy()->getLocale());

		$this->assertSame('i18n', $this->behavior->getStrategy()->getTranslationTable()->getTable());

		$config = $this->behavior->getConfig();

		$this->assertFalse($config['allowEmptyTranslations']);
		$this->assertSame(['title'], $config['fields']);
		$this->assertSame(['translations' => 'findTranslations'], $config['implementedFinders']);
		$this->assertSame('subquery', $config['strategy']);
		$this->assertFalse($config['onlyTranslated']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::initialize()
	 * @throws \Exception
	 */
	public function testInitializeWithoutLocale(): void {
		$table = new ContentTemplatesTable();
		$behavior = $this->getMockBuilder(TranslateBehavior::class)->onlyMethods([])->disableOriginalConstructor()->getMock();

		$config = [
			'fields' => [
				'title',
				'content',
				'description',
				'dummy',
			],
			'allowEmptyTranslations' => false,
			'defaultLocale' => '',
			'locale' => '',
			'strategyClass' => EavStrategy::class,
			'referenceName' => 'content_templates',
			'tableLocator' => null,
		];

		$behavior->__construct($table, $config);

		$this->assertInstanceOf(EavStrategy::class, $behavior->getStrategy());
		// If locale is not set, it should use the default locale
		$this->assertSame('en_AG', $behavior->getStrategy()->getLocale());

		$this->assertSame('i18n', $behavior->getStrategy()->getTranslationTable()->getTable());

		$config = $behavior->getConfig();

		$this->assertFalse($config['allowEmptyTranslations']);
		$this->assertSame([
			'title',
			'content',
			'description',
			'dummy',
		], $config['fields']);
		$this->assertSame(['translations' => 'findTranslations'], $config['implementedFinders']);
		$this->assertSame('subquery', $config['strategy']);
		$this->assertFalse($config['onlyTranslated']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::implementedEvents()
	 * @throws \Exception
	 */
	public function testImplementedEvents(): void {
		$events = $this->behavior->implementedEvents();

		$this->assertIsArray($events);
		$this->assertArrayHasKey('Model.beforeFind', $events);
		$this->assertArrayHasKey('Model.beforeMarshal', $events);
		$this->assertArrayHasKey('Model.beforeSave', $events);
		$this->assertArrayHasKey('Model.afterSave', $events);

		$this->assertSame('beforeFind', $events['Model.beforeFind']);
		$this->assertSame('beforeMarshal', $events['Model.beforeMarshal']);

		// Test that beforeSave has priority set
		$this->assertIsArray($events['Model.beforeSave']);
		$this->assertSame('beforeSave', $events['Model.beforeSave']['callable']);
		$this->assertSame(100, $events['Model.beforeSave']['priority']);

		$this->assertSame('afterSave', $events['Model.afterSave']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::beforeMarshal()
	 * @throws \Exception
	 */
	public function testBeforeMarshalWithoutTranslations(): void {
		$this->behavior->setConfig('fields', ['title', 'content']);

		$data = new ArrayObject(['title' => 'Test Title', 'content' => 'Test Content']);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal', $this->table);

		$this->behavior->beforeMarshal($event, $data, $options);

		// Data should remain unchanged when no translations are present
		$this->assertSame('Test Title', $data['title']);
		$this->assertSame('Test Content', $data['content']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::beforeMarshal()
	 * @throws \Exception
	 */
	public function testBeforeMarshalWithTranslations(): void {
		$this->behavior->setConfig('fields', ['title', 'content']);

		$data = new ArrayObject([
			'title' => 'Base Title',
			'content' => 'Base Content',
			'dummy' => 'Base Dummy',
			'_translations' => [
				'de' => [
					'title' => 'German Title',
					'content' => 'German Content',
					'dummy' => 'German Dummy',
				],
				'en' => [
					'title' => 'English Title',
					'content' => 'English Content',
					'dummy' => 'English Dummy',
				],
			],
		]);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal', $this->table);

		$this->behavior->beforeMarshal($event, $data, $options);

		// Should use the first language (de) as default for the main entity
		$this->assertSame('German Title', $data['title']);
		$this->assertSame('German Content', $data['content']);
		// Dummy field should remain as is
		$this->assertSame('Base Dummy', $data['dummy']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::beforeMarshal()
	 * @throws \Exception
	 */
	public function testBeforeMarshalWithForcedFields(): void {
		$this->behavior->setConfig([
			'fields' => ['title', 'content', 'dummy'],
		]);

		$request = new ServerRequest([
			'url' => '/es/dummy',
			'params' => [
				'lang' => 'es',
				'controller' => 'Dummy',
				'action' => 'overview',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		Router::setRequest($request);
		$this->behavior->setConfig('realm', Awyiss::REALM_FRONTEND);

		$data = new ArrayObject([
			'title' => 'Base Title',
			'content' => 'Base Content',
			'dummy' => 'Base Dummy',
			'_translations' => [
				'de' => [
					'title' => '',
					'content' => '',
					'dummy' => 'German Dummy',
				],
				'es' => [
					'title' => 'Spanish Title',
					'content' => 'Spanish Content',
					'dummy' => 'Spanish Dummy',
				],
			],
		]);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal', $this->table);

		$this->behavior->beforeMarshal($event, $data, $options);

		// Should use Spanish title because German title is empty and title is a forced field
		$this->assertSame('Spanish Title', $data['title']);
		// Should use German content because it is not a forced field, but always null for ''
		$this->assertNull($data['content']);
		// Dummy field should remain as is
		$this->assertSame('German Dummy', $data['dummy']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::beforeMarshal()
	 * @throws \Exception
	 */
	public function testBeforeMarshalWithEmptyStringValues(): void {
		$this->behavior->setConfig('fields', ['title', 'content']);

		$data = new ArrayObject([
			'title' => 'Base Title',
			'content' => 'Base Content',
			'_translations' => [
				'de' => [
					'title' => '',
					'content' => 'German Content',
				],
				'es' => [
					'title' => 'Spanish Title',
					'content' => '',
				],
			],
		]);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal', $this->table);

		$this->behavior->beforeMarshal($event, $data, $options);

		// Empty strings should be converted to null
		$this->assertNull($data['title']);
		$this->assertNull($data['_translations']['de']['title']);
		$this->assertSame('German Content', $data['content']);
		$this->assertSame('German Content', $data['_translations']['de']['content']);

		// Spanish translation should be preserved
		$this->assertSame('Spanish Title', $data['_translations']['es']['title']);
		$this->assertSame('', $data['_translations']['es']['content']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\TranslateBehavior::beforeMarshal()
	 * @throws \Exception
	 */
	public function testBeforeMarshalWithoutFields(): void {
		$this->behavior->setConfig('fields', [], false);
		$this->behavior->setConfig('forcedFields', [], false);

		$data = new ArrayObject([
			'title' => 'Base Title',
			'content' => 'Base Content',
			'_translations' => [
				'de' => [
					'title' => 'German Title',
					'content' => 'German Content',
				],
				'es' => [
					'title' => 'Spanish Title',
					'content' => 'Spanish Content',
				],
			],
		]);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal', $this->table);

		$this->behavior->beforeMarshal($event, $data, $options);

		// No fields should be set, data should remain unchanged
		$this->assertSame('Base Title', $data['title']);
		$this->assertSame('Base Content', $data['content']);
	}
}
