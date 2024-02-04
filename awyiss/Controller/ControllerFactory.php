<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Core\App;
use Cake\Controller\Controller;
use Cake\Controller\ControllerFactory as BaseControllerFactory;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use GenericPagesBase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;


/**
 * Factory method for building controllers for request.
 *
 * @implements \Cake\Http\ControllerFactoryInterface<Controller>
 */
class ControllerFactory extends BaseControllerFactory {
	/**
	 * Create a controller for a given request.
	 *
	 * @param ServerRequestInterface $ao_request The request to build a controller for.
	 * @return Controller
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpParamsInspection //somehow PhpStorm does not realize that ServerRequest extends ServerRequestInterface
	 */
	public function create(ServerRequestInterface $ao_request): Controller {
		$ls_className = $this->getControllerClass($ao_request);

		//No className means no class exists for the current request
		if ($ls_className === null) {
			//Try to get a controller based on \Awyiss\Controller\Backend\PagesController::class
			$lo_controller = $this->tryGenericPagesController($ao_request);
			if ($lo_controller) {
				return $lo_controller;
			}
		}

		if ($ls_className === null) {
			throw $this->missingController($ao_request);
		}

		$lo_reflection = new ReflectionClass($ls_className);
		if ($lo_reflection->isAbstract()) {
			throw $this->missingController($ao_request);
		}

		//If the controller has a container definition add the request as a service.
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
	 * {@inheritDoc}
	 *
	 * Reimplemented this method 1:1 from \Cake\Controller\ControllerFactory::getControllerClass,
	 * so it'll use \Awyiss\Core\App::className in the return-statement
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getControllerClass(ServerRequest $ao_request): ?string {
		$ls_pluginPath = '';
		$ls_namespace = 'Controller';
		$ls_controller = $ao_request->getParam('controller', '');
		if ($ao_request->getParam('plugin')) {
			$ls_pluginPath = $ao_request->getParam('plugin') . '.';
		}
		if ($ao_request->getParam('prefix')) {
			$ls_namespace .= '/' . $ao_request->getParam('prefix');
		}
		$ls_firstChar = substr($ls_controller, 0, 1);

		// Disallow plugin short forms, / and \\ from
		// controller names as they allow direct references to
		// be created.
		if (str_contains($ls_controller, '\\') || str_contains($ls_controller, '/') || str_contains($ls_controller, '.') || $ls_firstChar === strtolower($ls_firstChar)) {
			throw $this->missingController($ao_request);
		}


		/** @var class-string<Controller>|null */
		return App::className($ls_pluginPath . $ls_controller, $ls_namespace, 'Controller');
	}


	/**
	 * Tries to create an instance of a controller based on either a class named "GenericPagesBase" or "Pages"
	 * in the given namespace, e.g. "Backend".
	 * This allows page roles to have their own routes without the need to create a controller
	 *
	 * @throws \ReflectionException
	 * @see \Awyiss\Controller\Backend\PagesController::asPageRole()
	 * @noinspection PhpUndefinedClassInspection
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 */
	public function tryGenericPagesController(ServerRequest $ao_request): ?Controller {
		$ls_namespace = 'Controller';
		$ls_controller = $ao_request->getParam('controller', '');
		if ($ao_request->getParam('prefix')) {
			$ls_prefix = $ao_request->getParam('prefix');

			$ls_namespace .= '\\' . $ls_prefix;
		}

		$ls_singular = Inflector::singularize(Inflector::underscore($ls_controller));
		//Disallow calling the singular form of the controller, e.g. /backend/de/page instead of /backend/de/pages
		if ($ls_singular == Inflector::underscore($ls_controller) && $ls_singular != Inflector::pluralize(Inflector::underscore($ls_controller))) {
			return null;
		}

		//When there's no matching page role, we don't allow creating a controller
		/** @var class-string<\Awyiss\Database\Type\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		$le_pageRole = $ls_pageRoleEnum::tryFromName($ls_singular);
		if (!$le_pageRole) {
			return null;
		}

		/** @var \Cake\Controller\Controller::class $ls_baseController */
		$ls_baseController = App::className('GenericPagesBase', $ls_namespace, 'Controller');
		if (!$ls_baseController) {
			/** @var \Awyiss\Controller\Backend\PagesController::class $ls_baseController */
			$ls_baseController = App::className('Pages', $ls_namespace, 'Controller');
		}

		//Set a class alias, so it's available in the root namespace, allowing the extension of "\GenericPagesBase"
		if (!class_exists('GenericPagesBase')) {
			class_alias($ls_baseController, 'GenericPagesBase');
		}

		/**
		 * Create a new class instance and set the page_role to the defined constant.
		 *
		 * @var \Awyiss\Controller\Backend\PagesController $lo_controller
		 */
		//phpcs:disable SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces, Squiz.WhiteSpace.ScopeClosingBrace
		$lo_controller = new class ($ao_request) extends GenericPagesBase {};
		$lo_controller->asPageRole($le_pageRole, $ls_controller);
		//phpcs:enable


		return $lo_controller;
	}
}
