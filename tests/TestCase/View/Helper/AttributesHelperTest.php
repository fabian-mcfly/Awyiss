<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\AttributesHelper;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;
use RuntimeException;


/**
 * AttributesHelperTest class
 */
class AttributesHelperTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;
	/**
	 * @var \Awyiss\View\Helper\AttributesHelper
	 */
	protected AttributesHelper $helper;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->loadRoutes();

		$request = new ServerRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		$this->login();

		$this->view = new BackendView($request);
		$this->helper = new AttributesHelper($this->view);
	}


	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->resetStaticProperties();

		FactoryLocator::get('Table')->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::allControls()
	 * @throws \ReflectionException|\Exception
	 */
	public function testAllControlsThrowsExceptionWithoutFormContext(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form context set.');

		$this->helper->allControls('');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::allControls()
	 * @throws \ReflectionException|\Exception
	 */
	public function testAllControls(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('content');

		$this->assertStringContainsString('value="">', $result);
		$this->assertStringContainsString('<div class="FormInput', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::allControls()
	 * @throws \ReflectionException|\Exception
	 */
	public function testAllControlsForEmptyFieldset(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('conditions');

		$this->assertStringNotContainsString('value="">', $result);
		$this->assertStringNotContainsString('<div class="FormInput', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::allControls()
	 * @throws \ReflectionException|\Exception
	 */
	public function testAllControlsRendersOnlyProvided(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('content', ['freeText'], ['onlyProvided' => true]);

		$this->assertStringContainsString('<textarea name="attributes[freeText]"', $result);
		$this->assertStringNotContainsString('<select name="attributes[dropdownSelect][]', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlThrowsExceptionWithoutFormContext(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form context set.');

		$this->helper->control('testField');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControl(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('News')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$result = $this->helper->control('date');

		$this->assertStringContainsString('<input type="date" name="attributes[date]"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlUsesEntityValue(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('News')->newDefaultEntity();
		$entity->attributes->date = '2021-01-01';
		$this->helper->Form->create($entity);

		$result = $this->helper->control('date');

		$this->assertStringContainsString('value="2021-01-01"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlIncludesError(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('News')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$entity->attributes->setError('date', 'This is an error.');

		$result = $this->helper->control('date');

		$this->assertStringContainsString('<input type="date" name="attributes[date]"', $result);
		$this->assertStringContainsString('<div class="Error">This is an error.</div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlMarksRequiredField(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->control('freeText');

		$this->assertStringContainsString('FormInputName-Attributes-FreeText ColumnSpan-6', $result);
		$this->assertStringContainsString('<textarea name="attributes[freeText]"', $result);
		$this->assertStringNotContainsString('required', $result);

		$result = $this->helper->control('dropdownSelect');

		$this->assertStringContainsString('<input type="hidden" name="attributes[dropdownSelect]"', $result);
		$this->assertStringContainsString('FormInputName-Attributes-DropdownSelect Required ColumnSpan-6', $result);
		$this->assertStringContainsString('<select name="attributes[dropdownSelect][]"', $result);
		$this->assertStringContainsString('required="', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlCreatesTranslatableWhenSet(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', true);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity, ['languageRealm' => Awyiss::REALM_BACKEND]);

		$result = $this->helper->control('freeText');

		$this->assertStringContainsString('FormInputName-Attributes-FreeText ColumnSpan-6', $result);
		$this->assertStringContainsString('<textarea name="attributes[freeText]"', $result);
		$this->assertStringNotContainsString('required', $result);

		$result = $this->helper->control('dropdownSelect');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('<input type="hidden" name="attributes[_translations][de][dropdownSelect]"', $result);
		$this->assertStringContainsString('FormInputType-TranslatableText FormInputName-Attributes-DropdownSelect Required ColumnSpan-6', $result);
		$this->assertStringContainsString('<select name="attributes[_translations][de][dropdownSelect][]"', $result);
		$this->assertStringContainsString('required="', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlIncludesAttributeOptionsCollectionOptions(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->control('dropdownSelect');

		$this->assertStringContainsString('<select name="attributes[dropdownSelect][]"', $result);
		$this->assertStringContainsString('<option value="text" title="Text">Text</option>', $result);
		$this->assertStringContainsString('<option value="medium" title="Mittel">Mittel</option>', $result);
		$this->assertStringContainsString('<option value="main" title="Hauptfarbe">Hauptfarbe</option>', $result);
		$this->assertStringContainsString('<option value="contrast" title="Kontrastfarbe">Kontrastfarbe</option>', $result);
		$this->assertStringNotContainsString('<option value="dark" title="Dunkel">Dunkel</option>', $result);
		$this->assertStringNotContainsString('<option value="light" title="Hell">Hell</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlPrioritizesProvidedOptions(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->control('dropdownSelect', [
			'type' => 'select',
			'options' => [
				'val1' => 'Value 1',
				'val2' => 'Value 2',
			],
		]);

		$this->assertStringContainsString('<select name="attributes[dropdownSelect]"', $result);
		$this->assertStringContainsString('<option value="val1" title="Value 1">Value 1</option>', $result);
		$this->assertStringContainsString('<option value="val2" title="Value 2">Value 2</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlIncludesTimezoenForTranslatableDateTime(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', true);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity, ['languageRealm' => Awyiss::REALM_BACKEND]);

		$result = $this->helper->control('dropdownSelect', ['type' => 'datetime', 'options' => false]);

		$matches = [];
		preg_match_all('/<span class="Timezone">UTC<\/span>/', $result, $matches);

		$this->assertCount(2, $matches[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AttributesHelper::control()
	 * @throws \ReflectionException
	 */
	public function testControlReturnsEmptyFieldWhenAttributeNotFound(): void {
		$entity = $this->fetchTable('Pages')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$result = $this->helper->control('nonExistentField');

		$this->assertEmpty($result);
	}


	/**
	 * Reset AttributesHelper::$attributes, AttributesHelper::$attributesByFieldset,
	 * AttributesHelper::$initiatedSources and AttributesHelper::$attributeOptions
	 *
	 * @return void
	 * @noinspection PhpExpressionResultUnusedInspection
	 */
	protected function resetStaticProperties(): void {
		$reflection = new ReflectionClass(AttributesHelper::class);

		$property = $reflection->getProperty('attributes');
		$property->setAccessible(true);
		$property->setValue($this->helper, null);

		$property = $reflection->getProperty('attributesByFieldset');
		$property->setAccessible(true);
		$property->setValue($this->helper, null);

		$property = $reflection->getProperty('initiatedSources');
		$property->setAccessible(true);
		$property->setValue($this->helper, []);

		$property = $reflection->getProperty('attributeOptions');
		$property->setAccessible(true);
		$property->setValue($this->helper, []);
	}
}
