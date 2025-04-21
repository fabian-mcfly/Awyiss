<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function tearDownAfterClass(): void {
		$reflection = new ReflectionClass(BackendView::class);

		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);

		$property = $reflection->getProperty('twigInitialized');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(false);

		$lo_tableLocator = FactoryLocator::get('Table');
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_tableLocator->setTranslateLanguage(null);
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$lo_tableLocator = FactoryLocator::get('Table');
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_tableLocator->setTranslateLanguage(LocaleMiddleware::getLanguage());

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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->resetStaticProperties();

		FactoryLocator::get('Table')->clear();
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAllControlsThrowsExceptionWithoutFormContext(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form context set.');

		$this->helper->allControls('');
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAllControls(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('content');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('value="">', $result);
		$this->assertStringContainsString('<div class="FormInput', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAllControlsForEmptyFieldset(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('presentation');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('value="">', $result);
		$this->assertStringNotContainsString('<div class="FormInput', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAllControlsRendersOnlyProvided(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('content', ['free_text'], ['onlyProvided' => true]);

		$this->assertStringContainsString('<textarea name="attributes[free_text]"', $result);
		$this->assertStringNotContainsString('<select name="attributes[dropdown_select][]', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAllControlsNotContainsHiddenOnConsecutiveCalls(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->allControls('presentation');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('value="">', $result);

		$result = $this->helper->allControls('presentation');

		$this->assertStringNotContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringNotContainsString('value="">', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlThrowsExceptionWithoutFormContext(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No form context set.');

		$this->helper->control('testField');
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControl(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('News')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$result = $this->helper->control('date');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('<input type="date" name="attributes[date]"', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlNotContainsHiddenOnConsecutiveCalls(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('News')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$result = $this->helper->control('date');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('<input type="date" name="attributes[date]"', $result);

		$result = $this->helper->control('date');

		$this->assertStringNotContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('<input type="date" name="attributes[date]"', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlIncludesError(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('News')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$entity->attributes->setError('date', 'This is an error.');

		$result = $this->helper->control('date');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('<input type="date" name="attributes[date]"', $result);
		$this->assertStringContainsString('<div class="Error">This is an error.</div>', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlMarksRequiredField(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->control('free_text');

		$this->assertStringContainsString('FormInputName-Attributes-FreeText ColumnSpan-6', $result);
		$this->assertStringContainsString('<textarea name="attributes[free_text]"', $result);
		$this->assertStringNotContainsString('required', $result);

		$result = $this->helper->control('dropdown_select');

		$this->assertStringContainsString('<input type="hidden" name="attributes[dropdown_select]"', $result);
		$this->assertStringContainsString('FormInputName-Attributes-DropdownSelect Required ColumnSpan-6', $result);
		$this->assertStringContainsString('<select name="attributes[dropdown_select][]"', $result);
		$this->assertStringContainsString('required="', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlCreatesTranslatableWhenSet(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', true);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity, ['languageRealm' => Awyiss::REALM_BACKEND]);

		$result = $this->helper->control('free_text');

		$this->assertStringContainsString('FormInputName-Attributes-FreeText ColumnSpan-6', $result);
		$this->assertStringContainsString('<textarea name="attributes[free_text]"', $result);
		$this->assertStringNotContainsString('required', $result);

		$result = $this->helper->control('dropdown_select');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('<input type="hidden" name="attributes[_translations][de][dropdown_select]"', $result);
		$this->assertStringContainsString('FormInputType-TranslatableText FormInputName-Attributes-DropdownSelect Required ColumnSpan-6', $result);
		$this->assertStringContainsString('<select name="attributes[_translations][de][dropdown_select][]"', $result);
		$this->assertStringContainsString('required="', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlIncludesAttributeOptionsCollectionOptions(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->control('dropdown_select');

		$this->assertStringContainsString('<select name="attributes[dropdown_select][]"', $result);
		$this->assertStringContainsString('<option value="text" title="Text">Text</option>', $result);
		$this->assertStringContainsString('<option value="medium" title="Mittel">Mittel</option>', $result);
		$this->assertStringContainsString('<option value="main" title="Hauptfarbe">Hauptfarbe</option>', $result);
		$this->assertStringContainsString('<option value="contrast" title="Kontrastfarbe">Kontrastfarbe</option>', $result);
		$this->assertStringNotContainsString('<option value="dark" title="Dunkel">Dunkel</option>', $result);
		$this->assertStringNotContainsString('<option value="light" title="Hell">Hell</option>', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlPrioritizesProvidedOptions(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity);

		$result = $this->helper->control('dropdown_select', [
			'type' => 'select',
			'options' => [
				'val1' => 'Value 1',
				'val2' => 'Value 2',
			],
		]);

		$this->assertStringContainsString('<select name="attributes[dropdown_select]"', $result);
		$this->assertStringContainsString('<option value="val1" title="Value 1">Value 1</option>', $result);
		$this->assertStringContainsString('<option value="val2" title="Value 2">Value 2</option>', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlIncludesTimezoenForTranslatableDateTime(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', true);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->fetchTable('Cars')->newDefaultEntity();

		$this->helper->Form->create($entity, ['languageRealm' => Awyiss::REALM_BACKEND]);

		$result = $this->helper->control('dropdown_select', ['type' => 'datetime', 'options' => false]);

		$matches = [];
		preg_match_all('/<span class="Timezone">UTC<\/span>/', $result, $matches);

		$this->assertCount(2, $matches[0]);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlReturnsEmptyFieldWhenAttributeNotFound(): void {
		$entity = $this->fetchTable('Pages')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$result = $this->helper->control('nonExistentField');

		$this->assertStringContainsString('<input type="hidden" name="attributes"', $result);
		$this->assertStringContainsString('value="">', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlReturnsEmptyStringWhenAttributeNotFoundOnConsecutiveCalls(): void {
		$entity = $this->fetchTable('Pages')->newDefaultEntity();
		$this->helper->Form->create($entity);

		$result = $this->helper->control('nonExistentField');

		$this->assertNotEmpty($result);

		$result = $this->helper->control('nonExistentField');

		$this->assertEmpty($result);
	}


	/**
	 * Reset AttributesHelper::$attributes, AttributesHelper::$attributesByFieldset,
	 * AttributesHelper::$initiatedSources and AttributesHelper::$attributeOptions
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
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
