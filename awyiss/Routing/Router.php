<?php declare(strict_types=1);


namespace Awyiss\Routing;


use Awyiss\Awyiss;
use Cake\Routing\Router as BaseRouter;
use Psr\Http\Message\UriInterface;


/**
 * {@inheritDoc}
 */
class Router extends BaseRouter {
	/**
	 * @inheritDoc
	 */
	public static function url(UriInterface|array|string|null $url = null, bool $full = false): string {
		$lx_url = $url;
		if (is_array($lx_url)) {
			if (!array_key_exists('_name', $lx_url) && empty($lx_url['plugin'])) {
				$lx_url['_name'] = Awyiss::getRealm();

				if ($lx_url['_name'] === Awyiss::REALM_FRONTEND && empty($lx_url['slug']) && empty($lx_url['lang'])) {
					$lx_url['_name'] = Router::getRequest()->getParam('_name');
				}
			}

			if (empty($lx_url['_name'])) {
				unset($lx_url['_name']);
			}
		}

		/**
		 * If the route name is given and the realm is the backend,
		 * but the action is missing, try to get it from the request.
		 *
		 * This fixes what I deem to be a bug in CakePHP,
		 * where the action is automatically set to 'index'
		 * (or the default value defined in the route) if it is missing.
		 *
		 * It's honestly not really a bug, but having to set the action manually
		 * everywhere a URL is generated is a bit annoying.
		 * Especially in the PaginatorHelper.
		 *
		 * @see \Cake\Routing\RouteBuilder::_makeRoute()
		 */
		if (($lx_url['_name'] ?? null) === Awyiss::REALM_BACKEND && empty($lx_url['action'])) {
			$lx_url['action'] = static::getRequest()->getParam('action');
		}

		return parent::url($lx_url, $full);
	}
}
