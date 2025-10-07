<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Module;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Module\AbstractModule;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Awyiss\View\Helper\FormHelper;
use Awyiss\View\HelperRegistry;
use Customer\Module\EmptyModule;
use Customer\Module\NewsListingModule;


/**
 * Test case for AbstractModule
 *
 * @see \Awyiss\Module\AbstractModule
 */
class AbstractModuleTest extends TestCase {
	/**
	 * @var \Awyiss\Module\AbstractModule
	 */
	protected AbstractModule $testModule;
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockBackendView = $this->createMock(BackendView::class);
		$this->mockFrontendView = $this->createMock(FrontendView::class);
		$this->mockFormHelper = $this->createMock(FormHelper::class);
		$this->mockLanguage = $this->createMock(Language::class);

		// Create a concrete implementation of AbstractModule for testing
		$this->testModule = new class extends AbstractModule {
			/**
			 * @inheritDoc
			 */
			public static function getTitle(): string {
				return 'Test Module';
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
						'label' => 'Enable Module',
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
	 * @see \Awyiss\Module\AbstractModule::isAvailable()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsAvailableReturnsTrueByDefault(): void {
		$result = $this->testModule::isAvailable();

		$this->assertTrue($result);
	}


	/**
	 * Test isAvailable method can be overridden
	 *
	 * @return void
	 * @see \Awyiss\Module\AbstractModule::isAvailable()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsAvailableCanBeOverridden(): void {
		$customModule = new class extends AbstractModule {
			/**
			 * @inheritDoc
			 */
			public static function getTitle(): string {
				return 'Custom Module';
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

		$result = $customModule::isAvailable();

		$this->assertFalse($result);
	}


	/**
	 * Test renderForm method with form field arrays
	 *
	 * @return void
	 * @see \Awyiss\Module\AbstractModule::renderForm()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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

		$result = $this->testModule::renderForm($this->mockBackendView, $frontendLanguage, $userLanguage, $settings);

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
	 * @see \Awyiss\Module\AbstractModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderCallsViewElement(): void {
		$settings = ['title' => 'Test Title', 'content' => 'Test Content'];
		$entity = $this->createMock(Entity::class);
		$frontendLanguage = $this->mockLanguage;
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->testModule = new \Awyiss\Module\BreadcrumbsModule();

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/breadcrumbs',
			$this->callback(function (array $data) use ($entity, $frontendLanguage, $mediaRenderOptions, $settings) {
				return $data['entity'] === $entity &&
					   $data['frontendLanguage'] === $frontendLanguage &&
					   $data['mediaRenderOptions'] === $mediaRenderOptions &&
					   $data['settings'] === $settings;
			})
		)->willReturn('');

		$result = $this->testModule::render(
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
	 * @see \Awyiss\Module\AbstractModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderConvertsIdentifierToUnderscore(): void {
		$camelCaseModule = new NewsListingModule();

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/news_listing', // Should be converted to underscore
			$this->anything()
		)->willReturn('<div>Camel case module</div>');

		$result = $camelCaseModule::render([], $this->mockFrontendView, null);

		$this->assertSame('<div>Camel case module</div>', $result);
	}


	/**
	 * Test renderForm method with empty form fields
	 *
	 * @return void
	 * @see \Awyiss\Module\AbstractModule::renderForm()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderFormWithEmptyFields(): void {
		$emptyModule = new class extends AbstractModule {
			/**
			 * @inheritDoc
			 */
			public static function getTitle(): string {
				return 'Empty Module';
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

		$result = $emptyModule::renderForm($this->mockBackendView);

		$this->assertSame('', $result);
	}
}
