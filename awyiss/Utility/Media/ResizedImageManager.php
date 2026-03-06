<?php declare(strict_types=1);


namespace Awyiss\Utility\Media;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table\MediaResizedImagesTable;
use Awyiss\Model\Table\MediaTable;
use Cake\Datasource\EntityInterface;
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
	 * @return array
	 */
	public static function getMediaItems(): array {
		return static::$mediaItems;
	}


	/**
	 * Add a media item to the static storage
	 *
	 * @param \Awyiss\Model\Entity\Media $mediaItem
	 * @return void
	 */
	public static function addMediaItem(Media $mediaItem): void {
		static::$mediaItems[ $mediaItem->id ] = $mediaItem;

		if ($mediaItem->mediaResizedImages) {
			/** @var \Awyiss\Model\Entity\MediaResizedImage $resizedImage */
			foreach ($mediaItem->mediaResizedImages as $resizedImage) {
				static::$resizedRecords[ $mediaItem->id ][ $resizedImage->id ] = $resizedImage;
			}
		}
	}


	/**
	 * Add media items from an entity to the static storage
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public static function addMediaItemsFromEntity(EntityInterface $entity): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if (!$entity->mediaAssignments) {
			return;
		}

		$mediaElements = [];
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		foreach ($entity->mediaAssignments as $assignments) {
			foreach ($assignments as $identifier => $media) {
				if (!is_int($identifier) && str_starts_with($identifier, '_')) {
					continue;
				}

				if ($media instanceof Media) {
					$mediaElements[] = $media;
				}
				elseif ($media instanceof MediaAssignment && $media->media) {
					$mediaElements[] = $media->media;
				}
				elseif (is_array($media)) {
					foreach ($media as $mediaItem) {
						if ($mediaItem instanceof Media) {
							$mediaElements[] = $mediaItem;
						}
					}
				}
			}
		}

		if ($mediaElements) {
			static::setMediaItems($mediaElements);
		}
	}


	/**
	 * Add multiple media items to the static storage
	 *
	 * @param array<\Awyiss\Model\Entity\Media|int> $mediaItems
	 * @param bool $merge Whether to merge the media items with the existing ones or set them as the new ones
	 * @return void
	 */
	public static function setMediaItems(array $mediaItems, bool $merge = true): void {
		$itemsToFetch = [];

		if (!$merge) {
			static::$mediaItems = [];
		}

		/** @var \Awyiss\Model\Entity\Media|int $mediaItem */
		foreach ($mediaItems as $mediaItem) {
			if ($mediaItem instanceof Media) {
				static::$mediaItems[ $mediaItem->id ] = $mediaItem;

				if ($mediaItem->mediaResizedImages) {
					/** @var \Awyiss\Model\Entity\MediaResizedImage $resizedImage */
					foreach ($mediaItem->mediaResizedImages as $resizedImage) {
						static::$resizedRecords[ $mediaItem->id ][ $resizedImage->id ] = $resizedImage;
					}
				}

				continue;
			}

			if (!is_numeric($mediaItem)) {
				continue;
			}

			// If the media item is an id and not in the static storage, add it to the list of items to fetch
			if (!isset(static::$mediaItems[ $mediaItem ])) {
				$itemsToFetch[] = $mediaItem;
			}
		}

		if ($itemsToFetch) {
			if (!isset(static::$mediaTable)) {
				/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
				static::$mediaTable = FactoryLocator::get('Table')->get('Media');
			}

			$records = static::$mediaTable->find()->where(['id IN' => $itemsToFetch])->all();

			/** @var \Awyiss\Model\Entity\Media $record */
			foreach ($records as $record) {
				static::$mediaItems[ $record->id ] = $record;
			}
		}
	}


	/**
	 * Returns all resized images, indexed by the media id
	 *
	 * If a media id is provided, only the resized images for that media item are returned
	 *
	 * @return array<int, array<\Awyiss\Model\Entity\MediaResizedImage>>|null
	 */
	public static function getResizedItems(?int $mediaId = null): ?array {
		if ($mediaId) {
			return static::$resizedRecords[ $mediaId ] ?? null;
		}

		return static::$resizedRecords;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param float|int|null $aspectRatio
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param string $format
	 * @param bool $strictSize
	 * @param bool $allowUpscale
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	public static function resize(
		Media $media,
		float|int|null $width = null,
		float|int|null $height = null,
		float|int|null $aspectRatio = null,
		ResizeStrategy|string|int $strategy = ResizeStrategy::Contain,
		string $format = 'avif',
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

		['width' => $width, 'height' => $height] = static::normalizeSizes($width, $height, $aspectRatio);

		// Throw an error if both width and height are null
		if ($width === null && $height === null) {
			throw new InvalidArgumentException('Either width or height must be set.');
		}

		$strategy = ResizeStrategy::normalize($strategy);

		// Add the media item to the static storage
		static::addMediaItem($media);
		static::fetchMissingResizedRecords();

		$canBeResized = static::fileCanBeResized($media, $width, $height, $strategy, $allowUpscale);

		if (!$canBeResized) {
			return null;
		}

		// Check if the media item is already resized
		$resizedImage = static::findResizedImage($media, $width, $height, $strategy, $format, $strictSize);

		if ($resizedImage) {
			if (!$resizedImage->media) {
				$resizedImage->media = $media;
			}

			return $resizedImage;
		}

		$resizedImage = static::newMediaResizedImage($media, $width, $height, $strategy, $format);

		if (!static::$mediaResizedImagesTable->save($resizedImage, ['associated' => false])) {
			return null;
		}

		static::$resizedRecords[ $media->id ][ $resizedImage->id ] = $resizedImage;

		return $resizedImage;
	}


	/**
	 * Fetch all media items that do not have an entry in the resizedRecords array
	 *
	 * @return void
	 */
	protected static function fetchMissingResizedRecords(): void {
		$missingMediaIds = array_keys(array_diff_key(static::$mediaItems, static::$resizedRecords));

		// Fetch all missing resized records
		if ($missingMediaIds) {
			if (!isset(static::$mediaResizedImagesTable)) {
				/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
				static::$mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
			}

			$resizedRecords = static::$mediaResizedImagesTable->find()->where(['mediaId IN' => $missingMediaIds])->all();

			/**
			 * Group the fetched records by media id
			 *
			 * @var \Awyiss\Model\Entity\MediaResizedImage $resizedRecord
			 */
			foreach ($resizedRecords as $resizedRecord) {
				static::$resizedRecords[ $resizedRecord->mediaId ][ $resizedRecord->id ] = $resizedRecord;
			}
		}

		// Set an empty array for media items without related resized records
		foreach ($missingMediaIds as $mediaId) {
			if (!isset(static::$resizedRecords[ $mediaId ])) {
				static::$resizedRecords[ $mediaId ] = [];
			}
		}
	}


	/**
	 * Find a resized image with a size larger than the given one,
	 * but within a certain threshold.
	 * If a strategy is provided, the image must have the same strategy
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param string $format
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int|null $strategy
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	public static function findWithinThreshold(
		Media $media,
		float|int|null $width,
		float|int|null $height,
		string $format,
		ResizeStrategy|string|int|null $strategy = null
	): ?MediaResizedImage {
		$resizedImages = static::$resizedRecords[ $media->id ] ?? [];

		$widthThreshold = $width ? ceil($width * 1.1) : null;
		$heightThreshold = $height ? ceil($height * 1.1) : null;

		$strategy = $strategy ? ResizeStrategy::normalize($strategy) : null;

		/** @var \Awyiss\Model\Entity\MediaResizedImage $resizedImage */
		foreach ($resizedImages as $resizedImage) {
			if ($strategy && $resizedImage->strategy !== $strategy) {
				continue;
			}

			if ($width && ($resizedImage->width < $width || $resizedImage->width > $widthThreshold)) {
				continue;
			}

			if ($height && ($resizedImage->height < $height || $resizedImage->height > $heightThreshold)) {
				continue;
			}

			if ($resizedImage->extension === $format) {
				return $resizedImage;
			}
		}

		return null;
	}


	/**
	 * Clears all static storage
	 */
	public static function clear(): void {
		static::$mediaItems = [];
		static::$resizedRecords = [];
	}


	/**
	 * Find a resized image with the given parameters in the static storage
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param string $format
	 * @param bool $strictSize
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	protected static function findResizedImage(
		Media $media,
		float|int|null $width,
		float|int|null $height,
		ResizeStrategy|string|int $strategy,
		string $format,
		bool $strictSize = false,
	): ?MediaResizedImage {
		$resizedImages = static::$resizedRecords[ $media->id ] ?? [];

		$strategy = ResizeStrategy::normalize($strategy);

		/** @var \Awyiss\Model\Entity\MediaResizedImage $resizedImage */
		foreach ($resizedImages as $resizedImage) {
			if (
				$resizedImage->width == $width &&
				$resizedImage->height == $height &&
				$resizedImage->strategy === $strategy &&
				$resizedImage->extension === $format
			) {
				return $resizedImage;
			}
		}

		// If the size has to be strict, return null since we haven't found an image
		if ($strictSize) {
			return null;
		}

		// Check if there is a resized image within a certain threshold
		$resizedImage = static::findWithinThreshold($media, $width, $height, $format, $strategy);

		if ($resizedImage) {
			if (!$resizedImage->media) {
				$resizedImage->media = $media;
			}

			return $resizedImage;
		}

		return null;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param int|null $width
	 * @param int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param string $format
	 * @return \Awyiss\Model\Entity\MediaResizedImage
	 */
	protected static function newMediaResizedImage(Media $media, ?int $width, ?int $height, ResizeStrategy|string|int $strategy, string $format): MediaResizedImage {
		if (!isset(static::$mediaResizedImagesTable)) {
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			static::$mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
		}

		return static::$mediaResizedImagesTable->newEntityFromMedia($media, $width, $height, ResizeStrategy::normalize($strategy), $format);
	}


	/**
	 * Check if the file can be resized
	 * If the width and height are the same as the original, return null
	 * If the image is not allowed to be upscaled, check if the requested size is larger than the original
	 *
	 * If the width or height is not set, check if the strategy is "contain", otherwise throw an error
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param bool $allowUpscale
	 * @return bool
	 */
	protected static function fileCanBeResized(
		Media $media,
		float|int|null $width,
		float|int|null $height,
		ResizeStrategy|string|int $strategy,
		bool $allowUpscale
	): bool {
		/**
		 * If the width or height is not set, check if the strategy is "contain",
		 * otherwise throw an error
		 */
		if (!$width || !$height) {
			// If the strategy isn't contain, throw an error
			if (ResizeStrategy::normalize($strategy) !== ResizeStrategy::Contain) {
				throw new InvalidArgumentException('Both width and height must be set if the resize strategy is not "contain".');
			}
		}

		// If the width and height are the same as the original, return null
		if (
			(!$width || $width == $media->width) && (!$height || $height == $media->height)
		) {
			return false;
		}

		// If the image is not allowed to be upscaled, check if the requested size is larger than the original
		if (!$allowUpscale) {
			// If that's the case, return null
			if (
				($width && $width > $media->width) || ($height && $height > $media->height)
			) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Normalize the width and height values
	 * and calculate the missing dimension if the aspect ratio is set
	 *
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param float|int|null $aspectRatio
	 * @return array{width: int|null, height: int|null}
	 */
	protected static function normalizeSizes(float|int|null $width, float|int|null $height, float|int|null $aspectRatio): array {
		$width = $width ? round($width) : null;
		$height = $height ? round($height) : null;

		// If the aspect ratio is set but only one dimension (width or height) is provided, calculate the other
		if ($aspectRatio) {
			if ($width && !$height) {
				$height = round($width / $aspectRatio);
			}
			elseif ($height && !$width) {
				$width = round($height * $aspectRatio);
			}
		}

		return [
			'width' => $width ? (int)$width : null,
			'height' => $height ? (int)$height : null,
		];
	}
}
