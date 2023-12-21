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
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function url(UriInterface|array|string|null $ax_url = null, bool $ab_full = false): string {
		$lx_url = $ax_url;
		if (is_array($lx_url)) {
			if (!array_key_exists('_name', $lx_url) && empty($lx_url['plugin'])) {
				$lx_url['_name'] = Awyiss::getRealm();
			}
		}


		return parent::url($lx_url, $ab_full);
	}
}
