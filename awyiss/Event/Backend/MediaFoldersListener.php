<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table\MediaFoldersTable;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;


/**
 * Event listeners for the MediaFolders scope of the backend
 */
class MediaFoldersListener implements EventListenerInterface {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.MediaFolders.afterCopyCommit' => 'afterCopyCommit',
			'Model.MediaFolders.beforeSave' => 'beforeSave',
			'Model.MediaFolders.afterSave' => 'afterSave',
			'Model.MediaFolders.afterSaveCommit' => 'afterSaveCommit',
			'Model.MediaFolders.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.MediaFolders.afterDeleteCommit' => 'afterDeleteCommit',
			'Model.MediaFolders.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		];
	}


	/**
	 * Before saving a media folder, make sure its path is unique.
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, MediaFolder $entity): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFoldersTable */
		$mediaFoldersTable = $event->getSubject();

		$field = $mediaFoldersTable->getSchema()->getColumn('path');
		$fieldLength = $field ? $field['length'] : 0;

		if (empty($entity->path)) {
			// Make sure the path is set. Use the title if it's empty.
			$entity->set('path', $entity->title);
		}

		if (
			!$entity->isDirty('path') &&
			!$entity->isDirty('languageShortcode') &&
			!$entity->isDirty('parentId') &&
			!$entity->isDirty('deleted')
		) {
			// If neither the path, language, parent nor deleted have changed, skip the path logic
			return;
		}

		$prePath = '';
		if (!empty($entity->parentId)) {
			/** @var \Awyiss\Model\Entity\MediaFolder $parentMediaFolder */
			$parentMediaFolder = $mediaFoldersTable->get($entity->parentId);
			// If there's a parent media folder, add its path the one of the current media folder
			$prePath = trim($parentMediaFolder->path, '/') . '/';

			$entity->parentsActive = $parentMediaFolder->active && $parentMediaFolder->parentsActive;
		}
		elseif ($entity->parentsActive !== true) {
			$entity->parentsActive = true;
		}

		$parts = explode('/', $entity->path);
		$path = array_pop($parts);
		$path = $prePath . rtrim($path, '-');
		$languageShortcode = $entity->languageShortcode;

		$originalPath = $entity->hasOriginal('path') ? $entity->getOriginal('path') : $entity->path;
		$originalLanguageShortcode = $entity->hasOriginal('languageShortcode') ? $entity->getOriginal('languageShortcode') : $entity->languageShortcode;

		if (!str_starts_with($path, 'media/') && $path !== 'media') {
			$path = 'media/' . $path;
		}

		// When the path has changed
		if (
			$entity->isNew() ||
			$path != $originalPath ||
			$languageShortcode != $originalLanguageShortcode
		) {
			$path = $this->ensureUniquePath($mediaFoldersTable, $entity, $path, $fieldLength);
		}

		$entity->set('path', $path, ['setter' => false]);
		if (!$entity->isNew() && $path === $originalPath) {
			$entity->setDirty('path', false);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function afterCopyCommit(Event $event, MediaFolder $entity, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFoldersTable */
		$mediaFoldersTable = $event->getSubject();

		/**
		 * @var \Awyiss\Model\Entity\MediaFolder $originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$originalEntity = $entity->originalEntity;

		$this->copyMediaEntities($entity, $originalEntity, $mediaFoldersTable);

		if ($options['_primary'] === false) {
			return;
		}

		$originalPath = $originalEntity->path;
		if (!str_starts_with($originalPath, 'media/')) {
			$originalPath = 'media/' . $originalPath;
		}

		$this->copyDirectory(
			WWW_ROOT . str_replace('/', DS, $originalPath),
			WWW_ROOT . str_replace('/', DS, $entity->path)
		);
	}


	/**
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \ArrayObject $options
	 * @noinspection DuplicatedCode, PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, MediaFolder $entity, ArrayObject $options): void {
		if ($entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFoldersTable */
		$mediaFoldersTable = $event->getSubject();

		$originalPath = $entity->hasOriginal('path') ? $entity->getOriginal('path') : null;
		$pathChanged = $originalPath && $entity->path != $originalPath;

		if ($pathChanged) {
			$this->createHistoricalPaths($originalPath);

			foreach ([$mediaFoldersTable->getTable(), 'media', 'media_resized_images'] as $table) {
				$this->rebuildDatabasePath($table, $entity, $originalPath);
			}

			if (is_dir(WWW_ROOT . str_replace('/', DS, $originalPath))) {
				rename(
					WWW_ROOT . str_replace('/', DS, $originalPath),
					WWW_ROOT . str_replace('/', DS, $entity->path)
				);
			}
		}

		$originalActive = $entity->hasOriginal('active') ? $entity->getOriginal('active') : null;
		$activeChanged = $originalActive !== null && $entity->active !== $originalActive;

		$originalParentsActive = $entity->hasOriginal('parentsActive') ? $entity->getOriginal('parentsActive') : null;
		$parentsActiveChanged = $originalParentsActive !== null && $entity->parentsActive !== $originalParentsActive;

		if ($activeChanged || $parentsActiveChanged) {
			$this->updateDescendants(
				$mediaFoldersTable,
				$entity,
				$originalPath,
			);
		}
	}


	/**
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, MediaFolder $entity, ArrayObject $options): void {
		if (($options['isCopy'] ?? false) === false && !is_dir(WWW_ROOT . str_replace('/', DS, $entity->path))) {
			mkdir(WWW_ROOT . str_replace('/', DS, $entity->path), 0755, true);
		}

		$this->clearMediaFoldersCache();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(Event $event, MediaFolder $entity, ArrayObject $options): void {
		if ($options['_primary'] ?? null === true) {
			$entity->path = substr_replace($entity->path, '/_deleted_', strrpos($entity->path, '/'), 1) . '_' . time();
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDeleteCommit(Event $event, MediaFolder $entity, ArrayObject $options): void {
		$this->clearMediaFoldersCache();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(Event $event, MediaFolder $entity, ArrayObject $options): void {
		$this->clearMediaFoldersCache();
	}


	/**
	 * @param string $originalPath
	 * @return void
	 */
	protected function createHistoricalPaths(string $originalPath): void {
		if (
			!in_array(LocalConfig::read('createHistoricalPaths', false, 'Media'), [
				MediaConfigOptions::CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE,
				MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS,
			])
		) {
			return;
		}

		$mediaTable = $this->fetchTable('Media');
		$urlHistoryTable = $this->fetchTable('UrlHistory');

		// Find all media whose path starts with the original path of the provided folder
		$records = $mediaTable->find()->where(function (QueryExpression $expression) use ($originalPath) {
			return $expression->like('path', $originalPath . '/%');
		})->all();

		if (!$records->count()) {
			return;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$userId = $this->getIdentity()?->id;
		$now = new DateTime('now');

		$query = $urlHistoryTable->insertQuery()->insert(['url', 'scope', 'foreign_key', 'status', 'created_by', 'created_on']);

		/**
		 * For each media that has a path that starts with the original path of the provided folder,
		 * create a new historical entry.
		 *
		 * @var \Awyiss\Model\Entity\Media $media
		 */
		foreach ($records as $media) {
			$query->values([
				'url' => $media->path,
				'scope' => 'media',
				'foreign_key' => $media->id,
				'status' => 308,
				'created_by' => $userId,
				'created_on' => $now,
			]);
		}

		$query->execute();
	}


	/**
	 * @param string $table
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param mixed $originalPath
	 * @return void
	 */
	protected function rebuildDatabasePath(string $table, MediaFolder $entity, string $originalPath): void {
		$query = FactoryLocator::get('Table')->get(Inflector::camelize($table))->updateQuery();

		/**
		 * UPDATE media_folders SET path = (CONCAT('newpath', substr(path, '8'))) WHERE path LIKE 'oldpath/%'
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$query->update($table)->set('path', $query->newExpr($query->func()->concat([
			$entity->path,
			$query->func()->substr([
				'path' => 'identifier',
				mb_strlen($originalPath) + 1,
			], [
				null,
				'integer',
			]),
		])))->where(function (QueryExpression $expression/*, Query $query*/) use ($originalPath) {
			return $expression->like('path', $originalPath . '/%');
		})->execute();
	}


	/**
	 * @param \Awyiss\Model\Table\MediaFoldersTable $table
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param string|null $originalPath
	 * @return void
	 */
	protected function updateDescendants(
		MediaFoldersTable $table,
		MediaFolder $entity,
		?string $originalPath,
	): void {
		$query = $table->updateQuery();

		$parentsActive = $entity->active && $entity->parentsActive;
		if ($parentsActive) {
			/**
			 * When updating all media folders with the same path (LIKE 'oldpath/%'),
			 * do not set the parents_active to true for media folders that
			 * are descendants of inactive folders.
			 */
			$subFolders = $table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $expression) use ($entity, $originalPath) {
				return $expression->like('path', ($originalPath ?? $entity->path) . '/%');
			})->where(['active' => false])->all();

			foreach ($subFolders as $subFolder) {
				$query->where(function (QueryExpression $expression/*, Query $query*/) use ($subFolder) {
					return $expression->notLike('path', $subFolder->path . '/%');
				});
			}
		}

		$query->set('parents_active', $parentsActive);

		/**
		 * WHERE path LIKE 'oldpath/%'
		 */
		$query->where(function (QueryExpression $expression/*, Query $query*/) use ($entity, $originalPath) {
			return $expression->like('path', ($originalPath ?? $entity->path) . '/%');
		});

		$query->execute();
	}


	/**
	 * Copies a directory with all its content to another location.
	 *
	 * @param string $sourceDirectory The source directory.
	 * @param string $targetDirectory The destination directory.
	 * @returns void
	 * @throws \Exception
	 */
	protected function copyDirectory(string $sourceDirectory, string $targetDirectory): void {
		//Ensure the source directory exists
		if (!is_dir($sourceDirectory)) {
			throw new Exception("Source directory does not exist: $sourceDirectory");
		}

		//Create the destination directory if it does not exist
		if (!is_dir($targetDirectory)) {
			mkdir($targetDirectory, 0755, true);
		}

		//Open the source directory
		$dir = opendir($sourceDirectory);

		//Read each file in directory
		while (($fileName = readdir($dir)) !== false) {
			if (($fileName != '.') && ($fileName != '..')) {
				if (is_dir($sourceDirectory . DS . $fileName)) {
					//If the file is a directory, recursively copy it
					$this->copyDirectory($sourceDirectory . DS . $fileName, $targetDirectory . DS . $fileName);
				}
				else {
					//If the file is not a directory, copy it to the destination
					copy($sourceDirectory . DS . $fileName, $targetDirectory . DS . $fileName);
				}
			}
		}

		//Close the source directory
		closedir($dir);
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param \Awyiss\Model\Entity\MediaFolder $originalEntity
	 * @param \Awyiss\Model\Table\MediaFoldersTable $table
	 * @return void
	 * @throws \Exception
	 */
	protected function copyMediaEntities(MediaFolder $entity, MediaFolder $originalEntity, MediaFoldersTable $table): void {
		/** @uses \Awyiss\Model\Table::findTranslations() */
		$files = $table->Media->find('translations')->where(['media_folder_id' => $originalEntity->id])->all();
		/** @var \Awyiss\Model\Entity\Media $file */
		foreach ($files as $file) {
			$file->setNew(true);
			$file->mediaFolderId = $entity->id;
		}

		$table->Media->saveMany($files->toList(), [
			'checkRules' => false,
			'isCopy' => true,
		]);
	}


	/**
	 * @param \Awyiss\Model\Table\MediaFoldersTable $table
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param string $path
	 * @param int $fieldLength
	 * @return string
	 */
	protected function ensureUniquePath(MediaFoldersTable $table, MediaFolder $entity, string $path, int $fieldLength): string {
		$field = $table->getAlias() . '.path';

		$conditions = [
			$field => $path,
		];

		$primaryKey = $table->getPrimaryKey();
		$id = $entity->get($primaryKey);
		if ($id) {
			$conditions['NOT'] = [$table->getAlias() . '.' . $primaryKey => $id];
		}

		/**
		 * `$conditions` holds an array of query conditions that are used to find media folders with the same
		 * path
		 * ```
		 * [
		 *	"MediaFolders.path" => "new/path/of/the/current/mediafolder"
		 * 	"language_shortcode" => "de"
		 * 	"NOT" => [
		 * 		"MediaFolders.id" => 1234
		 * 	]
		 * ]
		 * ```
		 */

		$i = 1;
		$suffix = '';

		//As long as a media folder with the same path exists, append an increasing number to the path and try again
		while ($table->exists($conditions)) {
			$i++;
			$suffix = '-' . $i;

			if ($fieldLength && (mb_strlen($path . $suffix) > $fieldLength)) {
				$path = mb_substr($path, 0, $fieldLength - mb_strlen($suffix));
			}

			$conditions[ $field ] = $path . $suffix;
		}

		//Append the suffix, if it's not empty
		if ($suffix) {
			$path .= $suffix;
		}

		return $path;
	}


	/**
	 * Clear the media folders cache
	 * in the `\Awyiss\Event\Backend\MediaListener` class.
	 *
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::clearMediaFoldersCache
	 */
	protected function clearMediaFoldersCache(): void {
		MediaListener::clearMediaFoldersCache();
	}
}
