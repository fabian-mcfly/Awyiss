<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware as BaseAuthenticationMiddleware;
use Awyiss\Event\EventListenersProvider;


/**
 * Authentication Middleware
 */
class AuthenticationMiddleware extends BaseAuthenticationMiddleware {
	/**
	 * @param AuthenticationServiceInterface|AuthenticationServiceProviderInterface $subject
	 * @throws \ReflectionException
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(AuthenticationServiceInterface|AuthenticationServiceProviderInterface $subject) {
		$this->subject = $subject;

		EventListenersProvider::loadListener('authentication', 'Global');
	}
}
