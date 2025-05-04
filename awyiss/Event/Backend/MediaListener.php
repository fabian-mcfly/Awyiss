<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Core\LocalConfig;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table\MediaTable;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Imagick;


/**
 * Event listeners for the Media scope of the backend
 */
class MediaListener implements EventListenerInterface {
	use EventListenerTrait;
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var array<int, array<int, \Awyiss\Model\Entity\Media>>
	 */
	protected static array $media = [];
	/**
	 * @var array<int, \Awyiss\Model\Entity\MediaFolder>
	 */
	protected static array $mediaFolders;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Media.beforeSave' => 'beforeSave',
			'Model.Media.afterSave' => 'afterSave',
			'Model.Media.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * Before saving a file, make sure its name is unique, its path is set,
	 * image dimensions are known, and the file extension matches the mimetype
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \ImagickException
	 * @noinspection PhpComposerExtensionStubsInspection
	 */
	public function beforeSave(Event $event, Media $entity, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $event->getSubject();
		$lb_isNew = $entity->isNew();

		// If the systemOrder is the only dirty field, we don't need to do anything
		if ($entity->getDirty() === ['systemOrder']) {
			return;
		}

		if (!isset(static::$mediaFolders)) {
			/**
			 * @var \Cake\Collection\Iterator\TreeIterator $lo_mediaFolderse
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$lo_mediaFolderse = $lo_table->getBehavior('Categories')->getCategories(true)->compile(false);
			static::$mediaFolders = $lo_mediaFolderse->indexBy('id')->toArray();
		}

		if (!isset(static::$media[ $entity->mediaFolderId ])) {
			/** @var \Cake\Collection\Iterator\TreeIterator $lo_mediaFolderse */
			$lo_media = $lo_table->find()->where(['media_folder_id' => $entity->mediaFolderId])->all();
			static::$media[ $entity->mediaFolderId ] = $lo_media->indexBy('name')->toArray();
		}

		if (!$entity->extension) {
			$la_knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $entity->mimeType));

			if (!$la_knownExtensions) {
				$event->stopPropagation();

				$entity->setError(
					'name',
					__df(strtolower($lo_table->getI18nDomain()), 'validation', 'error_media_has_file_extension'),
					true
				);

				return;
			}

			$ls_realExtension = current($la_knownExtensions);
			if ($ls_realExtension === 'jpeg') {
				$ls_realExtension = 'jpg';
			}

