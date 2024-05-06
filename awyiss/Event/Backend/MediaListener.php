<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Core\LocalConfig;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table\MediaTable;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Media scope of the backend
 */
class MediaListener implements EventListenerInterface {
	use EventListenerTrait;


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
	 * Before saving a file, make sure its name is unique, path is set
	 * image dimensions are known and file extension matches the mimetype
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $ao_event, Media $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $ao_event->getSubject();
		$lb_isNew = $ao_entity->isNew();

		// If the systemOrder is the only dirty field, we don't need to do anything
		if ($ao_entity->getDirty() === ['systemOrder']) {
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

		if (!isset(static::$media[ $ao_entity->mediaFolderId ])) {
			/** @var \Cake\Collection\Iterator\TreeIterator $lo_mediaFolderse */
			$lo_media = $lo_table->find()->where(['media_folder_id' => $ao_entity->mediaFolderId])->all();
			static::$media[ $ao_entity->mediaFolderId ] = $lo_media->indexBy('name')->toArray();
		}

		if (!$ao_entity->extension) {
			$la_knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $ao_entity->mimeType));

			if (!$la_knownExtensions) {
				$ao_event->stopPropagation();

				$ao_entity->setError(
					'name',
					__df(strtolower($lo_table->getI18nDomain()), 'validation', 'error_media_has_file_extension'),
					true
				);


				return;
			}

