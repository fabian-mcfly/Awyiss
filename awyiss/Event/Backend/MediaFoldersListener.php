<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table\MediaFoldersTable;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;


/**
 * Event listeners for the MediaFolders scope of the backend
 */
class MediaFoldersListener implements EventListenerInterface {
	use LocatorAwareTrait;
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.MediaFolders.beforeCopy' => 'beforeCopy',
			'Model.MediaFolders.afterCopy' => 'afterCopy',
			'Model.MediaFolders.beforeSave' => 'beforeSave',
			'Model.MediaFolders.afterSave' => 'afterSave',
			'Model.MediaFolders.beforeSoftDelete' => 'beforeSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 */
	public function beforeCopy(Event $ao_event, MediaFolder $ao_entity, ArrayObject $ao_options): void {
		if ($ao_options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $ao_event->getSubject();

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_originalEntity */
		$lo_originalEntity = $ao_entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren();

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childMediaFolders')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_childMediaFolder */
		foreach ($lo_children as $lo_childMediaFolder) {
			$la_primaryKeys = $lo_childMediaFolder->extract((array)$lo_table->getPrimaryKey());
			$lo_childMediaFolder->originalPrimaryKeys = $la_primaryKeys;

			$lo_childMediaFolder->unset((array)$lo_table->getPrimaryKey());
			$lo_childMediaFolder->setNew(true);

			$lo_childMediaFolder->set($ao_entity->extract($la_relatedColumns));
		}

		$ao_entity->childMediaFolders = $lo_nestedChildren;
	}


	/**
	 * Before saving a media folder, make sure its path is unique.
	 *
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $ao_event, MediaFolder $ao_entity): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$ls_field = $lo_table->getSchema()->getColumn('path');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->path)) {
			//Make sure the path is set. Use the title if it's empty.
			$ao_entity->set('path', $ao_entity->title);
		}

		if (
			!$ao_entity->isDirty('path') && !$ao_entity->isDirty('languageShortcode') && !$ao_entity->isDirty('parentId') && !$ao_entity->isDirty('deleted')
		) {
			//If neither the path, the language nor the parent id have changed, skip the path logic
			return;
		}

		$ls_prePath = '';
		if (!empty($ao_entity->parentId)) {
			/** @var \Awyiss\Model\Entity\MediaFolder $lo_parentMediaFolder */
			$lo_parentMediaFolder = $lo_table->get($ao_entity->parentId);
			//If there's a parent media folder, add its path the one of the current media folder
			$ls_prePath = trim($lo_parentMediaFolder->path, '/') . '/';
		}

		$la_parts = explode('/', $ao_entity->path);
		$ls_path = array_pop($la_parts);
		$ls_path = $ls_prePath . rtrim($ls_path, '-');

		$ls_originalPath = $ao_entity->hasOriginal('path') ? $ao_entity->getOriginal('path') : $ao_entity->path;

		if (!str_starts_with($ls_path, 'media/') && $ls_path !== 'media') {
			$ls_path = 'media/' . $ls_path;
		}

		//When the path has changed
		if ($ao_entity->isNew() || $ls_path != $ls_originalPath) {
			$ls_field = $lo_table->getAlias() . '.path';

			$la_conditions = [
				$ls_field => $ls_path,
			];


			$ls_primaryKey = $lo_table->getPrimaryKey();
			$li_id = $ao_entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$lo_table->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			/**
			 * `$la_conditions` holds an array of query conditions that are used to find media folders with the same
			 * path
			 * ```
			 * [
			 *    "MediaFolders.path" => "new/path/of/the/current/mediafolder"
			 *    "language_shortcode" => "de"
			 *    "NOT" => [
			 *        "MediaFolders.id" => 1234
			 *    ]
			 * ]
			 * ```
			 */

			$li_i = 1;
			$ls_suffix = '';

			//As long as a media folder with the same path exists, append an increasing number to the path and try again
			while ($lo_table->exists($la_conditions)) {
				$li_i++;
				$ls_suffix = '-' . $li_i;

				if ($li_length && (mb_strlen($ls_path . $ls_suffix) > $li_length)) {
					$ls_path = mb_substr($ls_path, 0, $li_length - mb_strlen($ls_suffix));
				}

				$la_conditions[ $ls_field ] = $ls_path . $ls_suffix;
			}

			//Append the suffix, if it's not empty
			if ($ls_suffix) {
				$ls_path .= $ls_suffix;
			}
		}

