<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Awyiss\Core\App;
use Bake\CodeGen\FileBuilder;
use Bake\Command\ModelCommand as BaseModelCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;


/**
 * Command for generating model files.
 */
class ModelCommand extends BaseModelCommand {
	/*
	 * Use UtilTrait so that every call of `$this->getPath()` will use the one provided by this trait,
	 * honoring the `namespace`-option
	 */
	use UtilTrait;


	/**
	 * Re-implemented 1:1 but honors the `namespace`-option and leaves out the plugin name from the template
	 *
	 * @inheritDoc
	 * @param Table $ao_model Model name or object
	 * @param array $aa_data An array to use to generate the Table
	 * @param Arguments $ao_args CLI Arguments
	 * @param ConsoleIo $ao_io CLI ao_io
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bakeEntity(Table $ao_model, array $aa_data, Arguments $ao_args, ConsoleIo $ao_io): void {
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
		$ls_filePath = $ls_path . 'Entity' . DS . $ls_name . '.php';

		$lo_parsedFile = null;
		if ($ao_args->getOption('update')) {
			$lo_parsedFile = $this->parseFile($ls_filePath);
		}

		$la_data = $aa_data + [
				'fieldMap' => [],
				'name' => $ls_name,
				'namespace' => $ls_namespace,
				'plugin' => $this->plugin,
				'pluginPath' => $ls_pluginPath,
				'primaryKey' => [],
				'fileBuilder' => new FileBuilder($ao_io, $ls_namespace . '\Model\Entity', $lo_parsedFile),
			];

		foreach ($la_data['fields'] as &$ls_field) {
			$ls_variable = Inflector::variable($ls_field);

			if ($ls_variable !== $ls_field) {
				$la_data['fieldMap'][ $ls_field ] = $ls_variable;
			}

			$ls_field = $ls_variable;
		}
		unset($ls_field);

		foreach ($la_data['hidden'] as &$ls_field) {
			$ls_field = Inflector::variable($ls_field);
		}
		unset($ls_field);

		$ls_template = 'Model/entity';
		if ($ao_args->getOption('is-pagerole')) {
			$ls_template = 'Model/entity_for_pagerole';
		}

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate($ls_template);
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		$this->writeFile($ao_io, $ls_filePath, $ls_contents, $this->force);

		$ls_emptyFile = $ls_path . 'Entity' . DS . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $ao_io);
	}


	/**
	 * Re-implemented 1:1 but honors the `namespace`-option and leaves out the plugin name from the template
	 *
	 * @inheritDoc
	 * @param Table $ao_model Model name or object
	 * @param array $aa_data An array to use to generate the Table
	 * @param Arguments $ao_args CLI Arguments
	 * @param ConsoleIo $ao_io CLI Arguments
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bakeTable(Table $ao_model, array $aa_data, Arguments $ao_args, ConsoleIo $ao_io): void {
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
		$ls_filePath = $ls_path . 'Table' . DS . $ls_name . 'Table.php';

		$lo_parsedFile = null;
		if ($ao_args->getOption('update')) {
			$lo_parsedFile = $this->parseFile($ls_filePath);
		}

		if ($lo_parsedFile) {
			unset($lo_parsedFile->class->constants['ATTRIBUTABLE'], $lo_parsedFile->class->constants['TABLE']);
		}

		$ls_entity = $this->_entityName($ao_model->getAlias());
		if ($ao_args->getOption('is-pagerole')) {
			$ls_entity = 'Page';
		}

		$la_data = $aa_data + [
				'plugin' => $this->plugin,
				'pluginPath' => $ls_pluginPath,
				'namespace' => $ls_namespace,
				'name' => $ls_name,
				'entity' => $ls_entity,
				'associations' => [],
				'primaryKey' => 'id',
				'displayField' => null,
				'table' => null,
				'validation' => [],
				'rulesChecker' => [],
				'behaviors' => [],
				'connection' => $this->connection,
				'fileBuilder' => new FileBuilder($ao_io, $ls_namespace . '\Model\Table', $lo_parsedFile),
			];

		$ls_template = 'Model/table';
		if ($ao_args->getOption('is-pagerole')) {
			$ls_template = 'Model/table_for_pagerole';
		}

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate($ls_template);
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		$this->writeFile($ao_io, $ls_filePath, $ls_contents, $this->force);

		// Work around composer caching that classes/files do not exist.
		// Check for the file as it might not exist in tests.
		if (file_exists($ls_filePath)) {
			require_once $ls_filePath;
		}
		$this->getTableLocator()->clear();

		$ls_emptyFile = $ls_path . 'Table' . DS . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $ao_io);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getAssociations(Table $ao_table, Arguments $ao_args, ConsoleIo $ao_io): array {
		$la_allAssociations = parent::getAssociations($ao_table, $ao_args, $ao_io);

		/** @var class-string<\Awyiss\Database\Type\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		if (
			$ao_args->getOption('for-pagerole') &&
			$ls_pageRoleEnum::tryFromName($ao_args->getOption('for-pagerole')) &&
			!empty($la_allAssociations['belongsTo'])
		) {
			foreach ($la_allAssociations['belongsTo'] as &$la_association) {
				if ($la_association['alias'] === 'Pages') {
					$la_association['alias'] = Inflector::camelize($ao_args->getOption('for-pagerole'));
				}
			}
			unset($la_associations);
		}

		foreach ($la_allAssociations as &$la_associations) {
			$this->camelbackAssociationKeys($la_associations);
		}
		unset($la_associations);


		return $la_allAssociations;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getValidation(Table $ao_model, array $aa_associations, Arguments $ao_args): array|false {
		if ($ao_args->getOption('no-validation')) {
			return [];
		}

		$lo_schema = $ao_model->getSchema();
		$la_fields = $lo_schema->columns();
		if (!$la_fields) {
			return false;
		}

		$la_validate = [];
		$ls_primaryKey = $lo_schema->getPrimaryKey();
		$lx_foreignKeys = [];

		if (isset($aa_associations['belongsTo'])) {
			foreach ($aa_associations['belongsTo'] as $la_association) {
				$lx_foreignKeys[] = $la_association['foreignKey'];
			}
		}

		foreach ($la_fields as $ls_fieldName) {
			// Skip primary key
			if (in_array($ls_fieldName, $ls_primaryKey, true)) {
				continue;
			}
			$la_field = $lo_schema->getColumn($ls_fieldName);
			$la_field['isForeignKey'] = in_array(Inflector::variable($ls_fieldName), $lx_foreignKeys, true);
			$la_validation = $this->fieldValidation($lo_schema, $ls_fieldName, $la_field, $ls_primaryKey);
			if ($la_validation) {
				$la_validate[ Inflector::variable($ls_fieldName) ] = $la_validation;
			}
		}


		return $la_validate;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extends the parent-method with a check for column type 'json'.
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
				],
			] + $la_validations;
		}


		return $la_validations;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getRules(Table $ao_model, array $aa_associations, Arguments $aa_args): array {
		$la_rules = parent::getRules($ao_model, $aa_associations, $aa_args);

		if (str_starts_with($ao_model->getTable(), 'attributes_') && isset($la_rules['page_id'])) {
			$la_rules['page_id']['options']['skipPageRoleCheck'] = true;
		}


		return $la_rules;
	}


	/**
	 * We do not want to automatically add behaviors.
	 *
	 * @param Table $ao_model
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getBehaviors(Table $ao_model): array {
		return [];
	}


	/**
	 * Adds the `namespace`-option.
	 *
	 * @inheritDoc
	 * @param ConsoleOptionParser $ao_parser
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('namespace', [
			'help' => 'The namespace for the model. Should be either "Awyiss" or <CUSTOM_NAMESPACE>',
		])->addOption('is-pagerole', [
			'boolean' => true,
			'help' => 'Does the model reflect a pagerole? Will extend PagesTable and use db table `pages`.',
		])->addOption('for-pagerole', [
			'help' => 'Should the table be associated with a pagerole? Will remove a Page association if present.',
		]);


		return $lo_parser;
	}


	/**
	 * @param array $aa_associations
	 * @return void
	 */
	protected function camelbackAssociationKeys(array &$aa_associations): void {
		foreach ($aa_associations as &$la_association) {
			if (!empty($la_association['foreignKey'])) {
				if (is_string($la_association['foreignKey'])) {
					$la_association['foreignKey'] = Inflector::variable($la_association['foreignKey']);
				}
				elseif (is_array($la_association['foreignKey'])) {
					array_walk($la_association['foreignKey'], function (&$as_field): void {
						$as_field = Inflector::variable($as_field);
					});
				}
			}
		}
		unset($la_association);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extends the parent-method with a check for column type 'json'.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function getEmptyMethod(string $as_fieldName, array $aa_metaData, string $as_prefix = 'allow'): string {
		if ($aa_metaData['type'] == 'json') {
			return $as_prefix . 'EmptyArray';
		}


		return parent::getEmptyMethod($as_fieldName, $aa_metaData, $as_prefix);
	}
}