			$ao_entity->name .= '.' . current($la_knownExtensions);
		}

		if ($ao_entity->file && !$ao_entity->file->getError()) {
			$lo_stream = $ao_entity->file->getStream();
			$ls_tempName = $lo_stream->getMetadata('uri');

			if ($ao_entity->isImage()) {
				if ($ao_entity->mimeType === 'image/svg+xml') {
					$la_dimensions = $this->getSvgDimensions(file_get_contents($ls_tempName));

					$ao_entity->width = $la_dimensions['width'];
					$ao_entity->height = $la_dimensions['height'];
				}
				else {
					$la_imageSize = getimagesize($ls_tempName);

					$ao_entity->width = $la_imageSize[0];
					$ao_entity->height = $la_imageSize[1];
				}

				$ao_entity->preview = ProcessStatus::NotRequired;
				/*if ($ao_entity->mimeType !== 'image/webp') {
					$la_exifData = exif_read_data($ls_tempName, '', true);
				}*/
			}
			else {
				$ao_entity->width = null;
				$ao_entity->height = null;
				$ao_entity->preview = ProcessStatus::Undefined;
			}

			$ao_entity->webp = in_array($ao_entity->mimeType, ['image/webp', 'image/svg+xml']) ? ProcessStatus::NotRequired : ProcessStatus::Undefined;

			if ($lb_isNew && LocalConfig::read('upload.autoOverwrite', false, 'Media') === true) {
				$lo_currentMedia = static::$media[ $ao_entity->mediaFolderId ][ $ao_entity->name ] ?? null;
				if ($lo_currentMedia) {
					$ao_entity->setNew(false);
					$ao_entity->set([
						'id' => $lo_currentMedia->id,
						'alt' => $ao_entity->alt ?? $lo_currentMedia->alt,
						'systemOrder' => $lo_currentMedia->systemOrder,
						'createdBy' => $lo_currentMedia->createdBy,
						'createdOn' => $lo_currentMedia->createdOn,
					], [
						'guard' => false,
					]);

					$ao_entity->setDirty('systemOrder', false);

					if ($lo_currentMedia->attributes) {
						$ao_entity->attributes = $lo_currentMedia->attributes;
					}
				}
			}
			else {
				$this->ensureUniqueFileName($lo_table, $ao_entity);
			}
		}
		elseif ($ao_entity->isDirty('name') || $ao_entity->isDirty('mediaFolderId')) {
			$this->ensureUniqueFileName($lo_table, $ao_entity);
		}

		$ls_path = 'media/';

		if (isset(static::$mediaFolders[ $ao_entity->mediaFolderId ])) {
			$ls_path = static::$mediaFolders[ $ao_entity->mediaFolderId ]->path . '/';
		}

		$ao_entity->path = $ls_path . $ao_entity->name;
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, Media $ao_entity, ArrayObject $ao_options): void {
		if ($ao_entity->file && !$ao_entity->file->getError()) {
			$ao_entity->deleteConvertedFiles();
			$ao_entity->deleteResizedFiles();

			$ao_entity->file->moveTo(WWW_ROOT . str_replace('/', DS, $ao_entity->path));

			if ($ao_entity->hasOriginal('path') && $ao_entity->getOriginal('path') !== $ao_entity->get('path')) {
				unlink(WWW_ROOT . str_replace('/', DS, $ao_entity->getOriginal('path')));
			}
		}
		elseif ($ao_entity->hasOriginal('path') && $ao_entity->getOriginal('path') !== $ao_entity->get('path')) {
			$ao_entity->moveConvertedFiles();
			$ao_entity->moveResizedFiles();

			rename(
				WWW_ROOT . str_replace('/', DS, $ao_entity->getOriginal('path')),
				WWW_ROOT . str_replace('/', DS, $ao_entity->get('path'))
			);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(Event $ao_event, Media $ao_entity, ArrayObject $ao_options): void {
		$ls_sourceFile = $ao_entity->path;
		if ($ls_sourceFile && is_file($ls_sourceFile)) {
			unlink($ls_sourceFile);
		}

		$ao_entity->deleteConvertedFiles();
	}


	/**
	 * @param \Awyiss\Model\Table\MediaTable $ao_table
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @return void
	 */
	protected function ensureUniqueFileName(MediaTable $ao_table, Media $ao_entity): void {
		$ls_extension = $ao_entity->extension;
		$ls_fileName = $ao_entity->cleanName;

		$la_conditions = [
			'name' => $ao_entity->name,
			'media_folder_id' => $ao_entity->mediaFolderId,
		];

		$ls_primaryKey = $ao_table->getPrimaryKey();
		$li_id = $ao_entity->get($ls_primaryKey);
		if ($li_id) {
			$la_conditions['NOT'] = [$ao_table->getAlias() . '.' . $ls_primaryKey => $li_id];
		}


		$ls_field = $ao_table->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		$li_i = 1;
		$ls_suffix = '';

		//As long as a page with the same slug exists, append an increasing number to the slug and try again
		while ($ao_table->exists($la_conditions)) {
			$li_i++;
			$ls_suffix = '-' . $li_i;

			if ($li_length && (mb_strlen($ls_fileName . $ls_suffix) > $li_length)) {
				$ls_fileName = mb_substr($ls_fileName, 0, $li_length - mb_strlen($ls_suffix));
			}

			$la_conditions['name'] = $ls_fileName . $ls_suffix . '.' . $ls_extension;
		}

		//Append the suffix, if it's not empty
		if ($ls_suffix) {
			$ao_entity->name = $ls_fileName . $ls_suffix . '.' . $ls_extension;
		}
	}


	/**
	 * @param string $as_fileContents
	 * @return array
	 */
	protected function getSvgDimensions(string $as_fileContents): array {
		$lf_width = $lf_height = null;

		/** @noinspection RegExpRedundantEscape */
		preg_match('/viewbox="(?<sizes>[0-9\. ]*)"/i', $as_fileContents, $la_matches);
		if (!empty($la_matches['sizes'])) {
			$la_coordinates = explode(' ', $la_matches['sizes'], 4);

			$lf_width = (float)$la_coordinates[2];
			$lf_height = (float)$la_coordinates[3];
		}
		else {
			preg_match('/<svg[^>]*\s(width|height)="(\d+)"\s.*?(width|height)="(\d+)"/', $as_fileContents, $la_matches);
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
}
