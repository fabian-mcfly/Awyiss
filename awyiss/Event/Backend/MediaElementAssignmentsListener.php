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

		// Build scope-specific deletion conditions and, when possible, restrict by related entity ids.
		$scopeConditions = [];
		$associations = $table->associations()->getByType('HasMany');
		foreach ($associations as $association) {
			$targetTable = $association->getTarget();
			if (!$targetTable->hasBehavior('MediaAssignment')) {
				continue;
			}

			$targetScope = Inflector::camelize($association->getTable());

			if ($entity->foreignKey === null) {
				$scopeConditions[] = ['scope' => $targetScope];
				continue;
			}

			$bindingKey = $association->getBindingKey();
			$foreignKey = $association->getForeignKey();
			$targetPrimaryKey = $targetTable->getPrimaryKey();

			if (is_array($bindingKey) || is_array($foreignKey) || is_array($targetPrimaryKey)) {
				continue;
			}

			$bindingPrimaryKey = $table->getPrimaryKey();
			if (is_array($bindingPrimaryKey) || $bindingKey !== $bindingPrimaryKey) {
				continue;
			}

			$entityIds = $targetTable->find('all')
				->select($targetPrimaryKey)
				->where([$foreignKey => $entity->foreignKey])
				->disableHydration()
				->all()
				->extract($targetPrimaryKey)
				->toList();

			if (!$entityIds) {
				continue;
			}

			$scopeConditions[] = [
				'scope' => $targetScope,
				'foreignKey IN' => $entityIds,
			];
		}

		if (!$scopeConditions) {
			return;
		}

		/** @var \Awyiss\Model\Table\MediaElementAssignmentsTable $mediaAssignmentsTable */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$records = $mediaAssignmentsTable->find('all')->where([
			'mediaElementId' => $entity->mediaElementId,
			'OR' => $scopeConditions,
		])->all();

		if (!$records->count()) {
			return;
		}

		$mediaAssignmentsTable->deleteMany($records, [
			'transaction' => false,
		]);
	}
}
