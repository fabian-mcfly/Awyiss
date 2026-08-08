<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Widget;


use Awyiss\Model\Entity\Language;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Awyiss\Widget\SocialMediaEmbedWidget;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;


/**
 * Test case for SocialMediaEmbedWidget
 *
 * @see \Awyiss\Widget\SocialMediaEmbedWidget
 */
class SocialMediaEmbedWidgetTest extends TestCase {
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $mockBackendView;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $mockFrontendView;
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

		$this->mockBackendView = $this->createStub(BackendView::class);
		$this->mockFrontendView = $this->createMock(FrontendView::class);
		$this->mockLanguage = $this->createStub(Language::class);
	}


	/**
	 * Test getTitle method returns 'Social Media Embed'
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::getTitle()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetTitle(): void {
		$result = SocialMediaEmbedWidget::getTitle();

		$this->assertSame('Social Media Embed', $result);
	}


	/**
	 * Test getFormFields method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::getFormFields()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithDefaults(): void {
		$result = SocialMediaEmbedWidget::getFormFields($this->mockBackendView);

		$this->assertIsArray($result);
		$this->assertCount(1, $result);

		// Test that service field is present
		$this->assertArrayHasKey('settings.service', $result);
		$serviceField = $result['settings.service'];
		$this->assertSame('select', $serviceField['type']);
		$this->assertTrue($serviceField['required']);
		$this->assertNull($serviceField['value']);
		$this->assertTrue($serviceField['data-form-updater']);

		// Test service options
		$this->assertIsArray($serviceField['options']);
		$this->assertArrayHasKey('youtube', $serviceField['options']);
		$this->assertArrayHasKey('vimeo', $serviceField['options']);
		$this->assertArrayHasKey('instagram', $serviceField['options']);

		// Test that embed ID field is NOT present without service selection
		$this->assertArrayNotHasKey('settings.embedId', $result);
	}


	/**
	 * Test getFormFields method with YouTube service selected
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::getFormFields()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithYouTubeService(): void {
		$settings = ['service' => 'youtube'];

		$result = SocialMediaEmbedWidget::getFormFields($this->mockBackendView, null, null, $settings);

		// Test that both fields are present
		$this->assertArrayHasKey('settings.service', $result);
		$this->assertArrayHasKey('settings.embedId', $result);

		// Test embed ID field
		$embedIdField = $result['settings.embedId'];
		$this->assertSame('text', $embedIdField['type']);
		$this->assertTrue($embedIdField['required']);
		$this->assertNull($embedIdField['value']);
		$this->assertStringContainsString('youtube.com', $embedIdField['placeholder']);
	}


	/**
	 * Test getFormFields method with Vimeo service selected
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::getFormFields()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithVimeoService(): void {
		$settings = ['service' => 'vimeo'];

		$result = SocialMediaEmbedWidget::getFormFields($this->mockBackendView, null, null, $settings);

		// Test that both fields are present
		$this->assertArrayHasKey('settings.service', $result);
		$this->assertArrayHasKey('settings.embedId', $result);

		// Test embed ID field
		$embedIdField = $result['settings.embedId'];
		$this->assertSame('text', $embedIdField['type']);
		$this->assertTrue($embedIdField['required']);
		$this->assertNull($embedIdField['value']);
		$this->assertStringContainsString('vimeo.com', $embedIdField['placeholder']);
	}


	/**
	 * Test getFormFields method with Instagram service selected
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::getFormFields()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithInstagramService(): void {
		$settings = ['service' => 'instagram'];

		$result = SocialMediaEmbedWidget::getFormFields($this->mockBackendView, null, null, $settings);

		// Test that both fields are present
		$this->assertArrayHasKey('settings.service', $result);
		$this->assertArrayHasKey('settings.embedId', $result);

		// Test embed ID field
		$embedIdField = $result['settings.embedId'];
		$this->assertSame('text', $embedIdField['type']);
		$this->assertTrue($embedIdField['required']);
		$this->assertNull($embedIdField['value']);
		$this->assertStringContainsString('instagram.com', $embedIdField['placeholder']);
	}


	/**
	 * Test getFormFields method with custom embed ID value
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::getFormFields()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithCustomEmbedId(): void {
		$settings = [
			'service' => 'youtube',
			'embedId' => 'dQw4w9WgXcQ',
		];

		$result = SocialMediaEmbedWidget::getFormFields($this->mockBackendView, null, null, $settings);

		$this->assertSame('dQw4w9WgXcQ', $result['settings.embedId']['value']);
	}


	/**
	 * Test render method with invalid settings returns empty string
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithMissingService(): void {
		$settings = ['embedId' => 'test123'];

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('', $result);
	}


	/**
	 * Test render method with missing embed ID returns empty string
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithMissingEmbedId(): void {
		$settings = ['service' => 'youtube'];

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('', $result);
	}


	/**
	 * Test render method with unsupported service returns empty string
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithUnsupportedService(): void {
		$settings = [
			'service' => 'tiktok',
			'embedId' => 'test123',
		];

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('', $result);
	}


	/**
	 * Test render method with invalid embed ID returns empty string
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithInvalidYouTubeId(): void {
		$settings = [
			'service' => 'youtube',
			'embedId' => 'invalid',
		];

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('', $result);
	}


	/**
	 * Test render method with valid YouTube ID
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithValidYouTubeId(): void {
		$settings = [
			'service' => 'youtube',
			'embedId' => 'dQw4w9WgXcQ',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				return isset($data['settings'], $data['embedId']) && $data['settings']['service'] === 'youtube' && $data['embedId'] === 'dQw4w9WgXcQ';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test render method with YouTube URL
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithYouTubeUrl(): void {
		$settings = [
			'service' => 'youtube',
			'embedId' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				// Should extract the ID from the URL
				return $data['embedId'] === 'dQw4w9WgXcQ';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test render method with valid Vimeo ID
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithValidVimeoId(): void {
		$settings = [
			'service' => 'vimeo',
			'embedId' => '928368341',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				return $data['embedId'] === '928368341';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test render method with Vimeo URL
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithVimeoUrl(): void {
		$settings = [
			'service' => 'vimeo',
			'embedId' => 'https://vimeo.com/928368341',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				return $data['embedId'] === '928368341';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test render method with valid Instagram ID
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithValidInstagramId(): void {
		$settings = [
			'service' => 'instagram',
			'embedId' => 'DNVGGL2oGPt',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test render method with Instagram URL
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithInstagramUrl(): void {
		$settings = [
			'service' => 'instagram',
			'embedId' => 'https://www.instagram.com/p/DNVGGL2oGPt',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				return $data['embedId'] === 'DNVGGL2oGPt';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test render method with Instagram Reel URL
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithInstagramReelUrl(): void {
		$settings = [
			'service' => 'instagram',
			'embedId' => 'https://www.instagram.com/reel/DNVGGL2oGPt',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				return $data['embedId'] === 'DNVGGL2oGPt';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test registerService method adds new service and render works with valid input
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::registerService()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRegisterService(): void {
		// Register a custom service with an extractor that extracts numeric ID from URL
		$customExtractor = function (string $input): ?string {
			// Extract numeric ID from "custom://id123" format
			if (preg_match('/^custom:\/\/(\d+)$/', $input, $matches)) {
				return $matches[1];
			}

			// Or accept direct numeric ID
			if (preg_match('/^\d+$/', $input)) {
				return $input;
			}

			return null;
		};

		SocialMediaEmbedWidget::registerService('custom', $customExtractor);

		// Test with URL format - input and output should be different
		$settings = [
			'service' => 'custom',
			'embedId' => 'custom://12345',
		];

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/social_media_embed',
			$this->callback(function (array $data): bool {
				// Verify ID was extracted correctly from URL (not just passed through)
				return $data['settings']['service'] === 'custom' && $data['embedId'] === '12345';
			})
		)->willReturn('rendered_html');

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		$this->assertSame('rendered_html', $result);
	}


	/**
	 * Test registerService with invalid input returns empty string
	 *
	 * @return void
	 * @see \Awyiss\Widget\SocialMediaEmbedWidget::registerService()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRegisterServiceWithInvalidInput(): void {
		// Register a custom service with strict validation
		$customExtractor = function (string $input): ?string {
			// Only accept numeric IDs
			if (preg_match('/^\d+$/', $input)) {
				return $input;
			}

			return null;
		};

		SocialMediaEmbedWidget::registerService('strictcustom', $customExtractor);

		// Try to render with non-numeric input
		$settings = [
			'service' => 'strictcustom',
			'embedId' => 'invalid-abc',
		];

		$result = SocialMediaEmbedWidget::render($settings, $this->mockFrontendView);

		// Should return empty string because extractor rejected the input
		$this->assertSame('', $result);
	}
}
