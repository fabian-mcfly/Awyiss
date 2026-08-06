<?php declare(strict_types=1);


namespace Awyiss\Queue\Task\Attributes;


use Awyiss\Core\App;
use Awyiss\Model\Table\AttributesTable;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use Migrations\Db\Adapter\AdapterInterface;
use Queue\Model\QueueException;
use Queue\Queue\Task;
use ReflectionClass;


/**
 * This task handles changes to the attributes-table.
 *
 * - For new attribute-entities, it will bake an `add`-migration,
 * - For existing attribute-entities, it will bake an `alter`-migration.
 *
 * The target table is always 'attributes_<SCOPE>', e.g. 'attributes_contents' or 'attributes_pages'
 * If the target table does not exist, this task will bake a `create`-migration first.
 *
 * If an attribute-entity is moved to a different scope, it will check the table of the old scope.
 * If there are no other attributes left, it will bake a `drop`-migration.
 */
class UpsertTask extends Task {
	/**
	 * @inheritDoc
	 */
	public ?int $timeout = 5;
	/**
	 * @inheritDoc
	 */
	public ?int $retries = 3;


	/**
	 * @inheritDoc
	 */
	public function run(array $data, int $jobId): void {
		//$diff holds the entity's old values that differ from the new ones
		$diff = Hash::diff($data['old'], $data['new']);

		$column = $data['new']['identifier'];
		[$type, $length] = $this->getTypeAndLength($data['new']['type'] ?? null);

		//Type needs to be in the format of ':string?[50]', where ? marks the field as nullable and [50] sets the length.
		$column .= ':' . $type;
		if (empty($data['new']['required'])) {
			$column .= '?';
		}
		if (!empty($length)) {
			$column .= '[' . $length . ']';
		}
		if (isset($data['new']['defaultValue']) && $data['new']['defaultValue'] !== '') {
			$column .= '(' . $data['new']['defaultValue'] . ')';
		}
		if (!empty($data['new']['hasIndex'])) {
			$column .= ':index';
		}


		//Force migrations for attributes to be stored in the custom directory, to not mess with the Awyiss migrations.
		$migrationsPath = ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';

		$attributesTableName = 'attributes_' . Inflector::underscore($data['new']['scope']);
		$bakeOldModel = false;
		$commands = [];
		$foreignKey = Inflector::variable(Inflector::singularize($data['new']['scope']));

		$scopeIsPageRole = false;
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($foreignKey !== 'page' && $pageRoleEnum::tryFromName($foreignKey)) {
			$foreignKey = 'page';
			$scopeIsPageRole = true;
		}

		$foreignKey .= 'Id';

		$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		$tableExists = in_array($attributesTableName, $tables);

		if (isset($diff['deleted'])) {
			// For now, the sheer existence of the deleted key is enough since there's no undelete-method
			if (!$tableExists) {
				// If the table does not exist, there's nothing to do.
				return;
			}

			$bakeOldModel = $this->buildAlterOldTableCommands($commands, $data, $migrationsPath);
		}
		else {
			// The scope of the attribute has changed.
			$changedScope = isset($diff['scope']);
			if ($changedScope) {
				// We not only need to add the attribute to the new table, but also remove it from the old one.
				$bakeOldModel = $this->buildAlterOldTableCommands($commands, $data, $migrationsPath);
			}

			// The target attributes-table does not exist
			if (!$tableExists) {
				// Bake a `create`-migration that also adds the parent id-column and the column for the attribute-entity
				$commands[] = 'bin' . DS . 'cake bake migration create_' . $attributesTableName . ' ' . $foreignKey . ':integer[11]:index ' . $column . $migrationsPath;
			}
			else {
				$this->buildAlterTableCommands($commands, $data, $diff, $changedScope, $column, $migrationsPath);
			}
		}

		// If the last command is "sleep 1", remove it.
		if (end($commands) === 'sleep 1') {
			array_pop($commands);
		}

		$this->createJob($commands, $data, $scopeIsPageRole, $bakeOldModel);
	}


