<?php


namespace Awyiss\Middleware;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Awyiss\Event\EventListenersProvider;


class AuthenticationMiddleware extends \Authentication\Middleware\AuthenticationMiddleware {
	/**
	 * @param AuthenticationServiceInterface|AuthenticationServiceProviderInterface $subject
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct (
		AuthenticationServiceInterface|AuthenticationServiceProviderInterface $subject
	) {
		$this->subject = $subject;

		EventListenersProvider::loadListener('authentication', 'Global');
	}
}