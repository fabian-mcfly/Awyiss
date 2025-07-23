<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Design;
use Awyiss\Routing\Router;
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
			'Model.Designs.afterSave' => 'afterSave',
			'Model.Designs.afterSaveCommit' => 'afterSaveCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, Design $entity): void {
		// If the design is not in use, we don't need to do anything
		if (!$entity->inUse) {
			return;
		}

		// If the design is in use, we need to check if it was changed
		if (!$entity->hasOriginal('inUse') || $entity->getOriginal('inUse') === $entity->inUse) {
			return;
		}

		// If the design should be in use, other designs should be set to not in use
		if ($entity->inUse) {
			$lo_designs = $this->fetchTable('Designs');
			$lo_designs->updateAll([
				'in_use' => false,
			], [
				'id !=' => $entity->id,
			]);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function afterSaveCommit(Event $event, Design $entity): void {
		if (!$entity->inUse) {
			return;
		}

		$la_fonts = [];

		foreach (($entity->settings ?? []) as $lx_value) {
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
		$lo_queue->createJob('Design/WebfontDownload', [
			'fonts' => $la_fonts,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'designs::webfont_download',
		]);

		/** @var \Awyiss\Middleware\DesignMiddleware $lo_designMiddleware */
		$lo_designMiddleware = Router::getRequest()->getAttribute('design');
		$lo_designMiddleware->resetDesignVariables();
		$lo_designMiddleware->compileScss(true, Awyiss::REALM_FRONTEND);
	}
}
