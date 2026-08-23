<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table\MediaTable;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Dom\XMLDocument;
use Imagick;


/**
 * Event listeners for the Media scope of the backend
 */
class MediaListener implements EventListenerInterface {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var array<int, array<int, \Awyiss\Model\Entity\Media>>
	 */
	protected static array $media = [];
	/**
	 * @var array<int, \Awyiss\Model\Entity\MediaFolder>|null
	 */
	protected static ?array $mediaFolders = null;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Media.beforeSave' => 'beforeSave',
			'Model.Media.afterSave' => 'afterSave',
			'Model.Media.afterDelete' => 'afterDelete',
			'Configuration.Media.Frontend.resizing.fileType.afterSaveCommit' => 'clearMediaCacheAfterSave',
			'Configuration.Media.Frontend.resizing.quality.afterSaveCommit' => 'clearMediaCacheAfterSave',
			'Configuration.Media.Frontend.resizing.fileType.afterDeleteCommit' => 'clearMediaCacheAfterDelete',
			'Configuration.Media.Frontend.resizing.quality.afterDeleteCommit' => 'clearMediaCacheAfterDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnused,PhpUnusedParameterInspection
	 */
	public function clearMediaCacheAfterSave(Event $event, Configuration $configuration): void {
		if (
			!$configuration->isNew()
			&& (
				!$configuration->hasOriginal('value')
				|| $configuration->getOriginal('value') === $configuration->value
			)
		) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');

		$queuedJobsTable->createJob('Queue.Execute', [
			'command' => 'bin' . DS . 'cake media clear_cache',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Media::clearCache',
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnused,PhpUnusedParameterInspection
	 */
	public function clearMediaCacheAfterDelete(Event $event, Configuration $configuration): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');

		$queuedJobsTable->createJob('Queue.Execute', [
			'command' => 'bin' . DS . 'cake media clear_cache',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Media::clearCache',
		]);
	}


	/**
	 * Before saving a file, make sure its name is unique, its path is set,
	 * image dimensions are known, and the file extension matches the mimetype
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \ImagickException
	 * @noinspection PhpUnusedParameterInspection, PhpComposerExtensionStubsInspection
	 */
	public function beforeSave(Event $event, Media $entity, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $event->getSubject();
		$isNew = $entity->isNew();

		// If the systemOrder is the only dirty field, we don't need to do anything
		if ($entity->getDirty() === ['systemOrder']) {
			return;
		}

		if (!isset(static::$mediaFolders)) {
			/**
			 * @var \Cake\Collection\Iterator\TreeIterator $mediaFolders
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$mediaFolders = $mediaTable->MediaFolders
				->find()
				->select(['id', 'title', 'path'])
				->all()
			;
			static::$mediaFolders = $mediaFolders->indexBy('id')->toArray();
		}

		if (!isset(static::$media[ $entity->mediaFolderId ])) {
			/** @var \Cake\Collection\Iterator\TreeIterator $mediaFolders */
			$media = $mediaTable
				->find()
				->where(['mediaFolderId' => $entity->mediaFolderId])
				->all()
			;
			static::$media[ $entity->mediaFolderId ] = $media->indexBy('name')->toArray();
		}

		if (!$entity->extension) {
			$knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $entity->mimeType));

			if (!$knownExtensions) {
				$event->stopPropagation();

				$entity->setError(
					'name',
					__df($mediaTable->getI18nDomain(), 'Validation', 'error_media_has_file_extension'),
					true
				);

				return;
			}

			$realExtension = current($knownExtensions);
			if ($realExtension === 'jpeg') {
				$realExtension = 'jpg';
			}

			$entity->name .= '.' . $realExtension;
		}

		// Unset file if there was an error during upload
		if ($entity->file && $entity->file->getError()) {
			$entity->file = null;
		}

		if ($entity->file) {
			$this->setDimensions($entity);

			$entity->avif = in_array($entity->mimeType, ['image/avif', 'image/svg+xml'])
				? ProcessStatus::NotRequired
				: ProcessStatus::Undefined;

			$entity->webp = in_array($entity->mimeType, ['image/webp', 'image/svg+xml'])
				? ProcessStatus::NotRequired
				: ProcessStatus::Undefined;

			if ($isNew && LocalConfig::read('upload.autoOverwrite', false, 'Media') === true) {
				$this->useExistingsFileData($mediaTable, $entity);
			}
			else {
				$this->ensureUniqueFileName($mediaTable, $entity);
			}
		}
		elseif ($entity->isDirty('name') || $entity->isDirty('mediaFolderId')) {
			$this->ensureUniqueFileName($mediaTable, $entity);
		}

		$path = 'media/';
		$originalPath = $entity->hasOriginal('path') ? $entity->getOriginal('path') : $entity->path;

		if (isset(static::$mediaFolders[ $entity->mediaFolderId ])) {
			$path = static::$mediaFolders[ $entity->mediaFolderId ]->path . '/';
		}

		$entity->path = $path . $entity->name;
		if (!$entity->isNew() && $path . $entity->name === $originalPath) {
			$entity->setDirty('path', false);
		}
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

			$this->ensureSvgId(WWW_ROOT . str_replace('/', DS, $entity->path));

			if ($entity->hasOriginal('path') && $entity->getOriginal('path') !== $entity->get('path')) {
				$this->createHistoricalPaths($entity, $entity->getOriginal('path'));

				unlink(WWW_ROOT . str_replace('/', DS, $entity->getOriginal('path')));
			}
		}
		elseif (!$entity->isNew() && $entity->hasOriginal('path') && $entity->getOriginal('path') !== $entity->get('path')) {
			$this->createHistoricalPaths($entity, $entity->getOriginal('path'));

			$entity->moveConvertedFiles();
			$entity->moveResizedFiles();

			rename(
				WWW_ROOT . str_replace('/', DS, $entity->getOriginal('path')),
				WWW_ROOT . str_replace('/', DS, $entity->get('path'))
			);

			$this->ensureSvgId(WWW_ROOT . str_replace('/', DS, $entity->get('path')));
		}

