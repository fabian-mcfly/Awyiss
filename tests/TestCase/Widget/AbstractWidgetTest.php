<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Widget;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Awyiss\View\Helper\FormHelper;
use Awyiss\View\HelperRegistry;
use Awyiss\Widget\AbstractWidget;
use Awyiss\Widget\BreadcrumbsWidget;
use Customer\Widget\NewsListingWidget;


/**
 * Test case for AbstractWidget
 *
 * @see \Awyiss\Widget\AbstractWidget
 */
class AbstractWidgetTest extends TestCase {
	/**
	 * @var \Awyiss\Widget\AbstractWidget
	 */
	protected AbstractWidget $testWidget;
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $mockBackendView;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $mockFrontendView;
	/**
	 * @var \Awyiss\View\Helper\FormHelper
	 */
	protected FormHelper $mockFormHelper;
	/**
	 * @var \Awyiss\Model\Entity\Language
	 */
	protected Language $mockLanguage;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockBackendView = $this->createMock(BackendView::class);
		$this->mockFrontendView = $this->createMock(FrontendView::class);
		$this->mockFormHelper = $this->createMock(FormHelper::class);
		$this->mockLanguage = $this->createMock(Language::class);

		// Create a concrete implementation of AbstractWidget for testing
		$this->testWidget = new class extends AbstractWidget {
			/**
			 * @inheritDoc
			 */
			public static function getTitle(): string {
				return 'Test Widget';
			}


			/**
			 * @inheritDoc
			 */
			protected static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
				return [
					'settings.title' => [
						'type' => 'text',
						'label' => 'Title',
						'value' => $settings['title'] ?? 'Default Title',
					],
					'settings.content' => [
						'type' => 'textarea',
						'label' => 'Content',
						'rows' => 5,
						'value' => $settings['content'] ?? null,
					],
					'<div class="custom-section">Custom HTML content</div>',
					'settings.enabled' => [
						'method' => 'checkbox',
						'type' => 'checkbox',
						'label' => 'Enable Widget',
						'checked' => $settings['enabled'] ?? true,
					],
					'settings.mode' => [
						'type' => 'radio',
						'label' => 'Mode',
						'options' => [
							'simple' => 'Simple Mode',
							'advanced' => 'Advanced Mode',
						],
						'val' => $settings['mode'] ?? 'simple',
					],
				];
			}
		};
	}


	/**
	 * Test isAvailable method returns true by default
	 *
	 * @return void
	 * @see \Awyiss\Widget\AbstractWidget::isAvailable()
	 */
	public function testIsAvailableReturnsTrueByDefault(): void {
		$result = $this->testWidget::isAvailable();

		$this->assertTrue($result);
	}


	/**
	 * Test isAvailable method can be overridden
	 *
	 * @return void
	 * @see \Awyiss\Widget\AbstractWidget::isAvailable()
	 */
	public function testIsAvailableCanBeOverridden(): void {
		$customWidget = new class extends AbstractWidget {
			/**
			 * @inheritDoc
			 */
			public static function getTitle(): string {
				return 'Custom Widget';
			}


			/**
			 * @inheritDoc
			 */
			public static function isAvailable(): bool {
				return false;
			}


			/**
			 * @inheritDoc
			 */
			protected static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
				return [];
			}
		};

		$result = $customWidget::isAvailable();

		$this->assertFalse($result);
	}


	/**
	 * Test renderForm method with form field arrays
	 *
	 * @return void
	 * @see \Awyiss\Widget\AbstractWidget::renderForm()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testRenderFormWithFormFields(): void {
		$frontendLanguage = $this->mockLanguage;
		$userLanguage = $this->mockLanguage;
		$settings = ['some' => 'setting'];

		// Mock the helpers collection
		$helpersCollection = $this->createMock(HelperRegistry::class);
		$helpersCollection->method('get')->with('Form')->willReturn($this->mockFormHelper);

		$this->mockBackendView->method('helpers')->willReturn($helpersCollection);

		// Set up expectations for form helper calls
		$this->mockFormHelper->expects($this->exactly(3))->method('control')->willReturnOnConsecutiveCalls(
			'<input type="text" name="title" id="title" />',
			'<textarea name="content" id="content" rows="5"></textarea>',
			'<input type="checkbox" name="enabled" id="enabled" checked="checked" />'
		);

		$result = $this->testWidget::renderForm($this->mockBackendView, $frontendLanguage, $userLanguage, $settings);

		$expectedOutput = '<input type="text" name="title" id="title" />' .
						  '<textarea name="content" id="content" rows="5"></textarea>' .
						  '<div class="custom-section">Custom HTML content</div>' .
						  '<input type="checkbox" name="enabled" id="enabled" checked="checked" />';

		$this->assertSame($expectedOutput, $result);
	}


	/**
	 * Test render method calls view element with correct parameters
	 *
	 * @return void
	 * @see \Awyiss\Widget\AbstractWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderCallsViewElement(): void {
		$settings = ['title' => 'Test Title', 'content' => 'Test Content'];
		$entity = $this->createMock(Entity::class);
		$frontendLanguage = $this->mockLanguage;
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->testWidget = new BreadcrumbsWidget();

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/breadcrumbs',
			$this->callback(function (array $data) use ($entity, $frontendLanguage, $mediaRenderOptions, $settings) {
				return $data['entity'] === $entity &&
					   $data['frontendLanguage'] === $frontendLanguage &&
					   $data['mediaRenderOptions'] === $mediaRenderOptions &&
					   $data['settings'] === $settings;
			})
		)->willReturn('');

		$result = $this->testWidget::render(
			$settings,
			$this->mockFrontendView,
			$mediaRenderOptions,
			$entity,
			$frontendLanguage
		);

		$this->assertSame('', $result);
	}


	/**
	 * Test render method converts identifier to underscore case for element name
	 *
	 * @return void
	 * @see \Awyiss\Widget\AbstractWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderConvertsIdentifierToUnderscore(): void {
		$camelCaseWidget = new NewsListingWidget();

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/news_listing', // Should be converted to underscore
			$this->anything()
		)->willReturn('<div>Camel Case Widget</div>');

		$result = $camelCaseWidget::render([], $this->mockFrontendView, null);

		$this->assertSame('<div>Camel Case Widget</div>', $result);
	}


	/**
	 * Test renderForm method with empty form fields
	 *
	 * @return void
	 * @see \Awyiss\Widget\AbstractWidget::renderForm()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testRenderFormWithEmptyFields(): void {
		$emptyWidget = new class extends AbstractWidget {
			/**
			 * @inheritDoc
			 */
			public static function getTitle(): string {
				return 'Empty Widget';
			}


			/**
			 * @inheritDoc
			 */
			protected static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
				return [];
			}
		};

		$helpersCollection = $this->createMock(HelperRegistry::class);
		$helpersCollection->method('get')->with('Form')->willReturn($this->mockFormHelper);

		$this->mockBackendView->method('helpers')->willReturn($helpersCollection);

		$result = $emptyWidget::renderForm($this->mockBackendView);

		$this->assertSame('', $result);
	}
}
