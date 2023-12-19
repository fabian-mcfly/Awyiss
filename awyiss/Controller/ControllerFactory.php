<?php


namespace Awyiss\Controller;


use Awyiss\Core\App;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;


/**
 * Factory method for building controllers for request.
 *
 * @implements \Cake\Http\ControllerFactoryInterface<\Cake\Controller\Controller>
 */
class ControllerFactory extends \Cake\Controller\ControllerFactory {
	/**
	 * Create a controller for a given request.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request The request to build a controller for.
	 *
	 * @return \Cake\Controller\Controller
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpParamsInspection //somehow PhpStorm does not realize that ServerRequest extends ServerRequestInterface
	 */
	public function create (ServerRequestInterface $ao_request): Controller {
		$ls_className = NULL;

		if ($ls_className === NULL) {
			$ls_className = $this->getControllerClass($ao_request);
		}

		if ($ls_className === NULL) {
			if ($lo_controller = $this->tryGenericPagesController($ao_request)) {
				return $lo_controller;
			}
		}

		if ($ls_className === NULL) {
			throw $this->missingController($ao_request);
		}

		$lo_reflection = new ReflectionClass($ls_className);
		if ($lo_reflection->isAbstract()) {
			throw $this->missingController($ao_request);
		}

		// If the controller has a container definition
		// add the request as a service.
		if ($this->container->has($ls_className)) {
			$this->container->add(ServerRequest::class, $ao_request);
			$lo_controller = $this->container->get($ls_className);
		}
		else {
			$lo_controller = $lo_reflection->newInstance($ao_request);
		}

		return $lo_controller;
	}


	/**
	 * @inheritDoc
	 *
	 * Reimplemented this method 1:1 from \Cake\Controller\ControllerFactory::getControllerClass,
	 * so it'll use \Awyiss\Core\App::className
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getControllerClass (ServerRequest $ao_request): ?string {
		$ls_pluginPath = '';
		$ls_namespace = 'Controller';
		$ls_controller = $ao_request->getParam('controller', '');

		if ($ao_request->getParam('plugin')) {
			$ls_pluginPath = $ao_request->getParam('plugin') . '.';
		}

		if ($ao_request->getParam('prefix')) {
			$ls_prefix = $ao_request->getParam('prefix');

			$ls_firstChar = substr($ls_prefix, 0, 1);
			if ($ls_firstChar !== strtoupper($ls_firstChar)) {
				deprecationWarning(sprintf("The `%s` prefix did not start with an upper case character. " . 'Routing prefixes should be defined as CamelCase values. ' . 'Prefix inflection will be removed in 5.0', $ls_prefix));

				if ( ! str_contains($ls_prefix, '/')) {
					$ls_namespace .= '/' . Inflector::camelize($ls_prefix);
				}
				else {
					$ls_prefixes = array_map(function($val) {
						return Inflector::camelize($val);
					}, explode('/', $ls_prefix));
					$ls_namespace .= '/' . implode('/', $ls_prefixes);
				}
			}
			else {
				$ls_namespace .= '/' . $ls_prefix;
			}
		}
		$ls_firstChar = substr($ls_controller, 0, 1);


		// Disallow plugin short forms, / and \\ from
		// controller names as they allow direct references to
		// be created.
		if (str_contains($ls_controller, '\\') || str_contains($ls_controller, '/') || str_contains($ls_controller, '.') || $ls_firstChar === strtolower($ls_firstChar)) {
			throw $this->missingController($ao_request);
		}

		/** @var class-string<\Cake\Controller\Controller>|null */
		return App::className($ls_pluginPath . $ls_controller, $ls_namespace, 'Controller');
	}


	/**
	 * @noinspection PhpUndefinedClassInspection
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 * @throws \ReflectionException
	 */
	public function tryGenericPagesController (ServerRequest $ao_request): ?Controller {
		$ls_namespace = 'Controller';
		$ls_controller = $ao_request->getParam('controller', '');
		if ($ao_request->getParam('prefix')) {
			$ls_prefix = $ao_request->getParam('prefix');

			$ls_namespace .= '\\' . $ls_prefix;
		}

		$ls_singular = \Cake\Utility\Inflector::singularize($ls_controller);

		if ($ls_singular == $ls_controller) {
			return NULL;
		}

		$ls_constantIdentifier = 'PAGEROLE_' . strtoupper($ls_singular);
		if (!defined($ls_constantIdentifier)) {
			return NULL;
		}

		//dd($ls_constantIdentifier, constant($ls_constantIdentifier), $ls_singular, $ls_controller, $ls_namespace);

		$ls_baseController = App::className('Pages', $ls_namespace, 'Controller');
		//$ls_fullController = Configure::read('App.namespace') . '\\' . $ls_namespace . '\\' . $ls_controller . 'Controller';

		if (!class_exists('GenericPagesBase')) {
			class_alias($ls_baseController, 'GenericPagesBase');
		}

		/** @var \Awyiss\Controller\Backend\PagesController $lo_controller */
		$lo_controller = new class ($ao_request) extends \GenericPagesBase {};
		$lo_controller->asPageRole(constant($ls_constantIdentifier), $ls_controller);

		return $lo_controller;
	}
}