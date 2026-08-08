<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Core\App;
use Awyiss\Event\EventManager;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Utility\Inflector;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Controller\ControllerFactory as BaseControllerFactory;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use GenericDatatablesBase;
use GenericPagesBase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionNamedType;


/**
 * Factory method for building controllers for request.
 *
 * @implements \Cake\Http\ControllerFactoryInterface<Controller>
 */
class ControllerFactory extends BaseControllerFactory {
	/**
	 * Create a controller for a given request.
	 * Tries to create a GenericController before throwing
	 * an exception
	 *
	 * Also creates a new event manager instance
	 * and passes it to the controller constructor
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return \Cake\Controller\Controller
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 * @noinspection PhpParamsInspection
	 */
	public function create(ServerRequestInterface $request): Controller {
		$className = $this->getControllerClass($request);

		//No className means no class exists for the current request
		if ($className === null) {
			//Try to get a controller based on \Awyiss\Controller\Backend\PagesController::class
			$controller = $this->tryGenericController($request);
			if ($controller) {
				return $controller;
			}
		}

		if ($className === null) {
			throw $this->missingController($request);
		}

		$reflection = new ReflectionClass($className);
		if ($reflection->isAbstract()) {
			throw $this->missingController($request);
		}

		$this->container->addShared(
			ComponentRegistry::class,
			new ComponentRegistry(container: $this->container)
		);

		//If the controller has a container definition add the request as a service.
		if ($this->container->has($className)) {
			$this->container->add(ServerRequest::class, $request);
			/** @noinspection PhpUnhandledExceptionInspection */
			$controller = $this->container->get($className);
		}
		else {
			/**
			 * @var \Cake\Controller\ComponentRegistry $components
			 * @noinspection PhpUnhandledExceptionInspection
			 */
			$components = $this->container->get(ComponentRegistry::class);
			$constructor = $reflection->getConstructor();

			assert($constructor !== null);

			$hasComponents = false;

			foreach ($constructor->getParameters() as $parameter) {
				$paramType = $parameter->getType();
				// TODO: In a future minor release it would be good to start requiring the components parameter
				if (
					$parameter->getName() === 'components' &&
					$paramType instanceof ReflectionNamedType &&
					$paramType->getName() === ComponentRegistry::class
				) {
					$hasComponents = true;
					break;
				}
			}

			// Create a new event manager instance
			// Otherwise the controllers would create a new \Cake\Event\EventManager instance
			$eventManager = new EventManager();

			if ($hasComponents) {
				$controller = $reflection->newInstance(request: $request, components: $components, eventManager: $eventManager);
			}
			else {
				$controller = $reflection->newInstance($request, eventManager: $eventManager);
			}
		}

		return $controller;
	}


	/**
	 * Reimplemented this method 1:1 from \Cake\Controller\ControllerFactory::getControllerClass,
	 * so it'll use \Awyiss\Core\App::className in the return-statement
	 *
	 * @inheritDoc
	 */
	public function getControllerClass(ServerRequest $request): ?string {
		$pluginPath = '';
		$namespace = 'Controller';
		$controller = $request->getParam('controller', '');
		if ($request->getParam('plugin')) {
			$pluginPath = $request->getParam('plugin') . '.';
		}
		if ($request->getParam('prefix')) {
			$namespace .= '/' . $request->getParam('prefix');
		}
		$firstChar = substr($controller, 0, 1);

		// Disallow plugin short forms, / and \\ from
		// controller names as they allow direct references to
		// be created.
		if (str_contains($controller, '\\') || str_contains($controller, '/') || str_contains($controller, '.') || $firstChar === strtolower($firstChar)) {
			throw $this->missingController($request);
		}

		/** @var class-string<Controller>|null */
		return App::className($pluginPath . $controller, $namespace, 'Controller');
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
		$namespace = 'Controller';
		$controller = $request->getParam('controller', '');
		if ($request->getParam('prefix')) {
			$prefix = $request->getParam('prefix');

			$namespace .= '\\' . $prefix;
		}

		$singular = Inflector::singularize(Inflector::underscore($controller));
		//Disallow calling the singular form of the controller, e.g. /backend/de/page instead of /backend/de/pages
		if ($singular == Inflector::underscore($controller) && $singular != Inflector::pluralize(Inflector::underscore($controller))) {
			return null;
		}

		//When there's no matching page role, we don't allow creating a controller
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		$pageRole = $pageRoleEnum::tryFromName($singular);
		if ($pageRole) {
			return $this->buildGenericPageController($controller, $namespace, $request, $pageRole);
		}

		//Get all datatables from the database because we want them to have a generic policy too
		/** @var \Awyiss\Model\Table\DatatablesTable $table */
		$table = FactoryLocator::get('Table')->get('Datatables');
		$datatables = $table->findAllAndCache();
		$datatable = $datatables->firstMatch([
			'active' => true,
			'identifier' => $controller,
		]);

		if ($datatable) {
			return $this->buildGenericDatatablesController($controller, $namespace, $request, $datatable);
		}


		return null;
	}


