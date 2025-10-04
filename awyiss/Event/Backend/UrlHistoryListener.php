<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the UrlHistory< scope of the backend
 */
class UrlHistoryListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.UrlHistory.beforeMarshal' => 'beforeMarshal',
		];
	}


	/**
	 * Set the scope of the UrlHistory entity before marshalling
	 *
	 * @param Event $event
	 * @param ArrayObject $data
	 * @param ArrayObject $options
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void {
		if (empty($data['scope'])) {
			//Empty scope and foreign key when scope is empty
			$data['scope'] = null;
			$data['foreignKey'] = null;
		}
		else {
			// Otherwise set the target to null
			$data['target'] = null;
		}
	}
}
