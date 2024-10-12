<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\CategoriesHelper;
use Awyiss\View\Widget\LinkSelectWidget;
use Cake\Collection\Collection;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\Form\EntityContext;
use Cake\View\Widget\WidgetInterface;
use Cake\View\Widget\WidgetLocator;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use TypeError;


/**
 * CategoriesHelperTest class
 */
class CategoriesHelperTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;
	/**
	 * @var \Awyiss\View\Helper\CategoriesHelper
	 */
	protected CategoriesHelper $helper;


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
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);

		$this->loadRoutes();

		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
			],
		]);

		Router::setRequest($request);

		$this->view = new BackendView($request);

		$this->helper = new CategoriesHelper($this->view);

		$entity = $this->fetchTable('Media')->get(2);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->view->helpers()->get('Form')->context($context);
	}


	/**
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testContructorSetsWidgetLocatorAndWidgets(): void {
		$widgetLocator = $this->helper->getWidgetLocator();

		$this->assertInstanceOf(WidgetLocator::class, $widgetLocator);

		$this->assertInstanceOf(LinkSelectWidget::class, $widgetLocator->get('linkSelect'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetWidgetLocator(): void {
		$widgetLocator = $this->createMock(WidgetLocator::class);

		$this->helper->setWidgetLocator($widgetLocator);

		$this->assertSame($widgetLocator, $this->helper->getWidgetLocator());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetWidgetLocatorWithNull(): void {
		$this->expectException(TypeError::class);
		/** @noinspection PhpParamsInspection */
		$this->helper->setWidgetLocator(null);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidget(): void {
		$widget = $this->createMock(WidgetInterface::class);
		$widget->expects($this->once())->method('render')->willReturn('rendered widget');

		$widgetLocator = $this->createMock(WidgetLocator::class);
		$widgetLocator->expects($this->once())->method('get')->willReturn($widget);

		$this->helper->setWidgetLocator($widgetLocator);

		$result = $this->helper->widget('testWidget', ['data' => 'value']);

		$this->assertEquals('rendered widget', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidgetWithInvalidName(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->helper->widget('invalidWidget');
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlReturnsEmptyStringWithoutConfig(): void {
		$result = $this->helper->control('media_folders');

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlThrowsExceptionWithoutField(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true]]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot build categories control without field.');

		$this->helper->control('media_folders');
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithEnabledConfig(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder_id']]);

		$result = $this->helper->control('media_folders');

		// Check if the select element is present and has the correct name
		$this->assertMatchesRegularExpression('/<select[^>]*\sname="media_folder_id"/', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithDisabledConfig(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => false]]);

		$result = $this->helper->control('media_folders');

		$this->assertEquals('', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \Exception
	 */
	public function testControlWithProvidedOptions(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', ['options' => ['val1' => 'option1', 'val2' => 'option2']]);

		$this->assertStringContainsString('<option value="val1">option1</option>', $result);
		$this->assertStringContainsString('<option value="val2">option2</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithEmptyOptions(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', ['options' => []]);

		$this->assertStringContainsString('<select', $result);
		$this->assertStringNotContainsString('<option', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithCollectionAsOptions(): void {
		$this->view->set('_categories.mediaFolders', [
			'config' => [
				'enabled' => true,
				'field' => 'media_folder',
			],
		]);

		$options = new Collection([
			['id' => 1, 'title' => 'Child 1'],
			['id' => 2, 'title' => 'Child 2'],
			['id' => 3, 'title' => 'Child 3'],
			['id' => 4, 'title' => 'Child 4'],
			['id' => 10, 'title' => 'Child 10'],
		]);

		$result = $this->helper->control('media_folders', ['options' => $options]);

		$this->assertStringContainsString('<option value="1">Child 1</option>', $result);
		$this->assertStringContainsString('<option value="10">Child 10</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithCollectionAsOptionsAndCombinatorSet(): void {
		$this->view->set('_categories.mediaFolders', [
			'config' => [
				'enabled' => true,
				'field' => 'media_folder',
			],
		]);

		$options = new Collection([
			['id' => 1, 'external_id' => 'foobar1', 'title' => 'Child 1'],
			['id' => 2, 'external_id' => 'foobar2', 'title' => 'Child 2'],
			['id' => 3, 'external_id' => 'foobar3', 'title' => 'Child 3'],
			['id' => 4, 'external_id' => 'foobar4', 'title' => 'Child 4'],
			['id' => 10, 'external_id' => 'foobar10', 'title' => 'Child 10'],
		]);

		$result = $this->helper->control('media_folders', [
			'combinator' => [
				'external_id',
				'title',
			],
			'options' => $options,
		]);

		$this->assertStringContainsString('<option value="foobar1">Child 1</option>', $result);
		$this->assertStringContainsString('<option value="foobar10">Child 10</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithGroupingEnabled(): void {
		$this->view->set('_categories.mediaFolders', ['config' => [
			'enabled' => true,
			'field' => 'media_folder',
		]]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 6, 'title' => 'Child 3', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
			['id' => 9, 'title' => 'Child 4', '_group' => 8],
			['id' => 10, 'title' => 'Child 5', '_group' => 8],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => 9],
		]);

		$result = $this->helper->control('media_folders', [
			'options' => $options,
			'groupBy' => '_group',
			'groupLabels' => [
				1 => 'Parent 1',
				2 => 'Parent 2',
				3 => 'Parent 3',
				4 => 'Parent 4',
				5 => 'Parent 5',
				6 => 'Parent 6',
				7 => 'Parent 7',
				8 => 'Parent 8',
				9 => 'Parent 9',
			],
		]);

		$this->assertStringContainsString('<optgroup label="Parent 1">', $result);
		$this->assertStringNotContainsString('<optgroup label="Parent 2">', $result);
		$this->assertStringContainsString('<optgroup label="Parent 3">', $result);
		$this->assertStringContainsString('<optgroup label="Parent 4">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithGroupingDisabledOverridesIncludeParentCategories(): void {
		$this->view->set('_categories.mediaFolders', ['config' => [
			'enabled' => true,
			'field' => 'media_folder',
			'includeParentCategories' => true,
		]]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 6, 'title' => 'Child 3', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
			['id' => 9, 'title' => 'Child 4', '_group' => 8],
			['id' => 10, 'title' => 'Child 5', '_group' => 8],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => 9],
		]);

		$result = $this->helper->control('media_folders', [
			'options' => $options,
			'groupBy' => false,
		]);

		$this->assertStringNotContainsString('<optgroup', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithGroupingEnabledWithoutGroupLabels(): void {
		$this->view->set('_categories.mediaFolders', ['config' => [
			'enabled' => true,
			'field' => 'media_folder',
		]]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 6, 'title' => 'Child 3', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
			['id' => 9, 'title' => 'Child 4', '_group' => 8],
			['id' => 10, 'title' => 'Child 5', '_group' => 8],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => 9],
		]);

		$result = $this->helper->control('media_folders', [
			'options' => $options,
			'groupBy' => '_group',
		]);

		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_1">', $result);
		$this->assertStringNotContainsString('<optgroup label="the_controller::media_folder_grouplabel_2">', $result);
		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_3">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithGroupingEnabledAndGroupingValueIsArray(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder', 'includeParentCategories' => true]]);

		$options = new Collection([
			['id' => 1, 'title' => 'Parent 1', '_group' => []],
			['id' => 2, 'title' => 'Child 1', '_group' => [1]],
			['id' => 3, 'title' => 'Parent 2', '_group' => []],
			['id' => 4, 'title' => 'Child 2', '_group' => [3]],
			['id' => 5, 'title' => 'Parent 3', '_group' => []],
			['id' => 6, 'title' => 'Child 3', '_group' => [5]],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => [1, 2]], // Multiple parents
			['id' => 8, 'title' => 'Parent 4', '_group' => []],
			['id' => 9, 'title' => 'Child 4', '_group' => [8]],
			['id' => 10, 'title' => 'Child 5', '_group' => [8]],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => [8, 9]], // Multiple parents
		]);

		$result = $this->helper->control('media_folders', ['options' => $options, 'groupBy' => '_group']);

		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_general">', $result);
		$this->assertStringContainsString('<optgroup label="1 - 2">', $result);
		$this->assertStringContainsString('<optgroup label="8 - 9">', $result);

		$options = new Collection([
			new MediaFolder(['id' => 1, 'title' => 'Parent 1', '_parents' => null]),
			new MediaFolder(['id' => 2, 'title' => 'Child 1', '_parents' => [1]]),
			new MediaFolder(['id' => 3, 'title' => 'Parent 2', '_parents' => null]),
			new MediaFolder(['id' => 4, 'title' => 'Child 2', '_parents' => [3]]),
			new MediaFolder(['id' => 5, 'title' => 'Child 3', '_parents' => [3]]),
			new MediaFolder(['id' => 6, 'title' => 'Grandchild 1', '_parents' => [5, 3]]),
		]);

		$result = $this->helper->control('media_folders', ['options' => $options]);

		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_general">', $result);
		$this->assertStringContainsString('<optgroup label="5 - 3">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithGroupingEnabledAndGroupingValueIsArrayThrowsExceptionWhenGroupingValueNotScalarAndNotObject(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder', 'includeParentCategories' => true]]);

		$options = new Collection([
			new MediaFolder(['id' => 1, 'title' => 'Parent 1', '_parents' => null]),
			new MediaFolder(['id' => 2, 'title' => 'Child 1', '_parents' => [new DateTime()]]),
			new MediaFolder(['id' => 3, 'title' => 'Parent 2', '_parents' => null]),
			new MediaFolder(['id' => 4, 'title' => 'Child 2', '_parents' => [3]]),
			new MediaFolder(['id' => 5, 'title' => 'Child 3', '_parents' => [3]]),
			new MediaFolder(['id' => 6, 'title' => 'Grandchild 1', '_parents' => [5, 3]]),
		]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot group by non-scalars or non-entities.');

		$this->helper->control('media_folders', ['options' => $options]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithGroupingDisabled(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$options = new Collection([
			['id' => 1, 'title' => 'Parent 1', '_group' => []],
			['id' => 2, 'title' => 'Child 1', '_group' => [1]],
			['id' => 3, 'title' => 'Parent 2', '_group' => []],
			['id' => 4, 'title' => 'Child 2', '_group' => [3]],
			['id' => 5, 'title' => 'Parent 3', '_group' => []],
			['id' => 6, 'title' => 'Child 3', '_group' => [5]],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => [1, 2]], // Multiple parents
			['id' => 8, 'title' => 'Parent 4', '_group' => []],
			['id' => 9, 'title' => 'Child 4', '_group' => [8]],
			['id' => 10, 'title' => 'Child 5', '_group' => [8]],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => [8, 9]], // Multiple parents
		]);

		$result = $this->helper->control('media_folders', ['groupBy' => false, 'options' => $options]);

		$this->assertStringNotContainsString('<optgroup', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithValueSet(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
			'val' => 'value2',
		]);

		$this->assertStringContainsString('value="value2" selected="selected"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithValueSetInViewVars(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder'], 'selected' => 'value2']);

		$result = $this->helper->control('media_folders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
		]);

		$this->assertStringContainsString('value="value2" selected="selected"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithValueNotSet(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
		]);

		$this->assertStringNotContainsString('selected="selected"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithDifferentFieldName(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'different_field']]);

		$result = $this->helper->control('different_field');

		$this->assertSame('', $result);

		$result = $this->helper->control('media_folders');

		$this->assertStringContainsString('name="different_field"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithEmptyOption(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
			'empty' => true,
		]);

		$this->assertStringContainsString('<option value=""></option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithProvidedOptionsWithDisabledConfigTrue(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', ['options' => ['val1' => 'option1', 'val2' => 'option2'], 'disabled' => true]);

		$this->assertStringContainsString('<select name="media_folder" disabled="disabled"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlWithProvidedOptionsWithDisabledConfigArray(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'field' => 'media_folder']]);

		$result = $this->helper->control('media_folders', ['options' => ['val1' => 'option1', 'val2' => 'option2'], 'disabled' => ['val2']]);

		$this->assertStringContainsString('<option value="val1">option1</option>', $result);
		$this->assertStringContainsString('<option value="val2" disabled="disabled">option2</option>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilter(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['field' => 'media_folder']]);

		$result = $this->helper->filter('media_folders');

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithEnabledConfig(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true]]);

		$result = $this->helper->filter('media_folders');

		$this->assertStringContainsString('<div class="LinkSelect LinkSelect-MediaFolders"', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithDisabledConfig(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => false]]);

		$result = $this->helper->filter('media_folders');

		$this->assertEquals('', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithProvidedOptions(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true]]);

		$result = $this->helper->filter('media_folders', [5 => 'option1', 11 => 'option2']);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:5/" title="option1">option1</a>', $result);
		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:11/" title="option2">option2</a>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithUriParam(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true, 'uriParam' => 'mediaFolderId']]);

		$result = $this->helper->filter('media_folders', [5 => 'option1', 11 => 'option2']);


		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folder-id:5/" title="option1">option1</a>', $result);
		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folder-id:11/" title="option2">option2</a>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithCollectionAsOptions(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true]]);

		$options = new Collection([
			['id' => 1, 'title' => 'Option 1'],
			['id' => 2, 'title' => 'Option 2'],
			['id' => 3, 'title' => 'Option 3'],
			['id' => 4, 'title' => 'Option 4'],
		]);

		$result = $this->helper->filter('media_folders', $options);

		$this->assertStringContainsString('<li class="Item Item-Option1"><a href="/backend/xy/the-controller/the-action/media-folders:1/" title="Option 1">Option 1</a></li>', $result);
		$this->assertStringContainsString('<li class="Item Item-Option4"><a href="/backend/xy/the-controller/the-action/media-folders:4/" title="Option 4">Option 4</a></li>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithGroupingEnabled(): void {
		$this->view->set('_categories.mediaFolders', [
			'config' => [
				'enabled' => true,
				'field' => 'media_folder',
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 5, 'title' => 'Child 3', '_group' => 3],
			['id' => 6, 'title' => 'Child 4', '_group' => [5, 3]],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
		]);

		$result = $this->helper->filter('media_folders', $options, [
			'groupBy' => '_group',
			'groupLabels' => [
				1 => 'Parent 1',
				2 => 'Parent 2',
				3 => 'Parent 3',
				4 => 'Parent 4',
				5 => 'Parent 5',
			],
		]);

		$this->assertStringContainsString('<li class="Item Item-Parent1 GroupLabel GroupLabelItem-Parent1" title="Parent 1"><strong>Parent 1</strong></li>', $result);
		$this->assertStringContainsString('<li id="LinkSelect-MediaFoldersItem-2" class="Item Item-Child1 Item-2 IsGrouped"><a href="" title="Child 1">Child 1</a>', $result);
		$this->assertStringNotContainsString('Parent 2', $result);
		$this->assertStringContainsString(
			'<li class="Item Item-Parent3 GroupLabel GroupLabelItem-Parent3" title="Parent 3"><strong>Parent 3</strong></li>' .
			'<li id="LinkSelect-MediaFoldersItem-4" class="Item Item-Child2 Item-4 IsGrouped"><a href="" title="Child 2">Child 2</a></li>' .
			'<li id="LinkSelect-MediaFoldersItem-5" class="Item Item-Child3 Item-5 IsGrouped"><a href="" title="Child 3">Child 3</a></li>',
			$result
		);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testFilterWithGroupingDisabledOverridesIncludeParentCategories(): void {
		$this->view->set('_categories.mediaFolders', [
			'config' => [
				'enabled' => true,
				'field' => 'media_folder',
				'includeParentCategories' => true,
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 5, 'title' => 'Child 3', '_group' => 3],
			['id' => 6, 'title' => 'Child 4', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
		]);

		$result = $this->helper->filter('media_folders', $options, ['groupBy' => false]);

		$this->assertStringNotContainsString('GroupLabel', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testFilterWithGroupingEnabledWithoutGroupLabels(): void {
		$this->view->set('_categories.mediaFolders', [
			'config' => [
				'enabled' => true,
				'field' => 'media_folder',
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 5, 'title' => 'Child 3', '_group' => 3],
			['id' => 6, 'title' => 'Child 4', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
		]);

		$result = $this->helper->filter('media_folders', $options, [
			'groupBy' => '_group',
		]);

		$this->assertStringContainsString('the_controller::media_folders_grouplabel_1', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithAggregationEnabled(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true]]);

		$result = $this->helper->filter('media_folders', [], [
			'allowAggregation' => true,
			'aggregationLabel' => 'All Media Folders',
			'aggregationKey' => 'all_folders',
		]);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:all-folders/" title="All Media Folders">', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFilterWithUnassignedEnabled(): void {
		$this->view->set('_categories.mediaFolders', ['config' => ['enabled' => true]]);

		$result = $this->helper->filter('media_folders', [], [
			'allowUnassigned' => true,
			'unassignedLabel' => 'Unassigned Folders',
			'unassignedKey' => 'unassigned_folders',
		]);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:unassigned-folders/" title="Unassigned Folders">', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelect(): void {
		$result = $this->helper->linkSelect('media_folders');

		$this->assertStringContainsString('<div class="LinkSelect LinkSelect-MediaFolders" id="LinkSelect-MediaFolders">', $result);
		$this->assertStringContainsString('<ul class="List"></ul>', $result);
		$this->assertStringNotContainsString('<li', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLinkSelectWithDisabledConfigWithOutOptionsNotContainsDisabled(): void {
		$result = $this->helper->linkSelect('media_folders', [], ['disabled' => true]);

		$this->assertStringNotContainsString('disabled', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithProvidedOptions(): void {
		$result = $this->helper->linkSelect('media_folders', ['option1', 'option2']);

		$this->assertStringContainsString(
			'<li class="Item Item-Option1"><a href="/backend/xy/the-controller/the-action/media-folders:0/" title="option1">option1</a></li>',
			$result
		);
		$this->assertStringContainsString(
			'<li class="Item Item-Option2"><a href="/backend/xy/the-controller/the-action/media-folders:1/" title="option2">option2</a></li>',
			$result
		);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLinkSelectWithProvidedOptionsWithDisabledConfigTrue(): void {
		$result = $this->helper->linkSelect('media_folders', ['option1', 'option2'], ['disabled' => true]);

		$this->assertStringContainsString('<li class="Item Item-Option1 Disabled">option1</li>', $result);
		$this->assertStringContainsString('<li class="Item Item-Option2 Disabled">option2</li>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLinkSelectWithProvidedOptionsWithDisabledConfigArray(): void {
		$result = $this->helper->linkSelect('media_folders', ['option1', 'option2'], ['disabled' => [1]]);

		$this->assertStringContainsString(
			'<li class="Item Item-Option1"><a href="/backend/xy/the-controller/the-action/media-folders:0/" title="option1">option1</a></li>',
			$result
		);
		$this->assertStringContainsString('<li class="Item Item-Option2 Disabled">option2</li>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLinkSelectWithCollectionAsOptions(): void {
		$options = new Collection([
			['id' => 1, 'label' => 'Option 1'],
			['id' => 2, 'label' => 'Option 2'],
		]);

		$result = $this->helper->linkSelect('media_folders', $options);

		$this->assertStringContainsString('<li class="Item Item-Option1"><a href="/backend/xy/the-controller/the-action/media-folders:1/" title="Option 1">', $result);
		$this->assertStringContainsString('<li class="Item Item-Option2"><a href="/backend/xy/the-controller/the-action/media-folders:2/" title="Option 2">', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithGroupingEnabled(): void {
		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 3, 'title' => 'Child 2', '_group' => 1],
		]);

		$result = $this->helper->linkSelect('media_folders', $options, [
			'groupBy' => '_group',
			'groupLabels' => [
				1 => 'Parent 1',
			],
		]);

		$this->assertStringContainsString('<li class="Item Item-Parent1 GroupLabel GroupLabelItem-Parent1" title="Parent 1"><strong>Parent 1</strong></li>', $result);
		$this->assertStringContainsString('<li id="LinkSelect-MediaFoldersItem-2" class="Item Item-Child1 Item-2 IsGrouped"><a href="" title="Child 1">Child 1</a></li>', $result);
		$this->assertStringContainsString('<li id="LinkSelect-MediaFoldersItem-3" class="Item Item-Child2 Item-3 IsGrouped"><a href="" title="Child 2">Child 2</a></li>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithAggregationEnabled(): void {
		$result = $this->helper->linkSelect('media_folders', [], [
			'allowAggregation' => true,
			'aggregationLabel' => 'All Media Folders',
			'aggregationKey' => 'all_folders',
		]);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:all-folders/" title="All Media Folders">All Media Folders</a>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithUnassignedEnabled(): void {
		$result = $this->helper->linkSelect('media_folders', [], [
			'allowUnassigned' => true,
			'unassignedLabel' => 'Unassigned Folders',
			'unassignedKey' => 'unassigned_folders',
		]);

		$this->assertStringContainsString(
			'<a href="/backend/xy/the-controller/the-action/media-folders:unassigned-folders/" title="Unassigned Folders">Unassigned Folders</a>',
			$result
		);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetConfiguration(): void {
		$config = [
			'enabled' => true,
			'field' => 'media_folder',
			'includeParentCategories' => true,
			'groupBy' => 'group',
		];

		$this->view->set('_categories.mediaFolders', ['config' => $config]);

		$result = $this->helper->getConfiguration('mediaFolders');

		$this->assertEquals($config, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetConfigurationWithEmptyConfig(): void {
		$this->view->set('_categories.mediaFolders', []);

		$result = $this->helper->getConfiguration('mediaFolders');

		$this->assertEquals([], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetCategories(): void {
		$this->view->set('_categories.mediaFolders', ['raw' => ['category1', 'category2'], 'simple' => ['category3']]);

		$result = $this->helper->getCategories('mediaFolders', true);

		$this->assertEquals(['category1', 'category2'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetCategoriesWithSimple(): void {
		$this->view->set('_categories.mediaFolders', ['simple' => ['category3']]);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->helper->getCategories('mediaFolders', false);

		$this->assertEquals(['category3'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSelectedCategory(): void {
		$this->view->set('_categories.mediaFolders', ['selected' => 'category1']);

		$result = $this->helper->getSelectedCategory('mediaFolders');

		$this->assertEquals('category1', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSelectedCategoryWithNull(): void {
		$this->view->set('_categories.mediaFolders', []);

		$result = $this->helper->getSelectedCategory('mediaFolders');

		$this->assertNull($result);
	}
}
