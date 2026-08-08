<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\MediaHelper;
use Awyiss\View\HelperRegistry;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\View\Helper\HtmlHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * MediaHelperTest class
 */
class MediaHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\MediaHelper
	 */
	protected MediaHelper $mediaHelper;
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->getMockBuilder(BackendView::class)->getMock();

		$helperRegistry = $this->getStubBuilder(HelperRegistry::class)
			->setConstructorArgs([$this->view])
			->onlyMethods(['get'])
			->getStub();
		$this->view->method('helpers')->willReturn($helperRegistry);

		$htmlHelper = new HtmlHelper($this->view);

		$this->view->method('loadHelper')->willReturn($htmlHelper);

		$this->mediaHelper = new MediaHelper($this->view);
		$this->mediaHelper->initialize([]);

		$helperRegistry->method('get')->willReturnCallback(
			fn (string $name) => $name === 'Media' ? $this->mediaHelper : null
		);
	}


	/**
	 * @return array<string, bool>
	 */
	public static function include2xProvider(): array {
		return [
			'enabled' => [true],
			'disabled' => [false],
		];
	}


	/**
	 * @return array<string, bool>
	 */
	public static function lazyloadProvider(): array {
		return [
			'enabled' => [true],
			'disabled' => [false],
		];
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getMediaRenderOptions()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetMediaRenderOptions(): void {
		$this->assertInstanceOf(MediaRenderOptions::class, $this->mediaHelper->getMediaRenderOptions());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::element()
	 */
	public function testElement(): void {
		$this->view->expects($this->once())->method('element')->with('media/test_element', $this->anything())->willReturn('<div>media</div>');

		$result = $this->mediaHelper->element('testElement', ['testElement' => 'mediaData'], null, [
			'testOption' => ['key' => 'value'],
		]);

		$this->assertEquals('<div>media</div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::element()
	 */
	public function testElementReturnsEmptyStringForUnknownMediaAssignment(): void {
		$this->view->expects($this->never())->method('element');

		$result = $this->mediaHelper->element('another_element', ['testElement' => 'mediaData']);

		$this->assertEquals('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackground(): void {
		$media = new Media([
			'name' => 'image.jpg',
			'path' => '/path/to/image.jpg',
			'mimeType' => 'image/jpeg',
			'averageColor' => '#ffff00',
		]);

		$resizeStrategory = ResizeStrategy::Contain;

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$mediaRenderOptions->method('getResizeStrategy')->willReturn($resizeStrategory);
		$mediaRenderOptions->method('getSelector')->willReturn('.selector');

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertStringContainsString('<style>.selector', $result);
		$this->assertStringContainsString('background-image:url(\'/path/to/image.jpg\');', $result);
		$this->assertStringContainsString('background-color:#ffff00;', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundWithNoAverageColor(): void {
		$media = new Media([
			'name' => 'image.jpg',
			'path' => '/path/to/image.jpg',
			'mimeType' => 'image/jpeg',
			'averageColor' => null,
		]);

		$resizeStrategory = ResizeStrategy::Contain;

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$mediaRenderOptions->method('getResizeStrategy')->willReturn($resizeStrategory);
		$mediaRenderOptions->method('getSelector')->willReturn('.selector');

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertStringContainsString('<style>.selector', $result);
		$this->assertStringContainsString('background-image:url(\'/path/to/image.jpg\');', $result);
		$this->assertStringNotContainsString('background-color:', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundWithNoSelectorThrowsException(): void {
		$media = new Media([]);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$mediaRenderOptions->method('getSelector')->willReturn(null);

		$this->expectException(InvalidArgumentException::class);
		$this->mediaHelper->background($media, $mediaRenderOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundWithBreakpoints() {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'selector' => '.selector',
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertStringContainsString('<style>.selector', $result);
		$this->assertStringContainsString('background-image:url(\'_resized/dummypath/logo-awyiss-[w1024].avif\');', $result);
		$this->assertStringContainsString('@media (width <= 1234px) { .selector', $result);
		$this->assertStringContainsString('@media (width <= 768px) { .selector', $result);
		$this->assertStringContainsString('@media (width <= 640px) { .selector', $result);
		$this->assertStringContainsString('@media (width <= 480px) { .selector', $result);
		$this->assertStringContainsString('@media (width <= 320px) { .selector', $result);

		// Make sure breakpoints above the base width are not included
		$this->assertStringNotContainsString('@media (width <= 1920px) { .selector', $result);
		$this->assertStringNotContainsString('@media (width <= 1440px) { .selector', $result);

		/**
		 * Make sure all breakpoints are in the correct order
		 * The order of the breakpoints is important for the CSS to work correctly
		 *
		 * In contrast to the `source` elements of a `picture` element,
		 * the last applied media query is the one that is used.
		 *
		 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries/Using_media_queries
		 */
		$this->assertMatchesRegularExpression(
			'/@media \(width <= 1234px\) \{ \.selector.*' .
			'@media \(width <= 768px\) \{ \.selector.*' .
			'@media \(width <= 640px\) \{ \.selector.*' .
			'@media \(width <= 480px\) \{ \.selector.*' .
			'@media \(width <= 320px\) \{ \.selector/s',
			$result
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundWithBreakpointsHasNoCssBreakpointsWhenResizedImagesAreEmpty() {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'selector' => '.selector',
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertStringContainsString('<style>.selector', $result);
		$this->assertStringNotContainsString('@media', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundForNonImageWithPreview() {
		$media = new Media([
			'name' => 'document.pdf',
			'path' => '/path/to/document.pdf',
			'mimeType' => 'application/pdf',
			'preview' => ProcessStatus::Success,
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'selector' => '.selector',
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertStringContainsString('<style>.selector', $result);
		$this->assertStringContainsString('background-image:url(\'/path/to/_pdf_preview/document.jpg\');', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundForNonImageWithoutPreview() {
		$media = new Media([
			'name' => 'audio.mp3',
			'path' => '/path/to/audio.mp3',
			'mimeType' => 'audio/mpeg',
		]);

		$resizeStrategory = ResizeStrategy::Contain;

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$mediaRenderOptions->method('getResizeStrategy')->willReturn($resizeStrategory);
		$mediaRenderOptions->method('getSelector')->willReturn('.selector');

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundForNonImageWithoutPreviewButAverageColor(): void {
		$media = new Media([
			'name' => 'audio.mp3',
			'path' => '/path/to/audio.mp3',
			'mimeType' => 'audio/mpeg',
			'averageColor' => '#ffff00',
		]);

		$resizeStrategory = ResizeStrategy::Contain;

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$mediaRenderOptions->method('getResizeStrategy')->willReturn($resizeStrategory);
		$mediaRenderOptions->method('getSelector')->willReturn('.selector');

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);

		$this->assertSame('<style>.selector { background-color:#ffff00; }</style>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::background()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBackgroundCalculatesCorrectAspectRatioForUnfinishedResizeFiles(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'selector' => '#Foo',
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->background($media, $mediaRenderOptions);
		$this->assertStringContainsString('<style>#Foo { --backgroundAspectRatio:1.78; --backgroundImageHeight:1440px;', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getBackgroundStyleTag()
	 * @throws \ReflectionException
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetBackgroundStyleTagCalculatesCorrectAspectRatioForUnfinishedResizeFiles(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'selector' => '#Foo',
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->callProtectedMethod($this->mediaHelper, 'getBackgroundStyleTag', null, $media, $mediaRenderOptions, null, 1.78, '');

		$this->assertStringContainsString('@media (width <= 320px) { #Foo { background-image:', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForImage(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions();
		$mediaRenderOptions = $mediaRenderOptions->withResponsive(false);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringNotContainsString('<picture', $result);
		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);
		$this->assertStringContainsString('aria-hidden="true"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="logo-awyiss.png"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForImageWithAlt(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);
		$media->alt = 'Test alt';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions();
		$mediaRenderOptions = $mediaRenderOptions->withResponsive(false);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Test alt"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Test alt"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForImageWithAltAttribute(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);
		$media->alt = 'Test alt';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions();
		$mediaRenderOptions = $mediaRenderOptions->with([
			'attributes' => [
				'alt' => 'Another alt',
			],
			'responsive' => false,
		]);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringNotContainsString('Test alt', $result);
		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Another alt"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Another alt"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForNonImageWithPreview(): void {
		$media = new Media([
			'name' => 'document.pdf',
			'path' => '/path/to/document.pdf',
			'mimeType' => 'application/pdf',
			'preview' => ProcessStatus::Success,
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions();
		$mediaRenderOptions = $mediaRenderOptions->withResponsive(false);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<img data-src="/path/to/_pdf_preview/document.jpg"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="/path/to/_pdf_preview/document.jpg"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForNonImageWithPreviewAndDisabledPreview(): void {
		$media = new Media([
			'name' => 'document.pdf',
			'path' => '/path/to/document.pdf',
			'mimeType' => 'application/pdf',
			'preview' => ProcessStatus::Success,
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions();
		$mediaRenderOptions = $mediaRenderOptions->withResponsive(false);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions, false);

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForSvg(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(7);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions();
		$mediaRenderOptions = $mediaRenderOptions->with([
			'attributes' => [
				'id' => 'FoobarId',
			],
			'responsive' => false,
		]);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.svg" id="FoobarId"', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.svg" id="FoobarId-NoScript"', $result);
		$this->assertStringContainsString('<style>#FoobarId, #FoobarId-NoScript { --imageAspectRatio: 2; }</style>', $result);

		// Make sure that nothing changes when responsive is set
		$mediaRenderOptions = $mediaRenderOptions->withResponsive();

		$result2 = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertSame($result, $result2);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForAudio(): void {
		$media = new Media([
			'id' => 0,
			'name' => 'audio.mp3',
			'path' => '/path/to/audio.mp3',
			'mimeType' => 'audio/mpeg',
		]);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);
		$mediaRenderOptions = $mediaRenderOptions->withResponsive(false);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<audio', $result);
		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<source src="/path/to/audio.mp3" type="audio/mpeg">', $result);

		// Make sure that nothing changes when responsive is set
		$mediaRenderOptions = $mediaRenderOptions->withResponsive();

		$result2 = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertSame($result, $result2);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testHtmlTagForVideo(): void {
		$media = new Media([
			'id' => 0,
			'name' => 'video.mp4',
			'path' => '/path/to/video.mp4',
			'mimeType' => 'video/mp4',
		]);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);
		$mediaRenderOptions = $mediaRenderOptions->withResponsive(false);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<video', $result);
		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<source src="/path/to/video.mp4" type="video/mp4">', $result);

		// Make sure that nothing changes when responsive is set
		$mediaRenderOptions = $mediaRenderOptions->withResponsive();

		$result2 = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertSame($result, $result2);
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testHtmlTagForImageWithLazyload(bool $lazyload): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'responsive' => false,
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringNotContainsString('<picture', $result);

		if ($lazyload) {
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"', $result);
			$this->assertStringContainsString('<noscript', $result);
			$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"', $result);
		}
		else {
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"', $result);
			$this->assertStringNotContainsString('data-src=', $result);
			$this->assertStringNotContainsString('<noscript', $result);
		}
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testHtmlTagForImageResponsiveWithLazyload(bool $lazyload): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => true,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture', $result);

		if ($lazyload) {
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif 1x, _resized/dummypath/logo-awyiss-[w640].avif 2x"', $result);
			$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
			$this->assertStringContainsString('<noscript', $result);
			$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
		}
		else {
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<source media="(width <= 320px)" srcset="_resized/dummypath/logo-awyiss-[w320].avif 1x, _resized/dummypath/logo-awyiss-[w640].avif 2x"', $result);
			$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
			$this->assertStringNotContainsString('data-srcset=', $result);
			$this->assertStringNotContainsString('data-src=', $result);
			$this->assertStringNotContainsString('<noscript', $result);
		}
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testHtmlTagForVideoWithLazyload(bool $lazyload): void {
		$media = new Media([
			'id' => 0,
			'name' => 'video.mp4',
			'path' => '/path/to/video.mp4',
			'previewPath' => '/path/to/poster.jpg',
			'mimeType' => 'video/mp4',
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'responsive' => false,
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<video', $result);

		if ($lazyload) {
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source data-src="/path/to/video.mp4" type="video/mp4">', $result);
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('data-poster="/path/to/_mp4_preview/video.jpg"', $result);
			$this->assertStringNotContainsString(' poster="', $result);
		}
		else {
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source src="/path/to/video.mp4" type="video/mp4">', $result);
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('poster="/path/to/_mp4_preview/video.jpg"', $result);
			$this->assertStringNotContainsString('data-poster=', $result);
		}
	}


	/**
	 * @param bool $include2x
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::htmlTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('include2xProvider')]
	public function testHtmlTagForImageResponsive(bool $include2x): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => $include2x,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->htmlTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);

		if ($include2x) {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif 1x, _resized/dummypath/logo-awyiss-[w640].avif 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].avif 1x, _resized/dummypath/logo-awyiss-[w1024].avif 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].avif 1x, _resized/dummypath/logo-awyiss-[w1280].avif 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].avif 1x, _resized/dummypath/logo-awyiss-[w1152].avif 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].avif 1x, _resized/dummypath/logo-awyiss-[w1920].avif 2x', $result);
		}
		else {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].avif"', $result);
		}

		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);

		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);

		$this->assertStringNotContainsString('(width <= 1920px)', $result);
		$this->assertStringNotContainsString('(width <= 1440px)', $result);
		$this->assertStringNotContainsString('(width <= 1280px)', $result);

		/**
		 * Make sure all breakpoints are in the correct order
		 *
		 * The order of the breakpoints is important since
		 * the first source with a matching media query is used
		 *
		 * @noinspection RegExpRedundantEscape
		 */
		$this->assertMatchesRegularExpression(
			'/<source media="\(width <= 320px\)".*' .
			'<source media="\(width <= 480px\)".*' .
			'<source media="\(width <= 640px\)".*' .
			'<source media="\(width <= 768px\)".*' .
			'<source media="\(width <= 1234px\)".*' .
			'<img data-src="_resized\/dummypath\/logo-awyiss-\[w1024\].avif"/s',
			$result
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTag(): void {
		$media = new Media([
			'id' => 0,
			'name' => 'audio.mp3',
			'path' => '/path/to/audio.mp3',
			'mimeType' => 'audio/mpeg',
		]);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->audioTag($media, $mediaRenderOptions);
		$this->assertStringContainsString('<audio', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTagWithSources(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(19);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->audioTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<audio', $result);
		$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/audio-test.mp3" type="audio/mpeg">', $result);
		$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/audio-test.ogg" type="audio/ogg">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTagWithSingleSource(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(17);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->audioTag($media, $mediaRenderOptions);

		// Make sure the <source> tag is present only once
		$this->assertSame(1, substr_count($result, '<source'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTagWithSubtitlesGeneratesVideoTag(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(15);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$request = new ServerRequest([
			'url' => '/de/no-slug/',
			'params' => [
				'lang' => 'de',
				'slug' => 'no-slug',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$result = $this->mediaHelper->audioTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<video', $result);
		$this->assertStringNotContainsString('<audio', $result);
		$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/multimedia-test.mp3" type="audio/mpeg">', $result);
		$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/multimedia-test.ogg" type="audio/ogg">', $result);
		$this->assertStringContainsString('<track src="../awyiss/Command/Media/TestFiles/multimedia-test-de.vtt" kind="subtitles" default srclang="de" label="German">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTagWithSubtitlesGeneratesAudiTagIfForced(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(15);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->audioTag($media, $mediaRenderOptions, false);

		$this->assertStringNotContainsString('<video', $result);
		$this->assertStringContainsString('<audio', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTagWithSubtitlesAndNoDefault(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(15);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$request = new ServerRequest([
			'url' => '/es/no-slug/',
			'params' => [
				'lang' => 'es',
				'slug' => 'no-slug',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$result = $this->mediaHelper->audioTag($media, $mediaRenderOptions);

		$this->assertStringNotContainsString('default', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::audioTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAudioTagForNonAudio(): void {
		$media = new Media([
			'name' => 'image.jpg',
			'path' => '/path/to/image.jpg',
			'mimeType' => 'image/jpeg',
		]);

		$result = $this->mediaHelper->audioTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testImageTag(): void {
		$media = new Media([
			'name' => 'image.jpg',
			'path' => '/path/to/image.jpg',
			'mimeType' => 'image/jpeg',
		]);

		$result = $this->mediaHelper->imageTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertStringContainsString('<img data-src="/path/to/image.jpg"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="/path/to/image.jpg"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testImageTagWithAlt(): void {
		$media = new Media([
			'name' => 'image.jpg',
			'path' => '/path/to/image.jpg',
			'mimeType' => 'image/jpeg',
			'alt' => 'Test alt',
		]);

		$result = $this->mediaHelper->imageTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertStringContainsString('<img data-src="/path/to/image.jpg" alt="Test alt"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="/path/to/image.jpg" alt="Test alt"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testImageTagForSvg(): void {
		$media = new Media([
			'name' => 'image.svg',
			'path' => '/path/to/image.svg',
			'mimeType' => 'image/svg+xml',
		]);

		$result = $this->mediaHelper->imageTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertStringContainsString('<img data-src="/path/to/image.svg"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="/path/to/image.svg"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testImageTagForNonImageWithPreview(): void {
		$media = new Media([
			'name' => 'document.pdf',
			'path' => '/path/to/document.pdf',
			'mimeType' => 'application/pdf',
			'preview' => ProcessStatus::Success,
		]);

		$result = $this->mediaHelper->imageTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertStringContainsString('<img data-src="/path/to/_pdf_preview/document.jpg"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="/path/to/_pdf_preview/document.jpg"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testImageTagForNonImageWithoutPreview(): void {
		$media = new Media([
			'name' => 'audio.mp3',
			'path' => '/path/to/audio.mp3',
			'mimeType' => 'audio/mpeg',
		]);

		$result = $this->mediaHelper->imageTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testImageTagCalculatesCorrectAspectRatioForUnfinishedResizeFiles(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$result = $this->mediaHelper->imageTag($media, $this->mediaHelper->getMediaRenderOptions()->withWidth(1024)->withResponsive(false));

		$this->assertStringNotContainsString('width="1024"', $result);
		$this->assertStringContainsString('width="2560" height="1440"', $result);
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testImageTagWithLazyload(bool $lazyload): void {
		$media = new Media([
			'name' => 'image.jpg',
			'path' => '/path/to/image.jpg',
			'mimeType' => 'image/jpeg',
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->imageTag($media, $mediaRenderOptions);

		if ($lazyload) {
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img data-src="/path/to/image.jpg"', $result);
			$this->assertStringContainsString('<noscript', $result);
			$this->assertStringContainsString('<img src="/path/to/image.jpg"', $result);
		}
		else {
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img src="/path/to/image.jpg"', $result);
			$this->assertStringNotContainsString('data-src=', $result);
			$this->assertStringNotContainsString('<noscript', $result);
		}
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::imageTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testImageTagForSvgWithLazyload(bool $lazyload): void {
		$media = new Media([
			'name' => 'image.svg',
			'path' => '/path/to/image.svg',
			'mimeType' => 'image/svg+xml',
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->imageTag($media, $mediaRenderOptions);

		if ($lazyload) {
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img data-src="/path/to/image.svg"', $result);
			$this->assertStringContainsString('<noscript', $result);
			$this->assertStringContainsString('<img src="/path/to/image.svg"', $result);
		}
		else {
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img src="/path/to/image.svg"', $result);
			$this->assertStringNotContainsString('data-src=', $result);
			$this->assertStringNotContainsString('<noscript', $result);
		}
	}


	/**
	 * @param bool $include2x
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('include2xProvider')]
	public function testPictureTag(bool $include2x): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$media->averageColor = '#00ff00';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => $include2x,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);

		if ($include2x) {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif 1x, _resized/dummypath/logo-awyiss-[w640].avif 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].avif 1x, _resized/dummypath/logo-awyiss-[w1024].avif 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].avif 1x, _resized/dummypath/logo-awyiss-[w1280].avif 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].avif 1x, _resized/dummypath/logo-awyiss-[w1152].avif 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].avif 1x, _resized/dummypath/logo-awyiss-[w1920].avif 2x', $result);
		}
		else {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].avif"', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].avif"', $result);
		}

		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
		$this->assertStringContainsString('--imageBackgroundColor:#00ff00;', $result);

		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);

		$this->assertStringNotContainsString('(width <= 1920px)', $result);
		$this->assertStringNotContainsString('(width <= 1440px)', $result);
		$this->assertStringNotContainsString('(width <= 1280px)', $result);

		/**
		 * Make sure all breakpoints are in the correct order
		 * The order of the breakpoints is important since
		 * the first source with a matching media query is used
		 *
		 * @noinspection RegExpRedundantEscape
		 */
		$this->assertMatchesRegularExpression(
			'/<source media="\(width <= 320px\)".*' .
			'<source media="\(width <= 480px\)".*' .
			'<source media="\(width <= 640px\)".*' .
			'<source media="\(width <= 768px\)".*' .
			'<source media="\(width <= 1234px\)".*' .
			'<img data-src="_resized\/dummypath\/logo-awyiss-\[w1024\].avif"/s',
			$result
		);
	}


	/**
	 * @param bool $include2x
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('include2xProvider')]
	public function testPictureTagWithWebpResizeFileType(bool $include2x): void {
		Configure::write('Awyiss.Media.Frontend.resizing.fileType', 'webp');

		$this->mediaHelper = new MediaHelper($this->view);
		$this->mediaHelper->initialize([]);

		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$media->averageColor = '#00ff00';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => $include2x,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);

		if ($include2x) {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].webp 1x, _resized/dummypath/logo-awyiss-[w640].webp 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].webp 1x, _resized/dummypath/logo-awyiss-[w1024].webp 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].webp 1x, _resized/dummypath/logo-awyiss-[w1280].webp 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].webp 1x, _resized/dummypath/logo-awyiss-[w1152].webp 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].webp 1x, _resized/dummypath/logo-awyiss-[w1920].webp 2x', $result);
		}
		else {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].webp"', $result);
		}

		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].webp"', $result);
		$this->assertStringContainsString('--imageBackgroundColor:#00ff00;', $result);

		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].webp"', $result);

		$this->assertStringNotContainsString('(width <= 1920px)', $result);
		$this->assertStringNotContainsString('(width <= 1440px)', $result);
		$this->assertStringNotContainsString('(width <= 1280px)', $result);

		/**
		 * Make sure all breakpoints are in the correct order
		 * The order of the breakpoints is important since
		 * the first source with a matching media query is used
		 *
		 * @noinspection RegExpRedundantEscape
		 */
		$this->assertMatchesRegularExpression(
			'/<source media="\(width <= 320px\)".*' .
			'<source media="\(width <= 480px\)".*' .
			'<source media="\(width <= 640px\)".*' .
			'<source media="\(width <= 768px\)".*' .
			'<source media="\(width <= 1234px\)".*' .
			'<img data-src="_resized\/dummypath\/logo-awyiss-\[w1024\].webp"/s',
			$result
		);
	}


	/**
	 * @param bool $include2x
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('include2xProvider')]
	public function testPictureTagWithWebpResizeFileTypePerConfig(bool $include2x): void {
		$this->mediaHelper = new MediaHelper($this->view);
		$this->mediaHelper->initialize([
			'resizeMediaFileType' => 'webp',
		]);

		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$media->averageColor = '#00ff00';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => $include2x,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);

		if ($include2x) {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].webp 1x, _resized/dummypath/logo-awyiss-[w640].webp 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].webp 1x, _resized/dummypath/logo-awyiss-[w1024].webp 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].webp 1x, _resized/dummypath/logo-awyiss-[w1280].webp 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].webp 1x, _resized/dummypath/logo-awyiss-[w1152].webp 2x', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].webp 1x, _resized/dummypath/logo-awyiss-[w1920].webp 2x', $result);
		}
		else {
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].webp"', $result);
			$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].webp"', $result);
		}

		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].webp"', $result);
		$this->assertStringContainsString('--imageBackgroundColor:#00ff00;', $result);

		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].webp"', $result);

		$this->assertStringNotContainsString('(width <= 1920px)', $result);
		$this->assertStringNotContainsString('(width <= 1440px)', $result);
		$this->assertStringNotContainsString('(width <= 1280px)', $result);

		/**
		 * Make sure all breakpoints are in the correct order
		 * The order of the breakpoints is important since
		 * the first source with a matching media query is used
		 *
		 * @noinspection RegExpRedundantEscape
		 */
		$this->assertMatchesRegularExpression(
			'/<source media="\(width <= 320px\)".*' .
			'<source media="\(width <= 480px\)".*' .
			'<source media="\(width <= 640px\)".*' .
			'<source media="\(width <= 768px\)".*' .
			'<source media="\(width <= 1234px\)".*' .
			'<img data-src="_resized\/dummypath\/logo-awyiss-\[w1024\].webp"/s',
			$result
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testPictureTagNotContainsBackgroundColorVarIfEmpty(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringNotContainsString('--imageBackgroundColor:', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testPictureTagWithAlt(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);
		$media->alt = 'Test alt';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => false,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture', $result);
		$this->assertStringContainsString('class="Lazyload"', $result);

		$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif"', $result);
		$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].avif"', $result);
		$this->assertStringContainsString('<source media="(width <= 640px)" data-srcset="_resized/dummypath/logo-awyiss-[w640].avif"', $result);
		$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w576].avif"', $result);
		$this->assertStringContainsString('<source media="(width <= 1234px)" data-srcset="_resized/dummypath/logo-awyiss-[w925].avif"', $result);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].avif" alt="Test alt" ', $result);

		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);

		$this->assertStringNotContainsString('(width <= 1920px)', $result);
		$this->assertStringNotContainsString('(width <= 1440px)', $result);
		$this->assertStringNotContainsString('(width <= 1280px)', $result);

		/**
		 * Make sure all breakpoints are in the correct order
		 * The order of the breakpoints is important since
		 * the first source with a matching media query is used
		 *
		 * @noinspection RegExpRedundantEscape
		 */
		$this->assertMatchesRegularExpression(
			'/<source media="\(width <= 320px\)".*' .
			'<source media="\(width <= 480px\)".*' .
			'<source media="\(width <= 640px\)".*' .
			'<source media="\(width <= 768px\)".*' .
			'<source media="\(width <= 1234px\)".*' .
			'<img data-src="_resized\/dummypath\/logo-awyiss-\[w1024\].avif"/s',
			$result
		);
	}


	/**
	 * @param bool $include2x
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('include2xProvider')]
	public function testPictureTagForSvg(bool $include2x): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(7);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => $include2x,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture>', $result);
		$this->assertStringNotContainsString('<source', $result);
		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.svg"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.svg"', $result);
	}


	/**
	 * @param bool $include2x
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('include2xProvider')]
	public function testPictureTagWithoutResize(bool $include2x): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => $include2x,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<picture>', $result);
		$this->assertStringNotContainsString('<source', $result);
		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"', $result);
		$this->assertStringContainsString('<noscript', $result);
		$this->assertStringContainsString('<img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"', $result);
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::pictureTag()
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testPictureTagWithLazyload(bool $lazyload): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$media->averageColor = '#00ff00';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1280.00,
			'columnWidth' => 75.00,
			'include2x' => true,
			'responsive' => true,
			'singleColumnBreakpoint' => 640,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->pictureTag($media, $mediaRenderOptions);

		// Verify picture tag structure
		$this->assertStringContainsString('<picture>', $result);
		$this->assertStringContainsString('</picture>', $result);

		if ($lazyload) {
			// Verify sources use data-srcset for lazy loading
			$this->assertStringContainsString('<source media="(width <= 320px)" data-srcset="_resized/dummypath/logo-awyiss-[w320].avif 1x, _resized/dummypath/logo-awyiss-[w640].avif 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" data-srcset="_resized/dummypath/logo-awyiss-[w480].avif 1x, _resized/dummypath/logo-awyiss-[w1024].avif 2x"', $result);
			// Verify img tag has lazyload class and data-src
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
			// Verify noscript fallback exists
			$this->assertStringContainsString('<noscript', $result);
			$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
		}
		else {
			// Verify sources use regular srcset (not lazy loading)
			$this->assertStringContainsString('<source media="(width <= 320px)" srcset="_resized/dummypath/logo-awyiss-[w320].avif 1x, _resized/dummypath/logo-awyiss-[w640].avif 2x"', $result);
			$this->assertStringContainsString('<source media="(width <= 480px)" srcset="_resized/dummypath/logo-awyiss-[w480].avif 1x, _resized/dummypath/logo-awyiss-[w1024].avif 2x"', $result);
			// Verify img tag has regular src and no lazyload class
			$this->assertStringContainsString('<img src="_resized/dummypath/logo-awyiss-[w1024].avif"', $result);
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			// Ensure no lazy loading attributes or noscript
			$this->assertStringNotContainsString('data-srcset=', $result);
			$this->assertStringNotContainsString('data-src=', $result);
			$this->assertStringNotContainsString('<noscript', $result);
		}

		// Verify background color is present regardless of lazyload setting
		$this->assertStringContainsString('--imageBackgroundColor:#00ff00;', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testVideoTag(): void {
		$media = new Media([
			'id' => 0,
			'name' => 'video.mp4',
			'path' => '/path/to/video.mp4',
			'mimeType' => 'video/mp4',
		]);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);
		$this->assertStringContainsString('<video', $result);
		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<source src="/path/to/video.mp4" type="video/mp4">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testVideoTagWithSources(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(11);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/multimedia-test.mp4" type="video/mp4">', $result);
		$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/multimedia-test.webm" type="video/webm">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testVideoTagWithSingleSource(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(18);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);

		// Make sure the <source> tag is present only once
		$this->assertSame(1, substr_count($result, '<source'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testVideoTagWithSubtitles(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(11);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$request = new ServerRequest([
			'url' => '/de/no-slug/',
			'params' => [
				'lang' => 'de',
				'slug' => 'no-slug',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<track src="../awyiss/Command/Media/TestFiles/multimedia-test-de.vtt" kind="subtitles" default srclang="de" label="German">', $result);
		$this->assertStringContainsString('<track src="../awyiss/Command/Media/TestFiles/multimedia-test-en.vtt" kind="subtitles" srclang="en" label="English">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testVideoTagWithSubtitlesAndNoDefault(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(11);

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		$request = new ServerRequest([
			'url' => '/es/no-slug/',
			'params' => [
				'lang' => 'es',
				'slug' => 'no-slug',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);

		$this->assertStringNotContainsString('default', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testVideoTagForNonVideo(): void {
		$media = new Media([
			'name' => 'audio.mp3',
			'path' => '/path/to/audio.mp3',
			'mimeType' => 'audio/mpeg',
		]);

		$result = $this->mediaHelper->videoTag($media, $this->mediaHelper->getMediaRenderOptions());

		$this->assertSame('', $result);
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testVideoTagWithLazyload(bool $lazyload): void {
		$media = new Media([
			'id' => 0,
			'name' => 'video.mp4',
			'path' => '/path/to/video.mp4',
			'previewPath' => '/path/to/poster.jpg',
			'mimeType' => 'video/mp4',
		]);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);

		$this->assertStringContainsString('<video', $result);

		if ($lazyload) {
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source data-src="/path/to/video.mp4" type="video/mp4">', $result);
			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('data-poster="/path/to/_mp4_preview/video.jpg"', $result);
			$this->assertStringNotContainsString(' poster="', $result);
		}
		else {
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source src="/path/to/video.mp4" type="video/mp4">', $result);
			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('poster="/path/to/_mp4_preview/video.jpg"', $result);
			$this->assertStringNotContainsString('data-poster=', $result);
		}
	}


	/**
	 * @param bool $lazyload
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::videoTag()
	 * @throws \Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	#[DataProvider('lazyloadProvider')]
	public function testVideoTagWithSourcesWithLazyload(bool $lazyload): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(11);
		$media->previewPath = '/path/to/_mp4_preview/video.jpg';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'lazyload' => $lazyload,
		]);

		$result = $this->mediaHelper->videoTag($media, $mediaRenderOptions);

		if ($lazyload) {
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source data-src="../awyiss/Command/Media/TestFiles/multimedia-test.mp4" type="video/mp4">', $result);
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source data-src="../awyiss/Command/Media/TestFiles/multimedia-test.webm" type="video/webm">', $result);

			$this->assertStringContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('data-poster="../awyiss/Command/Media/TestFiles/_mp4_preview/multimedia-test.jpg"', $result);
			$this->assertStringNotContainsString(' poster="', $result);
		}
		else {
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/multimedia-test.mp4" type="video/mp4">', $result);
			/** @noinspection HtmlUnknownTarget */
			$this->assertStringContainsString('<source src="../awyiss/Command/Media/TestFiles/multimedia-test.webm" type="video/webm">', $result);

			$this->assertStringNotContainsString('class="Lazyload"', $result);
			$this->assertStringContainsString('poster="../awyiss/Command/Media/TestFiles/_mp4_preview/multimedia-test.jpg"', $result);
			$this->assertStringNotContainsString('data-poster=', $result);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::isVideoLink()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testIsVideoLink(): void {
		$this->assertTrue($this->mediaHelper->isVideoLink('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
		$this->assertTrue($this->mediaHelper->isVideoLink('https://vimeo.com/123456'));
		$this->assertFalse($this->mediaHelper->isVideoLink('https://example.com'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::contents()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testContents(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(7);

		$result = $this->mediaHelper->contents($media);

		$this->assertStringContainsString('<rect width="535.224" height="168.039" fill="#131a21" stroke-width="0"/>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::storeItems()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testStoreItems(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(7);

		$mediaItems = [$media];

		$this->assertArrayNotHasKey(7, ResizedImageManager::getMediaItems());

		$this->mediaHelper->storeItems($mediaItems);

		$this->assertArrayHasKey(7, ResizedImageManager::getMediaItems());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::preview()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testPreview(): void {
		$media = $this->createStub(Media::class);

		$this->view->expects($this->once())->method('element')->with('media/preview', $this->anything())->willReturn('<div>preview</div>');

		$result = $this->mediaHelper->preview($media);
		$this->assertEquals('<div>preview</div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::preview()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testPreviewForImage(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		Awyiss::setRealm('Backend');

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

		$view = new BackendView($request);

		$mediaHelper = new MediaHelper($view);

		$result = $mediaHelper->preview($media, [
			'class' => 'Preview',
			'resize' => [
				'width' => 320,
				'strategy' => ResizeStrategy::Contain,
			],
		]);

		$this->assertStringContainsString('<div class="Preview">', $result);
		$this->assertStringContainsString('<img src="http://localhost/_resized/dummypath/logo-awyiss-[w320].avif', $result);

		$result = $mediaHelper->preview($media, [
			'id' => 'Preview',
		]);

		$this->assertStringContainsString('<div class="" id="Preview">', $result);
		$this->assertStringContainsString('<img src="http://localhost/../awyiss/Command/Media/TestFiles/logo-awyiss.png', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::resize()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testResizeWithRenderOptions(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$media->preview = ProcessStatus::NotRequired;
		$media->avif = ProcessStatus::Success;

		$renderOptions = new MediaRenderOptions()
			->withWidth(400)
			->withHeight(300)
			->withResizeStrategy(ResizeStrategy::Contain);

		$result = $this->mediaHelper->resize($media, $renderOptions);

		$this->assertInstanceOf(MediaResizedImage::class, $result);
		$this->assertEquals('logo-awyiss-[w400h300].avif', $result->name);
		$this->assertEquals(400, $result->width);
		$this->assertEquals(300, $result->height);
		$this->assertEquals(ResizeStrategy::Contain, $result->strategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::resize()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testResizeWithoutRenderOptions(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$media->preview = ProcessStatus::NotRequired;
		$media->avif = ProcessStatus::Success;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->mediaHelper->resize($media, width: 400, height: 300, strategy: ResizeStrategy::Contain);

		$this->assertInstanceOf(MediaResizedImage::class, $result);
		$this->assertEquals('logo-awyiss-[w400h300].avif', $result->name);
		$this->assertEquals(400, $result->width);
		$this->assertEquals(300, $result->height);
		$this->assertEquals(ResizeStrategy::Contain, $result->strategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::resize()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testResizeWithoutRenderOptionsSameFormat(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$media->preview = ProcessStatus::NotRequired;
		$media->avif = ProcessStatus::Success;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->mediaHelper->resize($media, format: 'matchSource', width: 400, height: 300, strategy: ResizeStrategy::Contain);

		$this->assertInstanceOf(MediaResizedImage::class, $result);
		$this->assertEquals('logo-awyiss-[w400h300].jpg', $result->name);
		$this->assertEquals(400, $result->width);
		$this->assertEquals(300, $result->height);
		$this->assertEquals(ResizeStrategy::Contain, $result->strategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::resize()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testResizeWithoutRenderOptionsWebPFormat(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$media->preview = ProcessStatus::NotRequired;
		$media->avif = ProcessStatus::Success;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->mediaHelper->resize($media, format: 'webp', width: 400, height: 300, strategy: ResizeStrategy::Contain);

		$this->assertInstanceOf(MediaResizedImage::class, $result);
		$this->assertEquals('logo-awyiss-[w400h300].webp', $result->name);
		$this->assertEquals(400, $result->width);
		$this->assertEquals(300, $result->height);
		$this->assertEquals(ResizeStrategy::Contain, $result->strategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::resize()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testResizeWithoutRenderOptionsNullFormat(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(2);

		$media->preview = ProcessStatus::NotRequired;
		$media->avif = ProcessStatus::Success;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->mediaHelper->resize($media, format: null, width: 400, height: 300, strategy: ResizeStrategy::Contain);

		$this->assertInstanceOf(MediaResizedImage::class, $result);
		$this->assertEquals('logo-awyiss-[w400h300].avif', $result->name);
		$this->assertEquals(400, $result->width);
		$this->assertEquals(300, $result->height);
		$this->assertEquals(ResizeStrategy::Contain, $result->strategy);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getPixelColumnWidth()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetPixelColumnWidth(): void {
		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);
		$mediaRenderOptions->method('getBaseWidth')->willReturn(1920.0);
		$mediaRenderOptions->method('getColumnWidth')->willReturn(50.0);

		$result = $this->mediaHelper->getPixelColumnWidth($mediaRenderOptions);

		$this->assertEquals(960, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getPixelColumnWidth()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetPixelColumnWidthThrowsExceptionForMissingBaseWidth(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Base width must be set to calculate the pixel width of a column.');

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);
		$mediaRenderOptions->method('getBaseWidth')->willReturn(0.0);
		$mediaRenderOptions->method('getColumnWidth')->willReturn(50.0);

		$this->mediaHelper->getPixelColumnWidth($mediaRenderOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getPixelColumnWidth()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetPixelColumnWidthThrowsExceptionForMissingColumnWidth(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Column width must be set to calculate the pixel width of a column.');

		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);
		$mediaRenderOptions->method('getBaseWidth')->willReturn(1920.0);
		$mediaRenderOptions->method('getColumnWidth')->willReturn(0.0);

		$this->mediaHelper->getPixelColumnWidth($mediaRenderOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTags(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();

		$this->mediaHelper->rebuildSimpleImageTags($content);

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTagsWithFields(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();
		$content->title = $content->text;

		$this->mediaHelper->rebuildSimpleImageTags($content, ['title', 'text']);

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->title);
		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTagsInField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();

		$text = $this->mediaHelper->rebuildSimpleImageTagsInField($content, 'text');

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $text);
		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTagsInFieldWithUnknownMediaId(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();
		$content->text = '<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>';

		$text = $this->mediaHelper->rebuildSimpleImageTagsInField($content, 'text');

		$this->assertSame('<p></p>', $text);
		$this->assertSame('<p></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTagsInText()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTagsInText(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->get(44);

		$media = $this->fetchTable('Media')->find('all')->all()->indexBy('id')->toArray();

		$text = $this->mediaHelper->rebuildSimpleImageTagsInText($content->text, $media);

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTagsInText()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTagsInTextWithUnknownMediaId(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->get(44);
		$content->text = '<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>';

		$media = $this->fetchTable('Media')->find('all')->all()->indexBy('id')->toArray();

		$text = $this->mediaHelper->rebuildSimpleImageTagsInText($content->text, $media);

		$this->assertSame('<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::rebuildSimpleImageTagsInText()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRebuildSimpleImageTagsInTextWithNullValue(): void {
		$media = $this->fetchTable('Media')->find('all')->all()->indexBy('id')->toArray();

		$text = $this->mediaHelper->rebuildSimpleImageTagsInText(null, $media);

		$this->assertNull($text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testReplaceCustomImageTags(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->fetchTable('GlobalContents')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1440.00,
			'responsive' => true,
		]);

		$this->mediaHelper->replaceCustomImageTags($globalContent, $mediaRenderOptions);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $globalContent->text);
		$this->assertStringContainsString('<picture>', $globalContent->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $globalContent->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $globalContent->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $globalContent->text);
		$this->assertStringContainsString('</picture>', $globalContent->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testReplaceCustomImageTagsWithFields(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->fetchTable('GlobalContents')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$globalContent->title = $globalContent->text;

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1440.00,
			'responsive' => true,
		]);

		$this->mediaHelper->replaceCustomImageTags($globalContent, $mediaRenderOptions, ['title', 'text']);

		$this->assertSame($globalContent->title, $globalContent->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $globalContent->text);
		$this->assertStringContainsString('<picture>', $globalContent->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $globalContent->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $globalContent->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $globalContent->text);
		$this->assertStringContainsString('</picture>', $globalContent->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testReplaceCustomImageTagsWithUnknownImage(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->fetchTable('GlobalContents')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$globalContent->text = '<p>GlobalContent with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1440.00,
			'responsive' => true,
		]);

		$this->mediaHelper->replaceCustomImageTags($globalContent, $mediaRenderOptions);

		$this->assertSame(
			'<p>GlobalContent with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			$globalContent->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testReplaceCustomImageTagsInField(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->fetchTable('GlobalContents')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1440.00,
			'responsive' => true,
		]);

		$text = $this->mediaHelper->replaceCustomImageTagsInField($globalContent, $mediaRenderOptions, 'text');

		$this->assertSame($text, $globalContent->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $globalContent->text);
		$this->assertStringContainsString('<picture>', $globalContent->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $globalContent->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $globalContent->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $globalContent->text);
		$this->assertStringContainsString('</picture>', $globalContent->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget,
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testReplaceCustomImageTagsInFieldWithUnknownImage(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->fetchTable('GlobalContents')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$globalContent->text = '<p>GlobalContent with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1440.00,
			'responsive' => true,
		]);

		$text = $this->mediaHelper->replaceCustomImageTagsInField($globalContent, $mediaRenderOptions, 'text');

		$this->assertSame($text, $globalContent->text);

		$this->assertSame(
			'<p>GlobalContent with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			$globalContent->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testReplaceCustomImageTagsInFieldWithTagAttributes(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->fetchTable('GlobalContents')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$globalContent->text = '<p>GlobalContent with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4","alt":"Test Image","class":"Dummyclass"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1440.00,
			'responsive' => true,
		]);

		$text = $this->mediaHelper->replaceCustomImageTagsInField($globalContent, $mediaRenderOptions, 'text');

		$this->assertSame($text, $globalContent->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $globalContent->text);
		$this->assertStringContainsString('<picture>', $globalContent->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="Test Image" class="Lazyload Dummyclass"', $globalContent->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="Test Image" class="Dummyclass"', $globalContent->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $globalContent->text);
		$this->assertStringContainsString('</picture>', $globalContent->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getResponsiveImages()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetResponsiveImagesNotIgnoresBreakPointsOptionsOfTooLargeBreakpoints(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1000.00,
			'responsive' => true,
			'singleColumnBreakpoint' => 1024,
			'breakpoints' => [768, 1234, 1920, 640, 480, 320, 1440],
		]);

		$result = $this->mediaHelper->getResponsiveImages($media, $mediaRenderOptions);

		$this->assertCount(8, $result);

		$this->assertSame(768, $result[768]->width);
		$this->assertSame(1536, $result['768x2']->width);
	}


	/**
	 * Test that consecutive breakpoints generating identical file paths are filtered out
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\MediaHelper::getResponsiveImages()
	 * @see \Awyiss\View\Helper\MediaHelper::getBreakpointFiles()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetResponsiveImagesFiltersConsecutiveDuplicateFilePaths(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1024.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'singleColumnBreakpoint' => false,
			'breakpoints' => [
				['breakpoint' => 768, 'columnWidth' => 100],
				480,
			],
		]);

		$result = $this->mediaHelper->getResponsiveImages($media, $mediaRenderOptions, true);

		$this->assertArrayNotHasKey(768, $result);
		$this->assertArrayHasKey(480, $result);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1024.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'singleColumnBreakpoint' => 768,
			'breakpoints' => [
				480,
			],
		]);

		$result = $this->mediaHelper->getResponsiveImages($media, $mediaRenderOptions, true);

		$this->assertArrayNotHasKey(768, $result);
		$this->assertArrayHasKey(480, $result);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1024.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'singleColumnBreakpoint' => false,
			'breakpoints' => [
				768,
				480,
			],
		]);

		$result = $this->mediaHelper->getResponsiveImages($media, $mediaRenderOptions, true);

		$this->assertArrayHasKey(768, $result);
		$this->assertArrayHasKey(480, $result);

		$mediaRenderOptions = $this->mediaHelper->getMediaRenderOptions()->with([
			'baseWidth' => 1024.00,
			'columnWidth' => 75.00,
			'responsive' => true,
			'singleColumnBreakpoint' => false,
			'breakpoints' => [
				['breakpoint' => 960, 'columnWidth' => 100],
				['breakpoint' => 768, 'columnWidth' => 100],
				480,
			],
		]);

		$result = $this->mediaHelper->getResponsiveImages($media, $mediaRenderOptions, true);

		$this->assertArrayHasKey(960, $result);
		$this->assertArrayHasKey(768, $result);
		$this->assertArrayHasKey(480, $result);
	}
}
