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
					$lx_url['_name'] .= 'Root';
				}
			}
		}


		return parent::url($lx_url, $full);
	}
}
