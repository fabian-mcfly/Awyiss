<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Prevents access to the backend until the required two-factor method has been verified.
 */
class TwoFactorMiddleware implements MiddlewareInterface {
	/**
	 * The verification action must remain reachable while two-factor authentication is pending.
	 *
	 * @var array<string>
	 */
	protected const array EXCLUDED_ACTIONS = ['twoFactorSetup', 'twoFactorAuth', 'twoFactorDisable'];


	/**
	 * Enforce the configured backend two-factor policy.
	 *
	 * @param \Cake\Http\ServerRequest $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$identity = $request->getAttribute(Awyiss::REALM_BACKEND . 'Identity');

		$forced = (bool)Configure::read('Awyiss.Users.Backend.forceTwoFactor', false);

		if (!$identity || in_array($request->getParam('action'), self::EXCLUDED_ACTIONS, true)) {
			return $handler->handle($request);
		}

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $identity->getOriginalData();
		$enabled = $user->twoFactorEnabled;
		$session = $request->getAttribute('session');

		if (
			($forced && !$enabled)
			|| ($enabled && !$user->twoFactorSecret)
		) {
			return $this->redirect($request, 'twoFactorSetup');
		}

		if (!$enabled) {
			return $handler->handle($request);
		}

		if (!$session || !$session->read('Backend.twoFactorVerified')) {
			return $this->redirect($request, 'twoFactorAuth');
		}

		return $handler->handle($request);
	}


	/**
	 * Create a redirect response to a backend two-factor action.
	 *
	 * @param \Cake\Http\ServerRequest $request
	 * @param string $action
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \Exception
	 */
	protected function redirect(ServerRequestInterface $request, string $action): ResponseInterface {
		$url = Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'controller' => 'Users',
			'action' => $action,
			'lang' => $request->getParam('lang') ?? LocaleMiddleware::getLanguage()?->shortcode,
			'_base' => false,
		]);

		return new Response()->withStatus(302)->withHeader('Location', $url);
	}
}
