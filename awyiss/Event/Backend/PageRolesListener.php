<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


class PageRolesListener implements EventListenerInterface {
	use EventListenerTrait;

	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			'Model.PageRoles.afterSaveCommit' => 'removeCustomConfigFile',
			'Model.PageRoles.afterDelete' => 'removeCustomConfigFile',
		];
	}


	/**
	 * After saving or deleting a page role item, we delete the cached constants file.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\PageRole $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function removeCustomConfigFile (Event $ao_event, EntityInterface $ao_entity): void {
		$ls_filePath = ENV_CUSTOM_CONFIG . 'constants.php';

		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		\Awyiss\Application::loadConstants();
	}
}