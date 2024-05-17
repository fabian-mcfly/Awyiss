<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\IdentityInterface;
use Awyiss\Annotation\NoDirectAccess;
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
	#[NoDirectAccess]
	public function getEventManager(): EventManagerInterface {
		return $this->_eventManager ??= new EventManager();
	}


	/**
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface|null
	 */
	#[NoDirectAccess]
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
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $identity
	 */
	#[NoDirectAccess]
	public function setIdentity(?IdentityInterface $identity): static {
		$this->identity = $identity;


		return $this;
	}
}
