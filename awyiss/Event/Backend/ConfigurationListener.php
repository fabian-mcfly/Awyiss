<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Configuration;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Inflector;


/**
 * Event listeners for the Configuration scope of the backend
 */
class ConfigurationListener implements EventListenerInterface {
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
			'Model.Configuration.beforeSave' => 'beforeSave',
			'Model.Configuration.afterSaveCommit' => 'afterSaveCommit',
			'Model.Configuration.afterDelete' => 'createCustomConfiguration',
			'Configuration.createCustomConfiguration' => 'createCustomConfiguration',
			'Configuration.deleteCustomConfiguration' => 'deleteCustomConfiguration',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Configuration $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \ReflectionException
	 */
	public function beforeSave(Event $ao_event, Configuration $ao_entity): void {
		$ao_entity->value = ConfigOptionsProvider::typecastConfigValue(
			$ao_entity->scope,
			$ao_entity->realm,
			$ao_entity->identifier,
			$ao_entity->value,
			$ao_entity->languageShortcode
		);

		if (in_array(getType($ao_entity->value), ['array', 'object'])) {
			$ao_entity->value = json_encode($ao_entity->value);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Configuration $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	public function afterSaveCommit(Event $ao_event, Configuration $ao_entity): void {
		$this->rebuildSystemOrder($ao_event, $ao_entity);
		$this->createCustomConfiguration();
	}


	/**
	 * After saving or deleting a config item, we delete and create new cached config files.
	 * We are too lazy to delete only those of the current language.
	 * It's easier and doesn't affect performance that much to recreate each file once.

	 * @throws \Exception
	 */
	public function createCustomConfiguration(): void {
		//Remember the current config
		$la_rememberedConfig = Configure::read('Awyiss');

		$this->deleteCustomConfiguration();

		$la_languages = LocaleMiddleware::getLanguages();
		foreach ($la_languages as &$la_realmLanguages) {
			$la_realmLanguages = array_keys($la_realmLanguages);
		}
		unset($la_realmLanguages);

		foreach (collection($la_languages)->cartesianProduct()->toArray() as $la_languages) {
			//Load the config with the provided languages
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

			//Dump the config to a file
			Configure::dump($ls_fileName, 'default', ['Awyiss']);
		}

		Configure::delete('Awyiss');
		Configure::write($la_rememberedConfig ?? []);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Configuration $ao_entity
	 * @return void
	 */
	protected function rebuildSystemOrder(Event $ao_event, Configuration $ao_entity): void {
		if (
			$ao_entity->identifier === 'system_order.field' &&
			$ao_entity->isDirty('identifier') &&
			$ao_entity->value !== 'systemOrder'
		) {
			$li_direction = Configure::read(implode('.', [
				'Awyiss',
				Inflector::camelize($ao_entity->scope),
				Awyiss::getRealm(),
				'systemOrder',
				'direction',
			]));

			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($ao_entity->scope));
			if ($lo_table->hasBehavior('SystemOrder')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ao_event, $ao_entity->value, $li_direction);
			}
		}
		elseif (
			$ao_entity->identifier === 'system_order.direction' &&
			$ao_entity->isDirty('identifier')
		) {
			$ls_field = Configure::read(implode('.', [
				'Awyiss',
				Inflector::camelize($ao_entity->scope),
				Awyiss::getRealm(),
				'systemOrder',
				'field',
			]));

			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($ao_entity->scope));
			if ($lo_table->hasBehavior('SystemOrder')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ao_event, $ls_field, (int)$ao_entity->value);
			}
		}
	}


	/**
	 * Removes all custom config files
	 *
	 * @return string
	 */
	public function deleteCustomConfiguration(): void {
		//Delete all files
		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE) . '\[??\]\[??\].php';
		foreach (glob(ENV_CUSTOM_CONFIG . $ls_fileName) as $ls_filePath) {
			unlink($ls_filePath);
		}
	}
}
