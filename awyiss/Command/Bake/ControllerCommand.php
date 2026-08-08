<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Bake\Command\ControllerCommand as BaseControllerCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;


/**
 * Task class for creating and updating controller files.
 */
class ControllerCommand extends BaseControllerCommand {
	/*
	 * Use UtilTrait so that every call of `$this->getPath()` will use the one provided by this trait,
	 * honoring the `namespace`-option
	 */
	use UtilTrait;


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented nearly 1:1 but honors the `namespace`-option and
	 * uses `App::className` to find the defaultModel, instead of a simple sprintf-command, assuming we'd know the model name.
	 *
	 * It also changes the default actions: No 'index' and 'view', but 'overview' and 'save' method.
	 *
	 * @param string $controllerName Controller name already pluralized and correctly cased.
	 * @param Arguments $args The console arguments
	 * @param ConsoleIo $io The console io
	 * @return void
	 */
	public function bake(string $controllerName, Arguments $args, ConsoleIo $io): void {
		$io->quiet(sprintf('Baking controller class for %s...', $controllerName));

		$actions = [];
		if (!$args->getOption('no-actions') && !$args->getOption('actions')) {
			$actions = ['overview', 'add', 'edit', 'delete', 'save'];
		}
		if ($args->getOption('actions')) {
			$actions = array_map('trim', explode(',', (string)$args->getOption('actions')));
			$actions = array_filter($actions);
		}

		$helpers = $this->getHelpers($args);
		$components = $this->getComponents($args);

		$prefix = $this->getPrefix($args);
		if ($prefix) {
			$prefix = '\\' . str_replace('/', '\\', $prefix);
		}

		// Controllers default to importing AppController from `Awyiss`
		$baseNamespace = 'Awyiss';
		$namespace = Inflector::camelize($args->getOption('namespace') ?: Configure::read('App.namespace'));
		if ($this->plugin) {
			$namespace = $this->_pluginNamespace($this->plugin);

			// If the plugin has an AppController other plugin controllers
			// should inherit from it.
			if (class_exists($namespace . '\Controller\AppController')) {
				$baseNamespace = $namespace;
			}
		}

		$currentModelName = $controllerName;
		$plugin = $this->plugin;
		$pluginPath = $plugin;
		if ($pluginPath) {
			$pluginPath .= '.';
		}

		if ($this->getTableLocator()->exists($pluginPath . $currentModelName)) {
			$model = $this->getTableLocator()->get($pluginPath . $currentModelName);
		}
		else {
			$model = $this->getTableLocator()->get($pluginPath . $currentModelName, [
				'connectionName' => $this->connection,
			]);
		}

		$pluralName = $this->_variableName($currentModelName);
		$singularName = $this->_singularName($currentModelName);
		$singularHumanName = $this->_singularHumanName($controllerName);
		$pluralHumanName = $this->_variableName($controllerName);

		// Handle cases where singular and plural are identical (e.g., "news", "sheep")
		// to avoid variable collisions in generated controller code
		if ($singularName === $pluralName) {
			$singularName .= 'Entity';
		}

		$defaultModel = App::className($controllerName, 'Model/Table', 'Table');
		if (!class_exists($defaultModel)) {
			$defaultModel = null;
		}
		$entityClassName = $this->_entityName($model->getAlias());

		$data = [
			'actions' => $actions,
			'components' => $components,
			'currentModelName' => $currentModelName,
			'defaultModel' => $defaultModel,
			'entityClassName' => $entityClassName,
			'helpers' => $helpers,
			'modelObj' => $model,
			'namespace' => $namespace,
			'baseNamespace' => $baseNamespace,
			'plugin' => $plugin,
			'pluralHumanName' => $pluralHumanName,
			'pluralName' => $pluralName,
			'prefix' => $prefix,
			'singularHumanName' => $singularHumanName,
			'singularName' => $singularName,
		];
		$data['name'] = $controllerName;

		$this->bakeController($controllerName, $data, $args, $io);
		$this->bakeTest($controllerName, $args, $io);
	}


	/**
	 * No plugin prefix for the generated template
	 *
	 * @param string $controllerName
	 * @param array $data
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	public function bakeController(string $controllerName, array $data, Arguments $args, ConsoleIo $io): void {
		$data += [
			'name' => null,
			'namespace' => null,
			'prefix' => null,
			'actions' => null,
			'helpers' => null,
			'components' => null,
			'plugin' => null,
			'pluginPath' => null,
		];

		$contents = $this->createTemplateRenderer()->set($data)->generate('Controller/controller');

		$path = $this->getPath($args);
		$fileName = $path . $controllerName . 'Controller.php';

		$io->createFile($fileName, $contents, $this->force);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `namespace`-option.
	 *
	 * @param ConsoleOptionParser $parser The console option parser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('namespace', [
			'choices' => [
				'Awyiss',
				CUSTOM_NAMESPACE,
			],
			'default' => 'Awyiss',
			'help' => 'The namespace for the model.',
		]);


		return $parser;
	}
}