		$ao_entity->set('path', $ls_path, ['setter' => false]);
		if (!$ao_entity->isNew() && $ls_path === $ls_originalPath) {
			$ao_entity->setDirty('path', false);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @throws \Exception
	 */
	public function afterCopy(Event $ao_event, MediaFolder $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $ao_event->getSubject();

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_originalEntity */
		$lo_originalEntity = $ao_entity->originalEntity;

		$this->copyMediaEntities($ao_entity, $lo_originalEntity, $lo_table);

		if ($ao_options['_primary'] === false) {
			return;
		}

		$ls_originalPath = $lo_originalEntity->path;
		if (!str_starts_with($ls_originalPath, 'media/')) {
			$ls_originalPath = 'media/' . $ls_originalPath;
		}

		$this->copyDirectory(
			WWW_ROOT . str_replace('/', DS, $ls_originalPath),
			WWW_ROOT . str_replace('/', DS, $ao_entity->path)
		);
	}


	/**
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param \ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, MediaFolder $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $ao_event->getSubject();
		$ls_originalPath = $ao_entity->hasOriginal('path') ? $ao_entity->getOriginal('path') : null;

		if (!$ao_entity->isNew() && $ls_originalPath && $ao_entity->path != $ls_originalPath) {
			foreach ([$lo_table->getTable(), 'media'] as $ls_table) {
				$this->rebuildDatabasePath($lo_table, $ls_table, $ao_entity, $ls_originalPath);
			}

			if (is_dir(WWW_ROOT . str_replace('/', DS, $ls_originalPath))) {
				rename(
					WWW_ROOT . str_replace('/', DS, $ls_originalPath),
					WWW_ROOT . str_replace('/', DS, $ao_entity->path)
				);
			}
		}

		if (
			!is_dir(WWW_ROOT . str_replace('/', DS, $ao_entity->path)) &&
			(
				$ao_options['isCopy'] === false ||
				$ao_options['_primary'] === true
			)
		) {
			mkdir(WWW_ROOT . str_replace('/', DS, $ao_entity->path), 0750, true);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(Event $ao_event, MediaFolder $ao_entity, ArrayObject $ao_options): void {
		if ($ao_options['_primary'] ?? null === true) {
			$ao_entity->path = substr_replace($ao_entity->path, '/_deleted_', strrpos($ao_entity->path, '/'), 1);
		}
	}


	/**
	 * @param \Awyiss\Model\Table\MediaFoldersTable $ao_table
	 * @param string $as_table
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param mixed $ls_originalPath
	 * @return void
	 */
	protected function rebuildDatabasePath(MediaFoldersTable $ao_table, string $as_table, MediaFolder $ao_entity, mixed $ls_originalPath): void {
		$lo_query = $ao_table->updateQuery();

		/**
		 * UPDATE media_folders SET path = (CONCAT('newpath', substr(path, '8'))) WHERE path LIKE 'oldpath/%'
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_query->update($as_table)->set('path', $lo_query->newExpr($lo_query->func()->concat([
			$ao_entity->path,
			$lo_query->func()->substr([
				'path' => 'identifier',
				mb_strlen($ls_originalPath) + 1,
			], [
				null,
				'integer',
			]),
		])))->where(function (QueryExpression $ao_expression/*, Query $ao_query*/) use ($ls_originalPath) {
			return $ao_expression->like('path', $ls_originalPath . '/%');
		})->execute();
	}


	/**
	 * Copies a directory with all its content to another location.
	 *
	 * @param string $as_sourceDirectory The source directory.
	 * @param string $as_targetDirectory The destination directory.
	 * @returns void
	 * @throws \Exception
	 */
	protected function copyDirectory(string $as_sourceDirectory, string $as_targetDirectory): void {
		//Ensure the source directory exists
		if (!is_dir($as_sourceDirectory)) {
			throw new Exception("Source directory does not exist: $as_sourceDirectory");
		}

		//Create the destination directory if it does not exist
		if (!is_dir($as_targetDirectory)) {
			mkdir($as_targetDirectory, 0750, true);
		}

		//Open the source directory
		$lr_dir = opendir($as_sourceDirectory);

		//Read each file in directory
		while (($ls_fileName = readdir($lr_dir)) !== false) {
			if (($ls_fileName != '.') && ($ls_fileName != '..')) {
				if (is_dir($as_sourceDirectory . DS . $ls_fileName)) {
					//If the file is a directory, recursively copy it
					$this->copyDirectory($as_sourceDirectory . DS . $ls_fileName, $as_targetDirectory . DS . $ls_fileName);
				}
				else {
					//If the file is not a directory, copy it to the destination
					copy($as_sourceDirectory . DS . $ls_fileName, $as_targetDirectory . DS . $ls_fileName);
				}
			}
		}

		//Close the source directory
		closedir($lr_dir);
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_originalEntity
	 * @param \Awyiss\Model\Table\MediaFoldersTable $ao_table
	 * @return void
	 * @throws \Exception
	 */
	protected function copyMediaEntities(MediaFolder $ao_entity, MediaFolder $ao_originalEntity, MediaFoldersTable $ao_table): void {
		$lo_files = $ao_table->Media->find()->where(['media_folder_id' => $ao_originalEntity->id])->all();
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($lo_files as $lo_file) {
			$lo_file->unset((array)$ao_table->getPrimaryKey());
			$lo_file->unset(['mediaFolderId', 'path']);
			$lo_file->setNew(true);

			$lo_file->mediaFolderId = $ao_entity->id;
		}

		$ao_table->Media->saveMany($lo_files->toList(), [
			'checkRules' => false,
		]);
	}
}
