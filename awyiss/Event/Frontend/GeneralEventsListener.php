<?php declare(strict_types=1);


namespace Awyiss\Event\Frontend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\MediaResizedImage;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use DebugKit\Model\Entity\Panel;
use DebugKit\Model\Entity\Request;


/**
 * Event listeners for the general events of the frontend
 */
class GeneralEventsListener implements EventListenerInterface {
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
			'Model.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param EventInterface $event
	 * @param \Awyiss\Model\Entity\Media $entity
	 * @param \ArrayObject $options
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity/*, ArrayObject $options*/): void {
		if (
			$entity instanceof Panel ||
			$entity instanceof Request ||
			$entity instanceof MediaResizedImage
		) {
			return;
		}

		$event->stopPropagation();
		$entity->setError('_general', 'Saving inside the Frontend Realm is not allowed.');
	}
}
