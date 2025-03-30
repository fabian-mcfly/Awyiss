<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Routing\Router;
use Cake\Core\ContainerApplicationInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Http\ServerRequest;
use Cake\Routing\Middleware\RoutingMiddleware as BaseRoutingMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * @inheritDoc
 */
class RoutingMiddleware extends BaseRoutingMiddleware {
	/**
	 * Re-implemented to add the following lines to use the parts from AwyissRoute as QueryParams
	 *
	 *        $la_queryParams = $la_params['parts'] ?? [];
	 *        $lo_request = $lo_request->withQueryParams($la_queryParams);
	 *
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$this->loadRoutes();
		try {
			assert($request instanceof ServerRequest);
			Router::setRequest($request);
			$la_params = (array)$request->getAttribute('params', []);
			$la_middlewareNames = [];
			if (empty($la_params['controller'])) {
				$la_params = Router::parseRequest($request) + $la_params;
				if (isset($la_params['_middleware'])) {
					$la_middlewareNames = $la_params['_middleware'];
				}
				$lo_route = $la_params['_route'];
				unset($la_params['_middleware'], $la_params['_route']);

				$lo_request = $request->withAttribute('route', $lo_route);
				$lo_request = $lo_request->withAttribute('params', $la_params);

				$la_queryParams = $la_params['parts'] ?? [];
				$lo_request = $lo_request->withQueryParams($la_queryParams);

				Router::setRequest($lo_request);
			}
		}
			/** @noinspection PhpVariableNamingConventionInspection */
		catch (RedirectException $e) {
			return new RedirectResponse($e->getMessage(), $e->getCode(), $e->getHeaders());
		}

		$la_matchingMiddlewares = Router::getRouteCollection()->getMiddleware($la_middlewareNames);
		if (!$la_matchingMiddlewares) {
			return $handler->handle($lo_request ?? $request);
		}

		$lo_container = $this->app instanceof ContainerApplicationInterface ? $this->app->getContainer() : null;
		$lo_middlewareQueue = new MiddlewareQueue($la_matchingMiddlewares, $lo_container);
		$lo_runner = new Runner();


		/** @noinspection PhpUndefinedVariableInspection */
		return $lo_runner->run($lo_middlewareQueue, $lo_request, $handler);
	}
}
