<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
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
			'Model.Configuration.afterSave' => 'afterSave',
			'Model.Configuration.afterSaveCommit' => 'createCustomConfiguration',
			'Model.Configuration.afterDelete' => 'createCustomConfiguration',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Configuration $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, Configuration $ao_entity): void {
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
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ao_entity->value, $li_direction);
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
				$lo_table->getBehavior('SystemOrder')->rebuildSystemOrder($ls_field, (int)$ao_entity->value);
			}
		}
	}


	/**
	 * After saving or deleting a config item, we delete and create new cached config files.
	 * We are too lazy to delete only those of the current language.
	 * It's easier and doesn't affect performance that much to recreate each file once.
	 *
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Configuration $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	public function createCustomConfiguration(Event $ao_event, Configuration $ao_entity): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ($lo_queue->isQueued('create_custom_configuration')) {
			return;
		}

		$lo_queue->createJob('CreateCustomConfiguration', null, [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'create_custom_configuration',
		]);
	}
}
