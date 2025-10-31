<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Database\Exception\DatabaseException;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the Configuration scope of the backend
 */
class ConfigurationListener implements EventListenerInterface {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Configuration.beforeSave' => 'beforeSave',
			'Model.Configuration.afterSave' => 'afterSave',
			'Model.Configuration.afterSaveCommit' => 'afterSaveCommit',
			'Model.Configuration.beforeDelete' => 'beforeDelete',
			'Model.Configuration.afterDelete' => 'afterDelete',
			'Model.Configuration.afterDeleteCommit' => 'afterDeleteCommit',
			'Awyiss.Configuration.createCustomConfiguration' => 'createCustomConfiguration',
			'Awyiss.Configuration.deleteCustomConfiguration' => 'deleteCustomConfiguration',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(Event $event, Configuration $entity, ArrayObject $options): void {
		$entity->value = ConfigOptionsProvider::typecastConfigValue(
			$entity->scope,
			$entity->realm,
			$entity->identifier,
			$entity->value,
			$entity->languageShortcode
		);

		if (in_array(getType($entity->value), ['array', 'object'])) {
			$entity->value = json_encode($entity->value);
		}

		$this->dispatchEvent('beforeSave', $event, $entity, $options);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function afterSave(Event $event, Configuration $entity, ArrayObject $options): void {
		$this->dispatchEvent('afterSave', $event, $entity, $options);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function afterSaveCommit(Event $event, Configuration $entity, ArrayObject $options): void {
		$this->dispatchEvent('afterSaveCommit', $event, $entity, $options);

		$this->createCustomConfiguration();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function beforeDelete(Event $event, Configuration $entity, ArrayObject $options): void {
		$this->dispatchEvent('beforeDelete', $event, $entity, $options);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function afterDelete(Event $event, Configuration $entity, ArrayObject $options): void {
		$this->dispatchEvent('afterDelete', $event, $entity, $options);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function afterDeleteCommit(Event $event, Configuration $entity, ArrayObject $options): void {
		$this->dispatchEvent('afterDeleteCommit', $event, $entity, $options);

		$this->createCustomConfiguration();
	}


	/**
	 * After saving or deleting a config item, we delete and create new cached config files.
	 * We are too lazy to delete only those of the current language.
	 * It's easier and doesn't affect performance that much to recreate each file once.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function createCustomConfiguration(): void {
		// Remember the current config
		$la_rememberedConfig = Configure::read('Awyiss');

		$this->deleteCustomConfiguration();

		$la_languages = LocaleMiddleware::getLanguages();
		foreach ($la_languages as &$la_realmLanguages) {
			$la_realmLanguages = array_keys($la_realmLanguages);
		}
		unset($la_realmLanguages);

		foreach (collection($la_languages)->cartesianProduct()->toArray() as $la_languages) {
			// Load the config with the provided languages
			Awyiss::loadConfiguration($la_languages[0] ?? null, $la_languages[1] ?? null, true);

			$ls_frontendLanguage = $la_languages[0] ?? null;
			$ls_backendLanguage = $la_languages[1] ?? null;

			$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE);
			if ($ls_frontendLanguage) {
				$ls_fileName .= '[' . $ls_frontendLanguage . ']';

				if ($ls_backendLanguage) {
					$ls_fileName .= '[' . $ls_backendLanguage . ']';
				}
			}

			// Dump the config to a file
			Configure::dump($ls_fileName, 'default', ['Awyiss']);
		}

		Configure::delete('Awyiss');
		Configure::write($la_rememberedConfig ?? []);
	}


	/**
	 * Removes all custom config files
	 *
	 * @return void
	 */
	public function deleteCustomConfiguration(): void {
		// Delete all files
		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE) . '\[??\]\[??\].php';
		foreach (glob(ENV_CUSTOM_CONFIG . $ls_fileName) as $ls_filePath) {
			unlink($ls_filePath);
		}
	}


	/**
	 * @param string $name
	 * @param \Cake\Event\Event $originalEvent
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param \ArrayObject $options
	 * @return bool
	 */
	protected function dispatchEvent(string $name, Event $originalEvent, Configuration $entity, ArrayObject $options): bool {
		$ls_scope = Inflector::camelize($entity->scope);

		try {
			$lo_table = $this->fetchTable($ls_scope);
		}
		catch (MissingTableClassException | DatabaseException) {
			return false;
		}

		$la_eventParts = [
			'Configuration',
			$ls_scope,
			Inflector::camelize($entity->realm),
			Inflector::variable($entity->identifier),
			$name,
		];

		$lo_event = new Event(implode('.', $la_eventParts), $lo_table, ['entity' => $entity, 'options' => $options]);
		$lo_table->getEventManager()->dispatch($lo_event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($lo_event->isStopped()) {
			$originalEvent->stopPropagation();
			$originalEvent->setResult($lo_event->getResult());

			return false;
		}


		return true;
	}
}
