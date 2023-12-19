<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\AbstractListener;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;


class ContentTemplatesListener extends AbstractListener {
	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			'Model.ContentTemplate.afterSaveCommit' => 'afterSaveCommit',
			'Model.ContentTemplate.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete (Event $ao_event, EntityInterface $ao_entity) {
		$ls_fileName = \Cake\Utility\Inflector::underscore($ao_entity->filename);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = \Cake\Core\Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths[0] . 'Frontend' . DS . 'contents' . DS;

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if (file_exists($ls_filePath)) {
			$ls_newFilePath = $ls_filePath;
			while (file_exists($ls_newFilePath)) {
				$ls_newFilePath = $ls_folderPath . '_deleted-' . $ls_fileName . '-' . (new \Cake\I18n\FrozenTime())->getTimestamp() . $ls_extension;
			}
			rename($ls_filePath, $ls_newFilePath);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit (Event $ao_event, EntityInterface $ao_entity) {
		$ls_fileName = \Cake\Utility\Inflector::underscore($ao_entity->filename);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = \Cake\Core\Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths[0] . 'Frontend' . DS . 'contents' . DS;

		if (!file_exists($ls_folderPath)) {
			mkdir($ls_folderPath, 0777, TRUE);
			chmod($ls_folderPath, 0777);
		}

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if ($ao_entity->filename != $ao_entity->getOriginal('filename')) {
			//When renaming a file, we need to make sure that the new filename isn't already in use
			if (file_exists($ls_filePath)) {
				$ls_newFilePath = $ls_filePath;
				while (file_exists($ls_newFilePath)) {
					$ls_newFilePath = $ls_folderPath . $ls_fileName . '-' . (new \Cake\I18n\FrozenTime())->format('Ymd-His') . $ls_extension;
				}
				rename($ls_filePath, $ls_newFilePath);
			}

			//After changing the filename in the database, we also need to rename the existing file
			$ls_currentFilename = \Cake\Utility\Inflector::underscore($ao_entity->getOriginal('filename'));
			$ls_currentFilePath = $ls_folderPath . $ls_currentFilename . $ls_extension;
			if (file_exists($ls_currentFilePath)) {
				rename($ls_currentFilePath, $ls_filePath);
			}
		}

		//If the file does not exist, we create one based on a twig-template for fontent templates
		if (!file_exists($ls_filePath)) {
			$lo_view = new \Awyiss\View\BackendView();

			$ls_fileContents = $lo_view->element('ContentTemplates/file_template', [
				'ao_contentTemplate' => $ao_entity,
			]);

			file_put_contents($ls_filePath, $ls_fileContents);
			chmod($ls_filePath, 0777);
		}
	}
}