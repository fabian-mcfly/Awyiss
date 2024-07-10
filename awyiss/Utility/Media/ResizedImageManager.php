<?php declare(strict_types=1);


namespace Awyiss\Utility\Media;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table\MediaResizedImagesTable;
use Awyiss\Model\Table\MediaTable;
use Cake\Datasource\FactoryLocator;
use InvalidArgumentException;


/**
 * Class ResizedImageManager
 *
 * Manages the resized images for media items and caches them in static storage
 */
class ResizedImageManager {
	/**
	 * Static storage for media items
	 *
	 * @var array<int, \Awyiss\Model\Entity\Media> $mediaItems
	 */
	protected static array $mediaItems = [];
	/**
	 * Static storage for resized images, indexed by the media id
	 *
	 * @var array<int, array<\Awyiss\Model\Entity\MediaResizedImage>> $resizedRecords
	 */
	protected static array $resizedRecords = [];
	/**
	 * Media table instance
	 *
	 * @var \Awyiss\Model\Table\MediaTable $mediaTable
	 */
	protected static MediaTable $mediaTable;
	/**
	 * Media resized images table instance
	 *
	 * @var \Awyiss\Model\Table\MediaResizedImagesTable
	 */
	protected static MediaResizedImagesTable $mediaResizedImagesTable;


	/**
	 * ResizedImageManager constructor.
	 */
	public function __construct() {
		if (!isset(static::$mediaTable)) {
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			static::$mediaTable = FactoryLocator::get('Table')->get('Media');
		}

		if (!isset(static::$mediaResizedImagesTable)) {
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			static::$mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
		}
	}


	/**
	 * @return array
	 */
	public function getMediaItems(): array {
		return static::$mediaItems;
	}


	/**
	 * Add a media item to the static storage
	 *
	 * @param \Awyiss\Model\Entity\Media $mediaItem
	 * @return void
	 */
	public function addMediaItem(Media $mediaItem): void {
		static::$mediaItems[ $mediaItem->id ] = $mediaItem;

		if ($mediaItem->mediaResizedImages) {
			static::$resizedRecords[ $mediaItem->id ] = $mediaItem->mediaResizedImages;
		}
	}


	/**
	 * Add multiple media items to the static storage
	 *
	 * @param array<\Awyiss\Model\Entity\Media|int> $mediaItems
	 * @param bool $merge Whether to merge the media items with the existing ones or set them as the new ones
	 * @return void
	 */
	public function setMediaItems(array $mediaItems, bool $merge = true): void {
		$la_itemsToFetch = [];

		if (!$merge) {
			static::$mediaItems = [];
		}

		/** @var \Awyiss\Model\Entity\Media|int $lo_mediaItem */
		foreach ($mediaItems as $lx_mediaItem) {
			if ($lx_mediaItem instanceof Media) {
				static::$mediaItems[ $lx_mediaItem->id ] = $lx_mediaItem;

				if ($lx_mediaItem->mediaResizedImages) {
					static::$resizedRecords[ $lx_mediaItem->id ] = $lx_mediaItem->mediaResizedImages;
				}

				continue;
			}

			// If the media item is an id and not in the static storage, add it to the list of items to fetch
			if (!isset(static::$mediaItems[ $lx_mediaItem ])) {
				$la_itemsToFetch[] = $lx_mediaItem;
			}
		}

		if ($la_itemsToFetch) {
			$lo_records = static::$mediaTable->find()->where(['id IN' => $la_itemsToFetch])->all();

			/** @var \Awyiss\Model\Entity\Media $lo_record */
			foreach ($lo_records as $lo_record) {
				static::$mediaItems[ $lo_record->id ] = $lo_record;
			}
		}
	}


	/**
	 * Returns all resized images for the given media id
	 *
	 * @param int $mediaId
	 * @return array<\Awyiss\Model\Entity\MediaResizedImage>|null
	 */
	public function getResizedItem(int $mediaId): ?array {
		return static::$resizedRecords[ $mediaId ] ?? null;
	}