			$entity->name .= '.' . $ls_realExtension;
		}

		if ($entity->file && !$entity->file->getError()) {
			$this->setDimensions($entity);

			$entity->avif = in_array($entity->mimeType, ['image/avif', 'image/svg+xml']) ? ProcessStatus::NotRequired : ProcessStatus::Undefined;
			$entity->webp = in_array($entity->mimeType, ['image/webp', 'image/svg+xml']) ? ProcessStatus::NotRequired : ProcessStatus::Undefined;

			if ($lb_isNew && LocalConfig::read('upload.autoOverwrite', false, 'Media') === true) {
				$this->useExistingsFileData($lo_table, $entity);
			}
			else {
				$this->ensureUniqueFileName($lo_table, $entity);
			}
		}
		else {
			if ($entity->file && $entity->file->getError()) {
				$entity->file = null;
			}

			if ($entity->isDirty('name') || $entity->isDirty('mediaFolderId')) {
				$this->ensureUniqueFileName($lo_table, $entity);
			}
		}

		$ls_path = 'media/';

		if (isset(static::$mediaFolders[ $entity->mediaFolderId ])) {
			$ls_path = static::$mediaFolders[ $entity->mediaFolderId ]->path . '/';
		}

		$entity->path = $ls_path . $entity->name;
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, Media $entity, ArrayObject $options): void {
		if ($entity->file && !$entity->file->getError()) {
			$entity->deleteConvertedFiles();
			$entity->deleteResizedFiles();

			$entity->file->moveTo(WWW_ROOT . str_replace('/', DS, $entity->path));

			if ($entity->hasOriginal('path') && $entity->getOriginal('path') !== $entity->get('path')) {
				if (
					in_array(LocalConfig::read('createHistoricalPaths', false, 'Media'), [
						MediaConfigOptions::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE,
						MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS,
					])
				) {
					$this->createHistoricalPaths($entity, $entity->getOriginal('path'));
				}

				unlink(WWW_ROOT . str_replace('/', DS, $entity->getOriginal('path')));
			}
		}
		elseif ($entity->hasOriginal('path') && $entity->getOriginal('path') !== $entity->get('path')) {
			if (
				in_array(LocalConfig::read('createHistoricalPaths', false, 'Media'), [
					MediaConfigOptions::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE,
					MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS,
				])
			) {
				$this->createHistoricalPaths($entity, $entity->getOriginal('path'));
			}

			$entity->moveConvertedFiles();
			$entity->moveResizedFiles();

			rename(
				WWW_ROOT . str_replace('/', DS, $entity->getOriginal('path')),
				WWW_ROOT . str_replace('/', DS, $entity->get('path'))
			);
		}

		if ($entity->hasOriginal('focusPoint') && $entity->getOriginal('focusPoint') !== $entity->get('focusPoint')) {
			$entity->deleteResizedFiles();
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(Event $event, Media $entity, ArrayObject $options): void {
		$ls_sourceFile = $entity->path;
		if ($ls_sourceFile && is_file($ls_sourceFile)) {
			unlink($ls_sourceFile);
		}

		$entity->deleteResizedFiles();
		$entity->deleteConvertedFiles();
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param string $originalPath
	 * @return void
	 */
	protected function createHistoricalPaths(Media $entity, string $originalPath): void {
		$ls_originalPath = $originalPath;
		$lo_urlHistoryTable = $this->fetchTable('UrlHistory');

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$li_userId = $this->getIdentity()?->id;
		$ld_now = new DateTime('now');

		$lo_query = $lo_urlHistoryTable->insertQuery()->insert(['url', 'scope', 'foreign_key', 'status', 'created_by', 'created_on']);

		$lo_query->values([
			'url' => $ls_originalPath,
			'scope' => 'media',
			'foreign_key' => $entity->id,
			'status' => 308,
			'created_by' => $li_userId,
			'created_on' => $ld_now,
		]);

		$lo_query->execute();
	}


	/**
	 * @param \Awyiss\Model\Table\MediaTable $table
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @return void
	 */
	protected function ensureUniqueFileName(MediaTable $table, Media $entity): void {
		$ls_extension = $entity->extension;
		$ls_fileName = $entity->cleanName;

		$la_conditions = [
			'name' => $entity->name,
			'media_folder_id' => $entity->mediaFolderId,
		];

		$ls_primaryKey = $table->getPrimaryKey();
		$li_id = $entity->get($ls_primaryKey);
		if ($li_id) {
			$la_conditions['NOT'] = [$table->getAlias() . '.' . $ls_primaryKey => $li_id];
		}


		$ls_field = $table->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		$li_i = 1;
		$ls_suffix = '';

		//As long as a page with the same slug exists, append an increasing number to the slug and try again
		while ($table->exists($la_conditions)) {
			$li_i++;
			$ls_suffix = '-' . $li_i;

			if ($li_length && (mb_strlen($ls_fileName . $ls_suffix) > $li_length)) {
				$ls_fileName = mb_substr($ls_fileName, 0, $li_length - mb_strlen($ls_suffix));
			}

			$la_conditions['name'] = $ls_fileName . $ls_suffix . '.' . $ls_extension;
		}

		//Append the suffix if it's not empty
		if ($ls_suffix) {
			$entity->name = $ls_fileName . $ls_suffix . '.' . $ls_extension;
		}
	}


	/**
	 * @param string $fileContents
	 * @return array
	 */
	protected function getSvgDimensions(string $fileContents): array {
		$lf_width = $lf_height = null;

		/** @noinspection RegExpRedundantEscape */
		preg_match('/viewbox="(?<sizes>[0-9\. ]*)"/i', $fileContents, $la_matches);
		if (!empty($la_matches['sizes'])) {
			$la_coordinates = explode(' ', $la_matches['sizes'], 4);

			$lf_width = (float)$la_coordinates[2];
			$lf_height = (float)$la_coordinates[3];
		}
		else {
			preg_match('/<svg[^>]*\s(width|height)="(\d+)"\s.*?(width|height)="(\d+)"/', $fileContents, $la_matches);
			if ($la_matches) {
				if (strtolower($la_matches[1]) === 'width') {
					$lf_width = (float)$la_matches[2];
					$lf_height = (float)$la_matches[4];
				}
				else {
					$lf_width = (float)$la_matches[4];
					$lf_height = (float)$la_matches[2];
				}
			}
		}


		return [
			'width' => $lf_width,
			'height' => $lf_height,
		];
	}


	/**
	 * Returns the dimensions of an image
	 * using the getimagesize function if available.
	 *
	 * @param mixed $tempName
	 * @return array|false
	 * @throws \ImagickException
	 * @noinspection PhpComposerExtensionStubsInspection
	 */
	protected function getImageSize(mixed $tempName): array|false {
		if (function_exists('getimagesize')) {
			return getimagesize($tempName);
		}

		$la_imageSize = [null, null];

		if (!class_exists('Imagick')) {
			return $la_imageSize;
		}

		$lo_imagick = new Imagick();
		$lo_imagick->pingImage($tempName);

		return [
			$lo_imagick->getImageWidth(),
			$lo_imagick->getImageHeight(),
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @return void
	 * @throws \ImagickException
	 */
	protected function setDimensions(Media $entity): void {
		$lo_stream = $entity->file->getStream();
		$ls_tempName = $lo_stream->getMetadata('uri');

		if ($entity->isImage()) {
			if ($entity->mimeType === 'image/svg+xml') {
				$la_dimensions = $this->getSvgDimensions(file_get_contents($ls_tempName));

				$entity->width = $la_dimensions['width'];
				$entity->height = $la_dimensions['height'];
			}
			else {
				$la_imageSize = $this->getImageSize($ls_tempName);

				$entity->width = $la_imageSize[0];
				$entity->height = $la_imageSize[1];
			}

			$entity->preview = ProcessStatus::NotRequired;

			if (empty($entity->crop)) {
				$entity->crop = [
					'rotate' => 'auto',
				];
			}
		}
		else {
			$entity->width = null;
			$entity->height = null;
			$entity->preview = ProcessStatus::Undefined;
		}
	}


	/**
	 * @param \Awyiss\Model\Table\MediaTable $table
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @return void
	 */
	protected function useExistingsFileData(MediaTable $table, Media $entity): void {
		$lo_currentMedia = static::$media[ $entity->mediaFolderId ][ $entity->name ] ?? null;
		if ($lo_currentMedia) {
			$entity->setNew(false);
			$entity->set([
				'id' => $lo_currentMedia->id,
				'alt' => $entity->alt ?? $lo_currentMedia->alt,
				'systemOrder' => $lo_currentMedia->systemOrder,
				'createdBy' => $lo_currentMedia->createdBy,
				'createdOn' => $lo_currentMedia->createdOn,
			], [
				'guard' => false,
			]);

			$entity->usageCount = $table->MediaAssignments->find()->where(['media_id' => $lo_currentMedia->id, 'deleted' => 0])->count();

			$entity->setDirty('systemOrder', false);

			if ($lo_currentMedia->attributes) {
				$entity->attributes = $lo_currentMedia->attributes;
			}
		}
	}
}
