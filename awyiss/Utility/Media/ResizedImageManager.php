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
			static::$resizedRecords[ $mediaItem->id ] = $mediaItem->mediaResizedImages;
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

		$la_mediaElements = [];
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		foreach ($entity->mediaAssignments as $la_assignments) {
			foreach ($la_assignments as $ls_identifier => $lo_media) {
				if (str_starts_with($ls_identifier, '_')) {
					continue;
				}

				if ($lo_media instanceof Media) {
					$la_mediaElements[] = $lo_media;
				}
				elseif ($lo_media instanceof MediaAssignment && $lo_media->media) {
					$la_mediaElements[] = $lo_media->media;
				}
				elseif (is_array($lo_media)) {
					foreach ($lo_media as $lo_mediaItem) {
						if ($lo_mediaItem instanceof Media) {
							$la_mediaElements[] = $lo_mediaItem;
						}
					}
				}
			}
		}

		if ($la_mediaElements) {
			static::setMediaItems($la_mediaElements);
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

			if (!is_numeric($lx_mediaItem)) {
				continue;
			}

			// If the media item is an id and not in the static storage, add it to the list of items to fetch
			if (!isset(static::$mediaItems[ $lx_mediaItem ])) {
				$la_itemsToFetch[] = $lx_mediaItem;
			}
		}

		if ($la_itemsToFetch) {
			if (!isset(static::$mediaTable)) {
				/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
				static::$mediaTable = FactoryLocator::get('Table')->get('Media');
			}

			$lo_records = static::$mediaTable->find()->where(['id IN' => $la_itemsToFetch])->all();

			/** @var \Awyiss\Model\Entity\Media $lo_record */
			foreach ($lo_records as $lo_record) {
				static::$mediaItems[ $lo_record->id ] = $lo_record;
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

		['width' => $li_width, 'height' => $li_height] = static::normalizeSizes($width, $height, $aspectRatio);

		// Throw an error if both width and height are null
		if ($li_width === null && $li_height === null) {
			throw new InvalidArgumentException('Either width or height must be set.');
		}

		$le_strategy = ResizeStrategy::normalize($strategy);

		// Add the media item to the static storage
		static::addMediaItem($media);
		static::fetchMissingResizedRecords();

		$lb_canBeResized = static::fileCanBeResized($media, $li_width, $li_height, $le_strategy, $allowUpscale);

		if (!$lb_canBeResized) {
			return null;
		}

		// Check if the media item is already resized
		$lo_resizedImage = static::findResizedImage($media, $li_width, $li_height, $le_strategy, $format, $strictSize);

		if ($lo_resizedImage) {
			if (!$lo_resizedImage->media) {
				$lo_resizedImage->media = $media;
			}

			return $lo_resizedImage;
		}

		$lo_resizedImage = static::newMediaResizedImage($media, $li_width, $li_height, $le_strategy, $format);

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
	protected static function fetchMissingResizedRecords(): void {
		$la_missingMediaIds = array_keys(array_diff_key(static::$mediaItems, static::$resizedRecords));

		// Fetch all missing resized records
		if ($la_missingMediaIds) {
			if (!isset(static::$mediaResizedImagesTable)) {
				/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
				static::$mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
			}

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
	 * @param float|int|null $width
	 * @param float|int|null $height
	 * @param string $format
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int|null $strategy
	 * @return \Awyiss\Model\Entity\MediaResizedImage|null
	 */
	public static function findWithinThreshold(Media $media, float|int|null $width, float|int|null $height, string $format, ResizeStrategy|string|int|null $strategy = null): ?MediaResizedImage {
		$lo_resizedImages = static::$resizedRecords[ $media->id ] ?? [];

		$li_widthThreshold = $width ? ceil($width * 1.1) : null;
		$li_heightThreshold = $height ? ceil($height * 1.1) : null;

		$le_strategy = $strategy ? ResizeStrategy::normalize($strategy) : null;

		/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_resizedImage */
		foreach ($lo_resizedImages as $lo_resizedImage) {
			if ($le_strategy && $lo_resizedImage->strategy !== $le_strategy) {
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
		$lo_resizedImages = static::$resizedRecords[ $media->id ] ?? [];

		$le_strategy = ResizeStrategy::normalize($strategy);

		/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_resizedImage */
		foreach ($lo_resizedImages as $lo_resizedImage) {
			if (
				$lo_resizedImage->width == $width &&
				$lo_resizedImage->height == $height &&
				$lo_resizedImage->strategy === $le_strategy &&
				$lo_resizedImage->extension === $format
			) {
				return $lo_resizedImage;
			}
		}

		// If the size doesn't have to be strict, check if there is a resized image within a certain threshold
		if (!$strictSize) {
			$lo_resizedImage = static::findWithinThreshold($media, $width, $height, $format, $le_strategy);

			if ($lo_resizedImage) {
				if (!$lo_resizedImage->media) {
					$lo_resizedImage->media = $media;
				}

				return $lo_resizedImage;
			}
		}

		return null;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param int|null $li_width
	 * @param int|null $li_height
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $strategy
	 * @param string $format
	 * @return \Awyiss\Model\Entity\MediaResizedImage
	 */
	protected static function newMediaResizedImage(Media $media, ?int $li_width, ?int $li_height, ResizeStrategy|string|int $strategy, string $format): MediaResizedImage {
		if (!isset(static::$mediaResizedImagesTable)) {
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			static::$mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
		}

		return static::$mediaResizedImagesTable->newEntityFromMedia($media, $li_width, $li_height, ResizeStrategy::normalize($strategy), $format);
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
	 * @return void
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
		$li_width = $width ? round($width) : null;
		$li_height = $height ? round($height) : null;

		// If the aspect ratio is set but only one dimension (width or height) is provided, calculate the other
		if ($aspectRatio) {
			if ($li_width && !$li_height) {
				$li_height = round($li_width / $aspectRatio);
			}
			elseif ($li_height && !$li_width) {
				$li_width = round($li_height * $aspectRatio);
			}
		}

		return [
			'width' => $li_width ? (int)$li_width : null,
			'height' => $li_height ? (int)$li_height : null,
		];
	}
}
