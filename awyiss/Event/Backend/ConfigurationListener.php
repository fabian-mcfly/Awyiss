<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Core\LocalConfig;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the Configuration scope of the backend
 */
class ConfigurationListener implements EventListenerInterface {
	use EventListenerTrait;
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
			'Model.Configuration.beforeSave' => 'beforeSave',
			'Model.Configuration.afterSaveCommit' => 'afterSaveCommit',
			'Model.Configuration.afterDelete' => 'afterDelete',
			'Awyiss.Configuration.createCustomConfiguration' => 'createCustomConfiguration',
			'Awyiss.Configuration.deleteCustomConfiguration' => 'deleteCustomConfiguration',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Configuration $entity): void {
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
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	public function afterSaveCommit(Event $event, Configuration $entity): void {
		$this->unnestEntries($event, $entity);
		$this->rebuildSystemOrder($event, $entity);
		$this->createCustomConfiguration();
		$this->clearMediaCache($entity);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(Event $event, Configuration $entity): void {
		$this->unnestEntries($event, $entity, true);
		$this->createCustomConfiguration();
		$this->clearMediaCache($entity, true);
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
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param bool $deleted
	 * @return void
	 * @throws \Exception
	 */
	protected function unnestEntries(Event $event, Configuration $entity, bool $deleted = false): void {
		if ($entity->identifier !== 'nest.enabled') {
			return;
		}

		$lb_defaultNest = false;
		if ($deleted) {
			$lo_configuration = ConfigOptionsProvider::loadConfigOptions($entity->scope);
			$lo_configOption = $lo_configuration?->getConfigOption(Awyiss::REALM_BACKEND, $entity->identifier);
			$lb_defaultNest = $lo_configOption?->getDefaultValue() ?? false;
		}

		if (
			(
				$deleted &&
				!$lb_defaultNest
			) ||
			(
				!$deleted &&
				$entity->isDirty('value') &&
				!$entity->value
			)
		) {
			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($entity->scope));

			if (!$lo_table->hasBehavior('Nest')) {
				return;
			}

			$lo_schema = $lo_table->getSchema();
			$ls_column = $lo_table->getBehavior('Nest')->getConfig('children.foreignKey');

			if (!$lo_schema->hasColumn($ls_column)) {
				return;
			}

			// If the column is the same as the foreign key of the Categories behavior, we don't need to unnest the entries
			if ($lo_table->hasBehavior('Categories')) {
				$ls_foreignKey = $lo_table->getBehavior('Categories')->getConfig('foreignKey');
				if ($ls_foreignKey && Inflector::underscore($ls_foreignKey) === Inflector::underscore($ls_column)) {
					return;
				}
			}

			$lo_table->updateAll([
				$ls_column => null,
			], [
				$ls_column . ' IS NOT' => null,
			]);

			$ls_field = LocalConfig::read([
				'systemOrder',
				'field',
			], 'systemOrder', Inflector::camelize($entity->scope));

			$li_direction = LocalConfig::read([
				'systemOrder',
				'direction',
			], SORT_ASC, Inflector::camelize($entity->scope));

			if ($lo_table->hasBehavior('SystemOrder')) {
				/** @var \Awyiss\Model\Entity $ls_entityClass */
				$ls_entityClass = $lo_table->getEntityClass();
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ls_entityClass::unmapField($ls_field), $li_direction, $event);
			}
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @return void
	 * @throws \Exception
	 */
	protected function rebuildSystemOrder(Event $event, Configuration $entity): void {
		if (
			$entity->identifier === 'system_order.field' &&
			(
				$entity->isNew() ||
				(
					$entity->hasOriginal('value') &&
					$entity->getOriginal('value') !== $entity->value
				)
			) &&
			Inflector::variable($entity->value) !== 'systemOrder'
		) {
			$li_direction = LocalConfig::read([
				'systemOrder',
				'direction',
			], SORT_ASC, Inflector::camelize($entity->scope));

			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($entity->scope));
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();
			if ($lo_table->hasBehavior('SystemOrder')) {
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ls_entityClass::unmapField($entity->value), $li_direction, $event);
			}
		}
		elseif (
			$entity->identifier === 'system_order.direction' &&
			(
				$entity->isNew() ||
				(
					$entity->hasOriginal('value') &&
					$entity->getOriginal('value') !== $entity->value
				)
			)
		) {
			$ls_field = LocalConfig::read([
				'systemOrder',
				'field',
			], 'systemOrder', Inflector::camelize($entity->scope));

			// If the field is set to 'systemOrder', we don't need to rebuild the system order
			if ($ls_field === 'systemOrder') {
				return;
			}

			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($entity->scope));
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();
			if ($lo_table->hasBehavior('SystemOrder')) {
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ls_entityClass::unmapField($ls_field), (int)$entity->value, $event);
			}
		}
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
	 * If the resizing.fileType config is changed, we need to clear the media cache
	 * to remove unused files.
	 *
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param bool $deleted
	 * @return void
	 */
	protected function clearMediaCache(Configuration $entity, bool $deleted = false): void {
		if (
			$entity->scope !== 'media' ||
			(
				$entity->identifier !== 'resizing.file_type' &&
				$entity->identifier !== 'resizing.quality'
			)
		) {
			return;
		}

		if (
			$deleted ||
			$entity->isNew() ||
			(
				$entity->hasOriginal('value') &&
				$entity->getOriginal('value') !== $entity->value
			)
		) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = $this->fetchTable('Queue.QueuedJobs');

			$lo_queue->createJob('Queue.Execute', [
				'command' => 'bin' . DS . 'cake media clear_cache',
				'escape' => false,
				'log' => true,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]);
		}
	}
}
