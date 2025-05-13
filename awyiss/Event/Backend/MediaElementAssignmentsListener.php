<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Event\EventListenerTrait;
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
			'Model.MediaElementAssignments.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaElementAssignment $entity
	 * @return void
	 */
	public function afterDelete(Event $event, MediaElementAssignment $entity): void {
		$ls_scope = $entity->scope;
		$lo_table = $this->fetchTable(Inflector::camelize($ls_scope));

		$lo_reflection = new ReflectionClass($lo_table);

		$la_attributes = $lo_reflection->getAttributes(MediaElementAssignable::class);

		if (!$la_attributes) {
			return;
		}

		$lo_attributeInstance = $la_attributes[0]->newInstance();
		if (!($lo_attributeInstance->level & MediaElementAssignable::ENTITY_LEVEL)) {
			return;
		}

		// Get all associations of the table of type "HasMany"
		$la_scopes = [];
		$la_associations = $lo_table->associations()->getByType('HasMany');
		foreach ($la_associations as $lo_association) {
			$la_scopes[] = $lo_association->getTable();
		}

		/** @var \Awyiss\Model\Table\MediaElementAssignmentsTable $lo_mediaAssignmentsTable */
		$lo_mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$lo_records = $lo_mediaAssignmentsTable->find('all')->where([
			'media_element_id' => $entity->mediaElementId,
			'scope IN' => $la_scopes,
		])->all();

		if (!$lo_records->count()) {
			return;
		}

		$lo_mediaAssignmentsTable->deleteMany($lo_records, [
			'transaction' => false,
		]);
	}
}
