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

		$name = $this->_entityName($model->getAlias());
		$io->out("\n" . sprintf('Baking entity class for %s...', $name)/*, 1, ConsoleIo::NORMAL*/);

		$namespace = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$namespace = $this->_pluginNamespace($this->plugin);
			$pluginPath = $this->plugin . '.';
		}
		elseif ($args->getOption('namespace')) {
			$namespace = Inflector::underscore($args->getOption('namespace'));
			$namespace = Inflector::camelize($namespace);
		}

		$path = $this->getPath($args);
		$filePath = $path . 'Entity' . DS . $name . '.php';

		$parsedFile = null;
		if ($args->getOption('update')) {
			$parsedFile = $this->parseFile($filePath);
		}

		$data += [
			'fieldMap' => [],
			'name' => $name,
			'namespace' => $namespace,
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'primaryKey' => [],
			'fileBuilder' => new FileBuilder($io, $namespace . '\Model\Entity', $parsedFile),
		];

		$data['fields'] = array_filter($data['fields'], function (string $field): bool {
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

		foreach ($data['fields'] as &$field) {
			$variable = Inflector::variable($field);

			if ($variable !== $field) {
				$data['fieldMap'][ $field ] = $variable;
			}

			$field = $variable;
		}
		unset($data['fieldMap']['media_element_assignments'], $field);

		foreach ($data['hidden'] as &$field) {
			$field = Inflector::variable($field);
		}
		unset($field);

		$template = 'Model/entity';
		if ($args->getOption('is-pagerole')) {
			$template = 'Model/entity_is_pagerole';
		}

		$contents = $this->createTemplateRenderer()->set($data)->generate($template);
		$contents = str_replace('    ', "\t", $contents);

		$this->writeFile($io, $filePath, $contents, $this->force);

		$emptyFile = $path . 'Entity' . DS . '.gitkeep';
		$this->deleteEmptyFile($emptyFile, $io);
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

		$name = $model->getAlias();
		$io->out("\n" . sprintf('Baking table class for %s...', $name)/*, 1, ConsoleIo::NORMAL*/);

		$namespace = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$namespace = $this->_pluginNamespace($this->plugin);
		}
		elseif ($args->getOption('namespace')) {
			$namespace = Inflector::underscore($args->getOption('namespace'));
			$namespace = Inflector::camelize($namespace);
		}

		$path = $this->getPath($args);
		$filePath = $path . 'Table' . DS . $name . 'Table.php';

		$parsedFile = null;
		if ($args->getOption('update')) {
			$parsedFile = $this->parseFile($filePath);
		}

		if ($parsedFile) {
			unset($parsedFile->class->constants['ATTRIBUTABLE'], $parsedFile->class->constants['TABLE']);
		}

		$entity = $this->_entityName($model->getAlias());
		if ($args->getOption('is-pagerole')) {
			$entity = 'Page';
		}

		$data += [
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'namespace' => $namespace,
			'name' => $name,
			'entity' => $entity,
			'associations' => [],
			'primaryKey' => 'id',
			'displayField' => null,
			'table' => null,
			'validation' => [],
			'rulesChecker' => [],
			'behaviors' => [],
			'connection' => $this->connection,
			'fileBuilder' => new FileBuilder($io, $namespace . '\Model\Table', $parsedFile),
		];

		$template = 'Model/table';
		if ($args->getOption('for-pagerole')) {
			$template = 'Model/table_for_pagerole';
		}
		if ($args->getOption('is-datatable')) {
			$template = 'Model/table_is_datatable';
		}
		if ($args->getOption('is-pagerole')) {
			$template = 'Model/table_is_pagerole';
		}

		$contents = $this->createTemplateRenderer()->set($data)->generate($template);
		$contents = str_replace('    ', "\t", $contents);

		$this->writeFile($io, $filePath, $contents, $this->force);

		// Work around composer caching that classes/files do not exist.
		// Check for the file as it might not exist in tests.
		if (file_exists($filePath)) {
			require_once $filePath;
		}
		$this->getTableLocator()->clear();

		$emptyFile = $path . 'Table' . DS . '.gitkeep';
		$this->deleteEmptyFile($emptyFile, $io);
	}


	/**
	 * @inheritDoc
	 */
	public function getAssociations(Table $table, Arguments $args, ConsoleIo $io): array {
		$allAssociations = parent::getAssociations($table, $args, $io);

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');

		if (
			$args->getOption('for-pagerole') &&
			$pageRoleEnum::tryFromName($args->getOption('for-pagerole')) &&
			!empty($allAssociations['belongsTo'])
		) {
			foreach ($allAssociations['belongsTo'] as &$association) {
				if ($association['alias'] === 'Pages') {
					$association['alias'] = Inflector::camelize($args->getOption('for-pagerole'));
				}
			}
			unset($associations);
		}

		foreach ($allAssociations as &$associations) {
			$this->camelbackAssociationKeys($associations);
		}
		unset($associations);


		return $allAssociations;
	}


	/**
	 * @inheritDoc
	 */
	public function getValidation(Table $model, array $associations, Arguments $args): array|false {
		if ($args->getOption('no-validation')) {
			return [];
		}

		$schema = $model->getSchema();
		$fields = $schema->columns();
		if (!$fields) {
			return false;
		}

		$validate = [];
		$primaryKey = $schema->getPrimaryKey();
		$foreignKeys = [];

		if (isset($associations['belongsTo'])) {
			foreach ($associations['belongsTo'] as $association) {
				$foreignKeys[] = $association['foreignKey'];
			}
		}

		foreach ($fields as $fieldName) {
			// Skip primary key
			if (in_array($fieldName, $primaryKey, true)) {
				continue;
			}
			$field = $schema->getColumn($fieldName);
			$field['isForeignKey'] = in_array(Inflector::variable($fieldName), $foreignKeys, true);
			$validation = $this->fieldValidation($schema, $fieldName, $field, $primaryKey);
			if ($validation) {
				$validate[ Inflector::variable($fieldName) ] = $validation;
			}
		}


		return $validate;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extends the parent-method with a check for column type 'json'.
	 */
	public function fieldValidation(TableSchemaInterface $schema, string $fieldName, array $metaData, array $primaryKey): array {
		$validations = parent::fieldValidation($schema, $fieldName, $metaData, $primaryKey);

		if ($metaData['type'] === 'json') {
			$validations = [
				'isArray' => [
					'rule' => 'array',
					'args' => [],
				],
			] + $validations;
		}


		return $validations;
	}


	/**
	 * @inheritDoc
	 */
	public function getRules(Table $model, array $associations, Arguments $args): array {
		$rules = parent::getRules($model, $associations, $args);

		if (str_starts_with($model->getTable(), 'attributes_') && isset($rules['page_id'])) {
			$rules['page_id']['options']['skipPageRoleCheck'] = true;
		}


		return $rules;
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
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('namespace', [
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


		return $parser;
	}


	/**
	 * @param array $associations
	 * @return void
	 */
	protected function camelbackAssociationKeys(array &$associations): void {
		foreach ($associations as &$association) {
			if (!empty($association['foreignKey'])) {
				if (is_string($association['foreignKey'])) {
					$association['foreignKey'] = Inflector::variable($association['foreignKey']);
				}
				elseif (is_array($association['foreignKey'])) {
					array_walk($association['foreignKey'], function (&$field): void {
						$field = Inflector::variable($field);
					});
				}
			}
		}
		unset($association);
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
