<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Design;
use Awyiss\Routing\Router;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the Datatables scope of the backend
 */
class DesignsListener implements EventListenerInterface {
	use LocatorAwareTrait;


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
	 * @param \Awyiss\Model\Entity\Design $entity
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
			$designsTable = $this->fetchTable('Designs');
			$designsTable->updateAll([
				'inUse' => false,
			], [
				'id !=' => $entity->id,
			]);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Design $entity
	 * @return void
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Design $entity): void {
		if (!$entity->inUse) {
			return;
		}

		$fonts = [];
		foreach (($entity->settings ?? []) as $value) {
			if (!is_array($value) || !isset($value['font']['id'])) {
				continue;
			}

			$fonts[] = [
				'id' => $value['font']['id'],
				'name' => $value['font']['name'],
				'variants' => $value['variants'] ?? [],
				'version' => $value['font']['version'],
			];
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		$queuedJobsTable->createJob('Design/WebfontDownload', [
			'fonts' => $fonts,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Designs::webfontDownload',
		]);

		/** @var \Awyiss\Middleware\DesignMiddleware $designMiddleware */
		$designMiddleware = Router::getRequest()->getAttribute('design');
		$designMiddleware->resetDesignVariables();
		$designMiddleware->compileScss(true, Awyiss::REALM_FRONTEND);
	}
}
