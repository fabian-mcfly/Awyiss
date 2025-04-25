<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Collection\Collection;
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
	 */
	protected MediaRenderOptions $mediaRenderOptions;


	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->mediaRenderOptions = $this->mediaRenderOptions();

		/** @var \Twig\Environment $lo_twig */
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_twig = $this->getView()->getTwig();
		$lo_twig->addGlobal('ProcessStatus', ProcessStatus::class);
		$lo_twig->addGlobal('ResizeStrategy', ResizeStrategy::class);
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
		$lo_mediaRenderOptions = $mediaRenderOptions ?? $this->getMediaRenderOptions();

		if (!$lo_mediaRenderOptions->getSelector()) {
			throw new InvalidArgumentException('No selector provided.');
		}

		$ls_backgroundColorStyle = $this->getBackgroundColorStyle($lo_mediaRenderOptions, $media->averageColor);

		// If the media item is not an image and the preview is not yet created,
		// return a background color style if the background color is set
		if (!$media->isImage() && $media->preview !== ProcessStatus::Success) {
			if (!$ls_backgroundColorStyle) {
				return '';
			}

			return '<style>' . $lo_mediaRenderOptions->getSelector() . ' { ' . $ls_backgroundColorStyle . ' }</style>';
		}

		$lo_file = $this->getMediaResizedImage($media, $lo_mediaRenderOptions);

		$ls_path = $lo_file?->path;
		if (!$ls_path) {
			$ls_path = $media->isImage() ? ($media->webpPath ?? $media->path) : $media->previewPath;
		}

		$lf_aspectRatio = 1;
		if ($lo_file?->realWidth && $lo_file?->realHeight) {
			$lf_width = $lo_file?->realWidth;
			$lf_height = $lo_file?->realHeight;
		}
		elseif ($lo_file?->width && $lo_file?->height) {
			$lf_width = $lo_file?->width;
			$lf_height = $lo_file?->height;
		}
		else {
			$lf_width = $media->width;
			$lf_height = $media->height;
		}
		if ($lf_width && $lf_height) {
			$lf_aspectRatio = round($lf_width / $lf_height, 2);
		}

		return $this->getBackgroundStyleTag($lo_file, $media, $lo_mediaRenderOptions, $ls_path, $lf_aspectRatio, $ls_backgroundColorStyle);
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

		$lo_mediaRenderOptions = $mediaRenderOptions ?? $this->getMediaRenderOptions();

		if ($media->isAudio()) {
			return $this->audioTag($media, $lo_mediaRenderOptions);
		}

		if ($media->isVideo()) {
			return $this->videoTag($media, $lo_mediaRenderOptions);
		}

		if (
			$media->mimeType === 'image/svg+xml' ||
			!$lo_mediaRenderOptions->getResponsive()
		) {
			return $this->imageTag($media, $lo_mediaRenderOptions);
		}

		return $this->pictureTag($media, $lo_mediaRenderOptions);
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

		$lo_mediaRenderOptions = $mediaRenderOptions ?? $this->getMediaRenderOptions();

		$la_attributes = $lo_mediaRenderOptions->getAttributes();
		$la_attributes += [
			'controls' => true,
			'preload' => 'metadata',
		];

		$ls_attributes = $this->Html->templater()->formatAttributes($la_attributes);

		$ls_sources = $ls_subtitles = '';

		/** @var \Awyiss\Model\Entity\Media $lo_alternative */
		foreach ($media->findAlternatives() as $lo_alternative) {
			// If the mimetype of the alternative is an audio file, set the source
			if ($lo_alternative->isAudio()) {
				$ls_sources .= PHP_EOL . '<source src="' . $lo_alternative->path . '" type="' . $lo_alternative->mimeType . '">';
				continue;
			}

			// If the mimetype of the alternative is a subtitle, set the source
			$ls_subtitles = $this->getSubtitles($lo_alternative, $ls_subtitles);
		}

		if ($ls_subtitles && $allowVideoTag) {
			return '<video' . $ls_attributes . '><source src="' . $media->path . '" type="' . $media->mimeType . '">' . $ls_sources . $ls_subtitles . '</video>';
		}

		return '<audio' . $ls_attributes . '><source src="' . $media->path . '" type="' . $media->mimeType . '">' . $ls_sources . $ls_subtitles . '</audio>';
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

		$lo_mediaRenderOptions = $mediaRenderOptions ?? $this->getMediaRenderOptions();

		$la_attributes = $lo_mediaRenderOptions->getAttributes();
		$la_attributes += [
			'alt' => $media->alt ?: '',
		];

		$la_attributes['id'] ??= 'Image-' . substr(sha1($media->name . serialize($lo_mediaRenderOptions)), 0, 15);

		if ($media->mimeType === 'image/svg+xml') {
			$la_attributes += [
				'width' => $media->width,
				'height' => $media->height,
			];

			return $this->simpleImageTag($media->path, $la_attributes, $media->averageColor, $lo_mediaRenderOptions);
		}

		// If responsive is set, use the column width
		$lo_file = $this->getMediaResizedImage($media, $lo_mediaRenderOptions);

		$ls_path = $lo_file?->path;
		if (!$ls_path) {
			$ls_path = $media->isImage() ? ($media->webpPath ?? $media->path) : $media->previewPath;
		}

		if ($lo_file?->realWidth && $lo_file?->realHeight) {
			$la_attributes['width'] = $lo_file?->realWidth;
			$la_attributes['height'] = $lo_file?->realHeight;
		}
		elseif ($lo_file?->width && $lo_file?->height) {
			$la_attributes['width'] = $lo_file?->width;
			$la_attributes['height'] = $lo_file?->height;
		}
		elseif ($media->width && $media->height) {
			if (
				!empty($la_attributes['width']) &&
				empty($la_attributes['height'])
			) {
				// Use the media's original aspect ratio
				$la_attributes['height'] = round($media->height * $la_attributes['width'] / $media->width);
			}
			elseif (
				empty($la_attributes['width']) &&
				!empty($la_attributes['height'])
			) {
				// Use the aspect ratio
				$la_attributes['width'] = round($media->width * $la_attributes['height'] / $media->height);
			}
			else {
				$la_attributes['width'] = $media->width;
				$la_attributes['height'] = $media->height;
			}
		}

		return $this->simpleImageTag($ls_path, $la_attributes, $media->averageColor, $lo_mediaRenderOptions);
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

		$lo_mediaRenderOptions = $mediaRenderOptions ?? $this->getMediaRenderOptions();

		$ls_imageTag = $this->imageTag($media, $lo_mediaRenderOptions);

		$la_breakpointFiles = $this->getResponsiveImages($media, $lo_mediaRenderOptions, true);
		$la_breakpointFiles = array_reverse($la_breakpointFiles, true);

		$ls_sources = PHP_EOL;
		foreach ($la_breakpointFiles as $li_breakpoint => $lo_file) {
			$ls_path = $lo_file->path;

			$ls_mimeType = $media->mimeType;
			if (str_ends_with($ls_path, 'webp')) {
				$ls_mimeType = 'image/webp';
			}

			$ls_sources .= '<source media="(max-width:' . $li_breakpoint . 'px)" data-srcset="' . $ls_path . '" type="' . $ls_mimeType . '">' . PHP_EOL;
		}

		return '<picture>' . $ls_sources . $ls_imageTag . '</picture>';
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @return string
	 */
	public function videoTag(Media $media, ?MediaRenderOptions $mediaRenderOptions): string {
		if (!in_array($media->mimeType, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
			return '';
		}

		$lo_mediaRenderOptions = $mediaRenderOptions ?? $this->getMediaRenderOptions();

		$la_attributes = $lo_mediaRenderOptions->getAttributes();
		$la_attributes += [
			'autoplay' => false,
			'controls' => true,
			'loop' => false,
			'muted' => false,
			'preload' => 'metadata',
			'poster' => $media->previewPath,
		];

		$la_attributes['class'] = trim($this->lazyLoadClass . ' ' . ($la_attributes['class'] ?? ''));

		$la_attributes['data-poster'] = $la_attributes['poster'];
		unset($la_attributes['poster']);

		$ls_attributes = $this->Html->templater()->formatAttributes($la_attributes);

		$ls_path = $media->path;

		$ls_sources = $ls_subtitles = '';

		/** @var \Awyiss\Model\Entity\Media $lo_alternative */
		foreach ($media->findAlternatives() as $lo_alternative) {
			// If the mimetype of the alternative is a video, set the source
			if ($lo_alternative->isVideo()) {
				$ls_sources .= PHP_EOL . '<source src="' . $lo_alternative->path . '" type="' . $lo_alternative->mimeType . '">';
				continue;
			}

			$ls_subtitles = $this->getSubtitles($lo_alternative, $ls_subtitles);
		}

		return '<video ' . $ls_attributes . '><source src="' . $ls_path . '" type="' . $media->mimeType . '">' . $ls_sources . $ls_subtitles . '</video>';
	}


	/**
	 * @param string|null $link
	 * @return bool
	 */
	public function isVideoLink(?string $link): bool {
		if (!$link) {
			return false;
		}

		$ls_youtubePattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

		return match (true) {
			preg_match($ls_youtubePattern, $link) && !str_contains($link, '&list=') => true,
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
			return file_get_contents($media->pathAbsolute);
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
		$la_mediaItems = $mediaItems;

		if ($mediaItems instanceof Collection || $mediaItems instanceof PaginatedResultSet) {
			$la_mediaItems = $mediaItems->toArray();
		}

		ResizedImageManager::setMediaItems($la_mediaItems);
	}


	/**
	 * Return the preview media element for the given media item
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param array $viewData
	 * @return string
	 */
	public function preview(Media $media, array $viewData = []): string {
		$la_defaults = [
			'resize' => null,
		];

		$la_viewData = array_merge($la_defaults, $viewData, [
			'mediaItem' => $media,
			'MediaHelper' => $this,
		]);

		return $this->getView()->element('media/preview', $la_viewData);
	}


	/**
	 * Return the resized media element for the given media item
	 *
	 * If `strictSize` is set to false (default), an image will be
	 * returned that might be larger than the requested size to not
	 * create versions of it for approximately the same size.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param float|int|null $aspectRatio
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param string $format
	 * @param bool $strictSize
	 * @param bool $allowUpscale
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $renderOptions
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function resize(
		Media $media,
		?MediaRenderOptions $renderOptions = null,
		float|int|null $width = null,
		float|int|null $height = null,
		float|int|null $aspectRatio = null,
		ResizeStrategy|string|int $strategy = ResizeStrategy::Contain,
		string $format = 'webp',
		bool $strictSize = false,
		bool $allowUpscale = false,
	): ?MediaResizedImage {
		if (!$renderOptions) {
			$la_vars = get_defined_vars();
			unset($la_vars['renderOptions']);

			return ResizedImageManager::resize(...$la_vars);
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
		$li_baseWidth = $mediaRenderOptions->getBaseWidth();
		$li_columnWidth = $mediaRenderOptions->getColumnWidth();

		if (!$li_baseWidth) {
			throw new InvalidArgumentException('Base width must be set to calculate the pixel width of a column.');
		}

		if (!$li_columnWidth) {
			throw new InvalidArgumentException('Column width must be set to calculate the pixel width of a column.');
		}

		return (int)($li_baseWidth * $li_columnWidth / 100);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param bool $removeDuplicates
	 * @return array<int, \Awyiss\Model\Entity\MediaResizedImage|\Awyiss\Model\Entity\Media>
	 */
	public function getResponsiveImages(Media $media, MediaRenderOptions $mediaRenderOptions, bool $removeDuplicates = false): array {
		if (
			// If the media item is an SVG,
			$media->mimeType === 'image/svg+xml' ||
			// or if it requires a preview (since it's not an image) but the preview is not yet created, return an empty array
			($media->preview !== ProcessStatus::NotRequired && $media->preview !== ProcessStatus::Success)
		) {
			return [];
		}

		$la_breakpoints = array_reverse($mediaRenderOptions->getBreakpoints());
		$la_breakpoints = $this->addSingleColumnBreakpoint($la_breakpoints, $mediaRenderOptions);

		return $this->getBreakpointFiles($media, $mediaRenderOptions, $la_breakpoints, $removeDuplicates);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $fields
	 * @return void
	 * @throws \DOMException
	 * @noinspection PhpUnused
	 */
	public function rebuildSimpleImageTags(Entity $entity, array $fields = []): void {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		$ls_imageHandlerClass::rebuildSimpleImageTags($entity, $fields);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @param string|null $value
	 * @return string|null
	 * @throws \DOMException
	 */
	public function rebuildSimpleImageTagsInField(Entity $entity, string $field, ?string $value = null): ?string {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		return $ls_imageHandlerClass::rebuildSimpleImageTagsInField($entity, $field, $value);
	}


	/**
	 * @param string|null $value
	 * @param array $media
	 * @param bool $absolutePath
	 * @return string|null
	 * @throws \DOMException
	 * @noinspection PhpUnused
	 */
	public function rebuildSimpleImageTagsInText(?string $value, array $media, bool $absolutePath = false): ?string {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$value) {
			return $value;
		}

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		return $ls_imageHandlerClass::rebuildSimpleImageTagsInText($value, $media, $absolutePath);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $fields
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function replaceCustomImageTags(Entity $entity, MediaRenderOptions $mediaRenderOptions, array $fields = []): void {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		$ls_imageHandlerClass::replaceCustomImageTags($entity, $this->getView(), $mediaRenderOptions, $fields);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string $field
	 * @param string|null $value
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	public function replaceCustomImageTagsInField(Entity $entity, MediaRenderOptions $mediaRenderOptions, string $field, ?string $value = null): ?string {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		return $ls_imageHandlerClass::replaceCustomImageTagsInField($entity, $this->getView(), $mediaRenderOptions, $field, $value);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $breakpoints
	 * @param bool $removeDuplicates
	 * @return array
	 */
	protected function getBreakpointFiles(Media $media, MediaRenderOptions $mediaRenderOptions, array $breakpoints, bool $removeDuplicates): array {
		$la_breakpointFiles = [];

		$lo_file = $this->getMediaResizedImage($media, $mediaRenderOptions);

		$ls_lastPath = $lo_file?->path;
		if (!$ls_lastPath) {
			$ls_lastPath = $media->isImage() ? ($media->webpPath ?? $media->path) : $media->previewPath;
		}

		$la_overrideOptions = [
			'aspectRatio' => MediaRenderOptions::PRESERVE_VALUE,
			'baseWidth' => MediaRenderOptions::PRESERVE_VALUE,
			'columnWidth' => MediaRenderOptions::PRESERVE_VALUE,
			'height' => MediaRenderOptions::PRESERVE_VALUE,
			'resizeStrategy' => MediaRenderOptions::PRESERVE_VALUE,
			'width' => MediaRenderOptions::PRESERVE_VALUE,
		];

		foreach ($breakpoints as $la_breakpoint) {
			if ($la_breakpoint['breakpoint'] >= $mediaRenderOptions->getBaseWidth()) {
				continue;
			}

			$lo_mediaRenderOptions = $this->getBreakpointRenderOptions($la_breakpoint, $mediaRenderOptions, $la_overrideOptions);

			$lo_resizedImage = $this->resize(
				$media,
				renderOptions: $lo_mediaRenderOptions,
			);

			if ($lo_resizedImage && $lo_resizedImage->status === ProcessStatus::Success) {
				$ls_path = $lo_resizedImage->path;
			}
			else {
				$ls_path = $media->isImage() ? ($media->webpPath ?? $media->path) : $media->previewPath;
			}

			if (!$removeDuplicates || $ls_lastPath !== $ls_path) {
				$la_breakpointFiles[ $la_breakpoint['breakpoint'] ] = $lo_resizedImage ?? $media;
			}

			foreach ($la_overrideOptions as $lx_key => $lx_value) {
				if (!in_array($la_breakpoint[ $lx_key ], [$lx_value, MediaRenderOptions::PRESERVE_VALUE], true)) {
					$la_overrideOptions[ $lx_key ] = $la_breakpoint[ $lx_key ];
				}
			}

			$ls_lastPath = $ls_path;
		}

		return $la_breakpointFiles;
	}


	/**
	 * Return the background color style for the media item
	 *
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string|null $averageColor
	 * @return string
	 */
	protected function getBackgroundColorStyle(MediaRenderOptions $mediaRenderOptions, ?string $averageColor): string {
		$ls_backgroundColor = null;
		if ($mediaRenderOptions->getBackgroundColor() !== false) {
			$ls_backgroundColor = $mediaRenderOptions->getBackgroundColor();

			if (!$ls_backgroundColor && $averageColor) {
				$ls_backgroundColor = '#' . $averageColor;
			}
		}

		$ls_backgroundColorStyle = '';
		if ($ls_backgroundColor) {
			$ls_backgroundColorStyle = ' background-color:' . $ls_backgroundColor . ';';
		}

		return trim($ls_backgroundColorStyle);
	}


	/**
	 * @param array $breakpointOptions
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $overrideOptions
	 * @return \Awyiss\Utility\Media\MediaRenderOptions
	 */
	protected function getBreakpointRenderOptions(array $breakpointOptions, MediaRenderOptions $mediaRenderOptions, array $overrideOptions): MediaRenderOptions {
		$la_optionKeys = [
			'aspectRatio',
			'baseWidth',
			'columnWidth',
			'height',
			'resizeStrategy',
			'width',
		];

		$la_options = array_intersect_key($breakpointOptions, array_flip($la_optionKeys));

		foreach ($overrideOptions as $ls_key => $lx_value) {
			if (
				$lx_value === MediaRenderOptions::PRESERVE_VALUE ||
				!in_array($ls_key, $la_optionKeys, true)
			) {
				continue;
			}

			if (array_key_exists($ls_key, $la_options) && $la_options[ $ls_key ] !== MediaRenderOptions::PRESERVE_VALUE) {
				continue;
			}

			$la_options[ $ls_key ] = $lx_value;
		}

		if (empty($la_options['baseWidth'])) {
			$la_options['baseWidth'] = $breakpointOptions['breakpoint'];
		}

		$la_options = array_filter($la_options, fn($value) => $value !== MediaRenderOptions::PRESERVE_VALUE);

		$lo_mediaRenderOptions = $mediaRenderOptions->with($la_options);

		if (
			($breakpointOptions['width'] ?? MediaRenderOptions::PRESERVE_VALUE) === MediaRenderOptions::PRESERVE_VALUE &&
			($breakpointOptions['height'] ?? MediaRenderOptions::PRESERVE_VALUE) === MediaRenderOptions::PRESERVE_VALUE
		) {
			$la_options['width'] = $this->getPixelColumnWidth($lo_mediaRenderOptions);
			$lo_mediaRenderOptions = $lo_mediaRenderOptions->withWidth($la_options['width']);
		}

		return $lo_mediaRenderOptions;
	}


	/**
	 * @param string $id
	 * @param float $width
	 * @param float $height
	 * @param string|null $averageColor
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return string
	 */
	protected function getPlaceholderStyleTag(string $id, float $width, float $height, ?string $averageColor, MediaRenderOptions $mediaRenderOptions): string {
		$ls_backgroundColor = null;
		if ($mediaRenderOptions->getBackgroundColor() !== false) {
			$ls_backgroundColor = $mediaRenderOptions->getBackgroundColor();

			if (!$ls_backgroundColor && $averageColor) {
				$ls_backgroundColor = '#' . $averageColor;
			}
		}

		$ls_backgroundColorStyle = '';
		if ($ls_backgroundColor) {
			$ls_backgroundColorStyle = '--imageBackgroundColor:' . $ls_backgroundColor . ';';
		}

		/** @noinspection CssUnresolvedCustomProperty */
		return '<style>#' . $id . ' { --imageAspectRatio: ' . round($width / $height, 2) . ';' . $ls_backgroundColorStyle . ' }</style>';
	}


	/**
	 * @param string $path
	 * @param array $attributes
	 * @param string|null $averageColor
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return string
	 */
	protected function simpleImageTag(string $path, array $attributes, ?string $averageColor, MediaRenderOptions $mediaRenderOptions): string {
		$la_attributes = $attributes;
		$la_attributes['class'] = trim($this->lazyLoadClass . ' ' . ($la_attributes['class'] ?? ''));
		$ls_attributes = $this->Html->templater()->formatAttributes($la_attributes);

		$la_noScriptAttributes = $attributes;
		if (isset($la_noScriptAttributes['id'])) {
			$la_noScriptAttributes['id'] .= '-NoScript';
		}
		$ls_noScriptAttributes = $this->Html->templater()->formatAttributes($la_noScriptAttributes);

		$lf_width = $attributes['width'] ?? $this->getPixelColumnWidth($mediaRenderOptions);
		if (is_string($lf_width)) {
			$lf_width = (float)$lf_width;
		}

		$lf_height = $attributes['height'] ?? $lf_width;
		if (is_string($lf_height)) {
			$lf_height = (float)$lf_height;
		}

		$ls_placeholderStyleTag = $this->getPlaceholderStyleTag(
			$attributes['id'],
			$lf_width,
			$lf_height,
			$averageColor,
			$mediaRenderOptions,
		);

		/** @noinspection HtmlRequiredAltAttribute */
		return '<img data-src="' . $path . '"' . $ls_attributes . '>' . PHP_EOL .
			   '<noscript><img src="' . $path . '"' . $ls_noScriptAttributes . '></noscript>' . PHP_EOL .
			   $ls_placeholderStyleTag . PHP_EOL;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	protected function getMediaResizedImage(Media $media, ?MediaRenderOptions $mediaRenderOptions): ?MediaResizedImage {
		// If responsive is set, use the column width
		if ($mediaRenderOptions->getResponsive()) {
			$li_width = $this->getPixelColumnWidth($mediaRenderOptions);

			return $this->resize($media, renderOptions: $mediaRenderOptions->withWidth($li_width));
		}

		// If the width and height are not set, use the column width
		if (!$mediaRenderOptions->getWidth() && !$mediaRenderOptions->getHeight()) {
			if (!$mediaRenderOptions->getColumnWidth()) {
				return null;
			}

			$li_width = $this->getPixelColumnWidth($mediaRenderOptions);

			return $this->resize($media, renderOptions: $mediaRenderOptions->withWidth($li_width));
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
		/** @noinspection CssUnknownTarget */
		$ls_output = '<style>';
		$ls_output .= $mediaRenderOptions->getSelector() . ' { --backgroundAspectRatio:' . $aspectRatio . ';';
		$ls_output .= ' --backgroundImageHeight:' . ($resizedFile?->realHeight ?? $resizedFile?->height ?? $media->height) . 'px;';
		$ls_output .= ' background-image:url(\'' . $filePath . '\');';
		$ls_output .= $backgroundColorStyle . ' }';

		$la_breakpointFiles = [];
		if ($mediaRenderOptions->getResponsive()) {
			$la_breakpointFiles = $this->getResponsiveImages($media, $mediaRenderOptions, true);
		}

		foreach ($la_breakpointFiles as $li_breakpoint => $lo_file) {
			$ls_filePath = $lo_file->path;

			$lf_aspectRatio = 1;
			$lf_width = $lf_height = null;
			if ($lo_file->realWidth && $lo_file->realHeight) {
				$lf_width = $lo_file?->realWidth;
				$lf_height = $lo_file?->realHeight;
			}
			elseif ($lo_file->width && $lo_file->height) {
				$lf_width = $lo_file?->width;
				$lf_height = $lo_file?->height;
			}

			if ($lf_width && $lf_height) {
				$lf_aspectRatio = round($lf_width / $lf_height, 2);
			}

			$ls_output .= PHP_EOL . '@media (max-width:' . $li_breakpoint . 'px) { ';
			$ls_output .= $mediaRenderOptions->getSelector() . ' {';
			if ($lf_width && $lf_height) {
				$ls_output .= ' --backgroundAspectRatio:' . $lf_aspectRatio . ';';
				$ls_output .= ' --backgroundImageHeight:' . $lo_file->height . 'px;';
			}
			$ls_output .= ' background-image:url(\'' . $ls_filePath . '\'); } }';
		}

		$ls_output .= '</style>';

		return $ls_output;
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

		$lf_singleColumnBreakpoint = $mediaRenderOptions->getSingleColumnBreakpoint();

		$la_breakpoints = $breakpoints;

		$lb_hasSingleColumnBreakpoint = false;
		foreach ($la_breakpoints as &$la_breakpoint) {
			if ($la_breakpoint['breakpoint'] !== $lf_singleColumnBreakpoint) {
				continue;
			}

			$lb_hasSingleColumnBreakpoint = true;

			if (($la_breakpoint['columnWidth'] ?? MediaRenderOptions::PRESERVE_VALUE) === MediaRenderOptions::PRESERVE_VALUE) {
				$la_breakpoint['columnWidth'] = 100;
			}

			break;
		}
		unset($la_breakpoint);

		if (!$lb_hasSingleColumnBreakpoint) {
			$la_breakpoints[] = $mediaRenderOptions::normalizeBreakpoint($lf_singleColumnBreakpoint, [
				'columnWidth' => 100,
			]);
		}

		// Reorder the breakpoints by breakpoint value
		usort($la_breakpoints, function (array $a, array $b): int {
			return $b['breakpoint'] <=> $a['breakpoint'];
		});

		return $la_breakpoints;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $lo_alternative
	 * @param string $ls_subtitles
	 * @return string
	 */
	protected function getSubtitles(Media $lo_alternative, string $ls_subtitles): string {
		// If the mimetype of the alternative is a subtitle, set the source
		if ($lo_alternative->mimeType === 'text/vtt') {
			// Source language is the last two characters of the filename
			$ls_sourceLang = substr($lo_alternative->cleanName, -2);

			// If the source language is the current language, set it as default
			$ls_default = $ls_sourceLang === (Router::getRequest()?->getParam('lang') ?? '') ? ' default' : '';

			// Add a track tag for the subtitle
			$ls_subtitles .= PHP_EOL .  '<track src="' . $lo_alternative->path . '" kind="subtitles"' .
				$ls_default .
				' srclang="' . $ls_sourceLang . '" label="' . ($lo_alternative->alt ?? locale_get_display_language($ls_sourceLang)) . '">';
		}

		return $ls_subtitles;
	}
}
