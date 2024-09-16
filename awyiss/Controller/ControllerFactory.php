<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Core\App;
use Awyiss\Event\EventManager;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Controller\ControllerFactory as BaseControllerFactory;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use GenericDatatablesBase;
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
	 * @param ServerRequestInterface $request The request to build a controller for.
	 * @return Controller
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 * @noinspection PhpParamsInspection //somehow PhpStorm does not realize that ServerRequest extends ServerRequestInterface
	 */
	public function create(ServerRequestInterface $request): Controller {
		$ls_className = $this->getControllerClass($request);

		//No className means no class exists for the current request
		if ($ls_className === null) {
			//Try to get a controller based on \Awyiss\Controller\Backend\PagesController::class
			$lo_controller = $this->tryGenericController($request);
			if ($lo_controller) {
				return $lo_controller;
			}
		}

		if ($ls_className === null) {
			throw $this->missingController($request);
		}

		$lo_reflection = new ReflectionClass($ls_className);
		if ($lo_reflection->isAbstract()) {
			throw $this->missingController($request);
		}

		$this->container->addShared(
			ComponentRegistry::class,
			new ComponentRegistry(container: $this->container)
		);

		//If the controller has a container definition add the request as a service.
		if ($this->container->has($ls_className)) {
			$this->container->add(ServerRequest::class, $request);
			$lo_controller = $this->container->get($ls_className);
		}
		else {
			/** @var \Cake\Controller\ComponentRegistry $lo_components */
			$lo_components = $this->container->get(ComponentRegistry::class);
			$lo_constructor = $lo_reflection->getConstructor();

			assert($lo_constructor !== null);

			$lb_hasComponents = false;

			foreach ($lo_constructor->getParameters() as $lo_parameter) {
				$lo_paramType = $lo_parameter->getType();
				// TODO: In a future minor release it would be good to start requiring the components parameter
				if (
					$lo_parameter->getName() === 'components' && $lo_paramType !== null && $lo_paramType->getName() == ComponentRegistry::class
				) {
					$lb_hasComponents = true;
					break;
				}
			}

			$lo_eventManager = new EventManager();

			if ($lb_hasComponents) {
				$lo_controller = $lo_reflection->newInstance(request: $request, components: $lo_components, eventManager: $lo_eventManager);
			}
			else {
				$lo_controller = $lo_reflection->newInstance($request, eventManager: $lo_eventManager);
			}
		}

		return $lo_controller;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Reimplemented this method 1:1 from \Cake\Controller\ControllerFactory::getControllerClass,
	 * so it'll use \Awyiss\Core\App::className in the return-statement
	 */
	public function getControllerClass(ServerRequest $request): ?string {
		$ls_pluginPath = '';
		$ls_namespace = 'Controller';
		$ls_controller = $request->getParam('controller', '');
		if ($request->getParam('plugin')) {
			$ls_pluginPath = $request->getParam('plugin') . '.';
		}
		if ($request->getParam('prefix')) {
			$ls_namespace .= '/' . $request->getParam('prefix');
		}
		$ls_firstChar = substr($ls_controller, 0, 1);

		// Disallow plugin short forms, / and \\ from
		// controller names as they allow direct references to
		// be created.
		if (str_contains($ls_controller, '\\') || str_contains($ls_controller, '/') || str_contains($ls_controller, '.') || $ls_firstChar === strtolower($ls_firstChar)) {
			throw $this->missingController($request);
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
	 */
	public function tryGenericController(ServerRequest $request): ?Controller {
		$ls_namespace = 'Controller';
		$ls_controller = $request->getParam('controller', '');
		if ($request->getParam('prefix')) {
			$ls_prefix = $request->getParam('prefix');

			$ls_namespace .= '\\' . $ls_prefix;
		}

		$ls_singular = Inflector::singularize(Inflector::underscore($ls_controller));
		//Disallow calling the singular form of the controller, e.g. /backend/de/page instead of /backend/de/pages
		if ($ls_singular == Inflector::underscore($ls_controller) && $ls_singular != Inflector::pluralize(Inflector::underscore($ls_controller))) {
			return null;
		}

		//When there's no matching page role, we don't allow creating a controller
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		$le_pageRole = $ls_pageRoleEnum::tryFromName($ls_singular);
		if ($le_pageRole) {
			return $this->buildGenericPageController($ls_controller, $ls_namespace, $request, $le_pageRole);
		}


		//Get all datatables from the database because we want them to have a generic policy too
		/** @var \Awyiss\Model\Table\DatatablesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Datatables');
		$lo_datatables = $lo_table->findAllAndCache();
		$lo_datatable = $lo_datatables->firstMatch([
			'active' => true,
			'identifier' => Inflector::underscore($ls_controller),
		]);

		if ($lo_datatable) {
			return $this->buildGenericDatatablesController($ls_controller, $ls_namespace, $request, $lo_datatable);
		}


		return null;
	}


	/**
	 * @param string $controller
	 * @param string $namespace
	 * @param \Cake\Http\ServerRequest $request
	 * @param \Awyiss\Model\Entity\Datatable $datatable
	 * @return \Awyiss\Controller\Backend\GenericDatatablesController
	 * @throws \ReflectionException
	 * @noinspection PhpUndefinedClassInspection
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 */
	protected function buildGenericDatatablesController(
		string $controller,
		string $namespace,
		ServerRequest $request,
		Datatable $datatable
	): Controller {
		/** @var \Cake\Controller\Controller::class $ls_baseController */
		$ls_baseController = App::className('GenericDatatablesBase', $namespace, 'Controller');
		if (!$ls_baseController) {
			/** @var \Awyiss\Controller\Backend\GenericDatatablesController::class $ls_baseController */
			$ls_baseController = App::className('GenericDatatables', $namespace, 'Controller');
		}

		/*
		 * Set a class alias, so it's available in the root namespace, allowing the extension of "\GenericDatatablesBase"
		 *
		 * This is required because it's not possible to extend a dynamic class. And since $ls_baseController might
		 * return a controller inside the custom namespace, line 157 would extend the wrong class,
		 * if "GenericDatatables" would be hard coded
		 */
		if (!class_exists('GenericDatatablesBase')) {
			class_alias($ls_baseController, 'GenericDatatablesBase');
		}

		/**
		 * Create a new class instance and set the datatable to the defined constant.
		 *
		 * @var \Awyiss\Controller\Backend\GenericDatatablesController $lo_controller
		 */
		//phpcs:disable SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces, Squiz.WhiteSpace.ScopeClosingBrace
		$lo_controller = new class ($request) extends GenericDatatablesBase { };
		$lo_controller->forDatatable($datatable, $controller);
		//phpcs:enable


		return $lo_controller;
	}


	/**
	 * @param string $controller
	 * @param string $namespace
	 * @param \Cake\Http\ServerRequest $request
	 * @param \Awyiss\Model\Enum\PageRoleEnumInterface $pageRole
	 * @return \Awyiss\Controller\Backend\PagesController
	 * @throws \ReflectionException
	 * @noinspection PhpUndefinedClassInspection
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 */
	protected function buildGenericPageController(
		string $controller,
		string $namespace,
		ServerRequest $request,
		PageRoleEnumInterface $pageRole
	): Controller {
		/** @var \Cake\Controller\Controller::class $ls_baseController */
		$ls_baseController = App::className('GenericPagesBase', $namespace, 'Controller');
		if (!$ls_baseController) {
			/** @var \Awyiss\Controller\Backend\PagesController::class $ls_baseController */
			$ls_baseController = App::className('Pages', $namespace, 'Controller');
		}

		/*
		 * Set a class alias, so it's available in the root namespace, allowing the extension of "\GenericPagesBase"
		 *
		 * This is required because it's not possible to extend a dynamic class. And since $ls_baseController might
		 * return a controller inside the custom namespace, line 157 would extend the wrong class,
		 * if "Pages" would be hard coded
		 */
		if (!class_exists('GenericPagesBase')) {
			class_alias($ls_baseController, 'GenericPagesBase');
		}

		/**
		 * Create a new class instance and set the page_role to the defined enum.
		 *
		 * @var \Awyiss\Controller\Backend\PagesController $lo_controller
		 */
		//phpcs:disable SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces, Squiz.WhiteSpace.ScopeClosingBrace
		$lo_controller = new class ($request) extends GenericPagesBase { };
		$lo_controller->asPageRole($pageRole, $controller);
		//phpcs:enable


		return $lo_controller;
	}
}
