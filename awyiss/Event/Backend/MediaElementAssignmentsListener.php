<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Utility\Inflector;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use ReflectionClass;


/**
 * Event listeners for the MediaElementAssignments scope of the backend
 */
class MediaElementAssignmentsListener implements EventListenerInterface {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.MediaElementAssignments.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaElementAssignment $entity
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(Event $event, MediaElementAssignment $entity): void {
		$scope = $entity->scope;
		$table = $this->fetchTable(Inflector::camelize($scope));

		$reflection = new ReflectionClass($table);

		$attributes = $reflection->getAttributes(MediaElementAssignable::class);

		if (!$attributes) {
			return;
		}

		$attributeInstance = $attributes[0]->newInstance();
		if (!($attributeInstance->level & MediaElementAssignable::ENTITY_LEVEL)) {
			return;
		}

		// Get all associations of the table of type "HasMany"
		$scopes = [];
		$associations = $table->associations()->getByType('HasMany');
		foreach ($associations as $association) {
			$scopes[] = Inflector::camelize($association->getTable());
		}

		/** @var \Awyiss\Model\Table\MediaElementAssignmentsTable $mediaAssignmentsTable */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$records = $mediaAssignmentsTable->find('all')->where([
			'mediaElementId' => $entity->mediaElementId,
			'scope IN' => $scopes,
		])->all();

		if (!$records->count()) {
			return;
		}

		$mediaAssignmentsTable->deleteMany($records, [
			'transaction' => false,
		]);
	}
}
