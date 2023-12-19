<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\ContentTemplate;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Queue\Model\Table\QueuedJobsTable;


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
	 * @param Event           $ao_event
	 * @param ContentTemplate $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 *
	 * @throws \Exception
	 */
	public function removeCustomConfigFile (Event $ao_event, EntityInterface $ao_entity): void {
		/** @var QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ($lo_queue->isQueued('create_custom_configuration')) {
			return;
		}

		$la_languageShortcodes = [
			LocaleMiddleware::getLanguage()->shortcode,
			LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)->shortcode,
		];

		$lo_queue->createJob('CreateCustomConfiguration', [
			'languageShortcodes' => $la_languageShortcodes
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'create_custom_configuration',
		]);
	}
}