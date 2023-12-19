<?php

declare(strict_types=1);


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
	private $lo_application;


	public function __construct ($lo_application) {
		$this->lo_application = $lo_application;
	}


	public function process (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		try {
			$lo_controllerFactory = new class ($this->lo_application->getContainer()) extends ControllerFactory {
				/**
				 * {@inheritDoc}
				 * @noinspection PhpParamsInspection
				 */
				public function create (ServerRequestInterface $request): Controller {
					$ls_defaultNamespace = Configure::read('App.namespace');
					Configure::write('App.namespace', CUSTOM_NAMESPACE);

					$className = $this->getControllerClass($request);

					Configure::write('App.namespace', $ls_defaultNamespace);

					if ($className === NULL) {
						throw $this->missingController($request);
					}

					$reflection = new ReflectionClass($className);
					if ($reflection->isAbstract()) {
						throw $this->missingController($request);
					}

					// If the controller has a container definition
					// add the request as a service.
					if ($this->container->has($className)) {
						$this->container->add(ServerRequest::class, $request);
						$controller = $this->container->get($className);
					}
					else {
						$controller = $reflection->newInstance($request);
					}

					return $controller;
				}
			};
			$lo_controller = $lo_controllerFactory->create($request);
		}
		catch (\Cake\Http\Exception\MissingControllerException $lo_ex) {
			return $handler->handle($request);
		}

		return $lo_controllerFactory->invoke($lo_controller);
	}
}