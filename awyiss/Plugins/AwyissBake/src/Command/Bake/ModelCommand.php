<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Bake\CodeGen\FileBuilder;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;


class ModelCommand extends \Bake\Command\ModelCommand {
	/**
	 * Bake an entity class.
	 *
	 * @param \Cake\ORM\Table $ao_model Model name or object
	 * @param array $aa_data An array to use to generate the Table
	 * @param \Cake\Console\Arguments $ao_args CLI Arguments
	 * @param \Cake\Console\ConsoleIo $ao_io CLI ao_io
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bakeEntity (Table $ao_model, array $aa_data, Arguments $ao_args, ConsoleIo $ao_io): void {
		if ($ao_args->getOption('no-entity')) {
			return;
		}

		$ls_name = $this->_entityName($ao_model->getAlias());
		$ao_io->out("\n" . sprintf('Baking entity class for %s...', $ls_name)/*, 1, ConsoleIo::NORMAL*/);

		$ls_namespace = Configure::read('App.namespace');
		$ls_pluginPath = '';
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
			$ls_pluginPath = $this->plugin . '.';
		}
		elseif ($ao_args->getOption('namespace')) {
			$ls_namespace = Inflector::underscore($ao_args->getOption('namespace'));
			$ls_namespace = Inflector::camelize($ls_namespace);
		}

		$ls_path = $this->getPath($ao_args);
		$ls_filename = $ls_path . 'Entity' . DS . $ls_name . '.php';

		$lo_parsedFile = NULL;
		if ($ao_args->getOption('update')) {
			$lo_parsedFile = $this->parseFile($ls_filename);
		}

		$la_data = $aa_data + [
			'name' => $ls_name,
			'namespace' => $ls_namespace,
			'plugin' => $this->plugin,
			'pluginPath' => $ls_pluginPath,
			'primaryKey' => [],
			'fileBuilder' => new FileBuilder($ao_io, $ls_namespace . '\Model\Entity', $lo_parsedFile),
		];

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate('Bake.Model/entity');

		$this->writeFile($ao_io, $ls_filename, $ls_contents, $this->force);

		$ls_emptyFile = $ls_path . 'Entity' . DS . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $ao_io);
	}


	/**
	 * Bake a table class.
	 *
	 * @param \Cake\ORM\Table $ao_model Model name or object
	 * @param array $aa_data An array to use to generate the Table
	 * @param \Cake\Console\Arguments $ao_args CLI Arguments
	 * @param \Cake\Console\ConsoleIo $ao_io CLI Arguments
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bakeTable (Table $ao_model, array $aa_data, Arguments $ao_args, ConsoleIo $ao_io): void {
		if ($ao_args->getOption('no-table')) {
			return;
		}

		$ls_name = $ao_model->getAlias();
		$ao_io->out("\n" . sprintf('Baking table class for %s...', $ls_name)/*, 1, ConsoleIo::NORMAL*/);

		$ls_namespace = Configure::read('App.namespace');
		$ls_pluginPath = '';
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
		}
		elseif ($ao_args->getOption('namespace')) {
			$ls_namespace = Inflector::underscore($ao_args->getOption('namespace'));
			$ls_namespace = Inflector::camelize($ls_namespace);
		}

		$ls_path = $this->getPath($ao_args);
		$ls_filename = $ls_path . 'Table' . DS . $ls_name . 'Table.php';

		$lo_parsedFile = NULL;
		if ($ao_args->getOption('update')) {
			$lo_parsedFile = $this->parseFile($ls_filename);
		}

		if ($lo_parsedFile) {
			unset($lo_parsedFile->class->constants['ATTRIBUTABLE'], $lo_parsedFile->class->constants['TABLE']);
		}

		$ls_entity = $this->_entityName($ao_model->getAlias());
		$la_data = $aa_data + [
			'plugin' => $this->plugin,
			'pluginPath' => $ls_pluginPath,
			'namespace' => $ls_namespace,
			'name' => $ls_name,
			'entity' => $ls_entity,
			'associations' => [],
			'primaryKey' => 'id',
			'displayField' => NULL,
			'table' => NULL,
			'validation' => [],
			'rulesChecker' => [],
			'behaviors' => [],
			'connection' => $this->connection,
			'fileBuilder' => new FileBuilder($ao_io, $ls_namespace. '\Model\Table', $lo_parsedFile),
		];

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate('Bake.Model/table');

		$this->writefile($ao_io, $ls_filename, $ls_contents, $this->force);

		// Work around composer caching that classes/files do not exist.
		// Check for the file as it might not exist in tests.
		if (file_exists($ls_filename)) {
			require_once $ls_filename;
		}
		$this->getTableLocator()->clear();

		$ls_emptyFile = $ls_path . 'Table' . DS . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $ao_io);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function getEmptyMethod (string $as_fieldName, array $aa_metaData, string $as_prefix = 'allow'): string {
		if ($aa_metaData['type'] == 'json') {
			return $as_prefix . 'EmptyArray';
		}


		return parent::getEmptyMethod($as_fieldName, $aa_metaData, $as_prefix);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function fieldValidation(TableSchemaInterface $ao_schema, string $as_fieldName, array $aa_metaData, array $aa_primaryKey): array {
		$la_validations = parent::fieldValidation($ao_schema, $as_fieldName, $aa_metaData, $aa_primaryKey);

		if ($aa_metaData['type'] === 'json') {
			$la_validations = [
				'isArray' => [
					'rule' => 'isArray',
					'args' => [],
				]
			] + $la_validations;
		}

		return $la_validations;
	}


	/**
	 * @param \Cake\ORM\Table $ao_model
	 *
	 * @return array
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getBehaviors(Table $ao_model): array {
		return [];
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getPath (Arguments $ao_args): string {
		$ls_path = APP . $this->pathFragment;
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . 'src/' . $this->pathFragment;
		}
		elseif ($ao_args->getOption('namespace')) {
			$ls_namespace = Inflector::dasherize($ao_args->getOption('namespace'));
			$ls_path = ROOT . DS . $ls_namespace . DS . $this->pathFragment;
		}

		$ls_prefix = $this->getPrefix($ao_args);
		if ($ls_prefix) {
			$ls_path .= $ls_prefix . DIRECTORY_SEPARATOR;
		}

		return str_replace('/', DIRECTORY_SEPARATOR, $ls_path);
	}


	/**
	 * @inheritDoc
	 *
	 * @param \Cake\Console\ConsoleOptionParser $ao_parser
	 *
	 * @return \Cake\Console\ConsoleOptionParser
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