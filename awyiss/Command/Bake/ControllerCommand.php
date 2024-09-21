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
	 * It also changes the default actions: No 'index'- and 'view'-, but 'overview'- and 'save'-method.
	 *
	 * @param string $controllerName Controller name already pluralized and correctly cased.
	 * @param Arguments $args The console arguments
	 * @param ConsoleIo $io The console io
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bake(string $controllerName, Arguments $args, ConsoleIo $io): void {
		$io->quiet(sprintf('Baking controller class for %s...', $controllerName));

		$la_actions = [];
		if (!$args->getOption('no-actions') && !$args->getOption('actions')) {
			$la_actions = ['overview', 'add', 'edit', 'delete', 'save'];
		}
		if ($args->getOption('actions')) {
			$la_actions = array_map('trim', explode(',', $args->getOption('actions')));
			$la_actions = array_filter($la_actions);
		}

		$la_helpers = $this->getHelpers($args);
		$la_components = $this->getComponents($args);

		$ls_prefix = $this->getPrefix($args);
		if ($ls_prefix) {
			$ls_prefix = '\\' . str_replace('/', '\\', $ls_prefix);
		}

		// Controllers default to importing AppController from `Awyiss`
		$ls_baseNamespace = 'Awyiss';
		$ls_namespace = Inflector::camelize($args->getOption('namespace') ?: Configure::read('App.namespace'));
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);

			// If the plugin has an AppController other plugin controllers
			// should inherit from it.
			if (class_exists($ls_namespace . '\Controller\AppController')) {
				$ls_baseNamespace = $ls_namespace;
			}
		}

		$ls_currentModelName = $controllerName;
		$ls_plugin = $this->plugin;
		if ($ls_plugin) {
			$ls_plugin .= '.';
		}

		if ($this->getTableLocator()->exists($ls_plugin . $ls_currentModelName)) {
			$lo_model = $this->getTableLocator()->get($ls_plugin . $ls_currentModelName);
		}
		else {
			$lo_model = $this->getTableLocator()->get($ls_plugin . $ls_currentModelName, [
				'connectionName' => $this->connection,
			]);
		}

		$ls_pluralName = $this->_variableName($ls_currentModelName);
		$ls_singularName = $this->_singularName($ls_currentModelName);
		$ls_singularHumanName = $this->_singularHumanName($controllerName);
		$ls_pluralHumanName = $this->_variableName($controllerName);

		$ls_defaultModel = App::className($controllerName, 'Model/Table', 'Table');
		if (!class_exists($ls_defaultModel)) {
			$ls_defaultModel = null;
		}
		$ls_entityClassName = $this->_entityName($lo_model->getAlias());

		$la_data = [
			'actions' => $la_actions,
			'components' => $la_components,
			'currentModelName' => $ls_currentModelName,
			'defaultModel' => $ls_defaultModel,
			'entityClassName' => $ls_entityClassName,
			'helpers' => $la_helpers,
			'modelObj' => $lo_model,
			'namespace' => $ls_namespace,
			'baseNamespace' => $ls_baseNamespace,
			'plugin' => $ls_plugin,
			'pluralHumanName' => $ls_pluralHumanName,
			'pluralName' => $ls_pluralName,
			'prefix' => $ls_prefix,
			'singularHumanName' => $ls_singularHumanName,
			'singularName' => $ls_singularName,
		];
		$la_data['name'] = $controllerName;

		$this->bakeController($controllerName, $la_data, $args, $io);
		$this->bakeTest($controllerName, $args, $io);
	}


	/**
	 * No plugin prefix for the generated template
	 *
	 * @param string $ls_controllerName
	 * @param array $data
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bakeController(string $ls_controllerName, array $data, Arguments $args, ConsoleIo $io): void {
		$la_data = $data + [
			'name' => null,
			'namespace' => null,
			'prefix' => null,
			'actions' => null,
			'helpers' => null,
			'components' => null,
			'plugin' => null,
			'pluginPath' => null,
		];

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate('Controller/controller');

		$ls_path = $this->getPath($args);
		$ls_fileName = $ls_path . $ls_controllerName . 'Controller.php';

		$io->createFile($ls_fileName, $ls_contents, $this->force);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `namespace`-option.
	 *
	 * @param ConsoleOptionParser $parser The console option parser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('namespace', [
			'choices' => [
				'Awyiss',
				CUSTOM_NAMESPACE,
			],
			'default' => 'Awyiss',
			'help' => 'The namespace for the model.',
		]);


		return $lo_parser;
	}
}
