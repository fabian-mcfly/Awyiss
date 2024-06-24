<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
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
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param int|null $width
	 * @param int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy $strategy
	 * @param string $format
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	public function resize(
		Media $media,
		?int $width = null,
		?int $height = null,
		ResizeStrategy $strategy = ResizeStrategy::Contain,
		string $format = 'webp'
	): ?MediaResizedImage {
		if (!$media->path) {
			return null;
		}

		return static::$resizedImageManager->resize($media, $width, $height, $strategy, $format);
	}
}
