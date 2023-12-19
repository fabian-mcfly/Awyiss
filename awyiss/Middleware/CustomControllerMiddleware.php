<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Cake\Controller\Controller;
use Cake\Controller\ControllerFactory;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;


class CustomControllerMiddleware implements MiddlewareInterface {
	private object $lo_application;


	public function __construct ($ao_application) {
		$this->lo_application = $ao_application;
	}


	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		try {
			$lo_controllerFactory = new class ($this->lo_application->getContainer()) extends ControllerFactory {
				/**
				 * {@inheritDoc}
				 * @noinspection PhpParamsInspection
				 */
				public function create (ServerRequestInterface $ao_request): Controller {
					$ls_defaultNamespace = Configure::read('App.namespace');
					Configure::write('App.namespace', CUSTOM_NAMESPACE);

					$ls_className = $this->getControllerClass($ao_request);

					Configure::write('App.namespace', $ls_defaultNamespace);

					if ($ls_className === NULL) {
						throw $this->missingController($ao_request);
					}

					$reflection = new ReflectionClass($ls_className);
					if ($reflection->isAbstract()) {
						throw $this->missingController($ao_request);
					}

					// If the controller has a container definition
					// add the request as a service.
					if ($this->container->has($ls_className)) {
						$this->container->add(ServerRequest::class, $ao_request);
						$controller = $this->container->get($ls_className);
					}
					else {
						$controller = $reflection->newInstance($ao_request);
					}

					return $controller;
				}
			};
			$lo_controller = $lo_controllerFactory->create($ao_request);
		}
		catch (\Cake\Http\Exception\MissingControllerException $ex) {
			return $ao_handler->handle($ao_request);
		}

		return $lo_controllerFactory->invoke($lo_controller);
	}
}