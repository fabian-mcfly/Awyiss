<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Awyiss\Core\App;
use AwyissBake\UtilTrait;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Utility\Inflector;


/**
 * Task class for creating and updating controller files.
 */
class ControllerCommand extends \Bake\Command\ControllerCommand {
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
	 * @param string $as_controllerName Controller name already pluralized and correctly cased.
	 * @param \Cake\Console\Arguments $ao_args The console arguments
	 * @param \Cake\Console\ConsoleIo $ao_io The console io
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bake (string $as_controllerName, Arguments $ao_args, ConsoleIo $ao_io): void {
		$ao_io->quiet(sprintf('Baking controller class for %s...', $as_controllerName));

		$la_actions = [];
		if (!$ao_args->getOption('no-actions') && !$ao_args->getOption('actions')) {
			$la_actions = ['overview', 'add', 'edit', 'delete', 'save'];
		}
		if ($ao_args->getOption('actions')) {
			$la_actions = array_map('trim', explode(',', $ao_args->getOption('actions')));
			$la_actions = array_filter($la_actions);
		}
		$la_helpers = $this->getHelpers($ao_args);
		$la_components = $this->getComponents($ao_args);

		$ls_prefix = $this->getPrefix($ao_args);
		if ($ls_prefix) {
			$ls_prefix = '\\' . str_replace('/', '\\', $ls_prefix);
		}

		// Controllers default to importing AppController from `App`
		$ls_baseNamespace = 'Awyiss';
		$ls_namespace = Inflector::camelize($ao_args->getOption('namespace') ?: Configure::read('App.namespace'));
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);

			// If the plugin has an AppController other plugin controllers
			// should inherit from it.
			if (class_exists($ls_namespace . '\Controller\AppController')) {
				$ls_baseNamespace = $ls_namespace;
			}
		}

		$ls_currentModelName = $as_controllerName;
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
		$ls_singularHumanName = $this->_singularHumanName($as_controllerName);
		$ls_pluralHumanName = $this->_variableName($as_controllerName);

		$ls_defaultModel = App::className(sprintf('%sTable', $as_controllerName), 'Model/Table');
		if (!class_exists($ls_defaultModel)) {
			$ls_defaultModel = NULL;
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
		$la_data['name'] = $as_controllerName;

		$this->bakeController($as_controllerName, $la_data, $ao_args, $ao_io);
		$this->bakeTest($as_controllerName, $ao_args, $ao_io);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `namespace`-option.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $ao_parser The console option parser
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser (ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('namespace', [
			'help' => 'The namespace for the model. Should be either "Awyiss" or <CUSTOM_NAMESPACE>',
		]);

		return $lo_parser;
	}
}