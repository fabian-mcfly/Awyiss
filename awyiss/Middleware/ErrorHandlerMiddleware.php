<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Cake\Error\Middleware\ErrorHandlerMiddleware as BaseErrorHandlerMiddleware;
use Cake\Http\Exception\RedirectException;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;


/**
 * Error handling middleware.
 * Traps exceptions and converts them into HTML or content-type appropriate
 * error pages using the CakePHP ExceptionRenderer.
 */
class ErrorHandlerMiddleware extends BaseErrorHandlerMiddleware {
	/**
	 * Use the latest request since it might contain attributes the basic one doesn't have.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		try {
			return $handler->handle($request);
		}
		catch (RedirectException $ex) {
			return $this->handleRedirect($ex);
		}
		catch (Throwable $ex) {
			return $this->handleException($ex, Router::getRequest() ?? $request);
		}
	}
}
