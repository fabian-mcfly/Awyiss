<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


class ConfigurationListener implements EventListenerInterface {
	use EventListenerTrait;

	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			'Model.Configuration.afterSaveCommit' => 'removeCustomConfigFile',
			'Model.Configuration.afterDelete' => 'removeCustomConfigFile',
		];
	}


	/**
	 * After saving or deleting a config item, we delete the cached config file.
	 * We are too lazy to delete only those of the current language.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function removeCustomConfigFile (Event $ao_event, EntityInterface $ao_entity): void {
		$ls_fileName = \Cake\Utility\Inflector::underscore(CUSTOM_NAMESPACE);
		$ls_fileName .= '\[??\]\[??\].*';

		foreach (glob(ENV_CUSTOM_CONFIG . $ls_fileName) as $ls_filePath) {
			unlink($ls_filePath);
		}
	}
}