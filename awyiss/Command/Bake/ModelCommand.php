<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Bake\CodeGen\FileBuilder;
use Bake\Command\ModelCommand as BaseModelCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Table;


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
	 * @param Table $model Model name or object
	 * @param array $data An array to use to generate the Table
	 * @param Arguments $args CLI Arguments
	 * @param ConsoleIo $io CLI Input
	 * @return void
	 */
	public function bakeEntity(Table $model, array $data, Arguments $args, ConsoleIo $io): void {
		if ($args->getOption('no-entity')) {
			return;
		}

		$ls_name = $this->_entityName($model->getAlias());
		$io->out("\n" . sprintf('Baking entity class for %s...', $ls_name)/*, 1, ConsoleIo::NORMAL*/);

		$ls_namespace = Configure::read('App.namespace');
		$ls_pluginPath = '';
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
			$ls_pluginPath = $this->plugin . '.';
		}
		elseif ($args->getOption('namespace')) {
			$ls_namespace = Inflector::underscore($args->getOption('namespace'));
			$ls_namespace = Inflector::camelize($ls_namespace);
		}

		$ls_path = $this->getPath($args);
		$ls_filePath = $ls_path . 'Entity' . DS . $ls_name . '.php';

		$lo_parsedFile = null;
		if ($args->getOption('update')) {
			$lo_parsedFile = $this->parseFile($ls_filePath);
		}

		$la_data = $data + [
			'fieldMap' => [],
			'name' => $ls_name,
			'namespace' => $ls_namespace,
			'plugin' => $this->plugin,
			'pluginPath' => $ls_pluginPath,
			'primaryKey' => [],
			'fileBuilder' => new FileBuilder($io, $ls_namespace . '\Model\Entity', $lo_parsedFile),
		];

		$la_data['fields'] = array_filter($la_data['fields'], function (string $field): bool {
			return !in_array($field, [
				'deleted',
				'created_by',
				'created_on',
				'changed_by',
				'changed_on',
				'deleted_by',
				'deleted_on',
				'created_by_user',
				'changed_by_user',
				'deleted_by_user',
				'media_assignments',
				'media_element_assignments',
			]);
		});

		foreach ($la_data['fields'] as &$ls_field) {
			$ls_variable = Inflector::variable($ls_field);

			if ($ls_variable !== $ls_field) {
				$la_data['fieldMap'][ $ls_field ] = $ls_variable;
			}

			$ls_field = $ls_variable;
		}
		unset($la_data['fieldMap']['media_element_assignments'], $ls_field);

		foreach ($la_data['hidden'] as &$ls_field) {
			$ls_field = Inflector::variable($ls_field);
		}
		unset($ls_field);

		$ls_template = 'Model/entity';
		if ($args->getOption('is-pagerole')) {
			$ls_template = 'Model/entity_for_pagerole';
		}

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate($ls_template);
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		$this->writeFile($io, $ls_filePath, $ls_contents, $this->force);

		$ls_emptyFile = $ls_path . 'Entity' . DS . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $io);
	}


	/**
	 * Re-implemented 1:1 but honors the `namespace`-option and leaves out the plugin name from the template
	 *
	 * @inheritDoc
	 * @param Table $model Model name or object
	 * @param array $data An array to use to generate the Table
	 * @param Arguments $args CLI Arguments
	 * @param ConsoleIo $io CLI Arguments
	 * @return void
	 */
	public function bakeTable(Table $model, array $data, Arguments $args, ConsoleIo $io): void {
		if ($args->getOption('no-table')) {
			return;
		}

		$ls_name = $model->getAlias();
		$io->out("\n" . sprintf('Baking table class for %s...', $ls_name)/*, 1, ConsoleIo::NORMAL*/);

		$ls_namespace = Configure::read('App.namespace');
		$ls_pluginPath = '';
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
		}
		elseif ($args->getOption('namespace')) {
			$ls_namespace = Inflector::underscore($args->getOption('namespace'));
			$ls_namespace = Inflector::camelize($ls_namespace);
		}

		$ls_path = $this->getPath($args);
		$ls_filePath = $ls_path . 'Table' . DS . $ls_name . 'Table.php';

		$lo_parsedFile = null;
		if ($args->getOption('update')) {
			$lo_parsedFile = $this->parseFile($ls_filePath);
		}

		if ($lo_parsedFile) {
			unset($lo_parsedFile->class->constants['ATTRIBUTABLE'], $lo_parsedFile->class->constants['TABLE']);
		}

		$ls_entity = $this->_entityName($model->getAlias());
		if ($args->getOption('is-pagerole')) {
			$ls_entity = 'Page';
		}

		$la_data = $data + [
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
				'fileBuilder' => new FileBuilder($io, $ls_namespace . '\Model\Table', $lo_parsedFile),
			];

		$ls_template = 'Model/table';
		if ($args->getOption('is-datatable')) {
			$ls_template = 'Model/table_for_datatable';
		}
		if ($args->getOption('is-pagerole')) {
			$ls_template = 'Model/table_for_pagerole';
		}

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate($ls_template);
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		$this->writeFile($io, $ls_filePath, $ls_contents, $this->force);

		// Work around composer caching that classes/files do not exist.
		// Check for the file as it might not exist in tests.
		if (file_exists($ls_filePath)) {
			require_once $ls_filePath;
		}
		$this->getTableLocator()->clear();

		$ls_emptyFile = $ls_path . 'Table' . DS . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $io);
	}


	/**
	 * @inheritDoc
	 */
	public function getAssociations(Table $table, Arguments $args, ConsoleIo $io): array {
		$la_allAssociations = parent::getAssociations($table, $args, $io);

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		if (
			$args->getOption('for-pagerole') &&
			$ls_pageRoleEnum::tryFromName($args->getOption('for-pagerole')) &&
			!empty($la_allAssociations['belongsTo'])
		) {
			foreach ($la_allAssociations['belongsTo'] as &$la_association) {
				if ($la_association['alias'] === 'Pages') {
					$la_association['alias'] = Inflector::camelize($args->getOption('for-pagerole'));
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
	 */
	public function getValidation(Table $model, array $associations, Arguments $args): array|false {
		if ($args->getOption('no-validation')) {
			return [];
		}

		$lo_schema = $model->getSchema();
		$la_fields = $lo_schema->columns();
		if (!$la_fields) {
			return false;
		}

		$la_validate = [];
		$ls_primaryKey = $lo_schema->getPrimaryKey();
		$lx_foreignKeys = [];

		if (isset($associations['belongsTo'])) {
			foreach ($associations['belongsTo'] as $la_association) {
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
	 */
	public function fieldValidation(TableSchemaInterface $schema, string $fieldName, array $metaData, array $primaryKey): array {
		$la_validations = parent::fieldValidation($schema, $fieldName, $metaData, $primaryKey);

		if ($metaData['type'] === 'json') {
			$la_validations = [
				'isArray' => [
					'rule' => 'array',
					'args' => [],
				],
			] + $la_validations;
		}


		return $la_validations;
	}


	/**
	 * @inheritDoc
	 */
	public function getRules(Table $model, array $associations, Arguments $args): array {
		$la_rules = parent::getRules($model, $associations, $args);

		if (str_starts_with($model->getTable(), 'attributes_') && isset($la_rules['page_id'])) {
			$la_rules['page_id']['options']['skipPageRoleCheck'] = true;
		}


		return $la_rules;
	}


	/**
	 * We do not want to automatically add behaviors.
	 *
	 * @param Table $model
	 * @return array
	 */
	public function getBehaviors(Table $model): array {
		return [];
	}


	/**
	 * Adds the `namespace`-option.
	 *
	 * @inheritDoc
	 * @param ConsoleOptionParser $parser
	 * @return ConsoleOptionParser
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
		])->addOption('is-datatable', [
			'boolean' => true,
			'help' => 'Does the model reflect a datatable? Will extend GenericDatatablesTable.',
		])->addOption('is-pagerole', [
			'boolean' => true,
			'help' => 'Does the model reflect a pagerole? Will extend PagesTable and use db table `pages`.',
		])->addOption('for-pagerole', [
			'help' => 'Should the table be associated with a pagerole? Will remove a Page association if present.',
		]);


		return $lo_parser;
	}


	/**
	 * @param array $associations
	 * @return void
	 */
	protected function camelbackAssociationKeys(array &$associations): void {
		foreach ($associations as &$la_association) {
			if (!empty($la_association['foreignKey'])) {
				if (is_string($la_association['foreignKey'])) {
					$la_association['foreignKey'] = Inflector::variable($la_association['foreignKey']);
				}
				elseif (is_array($la_association['foreignKey'])) {
					array_walk($la_association['foreignKey'], function (&$field): void {
						/** @noinspection PhpVariableNamingConventionInspection */
						$field = Inflector::variable($field);
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
	 */
	protected function getEmptyMethod(string $fieldName, array $metaData, string $prefix = 'allow'): string {
		if ($metaData['type'] == 'json') {
			return $prefix . 'EmptyArray';
		}


		return parent::getEmptyMethod($fieldName, $metaData, $prefix);
	}
}
