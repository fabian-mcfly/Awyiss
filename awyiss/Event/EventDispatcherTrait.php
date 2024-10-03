<?php declare(strict_types=1);


namespace Awyiss\Event;


use Cake\Event\EventDispatcherTrait as BaseEventDispatcherTrait;
use Cake\Event\EventManagerInterface;


/**
 * Overrides the CakePHP EventDispatcherTrait to use the Awyiss EventManager
 */
trait EventDispatcherTrait {
	use BaseEventDispatcherTrait;


	/**
	 * Returns the Awyiss EventManager manager instance for this object.
	 *
	 * @return \Cake\Event\EventManagerInterface
	 */
	public function getEventManager(): EventManagerInterface {
		return $this->_eventManager ??= new EventManager();
	}
}
