<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\View\Helper;
use InvalidArgumentException;


/**
 * Helper class that provides methods related to the Media-logic in the views
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class MediaHelper extends Helper {
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Html'];
	/**
	 * @var string $lazyLoadClass A class added to image tags to enable lazy loading
	 */
	protected string $lazyLoadClass = 'Lazyload';
	/**
	 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
	 */
	protected MediaRenderOptions $mediaRenderOptions;
	/**
	 * @var string $resizeMediaFileType The file type used for resizing media
	 */
	protected string $resizeMediaFileType;


	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->mediaRenderOptions = $this->mediaRenderOptions();

		/** @var \Twig\Environment $twig */
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$twig = $this->getView()->getTwig();
		$twig->addGlobal('ProcessStatus', ProcessStatus::class);
		$twig->addGlobal('ResizeStrategy', ResizeStrategy::class);

		$this->resizeMediaFileType = $config['resizeMediaFileType'] ?? Configure::read('Awyiss.Media.Frontend.resizing.fileType', MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_AVIF);
	}


	/**
	 * Renders a media element with the given name.
	 * The element itself decides how to use the render options and additional options.
	 * The standard element (image/video + optional link/image) will use the mediaRenderOptions
	 * as a basis for visible element and use additional settings in `$options['media']`, if set.
	 * The options for the optional link/lightbox can be modified
	 * via the `$options['lightboxMedia']` setting.
	 *
	 * @param string $name
	 * @param array|null $mediaAssignments
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @param array<string, MediaRenderOptions|array> $options
	 * @return string
	 */
	public function element(string $name, ?array $mediaAssignments, ?MediaRenderOptions $mediaRenderOptions = null, array $options = []): string {
		if (!isset($mediaAssignments[ $name ])) {
			return '';
		}

		return $this->getView()->element('media/' . Inflector::underscore($name), [
			'mediaAssignments' => $mediaAssignments[ $name ],
			'mediaRenderOptions' => $mediaRenderOptions ?? $this->getMediaRenderOptions(),
			'options' => $options,
			'MediaHelper' => $this,
		]);
	}


	/**
	 * Returns a style tag with the background image of the media item,
	 * resized to the column width or fixed width and height,
	 * using the provided selector.
	 *
	 * If a background color is set, it will be used as the background color
	 * for the selector.
	 *
	 * If responsive is set to true, the style tag will include media queries
	 * for the breakpoints set in the media render options.
	 *
	 * All rules will include a custom property `--backgroundImageHeight` with
	 * the set or calculated height of the image.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @return string
	 */
	public function background(Media $media, ?MediaRenderOptions $mediaRenderOptions = null): string {
		$mediaRenderOptions ??= $this->getMediaRenderOptions();

		if (!$mediaRenderOptions->getSelector()) {
			throw new InvalidArgumentException('No selector provided.');
		}

		$backgroundColorStyle = $this->getBackgroundColorStyle($mediaRenderOptions, $media->averageColor, $media->focusPoint);

		// If the media item is not an image and the preview is not yet created,
		// return a background color style if the background color is set
		if (!$media->isImage() && $media->preview !== ProcessStatus::Success) {
			if (!$backgroundColorStyle) {
				return '';
			}

			$nonce = $this->getView()->getRequest()->getAttribute('cspStyleNonce') ?: '';
			if ($nonce) {
				$nonce = ' nonce="' . $nonce . '"';
			}

			return '<style' . $nonce . '>' . $mediaRenderOptions->getSelector() . ' { ' . $backgroundColorStyle . ' }</style>';
		}

		$file = $this->getMediaResizedImage($media, $mediaRenderOptions);

		$path = $file?->path;
		if (!$path) {
			$path = $media->isImage() ? $this->getTargetPath($media) : $media->previewPath;
		}

		$aspectRatio = 1;
		if ($file?->realWidth && $file?->realHeight) {
			$width = $file?->realWidth;
			$height = $file?->realHeight;
		}
		elseif ($file?->width && $file?->height) {
			$width = $file?->width;
			$height = $file?->height;
		}
		else {
			$width = $media->width;
			$height = $media->height;
		}
		if ($width && $height) {
			$aspectRatio = round($width / $height, 2);
		}

		return $this->getBackgroundStyleTag($file, $media, $mediaRenderOptions, $path, $aspectRatio, $backgroundColorStyle);
	}


	/**
	 * Returns a html tag, depending on the type of media.
	 *
	 * - For images, the tag will be a picture tag if responsive is set to true.
	 *  In both cases, the image will be resized to the column width or fixed width and height
	 *  and have their source set as a data-attribute to be lazy-loaded.
	 *  For non-js users, the returned string will contain a noscript tag with a simple image tag.
	 * - For svg files, the tag will be an img tag.
	 *  If you want to display the svg directly, use the `contents` method.
	 * - For videos, the tag will be a video tag using the preview (if available) as the poster.
	 * - For audio files, the tag will be an audio tag.
	 *  - For other files, the result will be empty.
	 *
	 * If a background color is set, it will be used as the background color for the tag,
	 * using a random generated id inside a style-tag.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @param bool $allowPreview
	 * @return string
	 * @throws \Exception
	 */
	public function htmlTag(Media $media, ?MediaRenderOptions $mediaRenderOptions = null, bool $allowPreview = true): string {
		if (
			!$media->isImage() &&
			$media->mimeType !== 'image/svg+xml' &&
			!$media->isVideo() &&
			!$media->isAudio() &&
			!($allowPreview && $media->preview === ProcessStatus::Success)
		) {
			return '';
		}

		$mediaRenderOptions ??= $this->getMediaRenderOptions();

		if ($media->isAudio()) {
			return $this->audioTag($media, $mediaRenderOptions);
		}

		if ($media->isVideo()) {
			return $this->videoTag($media, $mediaRenderOptions);
		}

		if (
			$media->mimeType === 'image/svg+xml' ||
			!$mediaRenderOptions->getResponsive()
		) {
			return $this->imageTag($media, $mediaRenderOptions);
		}

		return $this->pictureTag($media, $mediaRenderOptions);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @param bool $allowVideoTag
	 * @return string
	 * @throws \Exception
	 */
	public function audioTag(Media $media, ?MediaRenderOptions $mediaRenderOptions, bool $allowVideoTag = true): string {
		if (!in_array($media->mimeType, ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm'], true)) {
			return '';
		}

		$mediaRenderOptions ??= $this->getMediaRenderOptions();

		$attributes = $mediaRenderOptions->getAttributes();
		$attributes += [
			'controls' => true,
			'preload' => 'metadata',
		];

		$attributesString = $this->Html->templater()->formatAttributes($attributes);

		$sources = $subtitles = '';

		/** @var \Awyiss\Model\Entity\Media $alternative */
		foreach (($media->findAlternatives() ?? []) as $alternative) {
			// If the mimetype of the alternative is an audio file, set the source
			if ($alternative->isAudio()) {
				$sources .= PHP_EOL . '<source src="' . $alternative->path . '" type="' . $alternative->mimeType . '">';
				continue;
			}

			// If the mimetype of the alternative is a subtitle, set the source
			$subtitles = $this->getSubtitles($alternative, $subtitles);
		}

		if ($subtitles && $allowVideoTag) {
			return '<video' . $attributesString . '><source src="' . $media->path . '" type="' . $media->mimeType . '">' . $sources . $subtitles . '</video>';
		}

		return '<audio' . $attributesString . '><source src="' . $media->path . '" type="' . $media->mimeType . '">' . $sources . $subtitles . '</audio>';
	}


	/**
	 * Returns an image tag, resized to the column width or fixed width and height.
	 *
	 * If a background color is set, it will be used as the background color for the tag,
	 * using a random generated id inside a style-tag.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @param bool $allowPreview
	 * @return string
	 */
	public function imageTag(Media $media, ?MediaRenderOptions $mediaRenderOptions = null, bool $allowPreview = true): string {
		if (
			!$media->isImage() &&
			$media->mimeType !== 'image/svg+xml' &&
			!($allowPreview && $media->preview === ProcessStatus::Success)
		) {
			return '';
		}

		$mediaRenderOptions ??= $this->getMediaRenderOptions();

		$attributes = $mediaRenderOptions->getAttributes();
		$attributes += [
			'alt' => $media->alt ?: '',
		];

		$attributes['id'] ??= 'Image-' . substr(hash('xxh64', $media->name . serialize($mediaRenderOptions)), 0, 15);

		if (!$attributes['alt']) {
			$attributes['alt'] = $media->name;
			$attributes['aria-hidden'] = 'true';
		}

		if ($media->mimeType === 'image/svg+xml') {
			// If the <svg> tag has a `data-force-inline="true"` attribute, the svg will be inlined instead of using an <img> tag
			$contents = $this->contents($media);
			if (
				$contents &&
				preg_match('/<svg\\b[^>]*\\bdata-force-inline\\s*=\\s*(["\'])true\\1/i', $contents)
			) {
				return $contents;
			}

			$attributes += [
				'width' => $media->width,
				'height' => $media->height,
			];

			// If there's a g#AWYISS_SVG_ID in the SVG, return an <svg> tag with a use statement
			if (
				$contents &&
				preg_match('/<g\\b[^>]*\\bid\\s*=\\s*(["\'])AWYISS_SVG_ID\\1/i', $contents)
			) {
				// Take the viewbox from the SVG, if it exists
				$attributes['viewBox'] = preg_match('/<svg\\b[^>]*\\bviewBox\\s*=\\s*(["\'])([^"\']+)\\1/i', $contents, $matches) ? $matches[2] : '0 0 ' . $media->width . ' ' . $media->height;
				$attributes['xmlns'] = 'http://www.w3.org/2000/svg';
				$attributes['preserveAspectRatio'] = 'xMidYMid meet';
				$attributes['id'] ??= 'Image-' . substr(hash('xxh64', $media->name . serialize($mediaRenderOptions)), 0, 15);

				$placeholderStyleTag = $this->getPlaceholderStyleTag(
					$attributes['id'],
					$attributes['width'],
					$attributes['height'],
					null,
					$mediaRenderOptions
				);

				return '<svg' . $this->Html->templater()->formatAttributes($attributes) . '><use xlink:href="' . $media->path . '#AWYISS_SVG_ID"></use></svg>' . PHP_EOL .
					$placeholderStyleTag . PHP_EOL;
			}

			return $this->simpleImageTag($media->path, $attributes, $media, $mediaRenderOptions);
		}

		// If responsive is set, use the column width
		$file = $this->getMediaResizedImage($media, $mediaRenderOptions);

		$path = $file?->path;
		if (!$path || $file->status !== ProcessStatus::Success) {
			$path = $media->isImage() ? $this->getTargetPath($media) : $media->previewPath;
		}

		if ($file?->realWidth && $file?->realHeight) {
			$attributes['width'] = $file?->realWidth;
			$attributes['height'] = $file?->realHeight;
		}
		elseif ($file?->width && $file?->height) {
			$attributes['width'] = $file?->width;
			$attributes['height'] = $file?->height;
		}
		elseif ($media->width && $media->height) {
			if (
				!empty($attributes['width']) &&
				empty($attributes['height'])
			) {
				// Use the media's original aspect ratio
				$attributes['height'] = round($media->height * $attributes['width'] / $media->width);
			}
			elseif (
				empty($attributes['width']) &&
				!empty($attributes['height'])
			) {
				// Use the aspect ratio
				$attributes['width'] = round($media->width * $attributes['height'] / $media->height);
			}
			else {
				$attributes['width'] = $media->width;
				$attributes['height'] = $media->height;
			}
		}

		return $this->simpleImageTag($path, $attributes, $media, $mediaRenderOptions);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @param bool $allowPreview
	 * @return string
	 */
	public function pictureTag(Media $media, ?MediaRenderOptions $mediaRenderOptions, bool $allowPreview = true): string {
		if (
			!$media->isImage() &&
			$media->mimeType !== 'image/svg+xml' &&
			!($allowPreview && $media->preview === ProcessStatus::Success)
		) {
			return '';
		}

		$mediaRenderOptions ??= $this->getMediaRenderOptions();

		$imageTag = $this->imageTag($media, $mediaRenderOptions);

		$breakpointFiles = $this->getResponsiveImages($media, $mediaRenderOptions, true);
		$breakpointFiles = array_reverse($breakpointFiles, true);

		$sources = PHP_EOL;
		$sourceAttribute = $mediaRenderOptions->getLazyload() ? 'data-srcset' : 'srcset';
		foreach ($breakpointFiles as $breakpoint => $file) {
			if (is_string($breakpoint)) {
				continue;
			}

			$path = $file->path;

			$mimeType = $media->mimeType;
			if (str_ends_with($path, 'avif')) {
				$mimeType = 'image/avif';
			}
			elseif (str_ends_with($path, 'webp')) {
				$mimeType = 'image/webp';
			}

			if (isset($breakpointFiles[ $breakpoint . 'x2' ])) {
				$path .= ' 1x, ' . $breakpointFiles[ $breakpoint . 'x2' ]->path . ' 2x';
			}

			$mediaQuery = '(width <= ' . $breakpoint . 'px)';
			$sources .= '<source media="' . $mediaQuery . '" ' . $sourceAttribute . '="' . $path . '" type="' . $mimeType . '">' . PHP_EOL;
		}

		return '<picture>' . $sources . $imageTag . '</picture>';
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @return string
	 * @throws \Exception
	 */
	public function videoTag(Media $media, ?MediaRenderOptions $mediaRenderOptions): string {
		if (!in_array($media->mimeType, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
			return '';
		}

		$mediaRenderOptions ??= $this->getMediaRenderOptions();

		$attributes = $mediaRenderOptions->getAttributes();
		$attributes += [
			'autoplay' => false,
			'controls' => true,
			'loop' => false,
			'muted' => false,
			'preload' => 'metadata',
			'poster' => $media->previewPath,
		];
		$attributes['class'] = trim(($mediaRenderOptions->getLazyload() ? $this->lazyLoadClass : '') . ' ' . ($attributes['class'] ?? ''));

		if ($mediaRenderOptions->getLazyload()) {
			$attributes['data-poster'] = $attributes['poster'];
			unset($attributes['poster']);
		}

		$attributesString = $this->Html->templater()->formatAttributes($attributes);

		$path = $media->path;

		$sources = $subtitles = '';

		$srcAttribute = 'src';
		if ($mediaRenderOptions->getLazyload()) {
			$srcAttribute = 'data-src';
		}

		/** @var \Awyiss\Model\Entity\Media $alternative */
		foreach (($media->findAlternatives() ?? []) as $alternative) {
			// If the mimetype of the alternative is a video, set the source
			if ($alternative->isVideo()) {
				$sources .= PHP_EOL . '<source ' . $srcAttribute . '="' . $alternative->path . '" type="' . $alternative->mimeType . '">';
				continue;
			}

			$subtitles = $this->getSubtitles($alternative, $subtitles);
		}

		return '<video' . $attributesString . '><source ' . $srcAttribute . '="' . $path . '" type="' . $media->mimeType . '">' . $sources . $subtitles . '</video>';
	}


	/**
	 * @param string|null $link
	 * @return bool
	 */
	public function isVideoLink(?string $link): bool {
		if (!$link) {
			return false;
		}

		$youtubePattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

		return match (true) {
			preg_match($youtubePattern, $link) && !str_contains($link, '&list=') => true,
			str_contains($link, 'vimeo') => true,
			default => false,
		};
	}


	/**
	 * Return the contents of the media item
	 * Currently only for SVG files
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @return string|null
	 */
	public function contents(Media $media): ?string {
		if ($media->mimeType == 'image/svg+xml') {
			return file_exists($media->pathAbsolute) ? file_get_contents($media->pathAbsolute) : null;
		}

		return null;
	}


	/**
	 * @return \Awyiss\Utility\Media\MediaRenderOptions
	 */
	public function getMediaRenderOptions(): MediaRenderOptions {
		return $this->mediaRenderOptions;
	}


	/**
	 * Return the media render options.
	 *
	 * @param bool $allowUpscale
	 * @param float|int|null $aspectRatio
	 * @param array $attributes
	 * @param string|false|null $backgroundColor
	 * @param float|int $baseWidth
	 * @param array<float, array{baseWidth: float|null, breakpoint: float, columnWidth: float|null, width: float|null, height: float|null, resizeStrategy: \Awyiss\Model\Enum\ResizeStrategy|null}> $breakpoints
	 * @param float|int $columnWidth
	 * @param bool $lazyload
	 * @param float|int|null $height
	 * @param float|null $minBreakpoint
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $resizeStrategy
	 * @param bool $responsive
	 * @param string|null $selector
	 * @param float|int|false|null $singleColumnBreakpoint
	 * @param bool $strictSize
	 * @param float|int|null $width
	 * @return \Awyiss\Utility\Media\MediaRenderOptions
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function mediaRenderOptions(
		bool $allowUpscale = false,
		float|int|null $aspectRatio = null,
		array $attributes = [],
		string|false|null $backgroundColor = null,
		float|int $baseWidth = 3840,
		array $breakpoints = [],
		float|int $columnWidth = 100.00,
		bool $lazyload = true,
		float|int|null $height = null,
		?float $minBreakpoint = null,
		ResizeStrategy|string|int $resizeStrategy = ResizeStrategy::Contain,
		bool $responsive = true,
		?string $selector = null,
		float|int|false|null $singleColumnBreakpoint = null,
		bool $strictSize = false,
		float|int|null $width = null,
	): MediaRenderOptions {
		return new MediaRenderOptions(...get_defined_vars());
	}


	/**
	 * Store the media items in the static storage.
	 * For integer values, the items will be fetched from the database.
	 *
	 * @param \Cake\Collection\Collection|\Cake\Datasource\Paging\PaginatedResultSet|array $mediaItems
	 * @return void
	 */
	public function storeItems(Collection|PaginatedResultSet|array $mediaItems): void {
		if ($mediaItems instanceof Collection || $mediaItems instanceof PaginatedResultSet) {
			$mediaItems = $mediaItems->toArray();
		}

		ResizedImageManager::setMediaItems($mediaItems);
	}


	/**
	 * Return the preview media element for the given media item
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param array $viewData
	 * @return string
	 */
	public function preview(Media $media, array $viewData = []): string {
		$defaults = [
			'resize' => null,
		];

		$viewData = array_merge($defaults, $viewData, [
			'mediaItem' => $media,
			'MediaHelper' => $this,
		]);

		return $this->getView()->element('media/preview', $viewData);
	}


	/**
	 * Return the resized media element for the given media item
	 * If `strictSize` is set to false (default), an image will be
	 * returned that might be larger than the requested size to not
	 * create versions of it for approximately the same size.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param float|int|null $aspectRatio
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param string|null $format
	 * @param bool $strictSize
	 * @param bool $allowUpscale
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $renderOptions
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpFeatureEnvyLocalInspection
	 */
	public function resize(
		Media $media,
		?MediaRenderOptions $renderOptions = null,
		float|int|null $width = null,
		float|int|null $height = null,
		float|int|null $aspectRatio = null,
		ResizeStrategy|string|int $strategy = ResizeStrategy::Contain,
		?string $format = null,
		bool $strictSize = false,
		bool $allowUpscale = false,
	): ?MediaResizedImage {
		if (!$format) {
			$format = $this->resizeMediaFileType;
		}

		if ($format === MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_MATCH_SOURCE) {
			$format = $media->isImage() ? $media->extension : 'jpg';
		}

		if (!$renderOptions) {
			$vars = get_defined_vars();
			unset($vars['renderOptions']);

			return ResizedImageManager::resize(...$vars);
		}

		return ResizedImageManager::resize(
			$media,
			$renderOptions->getWidth(),
			$renderOptions->getHeight(),
			$renderOptions->getAspectRatio(),
			$renderOptions->getResizeStrategy(),
			$format,
			$renderOptions->getStrictSize(),
			$renderOptions->getAllowUpscale(),
		);
	}


	/**
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return int
	 */
	public function getPixelColumnWidth(MediaRenderOptions $mediaRenderOptions): int {
		$baseWidth = $mediaRenderOptions->getBaseWidth();
		$columnWidth = $mediaRenderOptions->getColumnWidth();

		if (!$baseWidth) {
			throw new InvalidArgumentException('Base width must be set to calculate the pixel width of a column.');
		}

		if (!$columnWidth) {
			throw new InvalidArgumentException('Column width must be set to calculate the pixel width of a column.');
		}

		return (int)($baseWidth * $columnWidth / 100);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param bool $removeDuplicates
	 * @param bool $remove2xDuplicates
	 * @return array<string|int, \Awyiss\Model\Entity\MediaResizedImage|\Awyiss\Model\Entity\Media>
	 */
	public function getResponsiveImages(Media $media, MediaRenderOptions $mediaRenderOptions, bool $removeDuplicates = false, bool $remove2xDuplicates = false): array {
		if (
			// If the media item is an SVG,
			$media->mimeType === 'image/svg+xml' ||
			// or if it requires a preview (since it's not an image) but the preview is not yet created, return an empty array
			($media->preview !== ProcessStatus::NotRequired && $media->preview !== ProcessStatus::Success)
		) {
			return [];
		}

		$breakpoints = array_reverse($mediaRenderOptions->getBreakpoints());
		$breakpoints = $this->addSingleColumnBreakpoint($breakpoints, $mediaRenderOptions);

		if ($mediaRenderOptions->getInclude2x()) {
			// Add 2x breakpoints for all breakpoints
			foreach ($breakpoints as $breakpoint) {
				$breakpoint['is2x'] = true;
				$breakpoints[] = $breakpoint;
			}

			// Sort breakpoints by breakpoint value
			usort($breakpoints, function (array $a, array $b): int {
				if ($a['breakpoint'] === $b['breakpoint']) {
					return $a['is2x'] <=> $b['is2x'];
				}

				return $b['breakpoint'] <=> $a['breakpoint'];
			});
		}

		$breakpointFiles = $this->getBreakpointFiles($media, $mediaRenderOptions, $breakpoints, $removeDuplicates, $remove2xDuplicates);

		if (!$breakpointFiles || !$mediaRenderOptions->getInclude2x()) {
			return $breakpointFiles;
		}

		uksort($breakpointFiles, function ($a, $b) {
			if (is_string($a) && is_string($b)) {
				return (int)$b <=> (int)$a;
			}

			if (is_string($a)) {
				return 1;
			}
			if (is_string($b)) {
				return -1;
			}

			return $b <=> $a;
		});

		return $breakpointFiles;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $fields
	 * @return void
	 */
	public function rebuildSimpleImageTags(Entity $entity, array $fields = []): void {
		$imageHandlerClass = $this->getImageHandlerClass();

		$imageHandlerClass::rebuildSimpleImageTags($entity, $fields);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @param string|null $value
	 * @return string|null
	 */
	public function rebuildSimpleImageTagsInField(Entity $entity, string $field, ?string $value = null): ?string {
		$imageHandlerClass = $this->getImageHandlerClass();

		return $imageHandlerClass::rebuildSimpleImageTagsInField($entity, $field, $value);
	}


	/**
	 * @param string|null $value
	 * @param array $media
	 * @param bool $absolutePath
	 * @return string|null
	 */
	public function rebuildSimpleImageTagsInText(?string $value, array $media, bool $absolutePath = false): ?string {
		if (!$value) {
			return $value;
		}

		$imageHandlerClass = $this->getImageHandlerClass();

		return $imageHandlerClass::rebuildSimpleImageTagsInText($value, $media, $absolutePath);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $fields
	 * @return void
	 * @throws \Exception
	 */
	public function replaceCustomImageTags(Entity $entity, MediaRenderOptions $mediaRenderOptions, array $fields = []): void {
		$imageHandlerClass = $this->getImageHandlerClass();

		$imageHandlerClass::replaceCustomImageTags($entity, $this->getView(), $mediaRenderOptions, $fields);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string $field
	 * @param string|null $value
	 * @return string|null
	 * @throws \Exception
	 */
	public function replaceCustomImageTagsInField(Entity $entity, MediaRenderOptions $mediaRenderOptions, string $field, ?string $value = null): ?string {
		$imageHandlerClass = $this->getImageHandlerClass();

		return $imageHandlerClass::replaceCustomImageTagsInField($entity, $this->getView(), $mediaRenderOptions, $field, $value);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $breakpoints
	 * @param bool $removeDuplicates
	 * @param bool $remove2xDuplicates
	 * @return array
	 */
	protected function getBreakpointFiles(Media $media, MediaRenderOptions $mediaRenderOptions, array $breakpoints, bool $removeDuplicates, bool $remove2xDuplicates = false): array {
		$breakpointFiles = [];

		$file = $this->getMediaResizedImage($media, $mediaRenderOptions);

		$lastPath = $last2xPath = $file?->path;
		if (!$lastPath || $file->status !== ProcessStatus::Success) {
			$lastPath = $last2xPath = $media->isImage() ? $this->getTargetPath($media) : $media->previewPath;
		}

		$overrideOptions = [
			'aspectRatio' => MediaRenderOptions::PRESERVE_VALUE,
			'baseWidth' => MediaRenderOptions::PRESERVE_VALUE,
			'columnWidth' => MediaRenderOptions::PRESERVE_VALUE,
			'height' => MediaRenderOptions::PRESERVE_VALUE,
			'resizeStrategy' => MediaRenderOptions::PRESERVE_VALUE,
			'width' => MediaRenderOptions::PRESERVE_VALUE,
		];

		$paths = [
			'1x' => [$lastPath],
			'2x' => [$last2xPath],
		];

		$baseMediaRenderOptions = $mediaRenderOptions;
		foreach ($breakpoints as $breakpoint) {
			if ($breakpoint['breakpoint'] >= $baseMediaRenderOptions->getBaseWidth()) {
				// Even if the breakpoint is too large to be considered, we need to remember the override options for the next iteration
				$overrideOptions = $this->getOverrideOptions($overrideOptions, $breakpoint);

				continue;
			}

			$mediaRenderOptions = $this->getBreakpointRenderOptions($breakpoint, $baseMediaRenderOptions, $overrideOptions);

			// Remember the new override options for the next iteration
			$overrideOptions = $this->getOverrideOptions($overrideOptions, $breakpoint);

			$is2x = ($breakpoint['is2x'] ?? false) === true;
			$with = [];
			if ($is2x) {
				if ($mediaRenderOptions->getWidth()) {
					$with['width'] = $mediaRenderOptions->getWidth() * 2;
				}
				if ($mediaRenderOptions->getHeight()) {
					$with['height'] = $mediaRenderOptions->getHeight() * 2;
				}
			}
			if ($with) {
				$mediaRenderOptions = $mediaRenderOptions->with($with);
			}

			$resizedImage = $this->resize(
				$media,
				renderOptions: $mediaRenderOptions,
			);

			if ($resizedImage && $resizedImage->status === ProcessStatus::Success) {
				$path = $resizedImage->path;
			}
			else {
				$path = $media->isImage() ? $this->getTargetPath($media) : $media->previewPath;
			}

			if (
				!$is2x &&
				(
					!$removeDuplicates ||
					!in_array($path, $paths['1x'], true)
				)
			) {
				$breakpointFiles[ $breakpoint['breakpoint'] ] = $resizedImage ?? $media;
				$paths['1x'] = [$path];
			}
			elseif (
				$is2x &&
				(
					!$remove2xDuplicates ||
					!in_array($path, $paths['2x'], true)
				)
			) {
				$breakpointFiles[ $breakpoint['breakpoint'] . 'x2' ] = $resizedImage ?? $media;
				$paths['2x'] = [$path];
			}
		}

		return $breakpointFiles;
	}


	/**
	 * Return the background color style for the media item
	 *
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string|null $averageColor
	 * @param array|string|null $focusPoint
	 * @return string
	 */
	protected function getBackgroundColorStyle(MediaRenderOptions $mediaRenderOptions, ?string $averageColor, string|array|null $focusPoint = null): string {
		$backgroundColor = null;
		if ($mediaRenderOptions->getBackgroundColor() !== false) {
			$backgroundColor = $mediaRenderOptions->getBackgroundColor();

			if (!$backgroundColor && $averageColor) {
				$backgroundColor = '#' . $averageColor;
			}
		}

		$backgroundColorStyle = '';
		if ($backgroundColor) {
			$backgroundColorStyle = ' background-color:' . $backgroundColor . ';';
		}

		if ($focusPoint) {
			$backgroundColorStyle .= ' --preferredPosition:' . $this->getFocusPointCssValue($focusPoint) . ';';
		}

		return trim($backgroundColorStyle);
	}


	/**
	 * @param array $breakpointOptions
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $overrideOptions
	 * @return \Awyiss\Utility\Media\MediaRenderOptions
	 */
	protected function getBreakpointRenderOptions(array $breakpointOptions, MediaRenderOptions $mediaRenderOptions, array $overrideOptions): MediaRenderOptions {
		$optionKeys = [
			'aspectRatio',
			'baseWidth',
			'columnWidth',
			'height',
			'resizeStrategy',
			'width',
		];

		$options = array_intersect_key($breakpointOptions, array_flip($optionKeys));

		foreach ($overrideOptions as $key => $value) {
			if (
				$value === MediaRenderOptions::PRESERVE_VALUE ||
				!in_array($key, $optionKeys, true)
			) {
				continue;
			}

			if (array_key_exists($key, $options) && $options[ $key ] !== MediaRenderOptions::PRESERVE_VALUE) {
				continue;
			}

			$options[ $key ] = $value;
		}

		if (empty($options['baseWidth'])) {
			$options['baseWidth'] = $breakpointOptions['breakpoint'];
		}

		$options = array_filter($options, fn($value) => $value !== MediaRenderOptions::PRESERVE_VALUE);

		$mediaRenderOptions = $mediaRenderOptions->with($options);

		if (
			($breakpointOptions['width'] ?? MediaRenderOptions::PRESERVE_VALUE) === MediaRenderOptions::PRESERVE_VALUE &&
			($breakpointOptions['height'] ?? MediaRenderOptions::PRESERVE_VALUE) === MediaRenderOptions::PRESERVE_VALUE
		) {
			$options['width'] = $this->getPixelColumnWidth($mediaRenderOptions);
			$mediaRenderOptions = $mediaRenderOptions->withWidth($options['width']);
		}

		return $mediaRenderOptions;
	}


	/**
	 * @param string $id
	 * @param float $width
	 * @param float $height
	 * @param string|null $averageColor
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array|string|null $focusPoint
	 * @return string
	 */
	protected function getPlaceholderStyleTag(
		string $id,
		float $width,
		float $height,
		?string $averageColor,
		MediaRenderOptions $mediaRenderOptions,
		string|array|null $focusPoint = null
	): string {
		$backgroundColor = null;
		if ($mediaRenderOptions->getBackgroundColor() !== false) {
			$backgroundColor = $mediaRenderOptions->getBackgroundColor();

			if (!$backgroundColor && $averageColor) {
				$backgroundColor = '#' . $averageColor;
			}
		}

		$backgroundColorStyle = '';
		if ($backgroundColor) {
			$backgroundColorStyle = '--imageBackgroundColor:' . $backgroundColor . ';';
		}
		if ($focusPoint) {
			$backgroundColorStyle .= ' --preferredPosition:' . $this->getFocusPointCssValue($focusPoint) . ';';
		}

		$nonce = $this->getView()->getRequest()->getAttribute('cspStyleNonce') ?: '';
		if ($nonce) {
			$nonce = ' nonce="' . $nonce . '"';
		}

		/** @noinspection CssInvalidHtmlTagReference, CssUnresolvedCustomProperty */
		return '<style' . $nonce . '>#' . $id . ', #' . $id . '-NoScript { --imageAspectRatio: ' . round($width / $height, 2) . ';' . $backgroundColorStyle . ' }</style>';
	}


	/**
	 * @param string $path
	 * @param array $attributes
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return string
	 */
	protected function simpleImageTag(string $path, array $attributes, Media $media, MediaRenderOptions $mediaRenderOptions): string {
		$noScriptAttributes = $attributes;

		$attributes['class'] = trim(($mediaRenderOptions->getLazyload() ? $this->lazyLoadClass : '') . ' ' . ($attributes['class'] ?? ''));
		$attributesString = $this->Html->templater()->formatAttributes($attributes);

		if (isset($noScriptAttributes['id'])) {
			$noScriptAttributes['id'] .= '-NoScript';
		}
		$noScriptAttributesString = $this->Html->templater()->formatAttributes($noScriptAttributes);

		$width = $attributes['width'] ?? $this->getPixelColumnWidth($mediaRenderOptions);
		if (is_string($width)) {
			$width = (float)$width;
		}

		$height = $attributes['height'] ?? $width;
		if (is_string($height)) {
			$height = (float)$height;
		}

		$placeholderStyleTag = $this->getPlaceholderStyleTag(
			$attributes['id'],
			$width,
			$height,
			$media->averageColor,
			$mediaRenderOptions,
			$media->focusPoint
		);

		$srcSet = $noScriptSrcSet = '';
		$sourceAttribute = $mediaRenderOptions->getLazyload() ? 'data-src' : 'src';
		if ($mediaRenderOptions->getInclude2x()) {
			$x2File = $this->get2xFile($media, $mediaRenderOptions);
			if ($x2File && $x2File->status === ProcessStatus::Success) {
				$srcSet = ' ' . $sourceAttribute . 'set="' . $x2File->path . ' 2x"';
				$noScriptSrcSet = ' srcset="' . $x2File->path . ' 2x"';
			}
		}

		/** @noinspection HtmlRequiredAltAttribute */
		return '<img ' . $sourceAttribute . '="' . $path . '"' . $srcSet . $attributesString . '>' . PHP_EOL .
			($mediaRenderOptions->getLazyload() ? '<noscript><img src="' . $path . '"' . $noScriptSrcSet . $noScriptAttributesString . '></noscript>' . PHP_EOL : '') .
			$placeholderStyleTag . PHP_EOL;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	protected function getMediaResizedImage(Media $media, ?MediaRenderOptions $mediaRenderOptions): ?MediaResizedImage {
		// If responsive is set, use the column width
		if ($mediaRenderOptions->getResponsive()) {
			$width = $this->getPixelColumnWidth($mediaRenderOptions);

			return $this->resize($media, renderOptions: $mediaRenderOptions->withWidth($width));
		}

		// If the width and height are not set, use the column width
		if (!$mediaRenderOptions->getWidth() && !$mediaRenderOptions->getHeight()) {
			if (!$mediaRenderOptions->getColumnWidth()) {
				return null;
			}

			$width = $this->getPixelColumnWidth($mediaRenderOptions);

			return $this->resize($media, renderOptions: $mediaRenderOptions->withWidth($width));
		}

		return $this->resize($media, renderOptions: $mediaRenderOptions);
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage|null $resizedFile
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string|null $filePath
	 * @param float|int $aspectRatio
	 * @param string $backgroundColorStyle
	 * @return string
	 */
	protected function getBackgroundStyleTag(
		?MediaResizedImage $resizedFile,
		Media $media,
		MediaRenderOptions $mediaRenderOptions,
		?string $filePath,
		float|int $aspectRatio,
		string $backgroundColorStyle
	): string {
		$nonce = $this->getView()->getRequest()->getAttribute('cspStyleNonce') ?: '';
		if ($nonce) {
			$nonce = ' nonce="' . $nonce . '"';
		}

		/** @noinspection CssUnknownTarget */
		$output = '<style' . $nonce . '>';
		$output .= $mediaRenderOptions->getSelector() . ' { --backgroundAspectRatio:' . $aspectRatio . ';';
		$output .= ' --backgroundImageHeight:' . ($resizedFile?->realHeight ?? $resizedFile?->height ?? $media->height) . 'px;';
		$output .= ' background-image:url(\'' . $filePath . '\');';
		$output .= $backgroundColorStyle . ' }';

		$breakpointFiles = [];
		if ($mediaRenderOptions->getResponsive()) {
			$breakpointFiles = $this->getResponsiveImages($media, $mediaRenderOptions, true, true);
		}
		elseif ($mediaRenderOptions->getInclude2x()) {
			$x2File = $this->get2xFile($media, $mediaRenderOptions);

			if ($x2File) {
				$output = PHP_EOL . '@media only screen and (min-resolution: 192dpi) { ';
				$output .= $mediaRenderOptions->getSelector() . ' {';
				$output .= ' background-image:url(\'' . $x2File->path . '\'); } }';
			}
		}

		foreach ($breakpointFiles as $breakpoint => $file) {
			$filePath = $file->path;

			$aspectRatio = 1;
			$width = $height = null;
			if ($file->realWidth && $file->realHeight) {
				$width = $file?->realWidth;
				$height = $file?->realHeight;
			}
			elseif ($file->width && $file->height) {
				$width = $file?->width;
				$height = $file?->height;
			}

			if ($width && $height) {
				$aspectRatio = round($width / $height, 2);
			}

			$is2x = is_string($breakpoint) && str_ends_with($breakpoint, 'x2');
			$breakpoint = (int)$breakpoint;

			if ($is2x) {
				$output .= PHP_EOL . '@media only screen and (width <= ' . $breakpoint . 'px) ';
				$output .= 'and (min-resolution: 192dpi) { ';
			}
			else {
				$output .= PHP_EOL . '@media (width <= ' . $breakpoint . 'px) { ';
			}

			$output .= $mediaRenderOptions->getSelector() . ' {';
			if ($width && $height && !$is2x) {
				$output .= ' --backgroundAspectRatio:' . $aspectRatio . ';';
				$output .= ' --backgroundImageHeight:' . $height . 'px;';
			}
			$output .= ' background-image:url(\'' . $filePath . '\'); } }';
		}

		$output .= '</style>';

		return $output;
	}


	/**
	 * Check if there is a breakpoint with the same value as the single column breakpoint.
	 * If there is, use its current values and set the column width to 100 (if not set),
	 * otherwise add a new breakpoint with the single column breakpoint value and a column width of 100
	 *
	 * @param array $breakpoints
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return array
	 */
	protected function addSingleColumnBreakpoint(array $breakpoints, MediaRenderOptions $mediaRenderOptions): array {
		if (!$mediaRenderOptions->getSingleColumnBreakpoint()) {
			return $breakpoints;
		}

		$singleColumnBreakpoint = $mediaRenderOptions->getSingleColumnBreakpoint();

		$hasSingleColumnBreakpoint = false;
		foreach ($breakpoints as &$breakpoint) {
			if ($breakpoint['breakpoint'] !== $singleColumnBreakpoint) {
				continue;
			}

			$hasSingleColumnBreakpoint = true;

			if (($breakpoint['columnWidth'] ?? MediaRenderOptions::PRESERVE_VALUE) === MediaRenderOptions::PRESERVE_VALUE) {
				$breakpoint['columnWidth'] = 100;
			}

			break;
		}
		unset($breakpoint);

		if (!$hasSingleColumnBreakpoint) {
			$breakpoints[] = $mediaRenderOptions::normalizeBreakpoint($singleColumnBreakpoint, [
				'columnWidth' => 100,
			]);
		}

		// Reorder the breakpoints by breakpoint value
		usort($breakpoints, function (array $a, array $b): int {
			return $b['breakpoint'] <=> $a['breakpoint'];
		});

		return $breakpoints;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param string $subtitles
	 * @return string
	 * @throws \Exception
	 */
	protected function getSubtitles(Media $media, string $subtitles): string {
		// If the mimetype of the alternative is a subtitle, set the source
		if ($media->mimeType === 'text/vtt') {
			// Source language is the last two characters of the filename
			$sourceLang = substr($media->cleanName, -2);

			// If the source language is the current language, set it as default
			$default = $sourceLang === (LocaleMiddleware::getLanguage()->shortcode ?? '') ? ' default' : '';

			// Add a track tag for the subtitle
			$subtitles .= PHP_EOL . '<track src="' . $media->path . '" kind="subtitles"' .
						  $default .
				' srclang="' . $sourceLang . '" label="' . ($media->alt ?? locale_get_display_language($sourceLang)) . '">';
		}

		return $subtitles;
	}


	/**
	 * Return the target path for the media item,
	 * depending on the configured media type.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @return string|null
	 */
	protected function getTargetPath(Media $media): ?string {
		if (
			$this->resizeMediaFileType === MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_AVIF &&
			in_array($media->avif, [ProcessStatus::Success, ProcessStatus::NotRequired], true)
		) {
			return $media->avifPath;
		}

		if (
			$this->resizeMediaFileType === MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_WEBP &&
			in_array($media->webp, [ProcessStatus::Success, ProcessStatus::NotRequired], true)
		) {
			return $media->webpPath;
		}

		return $media->path;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	protected function get2xFile(Media $media, MediaRenderOptions $mediaRenderOptions): ?MediaResizedImage {
		$mediaRenderOptions = $mediaRenderOptions->withBaseWidth(
			$mediaRenderOptions->getBaseWidth() * 2,
		);

		if ($mediaRenderOptions->getWidth()) {
			$mediaRenderOptions = $mediaRenderOptions->withWidth(
				$mediaRenderOptions->getWidth() * 2,
			);
		}

		if ($mediaRenderOptions->getHeight()) {
			$mediaRenderOptions = $mediaRenderOptions->withHeight(
				$mediaRenderOptions->getHeight() * 2,
			);
		}

		return $this->getMediaResizedImage($media, $mediaRenderOptions);
	}


	/**
	 * @param array|string|null $focusPoint
	 * @return string
	 */
	protected function getFocusPointCssValue(array|string|null $focusPoint = null): string {
		if (!$focusPoint) {
			return 'center';
		}

		if (is_string($focusPoint)) {
			$focusPoint = explode(',', $focusPoint);
			$focusPoint = array_map('trim', $focusPoint);
		}

		if (count($focusPoint) !== 2) {
			return 'center';
		}

		$backgroundPosition = '';
		$focusPoints = ['x' => ['left', 'center', 'right'], 'y' => ['top', 'center', 'bottom']];

		$backgroundPosition .= $focusPoints['x'][ max(0, min(2, (int)$focusPoint[1])) ];
		$backgroundPosition .= ' ';
		$backgroundPosition .= $focusPoints['y'][ max(0, min(2, (int)$focusPoint[0])) ];

		return trim($backgroundPosition);
	}


	/**
	 * @param array $overrideOptions
	 * @param array $breakpoint
	 * @return array
	 */
	protected function getOverrideOptions(array $overrideOptions, array $breakpoint): array {
		// Use the value from the breakpoint if the value is not set to preserve
		foreach ($overrideOptions as $key => $value) {
			/**
			 * If the value is not set to preserve, and the value is not equal to the current value
			 * use the value from the breakpoint.
			 * This forces breakpoints to use the value of its predecessor if no own value is set.
			 * For example:
			 * - If a `columnWidth` is set for a breakpoint, all following breakpoints will use this value instead of the
			 *   `columnWidth` of the media item.
			 * - If a `width` is set for a breakpoint, all following breakpoints will use this value instead of the
			 *  `width` of the media item.
			 */
			if (!in_array($breakpoint[ $key ], [$value, MediaRenderOptions::PRESERVE_VALUE], true)) {
				$overrideOptions[ $key ] = $breakpoint[ $key ];
			}
		}

		return $overrideOptions;
	}


	/**
	 * @return class-string<\Awyiss\Utility\Content\ImageHandler>
	 */
	protected function getImageHandlerClass(): string {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $imageHandlerClass */
		static $imageHandlerClass;

		if (!$imageHandlerClass) {
			$imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		return $imageHandlerClass;
	}
}
