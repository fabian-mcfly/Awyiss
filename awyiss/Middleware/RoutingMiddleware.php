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
	 *        $queryParams = $params['parts'] ?? [];
	 *        $request = $request->withQueryParams($queryParams);
	 *
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$this->loadRoutes();
		try {
			assert($request instanceof ServerRequest);
			Router::setRequest($request);
			$params = (array)$request->getAttribute('params', []);
			$middlewareNames = [];
			if (empty($params['controller'])) {
				$params = Router::parseRequest($request) + $params;
				if (isset($params['_middleware'])) {
					$middlewareNames = $params['_middleware'];
				}
				$route = $params['_route'];
				unset($params['_middleware'], $params['_route']);

				$request = $request->withAttribute('route', $route);
				$request = $request->withAttribute('params', $params);

				$queryParams = $params['parts'] ?? [];
				$request = $request->withQueryParams($queryParams);

				Router::setRequest($request);
			}
		}
		catch (RedirectException $e) {
			return new RedirectResponse($e->getMessage(), $e->getCode(), $e->getHeaders());
		}

		$matchingMiddlewares = Router::getRouteCollection()->getMiddleware($middlewareNames);
		if (!$matchingMiddlewares) {
			return $handler->handle($request);
		}

		$container = $this->app instanceof ContainerApplicationInterface ? $this->app->getContainer() : null;
		$middlewareQueue = new MiddlewareQueue($matchingMiddlewares, $container);
		$runner = new Runner();

		return $runner->run($middlewareQueue, $request, $handler);
	}
}
