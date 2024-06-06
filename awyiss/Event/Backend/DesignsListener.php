<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Design;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the Datatables scope of the backend
 */
class DesignsListener implements EventListenerInterface {
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
			'Model.Designs.afterSaveCommit' => 'afterSaveCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Design $entity): void {
		$la_settings = $entity->settings;
		$la_fonts = [];

		foreach ($la_settings as $lx_value) {
			if (!is_array($lx_value) || !isset($lx_value['font']['id'])) {
				continue;
			}

			$la_fonts[] = [
				'id' => $lx_value['font']['id'],
				'name' => $lx_value['font']['name'],
				'variants' => $lx_value['variants'] ?? [],
				'version' => $lx_value['font']['version'],
			];
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = $this->fetchTable('Queue.QueuedJobs');
		$lo_queue->createJob('WebfontDownload', [
			'fonts' => $la_fonts,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'designs::webfont_download',
		]);
	}
}
