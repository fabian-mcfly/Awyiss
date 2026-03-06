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
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\Form\EntityContext;
use Cake\View\Widget\WidgetInterface;
use Cake\View\Widget\WidgetLocator;
use InvalidArgumentException;
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
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

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
				'plugin' => null,
			],
		]);

		Router::setRequest($request);

		$this->view = new BackendView($request);

		$this->helper = new CategoriesHelper($this->view, [
			'templates' => 'form_templates_backend',
		]);

		$entity = $this->fetchTable('Media')->get(2);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->view->helpers()->get('Form')->context($context);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::__construct()
	 */
	public function testContructorSetsWidgetLocatorAndWidgets(): void {
		$widgetLocator = $this->helper->getWidgetLocator();

		$this->assertInstanceOf(LinkSelectWidget::class, $widgetLocator->get('linkSelect'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::setWidgetLocator()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSetWidgetLocator(): void {
		$widgetLocator = $this->createMock(WidgetLocator::class);

		$this->helper->setWidgetLocator($widgetLocator);

		$this->assertSame($widgetLocator, $this->helper->getWidgetLocator());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::setWidgetLocator()
	 */
	public function testSetWidgetLocatorWithNull(): void {
		$this->expectException(TypeError::class);
		/** @noinspection PhpParamsInspection */
		$this->helper->setWidgetLocator(null);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::widget()
	 * @throws \PHPUnit\Framework\MockObject\Exception
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::widget()
	 */
	public function testWidgetWithInvalidName(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->helper->widget('invalidWidget');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlReturnsEmptyStringWithoutConfig(): void {
		$result = $this->helper->control('mediaFolders');

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlThrowsExceptionWithoutField(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true]]]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot build categories control without field.');

		$this->helper->control('mediaFolders');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithEnabledConfig(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolderId']]]);

		$result = $this->helper->control('mediaFolders');

		// Check if the select element is present and has the correct name
		$this->assertMatchesRegularExpression('/<select[^>]*\sname="mediaFolderId"/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithDisabledConfig(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => false]]]);

		$result = $this->helper->control('mediaFolders');

		$this->assertEquals('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithProvidedOptions(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', ['options' => ['val1' => 'option1', 'val2' => 'option2']]);

		$this->assertStringContainsString('<option value="val1" title="option1">option1</option>', $result);
		$this->assertStringContainsString('<option value="val2" title="option2">option2</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithEmptyOptions(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', ['options' => []]);

		$this->assertStringContainsString('<select', $result);
		$this->assertStringNotContainsString('<option', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithCollectionAsOptions(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
				],
			],
		]);

		$options = new Collection([
			['id' => 1, 'title' => 'Child 1'],
			['id' => 2, 'title' => 'Child 2'],
			['id' => 3, 'title' => 'Child 3'],
			['id' => 4, 'title' => 'Child 4'],
			['id' => 10, 'title' => 'Child 10'],
		]);

		$result = $this->helper->control('mediaFolders', ['options' => $options]);

		$this->assertStringContainsString('<option value="1" title="Child 1">Child 1</option>', $result);
		$this->assertStringContainsString('<option value="10" title="Child 10">Child 10</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithCollectionAsOptionsAndCombinatorSet(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
				],
			],
		]);

		$options = new Collection([
			['id' => 1, 'externalId' => 'foobar1', 'title' => 'Child 1'],
			['id' => 2, 'externalId' => 'foobar2', 'title' => 'Child 2'],
			['id' => 3, 'externalId' => 'foobar3', 'title' => 'Child 3'],
			['id' => 4, 'externalId' => 'foobar4', 'title' => 'Child 4'],
			['id' => 10, 'externalId' => 'foobar10', 'title' => 'Child 10'],
		]);

		$result = $this->helper->control('mediaFolders', [
			'combinator' => [
				'externalId',
				'title',
			],
			'options' => $options,
		]);

		$this->assertStringContainsString('<option value="foobar1" title="Child 1">Child 1</option>', $result);
		$this->assertStringContainsString('<option value="foobar10" title="Child 10">Child 10</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithGroupingEnabled(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
				],
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 6, 'title' => 'Child 3', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
			['id' => 9, 'title' => 'Child 4', '_group' => 8],
			['id' => 10, 'title' => 'Child 5', '_group' => 8],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => 9],
		]);

		$result = $this->helper->control('mediaFolders', [
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

		$this->assertStringContainsString('<optgroup label="Parent 1"', $result);
		$this->assertStringNotContainsString('<optgroup label="Parent 2"', $result);
		$this->assertStringContainsString('<optgroup label="Parent 3"', $result);
		$this->assertStringContainsString('<optgroup label="Parent 4"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithGroupingDisabledOverridesIncludeParentCategories(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
					'includeParentCategories' => true,
				],
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 6, 'title' => 'Child 3', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
			['id' => 9, 'title' => 'Child 4', '_group' => 8],
			['id' => 10, 'title' => 'Child 5', '_group' => 8],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => 9],
		]);

		$result = $this->helper->control('mediaFolders', [
			'options' => $options,
			'groupBy' => false,
		]);

		$this->assertStringNotContainsString('<optgroup', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithGroupingEnabledWithoutGroupLabels(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
				],
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 6, 'title' => 'Child 3', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
			['id' => 9, 'title' => 'Child 4', '_group' => 8],
			['id' => 10, 'title' => 'Child 5', '_group' => 8],
			['id' => 11, 'title' => 'Grandchild 2', '_group' => 9],
		]);

		$result = $this->helper->control('mediaFolders', [
			'options' => $options,
			'groupBy' => '_group',
		]);

		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_1"', $result);
		$this->assertStringNotContainsString('<optgroup label="the_controller::media_folder_grouplabel_2"', $result);
		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_3"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithGroupingEnabledAndGroupingValueIsArray(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder', 'includeParentCategories' => true]]]);

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

		$result = $this->helper->control('mediaFolders', ['options' => $options, 'groupBy' => '_group']);

		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_general"', $result);
		$this->assertStringContainsString('<optgroup label="1 - 2"', $result);
		$this->assertStringContainsString('<optgroup label="8 - 9"', $result);

		$options = new Collection([
			new MediaFolder(['id' => 1, 'title' => 'Parent 1', '_parents' => null]),
			new MediaFolder(['id' => 2, 'title' => 'Child 1', '_parents' => [1]]),
			new MediaFolder(['id' => 3, 'title' => 'Parent 2', '_parents' => null]),
			new MediaFolder(['id' => 4, 'title' => 'Child 2', '_parents' => [3]]),
			new MediaFolder(['id' => 5, 'title' => 'Child 3', '_parents' => [3]]),
			new MediaFolder(['id' => 6, 'title' => 'Grandchild 1', '_parents' => [5, 3]]),
		]);

		$result = $this->helper->control('mediaFolders', ['options' => $options]);

		$this->assertStringContainsString('<optgroup label="the_controller::media_folder_grouplabel_general"', $result);
		$this->assertStringContainsString('<optgroup label="5 - 3"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithGroupingEnabledAndGroupingValueIsArrayThrowsExceptionWhenGroupingValueNotScalarAndNotObject(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder', 'includeParentCategories' => true]]]);

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

		$this->helper->control('mediaFolders', ['options' => $options]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithGroupingDisabled(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

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

		$result = $this->helper->control('mediaFolders', ['groupBy' => false, 'options' => $options]);

		$this->assertStringNotContainsString('<optgroup', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithValueSet(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
			'val' => 'value2',
		]);

		$this->assertStringContainsString('value="value2" title="Option 2" selected="selected"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithValueSetInViewVars(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder'], 'selected' => 'value2']]);

		$result = $this->helper->control('mediaFolders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
		]);

		$this->assertStringContainsString('value="value2" title="Option 2" selected="selected"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithValueNotSet(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', [
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithDifferentFieldName(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'different_field']]]);

		$result = $this->helper->control('differentField');

		$this->assertSame('', $result);

		$result = $this->helper->control('mediaFolders');

		$this->assertStringContainsString('name="differentField"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithEmptyOption(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', [
			'options' => [
				'value1' => 'Option 1',
				'value2' => 'Option 2',
				'value3' => 'Option 3',
			],
			'empty' => true,
		]);

		$this->assertStringContainsString('<option value="" title=""></option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithProvidedOptionsWithDisabledConfigTrue(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', ['options' => ['val1' => 'option1', 'val2' => 'option2'], 'disabled' => true]);

		$this->assertStringContainsString('<select name="mediaFolder" disabled="disabled"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithProvidedOptionsWithDisabledConfigArray(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'field' => 'mediaFolder']]]);

		$result = $this->helper->control('mediaFolders', ['options' => ['val1' => 'option1', 'val2' => 'option2'], 'disabled' => ['val2']]);

		$this->assertStringContainsString('<option value="val1" title="option1">option1</option>', $result);
		$this->assertStringContainsString('<option value="val2" title="option2" disabled="disabled">option2</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilter(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['field' => 'mediaFolder']]]);

		$result = $this->helper->filter('media_folders');

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithEnabledConfig(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true]]]);

		$result = $this->helper->filter('media_folders');

		$this->assertStringContainsString('<div class="LinkSelect LinkSelect-MediaFolders"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithDisabledConfig(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => false]]]);

		$result = $this->helper->filter('media_folders');

		$this->assertEquals('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithProvidedOptions(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true]]]);

		$result = $this->helper->filter('media_folders', [5 => 'option1', 11 => 'option2']);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:5/" title="option1">option1</a>', $result);
		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:11/" title="option2">option2</a>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithUriParam(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true, 'uriParam' => 'mediaFolderId']]]);

		$result = $this->helper->filter('media_folders', [5 => 'option1', 11 => 'option2']);


		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folder-id:5/" title="option1">option1</a>', $result);
		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folder-id:11/" title="option2">option2</a>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithCollectionAsOptions(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true]]]);

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
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithGroupingEnabled(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
				],
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithGroupingDisabledOverridesIncludeParentCategories(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
					'includeParentCategories' => true,
				],
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithGroupingEnabledWithoutGroupLabels(): void {
		$this->view->set('_categories', [
			'mediaFolders' => [
				'config' => [
					'enabled' => true,
					'field' => 'mediaFolder',
				],
			],
		]);

		$options = new Collection([
			['id' => 2, 'title' => 'Child 1', '_group' => 1],
			['id' => 4, 'title' => 'Child 2', '_group' => 3],
			['id' => 5, 'title' => 'Child 3', '_group' => 3],
			['id' => 6, 'title' => 'Child 4', '_group' => 5],
			['id' => 7, 'title' => 'Grandchild 1', '_group' => 4],
		]);

		$result = $this->helper->filter('mediaFolders', $options, [
			'groupBy' => '_group',
		]);

		$this->assertStringContainsString('the_controller::media_folders_grouplabel_1', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithAggregationEnabled(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true]]]);

		$result = $this->helper->filter('media_folders', [], [
			'allowAggregation' => true,
			'aggregationLabel' => 'All Media Folders',
			'aggregationKey' => 'all_folders',
		]);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:all-folders/" title="All Media Folders">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::filter()
	 */
	public function testFilterWithUnassignedEnabled(): void {
		$this->view->set('_categories', ['mediaFolders' => ['config' => ['enabled' => true]]]);

		$result = $this->helper->filter('media_folders', [], [
			'allowUnassigned' => true,
			'unassignedLabel' => 'Unassigned Folders',
			'unassignedKey' => 'unassigned_folders',
		]);

		$this->assertStringContainsString('<a href="/backend/xy/the-controller/the-action/media-folders:unassigned-folders/" title="Unassigned Folders">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
	 */
	public function testLinkSelect(): void {
		$result = $this->helper->linkSelect('media_folders');

		$this->assertStringContainsString('<div class="LinkSelect LinkSelect-MediaFolders" id="LinkSelect-MediaFolders">', $result);
		$this->assertStringContainsString('<ul class="List"></ul>', $result);
		$this->assertStringNotContainsString('<li', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
	 */
	public function testLinkSelectWithDisabledConfigWithOutOptionsNotContainsDisabled(): void {
		$result = $this->helper->linkSelect('media_folders', [], ['disabled' => true]);

		$this->assertStringNotContainsString('disabled', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
	 */
	public function testLinkSelectWithProvidedOptionsWithDisabledConfigTrue(): void {
		$result = $this->helper->linkSelect('media_folders', ['option1', 'option2'], ['disabled' => true]);

		$this->assertStringContainsString('<li class="Item Item-Option1 Disabled" title="option1">option1</li>', $result);
		$this->assertStringContainsString('<li class="Item Item-Option2 Disabled" title="option2">option2</li>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
	 */
	public function testLinkSelectWithProvidedOptionsWithDisabledConfigArray(): void {
		$result = $this->helper->linkSelect('media_folders', ['option1', 'option2'], ['disabled' => [1]]);

		$this->assertStringContainsString(
			'<li class="Item Item-Option1"><a href="/backend/xy/the-controller/the-action/media-folders:0/" title="option1">option1</a></li>',
			$result
		);
		$this->assertStringContainsString('<li class="Item Item-Option2 Disabled" title="option2">option2</li>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::linkSelect()
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
	 * @see \Awyiss\View\Helper\CategoriesHelper::getConfiguration()
	 */
	public function testGetConfiguration(): void {
		$config = [
			'enabled' => true,
			'field' => 'mediaFolder',
			'includeParentCategories' => true,
			'groupBy' => 'group',
		];

		$this->view->set('_categories', ['mediaFolders' => ['config' => $config]]);

		$result = $this->helper->getConfiguration('mediaFolders');

		$this->assertEquals($config, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::getConfiguration()
	 */
	public function testGetConfigurationWithEmptyConfig(): void {
		$this->view->set('_categories', ['mediaFolders' => []]);

		$result = $this->helper->getConfiguration('mediaFolders');

		$this->assertEquals([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::getCategories()
	 */
	public function testGetCategories(): void {
		$this->view->set('_categories', ['mediaFolders' => ['raw' => ['category1', 'category2'], 'simple' => ['category3']]]);

		$result = $this->helper->getCategories('mediaFolders', true);

		$this->assertEquals(['category1', 'category2'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::getCategories()
	 */
	public function testGetCategoriesWithSimple(): void {
		$this->view->set('_categories', ['mediaFolders' => ['simple' => ['category3']]]);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->helper->getCategories('mediaFolders', false);

		$this->assertEquals(['category3'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::getSelectedCategory()
	 */
	public function testGetSelectedCategory(): void {
		$this->view->set('_categories', ['mediaFolders' => ['selected' => 'category1']]);

		$result = $this->helper->getSelectedCategory('mediaFolders');

		$this->assertEquals('category1', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\CategoriesHelper::getSelectedCategory()
	 */
	public function testGetSelectedCategoryWithNull(): void {
		$this->view->set('_categories', ['mediaFolders' => []]);

		$result = $this->helper->getSelectedCategory('mediaFolders');

		$this->assertNull($result);
	}
}