	/**
	 * Returns all resized images, indexed by the media id
	 *
	 * @return array<int, array<\Awyiss\Model\Entity\MediaResizedImage>>
	 */
	public function getResizedItems(): array {
		return static::$resizedRecords;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy $strategy
	 * @param string $format
	 * @param bool $strictSize
	 * @param bool $allowUpscale
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	public function resize(
		Media $media,
		float|int|null $width = null,
		float|int|null $height = null,
		ResizeStrategy $strategy = ResizeStrategy::Contain,
		string $format = 'webp',
		bool $strictSize = false,
		bool $allowUpscale = false,
	): ?MediaResizedImage {
		if (
			!$media->path ||
			// If the media item is an SVG, return null
			$media->mimeType === 'image/svg+xml' ||
			// If the media item requires a preview (not an image) but the preview is not yet created, return null
			($media->preview !== ProcessStatus::NotRequired && $media->preview !== ProcessStatus::Success)
		) {
			return null;
		}

		// Throw an error if both width and height are null
		if ($width === null && $height === null) {
			throw new InvalidArgumentException('Either width or height must be set.');
		}

		// Add the media item to the static storage
		$this->addMediaItem($media);
		$this->fetchMissingResizedRecords();

		/**
		 * If the width or height is not set, check if the strategy is "contain",
		 * otherwise throw an error
		 */
		if (!$width || !$height) {
			// If the strategy isn't contain, throw an error
			if ($strategy !== ResizeStrategy::Contain) {
				throw new InvalidArgumentException('Both width and height must be set if the resize strategy is not "contain".');
			}
		}

		// If the width and height are the same as the original, return null
		if (
			(!$width || $width == $media->width) &&
			(!$height || $height == $media->height)
		) {
			return null;
		}

		// If the image is not allowed to be upscaled, check if the requested size is larger than the original
		if (!$allowUpscale) {
			// If that's the case, return null
			if (
				($width && $width > $media->width) ||
				($height && $height > $media->height)
			) {
				return null;
			}
		}

		$li_width = $width ? (int)$width : null;
		$li_height = $height ? (int)$height : null;

		// Check if the media item is already resized
		$lo_resizedImage = $this->findResizedImage($media, $li_width, $li_height, $strategy, $format, $strictSize);

		if ($lo_resizedImage) {
			if (!$lo_resizedImage->media) {
				$lo_resizedImage->media = $media;
			}

			return $lo_resizedImage;
		}

		$lo_resizedImage = static::$mediaResizedImagesTable->newEntityFromMedia($media, $li_width, $li_height, $strategy, $format);

		if (!static::$mediaResizedImagesTable->save($lo_resizedImage, ['associated' => false])) {
			return null;
		}

		static::$resizedRecords[ $media->id ][] = $lo_resizedImage;

		return $lo_resizedImage;
	}


	/**
	 * Fetch all media items that do not have an entry in the resizedRecords array
	 *
	 * @return void
	 */
	protected function fetchMissingResizedRecords(): void {
		$la_missingMediaIds = array_keys(array_diff_key(static::$mediaItems, static::$resizedRecords));

		// Fetch all missing resized records
		if ($la_missingMediaIds) {
			$lo_resizedRecords = static::$mediaResizedImagesTable->find()->where(['media_id IN' => $la_missingMediaIds])->all();

			// Group the fetched records by media id
			foreach ($lo_resizedRecords as $lo_resizedRecord) {
				static::$resizedRecords[ $lo_resizedRecord->media_id ][] = $lo_resizedRecord;
			}
		}

		// Set an empty array for media items without related resized records
		foreach ($la_missingMediaIds as $li_mediaId) {
			if (!isset(static::$resizedRecords[ $li_mediaId ])) {
				static::$resizedRecords[ $li_mediaId ] = [];
			}
		}
	}


	/**
	 * Find a resized image with a size larger than the given one,
	 * but within a certain threshold.
	 * If a strategy is provided, the image must have the same strategy
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param int|null $width
	 * @param int|null $height
	 * @param string $format
	 * @param \Awyiss\Model\Enum\ResizeStrategy|null $strategy
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	public function findWithinThreshold(Media $media, ?int $width, ?int $height, string $format, ?ResizeStrategy $strategy = null): ?MediaResizedImage {
		$lo_resizedImages = static::$resizedRecords[ $media->id ] ?? [];

		$li_widthThreshold = $width ? ceil($width * 1.1) : null;
		$li_heightThreshold = $height ? ceil($height * 1.1) : null;

		/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_resizedImage */
		foreach ($lo_resizedImages as $lo_resizedImage) {
			if ($strategy && $lo_resizedImage->strategy !== $strategy) {
				continue;
			}

			if ($width && ($lo_resizedImage->width < $width || $lo_resizedImage->width > $li_widthThreshold)) {
				continue;
			}

			if ($height && ($lo_resizedImage->height < $height || $lo_resizedImage->height > $li_heightThreshold)) {
				continue;
			}

			if ($lo_resizedImage->extension === $format) {
				return $lo_resizedImage;
			}
		}

		return null;
	}


	/**
	 * Find a resized image with the given parameters in the static storage
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param int|null $width
	 * @param int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy $strategy
	 * @param string $format
	 * @param bool $strictSize
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	protected function findResizedImage(
		Media $media,
		?int $width,
		?int $height,
		ResizeStrategy $strategy,
		string $format,
		bool $strictSize = false,
	): ?MediaResizedImage {
		$lo_resizedImages = static::$resizedRecords[ $media->id ] ?? [];

		/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_resizedImage */
		foreach ($lo_resizedImages as $lo_resizedImage) {
			if ($lo_resizedImage->width === $width && $lo_resizedImage->height === $height && $lo_resizedImage->strategy === $strategy && $lo_resizedImage->extension === $format) {
				return $lo_resizedImage;
			}
		}

		// If the size doesn't have to be strict, check if there is a resized image within a certain threshold
		if (!$strictSize) {
			$lo_resizedImage = $this->findWithinThreshold($media, $width, $height, $format, $strategy);

			if ($lo_resizedImage) {
				if (!$lo_resizedImage->media) {
					$lo_resizedImage->media = $media;
				}

				return $lo_resizedImage;
			}
		}

		return null;
	}
}