	/**
	 * Splits strings into valid phinx column types and length
	 *   "varchar(255)" => ['string', '255']
	 *   "int(10,4)" => ['integer', '10,4']
	 *   "tinyint" => ['tinyint', null]
	 *
	 * If no valid type is found, 'string' is returned
	 *
	 * @param string|null $type
	 * @return array
	 */
	protected function getTypeAndLength(?string $type): array {
		$type = $type ?: 'varchar(255)';

		if (!preg_match(AttributesTable::TYPE_PATTERN, $type, $typeMatches, PREG_UNMATCHED_AS_NULL)) {
			return ['string', '255'];
		}

		$reflector = new ReflectionClass(AdapterInterface::class);
		$collection = new Collection($reflector->getConstants());

		$validTypes = $collection->filter(function ($value, $constant) {
			return str_starts_with($constant, 'TYPE_');
		})->toArray();

		if (empty($typeMatches[1]) || !in_array($typeMatches[1], $validTypes)) {
			$typeMatches[1] = match ($typeMatches[1]) {
				'int' => 'integer',
				'tinyint' => 'tinyinteger',
				'mediumtext' => 'mediumtext',
				'longtext' => 'longtext',
				default => 'string',
			};
		}


		return [$typeMatches[1], $typeMatches[2] ?: null];
	}


	/**
	 * @param array $commands
	 * @param array $data
	 * @param string $migrationsPath
	 * @return bool
	 * @noinspection DuplicatedCode
	 */
	protected function buildAlterOldTableCommands(array &$commands, array $data, string $migrationsPath): bool {
		$oldAttributesTableName = 'attributes_' . Inflector::underscore($data['old']['scope']);

		$schema = ConnectionManager::get('default')->getSchemaCollection()->describe($oldAttributesTableName);

		$oldTableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($oldAttributesTableName) . 'Table.php';
		$oldEntityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($oldAttributesTableName) . '.php';

		$bakeOldModel = true;

		/*
		 * If there are three or fewer columns in the table, the old attributes-table is no longer required.
		 *
		 * Why 3, you ask?
		 * One is the attribute that's getting changed and the other two are the `id`- and the `(parent)Id`-column.
		 */
		if (count($schema->columns()) <= 3) {
			//Bake a `drop`-migration
			$commands[] = 'bin' . DS . 'cake bake migration drop_' . $oldAttributesTableName . $migrationsPath;

			//Remove both the table and the entity files from the custom directory.
			if (file_exists($oldTableFile)) {
				$commands[] = 'unlink ' . $oldTableFile;
			}
			if (file_exists($oldEntityFile)) {
				$commands[] = 'unlink ' . $oldEntityFile;
			}

			$bakeOldModel = false;
		}
		else {
			if (file_exists($oldTableFile)) {
				if (!is_writeable($oldTableFile)) {
					throw new QueueException(
						sprintf(
							'Cannot bake migration file `%s` in `%s`. Make sure the file is writeable for the cronjob user.',
							$oldTableFile,
							self::class
						)
					);
				}
			}

			if (file_exists($oldEntityFile)) {
				if (!is_writeable($oldEntityFile)) {
					throw new QueueException(
						sprintf(
							'Cannot bake migration file `%s` in `%s`. Make sure the file is writeable for the cronjob user.',
							$oldEntityFile,
							self::class
						)
					);
				}
			}

			//Bake a `remove`-migration
			$commands[] = 'bin' . DS . 'cake bake migration remove_' . ($data['old']['identifier'] ?? $data['new']['identifier']) . '_from_' . $oldAttributesTableName . ' ' . $data['old']['identifier'] . $migrationsPath;
		}

		/*
		 * Sleep for one second. Otherwise, the migration that removes the column from the old table
		 * has the same "version" as the migration that adds the column to the new table.
		*/
		$commands[] = 'sleep 1';


		return $bakeOldModel;
	}


