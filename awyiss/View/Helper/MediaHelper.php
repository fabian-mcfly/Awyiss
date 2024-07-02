<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Collection\Collection;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\View\Helper;


/**
 * Helper class that provides methods related to the Media-logic in the views
 */
class MediaHelper extends Helper {
	/**
	 * @var \Awyiss\Utility\Media\ResizedImageManager $resizedImageManager
	 */
	protected static ResizedImageManager $resizedImageManager;


	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		if (!isset(static::$resizedImageManager)) {
			static::$resizedImageManager = new ResizedImageManager();
		}

		/** @var \Twig\Environment $lo_twig */
		$lo_twig = $this->getView()->getTwig();
		$lo_twig->addGlobal('ProcessStatus', ProcessStatus::class);
		$lo_twig->addGlobal('ResizeStrategy', ResizeStrategy::class,);
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

		static::$resizedImageManager->setMediaItems($la_mediaItems);
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
	 * @param \Awyiss\Model\Enum\ResizeStrategy $strategy
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
		ResizeStrategy $strategy = ResizeStrategy::Contain,
		string $format = 'webp',
		bool $strictSize = false,
		bool $allowUpscale = false,
	): ?MediaResizedImage {
		if (!$renderOptions) {
			$la_vars = get_defined_vars();
			unset($la_vars['renderOptions']);

			return static::$resizedImageManager->resize(...$la_vars);
		}

		return static::$resizedImageManager->resize(
			$media,
			$renderOptions->getWidth(),
			$renderOptions->getHeight(),
			$renderOptions->getResizeStrategy(),
			$format,
			$renderOptions->getStrictSize(),
			$renderOptions->getAllowUpscale(),
		);
	}
}