		if (!$entity->isNew() && $entity->hasOriginal('focusPoint') && $entity->getOriginal('focusPoint') !== $entity->get('focusPoint')) {
			$entity->deleteResizedFiles();
		}

		static::clearMediaFoldersCache();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(Event $event, Media $entity, ArrayObject $options): void {
		$sourceFile = $entity->path;
		if ($sourceFile && is_file($sourceFile)) {
			unlink($sourceFile);
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
		if (
			!in_array(LocalConfig::read('createHistoricalPaths', false, 'Media'), [
				MediaConfigOptions::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE,
				MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS,
			])
		) {
			return;
		}

		$urlHistoryTable = $this->fetchTable('UrlHistory');

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$userId = $this->getIdentity()?->id;
		$now = new DateTime('now');

		$query = $urlHistoryTable->insertQuery()->insert(['url', 'scope', 'foreignKey', 'status', 'createdBy', 'createdOn']);

		$query->values([
			'url' => $originalPath,
			'scope' => 'Media',
			'foreignKey' => $entity->id,
			'status' => 308,
			'createdBy' => $userId,
			'createdOn' => $now,
		]);

		$query->execute();
	}


	/**
	 * @param \Awyiss\Model\Table\MediaTable $table
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @return void
	 */
	protected function ensureUniqueFileName(MediaTable $table, Media $entity): void {
		$extension = $entity->extension;
		$fileName = $entity->cleanName;

		$conditions = [
			'name' => $entity->name,
			'mediaFolderId' => $entity->mediaFolderId,
		];

		$primaryKey = $table->getPrimaryKey();
		$id = $entity->get($primaryKey);
		if ($id) {
			$conditions['NOT'] = [$table->getAlias() . '.' . $primaryKey => $id];
		}


		$field = $table->getSchema()->getColumn('name');
		$length = $field ? $field['length'] : 0;

		$i = 1;
		$suffix = '';

		// As long as a page with the same slug exists, append an increasing number to the slug and try again
		while ($table->exists($conditions)) {
			$i++;
			$suffix = '-' . $i . '.' . $extension;

			if ($length && (mb_strlen($fileName . $suffix) > $length)) {
				$fileName = mb_substr($fileName, 0, $length - mb_strlen($suffix));
			}

			$conditions['name'] = $fileName . $suffix;
		}

		// Append the suffix if it's not empty
		if ($suffix) {
			$entity->name = $fileName . $suffix;
		}
	}


	/**
	 * @param string $fileContents
	 * @return array
	 */
	protected function getSvgDimensions(string $fileContents): array {
		$width = $height = null;

		preg_match('/<svg[^>]*\s(width|height)="(\d+)"[^>]*\s(width|height)="(\d+)"[^>]*>/i', $fileContents, $matches);
		if ($matches) {
			if (strtolower($matches[1]) === 'width') {
				$width = (float)$matches[2];
				$height = (float)$matches[4];
			}
			else {
				$width = (float)$matches[4];
				$height = (float)$matches[2];
			}
		}
		else {
			/** @noinspection RegExpRedundantEscape */
			preg_match('/viewbox="(?<sizes>[0-9\. ]*)"/i', $fileContents, $matches);
			if (!empty($matches['sizes'])) {
				$coordinates = explode(' ', $matches['sizes'], 4);
				$width = (float)$coordinates[2];
				$height = (float)$coordinates[3];
			}
		}

		return [
			'width' => $width,
			'height' => $height,
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

		$imageSize = [null, null];

		if (!class_exists('Imagick')) {
			return $imageSize;
		}

		$imagick = new Imagick();
		$imagick->pingImage($tempName);

		return [
			$imagick->getImageWidth(),
			$imagick->getImageHeight(),
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @return void
	 * @throws \ImagickException
	 */
	protected function setDimensions(Media $entity): void {
		$stream = $entity->file->getStream();
		$tempName = $stream->getMetadata('uri');

		if ($entity->mimeType === 'image/svg+xml') {
			$dimensions = $this->getSvgDimensions(file_get_contents($tempName));

			$entity->width = $dimensions['width'];
			$entity->height = $dimensions['height'];

			$entity->preview = ProcessStatus::NotRequired;
		}
		elseif ($entity->isImage()) {
			$imageSize = $this->getImageSize($tempName);

			$entity->width = (float)$imageSize[0];
			$entity->height = (float)$imageSize[1];

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
		$currentMedia = static::$media[ $entity->mediaFolderId ][ $entity->name ] ?? null;
		if ($currentMedia) {
			$entity->setNew(false);
			$entity->patch([
				'id' => $currentMedia->id,
				'alt' => $entity->alt ?? $currentMedia->alt,
				'systemOrder' => $currentMedia->systemOrder,
				'createdBy' => $currentMedia->createdBy,
				'createdOn' => $currentMedia->createdOn,
			], [
				'guard' => false,
			]);

			$entity->usageCount = $table->MediaAssignments
				->find()
				->where(['mediaId' => $currentMedia->id, 'deleted' => 0])
				->count()
			;

			$entity->setDirty('systemOrder', false);

			if ($currentMedia->attributes) {
				$entity->attributes = $currentMedia->attributes;
			}
		}
	}


	/**
	 * Make sure the contents of the <svg> tag are wrapped inside an AWYISS_SVG_ID
	 *
	 * @param string $path
	 * @return void
	 */
	protected function ensureSvgId(string $path): void {
		if (!str_ends_with($path, '.svg')) {
			return;
		}

		$contents = file_get_contents($path);

		$dom = XMLDocument::createFromString($contents, LIBXML_NOERROR);
		$svg = $dom->getElementsByTagName('svg')->item(0);
		if (!$svg) {
			return;
		}

		// If there is no first level child with id="AWYISS_SVG_ID", add it
		foreach ($svg->childNodes as $child) {
			if ($child->nodeType === XML_ELEMENT_NODE && $child->getAttribute('id') === 'AWYISS_SVG_ID') {
				return;
			}
		}

		// Create a <g> element with id="AWYISS_SVG_ID" in the SVG namespace and move all children of <svg> into it
		$g = $dom->createElementNS('http://www.w3.org/2000/svg', 'g');
		$g->setAttribute('id', 'AWYISS_SVG_ID');

		// Move all children of <svg> into the new <g> element
		while ($child = $svg->firstChild) {
			$g->appendChild($child);
		}

		$svg->appendChild($g);

		$content = $dom->saveXml($dom->documentElement, LIBXML_NOEMPTYTAG);

		file_put_contents($path, $content);
	}


	/**
	 * Clears the cached media folder data
	 *
	 * @return void
	 */
	public static function clearMediaFoldersCache(): void {
		static::$mediaFolders = null;
		static::$media = [];
	}
}