	/**
	 * @param array $commands
	 * @param array $data
	 * @param bool $scopeIsPageRole
	 * @param bool $bakeOldModel
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function createJob(array $commands, array $data, bool $scopeIsPageRole, bool $bakeOldModel): void {
		if (empty($commands)) {
			return;
		}

		$attributesTableName = 'attributes_' . Inflector::underscore($data['new']['scope']);

		//Migrate all the newly baked migrations
		$commands[] = 'bin' . DS . 'cake migrations migrate --source ../../' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock';
		//Clear the database schema
		$commands[] = 'bin' . DS . 'cake schema_cache clear';

		//And bake the model
		$forPageRole = $scopeIsPageRole ? ' --for-pagerole ' . $data['new']['scope'] : null;

		if (empty($data['new']['deleted'])) {
			$commands[] = 'bin' . DS . 'cake bake model ' . $attributesTableName . ' --namespace ' . CUSTOM_NAMESPACE . ' --no-fixture --no-test --update --force' . $forPageRole;
		}

		if ($bakeOldModel) {
			//If the old table was changed but still exists, bake the model for the old table as well.
			$forPageRole = null;

			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');

			if ($pageRoleEnum::tryFromName($data['old']['scope'])) {
				$forPageRole = ' --for-pagerole ' . $data['old']['scope'];
			}

			$oldAttributesTable = 'attributes_' . Inflector::underscore($data['old']['scope']);

			$commands[] = 'bin' . DS . 'cake bake model ' . $oldAttributesTable . ' --namespace ' . CUSTOM_NAMESPACE . ' --no-fixture --no-test --update --force' . $forPageRole;
		}

		$commands[] = 'bin' . DS . 'cake bake seed --data Attributes --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$this->QueuedJobs->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Attributes::tableChanges',
		]);
	}


	/**
	 * @param array &$commands
	 * @param array $data
	 * @param array $diff
	 * @param bool $changedScope
	 * @param string $column
	 * @param string $migrationsPath
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function buildAlterTableCommands(array &$commands, array $data, array $diff, bool $changedScope, string $column, string $migrationsPath): void {
		$attributesTableName = 'attributes_' . Inflector::underscore($data['new']['scope']);

		$tableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($attributesTableName) . 'Table.php';
		$entityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($attributesTableName) . '.php';

		if (file_exists($tableFile)) {
			if (!is_writeable($tableFile)) {
				throw new QueueException(
					sprintf(
						'Cannot bake migration file `%s` in `%s`. Make sure the file is writeable for the cronjob user.',
						$tableFile,
						self::class
					)
				);
			}
		}

		if (file_exists($entityFile)) {
			if (!is_writeable($entityFile)) {
				throw new QueueException(
					sprintf(
						'Cannot bake migration file `%s` in `%s`. Make sure the file is writeable for the cronjob user.',
						$entityFile,
						self::class
					)
				);
			}
		}

		//Column is renamed but only if the scope is still the same.
		if (!$changedScope && isset($diff['identifier'])) {
			//The scope has not changed, but the identifier has: alter the column
			$commands[] = 'bin' . DS . 'cake bake migration alter_' . $data['old']['identifier'] . '_on_' . $attributesTableName . ' ' . $column . $migrationsPath;
		}
		else {
			$schema = ConnectionManager::get('default')->getSchemaCollection()->describe($attributesTableName);
			$columnExists = $schema->hasColumn($data['new']['identifier']);

			if (!$columnExists) {
				//The column does not exist in the target table? Add it.
				$commands[] = 'bin' . DS . 'cake bake migration add_' . $data['new']['identifier'] . '_to_' . $attributesTableName . ' ' . $column . $migrationsPath;
			}
			else {
				//The column does exist in the target table? Alter it.
				$commands[] = 'bin' . DS . 'cake bake migration alter_' . $data['new']['identifier'] . '_on_' . $attributesTableName . ' ' . $column . $migrationsPath;
			}
		}
	}
}
