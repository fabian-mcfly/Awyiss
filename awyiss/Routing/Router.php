<?php declare(strict_types=1);


namespace Awyiss\Routing;


use Awyiss\Awyiss;
use Cake\Core\Configure;
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
		// Strings and URLs for plugins are handled by the parent method.
		if (!is_array($url) || !empty($url['plugin'])) {
			return parent::url($url, $full);
		}

		$la_url = $url;

		// If the `_name` key is not set, set it to the current realm.
		if (!array_key_exists('_name', $la_url)) {
			$la_url['_name'] = Awyiss::getRealm();

			/**
			 * If the realm is the frontend but both `slug` and `lang` are empty,
			 * set the `_name` to the request's `_name` parameter to keep the current route.
			 *
			 * This is used to keep routes like the frontend root or the frontend language root,
			 * as well as form anti-spam and route generation urls.
			 */
			if ($la_url['_name'] === Awyiss::REALM_FRONTEND && empty($la_url['slug']) && empty($la_url['lang'])) {
				$la_url['_name'] = Router::getRequest()->getParam('_name');
			}
		}

		// If the `_name` key is set but empty, remove it.
		if (empty($la_url['_name'])) {
			unset($la_url['_name']);
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
		if (($la_url['_name'] ?? null) === Awyiss::REALM_BACKEND && empty($la_url['action'])) {
			$la_url['action'] = static::getRequest()->getParam('action');
		}

		/**
		 * If the route name is given and the realm is the frontend,
		 * remove the language shortcode from the URL if the config
		 * `Route.includeLanguageShortcode` is set to false.
		 */
		if (($la_url['_name'] ?? null) === Awyiss::REALM_FRONTEND && !Configure::read('Route.includeLanguageShortcode')) {
			unset($la_url['lang']);
		}

		return parent::url($la_url, $full);
	}
}
