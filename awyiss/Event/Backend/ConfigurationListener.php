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
		$rememberedConfig = Configure::read('Awyiss');

		$this->deleteCustomConfiguration();

		$languages = LocaleMiddleware::getLanguages();
		foreach ($languages as &$realmLanguages) {
			$realmLanguages = array_keys($realmLanguages);
		}
		unset($realmLanguages);

		foreach (collection($languages)->cartesianProduct()->toArray() as $languages) {
			// Load the config with the provided languages
			Awyiss::loadConfiguration($languages[0] ?? null, $languages[1] ?? null, true);

			$frontendLanguage = $languages[0] ?? null;
			$backendLanguage = $languages[1] ?? null;

			$fileName = Inflector::underscore(CUSTOM_NAMESPACE);
			if ($frontendLanguage) {
				$fileName .= '[' . $frontendLanguage . ']';

				if ($backendLanguage) {
					$fileName .= '[' . $backendLanguage . ']';
				}
			}

			// Dump the config to a file
			Configure::dump($fileName, 'default', ['Awyiss']);
		}

		Configure::delete('Awyiss');
		Configure::write($rememberedConfig ?? []);
	}


	/**
	 * Removes all custom config files
	 *
	 * @return void
	 */
	public function deleteCustomConfiguration(): void {
		// Delete all files
		$fileName = Inflector::underscore(CUSTOM_NAMESPACE) . '\[??\]\[??\].php';
		foreach (glob(ENV_CUSTOM_CONFIG . $fileName) as $filePath) {
			unlink($filePath);
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
		$scope = Inflector::camelize($entity->scope);

		try {
			$table = $this->fetchTable($scope);
		}
		catch (MissingTableClassException | DatabaseException) {
			return false;
		}

		$eventParts = [
			'Configuration',
			$scope,
			Inflector::camelize($entity->realm),
			Inflector::variable($entity->identifier),
			$name,
		];

		$event = new Event(implode('.', $eventParts), $table, ['entity' => $entity, 'options' => $options]);
		$table->getEventManager()->dispatch($event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($event->isStopped()) {
			$originalEvent->stopPropagation();
			$originalEvent->setResult($event->getResult());

			return false;
		}


		return true;
	}
}
