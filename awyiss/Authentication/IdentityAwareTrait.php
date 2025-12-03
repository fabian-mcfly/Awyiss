<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\IdentityInterface;
use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Event\EventDispatcherTrait;
use Awyiss\Event\EventManager;
use Cake\Event\EventManagerInterface;


/**
 * Allows retrieving the identity from the Authentication Service using an event
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
	 * @return \Authentication\IdentityInterface|null
	 */
	#[NoDirectAccess]
	public function getIdentity(): ?IdentityInterface {
		if (isset($this->identity)) {
			return $this->identity;
		}

		$event = $this->dispatchEvent('Authentication.requestIdentity', [], $this);

		//Maybe the event handler has found a policy.
		//This is my Last Resort!
		$this->identity = $event->getResult();

		return $this->identity;
	}


	/**
	 * @param \Authentication\IdentityInterface|null $identity
	 * @return \Awyiss\Authentication\IdentityAwareTrait
	 */
	#[NoDirectAccess]
	public function setIdentity(?IdentityInterface $identity): static {
		$this->identity = $identity;


		return $this;
	}
}
