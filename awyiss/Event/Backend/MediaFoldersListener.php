<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Core\LocalConfig;
use Awyiss\Event\EventListenerTrait;
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
	use EventListenerTrait;
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


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
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $event->getSubject();

		$ls_field = $lo_table->getSchema()->getColumn('path');
		$li_fieldLength = $ls_field ? $ls_field['length'] : 0;

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

		$ls_prePath = '';
		if (!empty($entity->parentId)) {
			/** @var \Awyiss\Model\Entity\MediaFolder $lo_parentMediaFolder */
			$lo_parentMediaFolder = $lo_table->get($entity->parentId);
			// If there's a parent media folder, add its path the one of the current media folder
			$ls_prePath = trim($lo_parentMediaFolder->path, '/') . '/';

			$entity->parentsActive = $lo_parentMediaFolder->active && $lo_parentMediaFolder->parentsActive;
		}
		elseif ($entity->parentsActive !== true) {
			$entity->parentsActive = true;
		}

		$la_parts = explode('/', $entity->path);
		$ls_path = array_pop($la_parts);
		$ls_path = $ls_prePath . rtrim($ls_path, '-');
		$ls_languageShortcode = $entity->languageShortcode;

		$ls_originalPath = $entity->hasOriginal('path') ? $entity->getOriginal('path') : $entity->path;
		$ls_originalLanguageShortcode = $entity->hasOriginal('languageShortcode') ? $entity->getOriginal('languageShortcode') : $entity->languageShortcode;

		if (!str_starts_with($ls_path, 'media/') && $ls_path !== 'media') {
			$ls_path = 'media/' . $ls_path;
		}

		// When the path has changed
		if (
			$entity->isNew() ||
			$ls_path != $ls_originalPath ||
			$ls_languageShortcode != $ls_originalLanguageShortcode
		) {
			$ls_path = $this->ensureUniquePath($lo_table, $entity, $ls_path, $li_fieldLength);
		}

		$entity->set('path', $ls_path, ['setter' => false]);
		if (!$entity->isNew() && $ls_path === $ls_originalPath) {
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
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $event->getSubject();

		/**
		 * @var \Awyiss\Model\Entity\MediaFolder $lo_originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$lo_originalEntity = $entity->originalEntity;

		$this->copyMediaEntities($entity, $lo_originalEntity, $lo_table);

		if ($options['_primary'] === false) {
			return;
		}

		$ls_originalPath = $lo_originalEntity->path;
		if (!str_starts_with($ls_originalPath, 'media/')) {
			$ls_originalPath = 'media/' . $ls_originalPath;
		}

		$this->copyDirectory(
			WWW_ROOT . str_replace('/', DS, $ls_originalPath),
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

		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $event->getSubject();

		$ls_originalPath = $entity->hasOriginal('path') ? $entity->getOriginal('path') : null;
		$lb_pathChanged = $ls_originalPath && $entity->path != $ls_originalPath;

		$lb_originalActive = $entity->hasOriginal('active') ? $entity->getOriginal('active') : null;
		$lb_activeChanged = $lb_originalActive !== null && $entity->active !== $lb_originalActive;

		$lb_originalParentsActive = $entity->hasOriginal('parentsActive') ? $entity->getOriginal('parentsActive') : null;
		$lb_parentsActiveChanged = $lb_originalParentsActive !== null && $entity->parentsActive !== $lb_originalParentsActive;

		if ($lb_pathChanged) {
			$this->createHistoricalPaths($ls_originalPath);

			foreach ([$lo_table->getTable(), 'media', 'media_resized_images'] as $ls_table) {
				$this->rebuildDatabasePath($ls_table, $entity, $ls_originalPath);
			}

			if (is_dir(WWW_ROOT . str_replace('/', DS, $ls_originalPath))) {
				rename(
					WWW_ROOT . str_replace('/', DS, $ls_originalPath),
					WWW_ROOT . str_replace('/', DS, $entity->path)
				);
			}
		}

		if ($lb_activeChanged || $lb_parentsActiveChanged) {
			$this->updateDescendants(
				$lo_table,
				$entity,
				$ls_originalPath,
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
		if (!is_dir(WWW_ROOT . str_replace('/', DS, $entity->path)) && ($options['isCopy'] ?? false) === false) {
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

		$ls_originalPath = $originalPath;
		$lo_mediaTable = $this->fetchTable('Media');
		$lo_urlHistoryTable = $this->fetchTable('UrlHistory');

		// Find all media whose path starts with the original path of the provided folder
		$lo_records = $lo_mediaTable->find()->where(function (QueryExpression $expression) use ($ls_originalPath) {
			return $expression->like('path', $ls_originalPath . '/%');
		})->all();

		if (!$lo_records->count()) {
			return;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$li_userId = $this->getIdentity()?->id;
		$ld_now = new DateTime('now');

		$lo_query = $lo_urlHistoryTable->insertQuery()->insert(['url', 'scope', 'foreign_key', 'status', 'created_by', 'created_on']);

		/**
		 * For each media that has a path that starts with the original path of the provided folder,
		 * create a new historical entry.
		 *
		 * @var \Awyiss\Model\Entity\Media $lo_media
		 */
		foreach ($lo_records as $lo_media) {
			$lo_query->values([
				'url' => $lo_media->path,
				'scope' => 'media',
				'foreign_key' => $lo_media->id,
				'status' => 308,
				'created_by' => $li_userId,
				'created_on' => $ld_now,
			]);
		}

		$lo_query->execute();
	}


	/**
	 * @param string $table
	 * @param \Awyiss\Model\Entity\MediaFolder $entity
	 * @param mixed $ls_originalPath
	 * @return void
	 */
	protected function rebuildDatabasePath(string $table, MediaFolder $entity, mixed $ls_originalPath): void {
		$lo_query = FactoryLocator::get('Table')->get(Inflector::camelize($table))->updateQuery();

		/**
		 * UPDATE media_folders SET path = (CONCAT('newpath', substr(path, '8'))) WHERE path LIKE 'oldpath/%'
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_query->update($table)->set('path', $lo_query->newExpr($lo_query->func()->concat([
			$entity->path,
			$lo_query->func()->substr([
				'path' => 'identifier',
				mb_strlen($ls_originalPath) + 1,
			], [
				null,
				'integer',
			]),
		])))->where(function (QueryExpression $expression/*, Query $query*/) use ($ls_originalPath) {
			return $expression->like('path', $ls_originalPath . '/%');
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
		$lo_query = $table->updateQuery();

		$lb_parentsActive = $entity->active && $entity->parentsActive;

		$lo_entity = $entity;
		$ls_originalPath = $originalPath;

		if ($lb_parentsActive) {
			/**
			 * When updating all media folders with the same path (LIKE 'oldpath/%'),
			 * do not set the parents_active to true for media folders that
			 * are descendants of inactive folders.
			 */
			$lo_subFolders = $table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $expression) use ($lo_entity, $ls_originalPath) {
				return $expression->like('path', ($ls_originalPath ?? $lo_entity->path) . '/%');
			})->where(['active' => false])->all();

			foreach ($lo_subFolders as $lo_subFolder) {
				$lo_query->where(function (QueryExpression $expression/*, Query $query*/) use ($lo_subFolder) {
					return $expression->notLike('path', $lo_subFolder->path . '/%');
				});
			}
		}

		$lo_query->set('parents_active', $lb_parentsActive);

		/**
		 * WHERE path LIKE 'oldpath/%'
		 */
		$lo_query->where(function (QueryExpression $expression/*, Query $query*/) use ($lo_entity, $ls_originalPath) {
			return $expression->like('path', ($ls_originalPath ?? $lo_entity->path) . '/%');
		});

		$lo_query->execute();
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
		$lr_dir = opendir($sourceDirectory);

		//Read each file in directory
		while (($ls_fileName = readdir($lr_dir)) !== false) {
			if (($ls_fileName != '.') && ($ls_fileName != '..')) {
				if (is_dir($sourceDirectory . DS . $ls_fileName)) {
					//If the file is a directory, recursively copy it
					$this->copyDirectory($sourceDirectory . DS . $ls_fileName, $targetDirectory . DS . $ls_fileName);
				}
				else {
					//If the file is not a directory, copy it to the destination
					copy($sourceDirectory . DS . $ls_fileName, $targetDirectory . DS . $ls_fileName);
				}
			}
		}

		//Close the source directory
		closedir($lr_dir);
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
		$lo_files = $table->Media->find('translations')->where(['media_folder_id' => $originalEntity->id])->all();
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($lo_files as $lo_file) {
			$lo_file->unset((array)$table->getPrimaryKey());
			$lo_file->unset(['mediaFolderId', 'path']);
			$lo_file->setNew(true);

			$lo_file->mediaFolderId = $entity->id;
		}

		$table->Media->saveMany($lo_files->toList(), [
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
		$ls_field = $table->getAlias() . '.path';

		$ls_path = $path;
		$la_conditions = [
			$ls_field => $ls_path,
		];

		$ls_primaryKey = $table->getPrimaryKey();
		$li_id = $entity->get($ls_primaryKey);
		if ($li_id) {
			$la_conditions['NOT'] = [$table->getAlias() . '.' . $ls_primaryKey => $li_id];
		}

		/**
		 * `$la_conditions` holds an array of query conditions that are used to find media folders with the same
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

		$li_i = 1;
		$ls_suffix = '';

		//As long as a media folder with the same path exists, append an increasing number to the path and try again
		while ($table->exists($la_conditions)) {
			$li_i++;
			$ls_suffix = '-' . $li_i;

			if ($fieldLength && (mb_strlen($ls_path . $ls_suffix) > $fieldLength)) {
				$ls_path = mb_substr($ls_path, 0, $fieldLength - mb_strlen($ls_suffix));
			}

			$la_conditions[ $ls_field ] = $ls_path . $ls_suffix;
		}

		//Append the suffix, if it's not empty
		if ($ls_suffix) {
			$ls_path .= $ls_suffix;
		}

		return $ls_path;
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
