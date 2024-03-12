<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\IdentityInterface;
use Awyiss\Event\EventManager;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManagerInterface;


/**
 * Allows retreiving the identiy from the Authentication Service using an event
 */
trait IdentityAwareTrait {
	use EventDispatcherTrait;


	/**
	 * @var \Authentication\IdentityInterface|null
	 */
	protected ?IdentityInterface $identity = null;


	/**
	 * @inheritDoc
	 */
	public function getEventManager(): EventManagerInterface {
		return $this->_eventManager ??= new EventManager();
	}


	/**
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface|null
	 */
	public function getIdentity(): ?IdentityInterface {
		if (isset($this->identity)) {
			return $this->identity;
		}

		$lo_event = $this->dispatchEvent('Authentication.requestIdentity', [], $this);

		//Maybe the event handler has found a policy.
		//This is my Last Resort!
		$this->identity = $lo_event->getResult();

		return $this->identity;
	}


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $ao_identity
	 */
	public function setIdentity(?IdentityInterface $ao_identity): static {
		$this->identity = $ao_identity;


		return $this;
	}
}
