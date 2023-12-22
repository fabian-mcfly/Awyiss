<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\IdentityInterface;
use Cake\Event\EventDispatcherTrait;


trait IdentityAwareTrait {
	use EventDispatcherTrait;


	/**
	 * @var \Authentication\IdentityInterface|null
	 */
	protected ?IdentityInterface $identity = null;


	/**
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface|null
	 */
	public function getIdentity(): ?IdentityInterface {
		if (!$this->identity) {
			$lo_event = $this->dispatchEvent('Authentication.requestIdentity', [], $this);

			//Maybe the event handler has found a policy.
			//This is my Last Resort!
			$this->identity = $lo_event->getResult();
		}

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
