<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\FormElement;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Security;


/**
 * Event listeners for the FormElements scope of the backend
 */
class FormElementsListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.FormElements.beforeCopy' => 'beforeCopy',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\FormElement $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeCopy(Event $event, FormElement $entity, ArrayObject $options): void {
		if (($options['_primary'] ?? false) !== true || !$entity->childFormElements) {
			return;
		}

		// Transform the children into a flat list
		$children = collection($entity->childFormElements)->listNested('desc', 'childFormElements');

		/** @var \Awyiss\Model\Entity\FormElement $childFormElement */
		foreach ($children as $childFormElement) {
			if (in_array($childFormElement->type, ['free_text', 'submit'])) {
				continue;
			}

			// Copied form elements must have a unique identifier
			// Otherwise the validation will fail
			if (strlen($childFormElement->identifier) > 36) {
				// If the identifier is longer than 36 characters, we need to truncate it
				// to 36 characters, otherwise the validation will fail (50 characters max)
				$childFormElement->identifier = substr($childFormElement->identifier, 0, 36);
			}

			$childFormElement->identifier .= '_copy_' . Security::randomString(8);
		}
	}
}