	/**
	 * @param string $controllerName
	 * @param string $namespace
	 * @param \Cake\Http\ServerRequest $request
	 * @param \Awyiss\Model\Entity\Datatable $datatable
	 * @return \Awyiss\Controller\Backend\GenericDatatablesController
	 * @noinspection PhpUndefinedClassInspection
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 */
	protected function buildGenericDatatablesController(
		string $controllerName,
		string $namespace,
		ServerRequest $request,
		Datatable $datatable
	): Controller {
		/** @var \Cake\Controller\Controller::class $baseController */
		$baseController = App::className('GenericDatatablesBase', $namespace, 'Controller');
		if (!$baseController) {
			/** @var \Awyiss\Controller\Backend\GenericDatatablesController::class $baseController */
			$baseController = App::className('GenericDatatables', $namespace, 'Controller');
		}

		/*
		 * Set a class alias, so it's available in the root namespace, allowing the extension of "\GenericDatatablesBase"
		 *
		 * This is required because it's not possible to extend a dynamic class. And since $baseController might
		 * return a controller inside the custom namespace, line 235 would extend the wrong class,
		 * if "GenericDatatables" would be hard coded
		 */
		if (!class_exists('GenericDatatablesBase')) {
			class_alias($baseController, 'GenericDatatablesBase');
		}

		/**
		 * Create a new class instance and set the datatable to the defined constant.
		 *
		 * @var \Awyiss\Controller\Backend\GenericDatatablesController $controller
		 */
		//phpcs:disable SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces, Squiz.WhiteSpace.ScopeClosingBrace
		$controller = new class ($request) extends GenericDatatablesBase { };
		$controller->forDatatable($datatable, $controllerName);
		//phpcs:enable


		return $controller;
	}


	/**
	 * @param string $controllerName
	 * @param string $namespace
	 * @param \Cake\Http\ServerRequest $request
	 * @param \Awyiss\Model\Enum\PageRoleEnumInterface $pageRole
	 * @return \Awyiss\Controller\Backend\PagesController
	 * @noinspection PhpUndefinedClassInspection
	 * @noinspection PhpMethodParametersCountMismatchInspection
	 */
	protected function buildGenericPageController(
		string $controllerName,
		string $namespace,
		ServerRequest $request,
		PageRoleEnumInterface $pageRole
	): Controller {
		/** @var \Cake\Controller\Controller::class $baseController */
		$baseController = App::className('GenericPagesBase', $namespace, 'Controller');
		if (!$baseController) {
			/** @var \Awyiss\Controller\Backend\PagesController::class $baseController */
			$baseController = App::className('Pages', $namespace, 'Controller');
		}

		/*
		 * Set a class alias, so it's available in the root namespace, allowing the extension of "\GenericPagesBase"
		 *
		 * This is required because it's not possible to extend a dynamic class. And since $baseController might
		 * return a controller inside the custom namespace, line 284 would extend the wrong class,
		 * if "Pages" would be hard coded
		 */
		if (!class_exists('GenericPagesBase')) {
			class_alias($baseController, 'GenericPagesBase');
		}

		/**
		 * Create a new class instance and set the page_role to the defined enum.
		 *
		 * @var \Awyiss\Controller\Backend\PagesController $controller
		 */
		//phpcs:disable SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces, Squiz.WhiteSpace.ScopeClosingBrace
		$controller = new class ($request) extends GenericPagesBase { };
		$controller->asPageRole($pageRole, $controllerName);
		//phpcs:enable


		return $controller;
	}
}
