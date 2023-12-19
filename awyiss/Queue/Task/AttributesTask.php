<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Awyiss\Model\Table\AttributesTable;
use Cake\Collection\Collection;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Phinx\Db\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Queue\Console\Io;
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
class AttributesTask extends Task/* implements AddInterface*/ {
	/**
	 * @inheritDoc
	 */
	public ?int $timeout = 5;
	/**
	 * @inheritDoc
	 */
	public ?int $retries = 0;


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function run (array $aa_data, int $ai_jobId): void {
		//$la_diff holds the entity's old values that differ from the new ones
		$la_diff = Hash::diff($aa_data['old'], $aa_data['new']);

		$ls_column = $aa_data['new']['identifier'];
		[$ls_type, $lx_length] = $this->getTypeAndLength($aa_data['new']['type'] ?? NULL);

		//Type needs to be in the format of ':string?[50]', where ? marks the field as nullable and [50] sets the length
		$ls_column .= ':' . $ls_type;
		if (empty($aa_data['new']['required'])) {
			$ls_column .= '?';
		}
		if (!empty($lx_length)) {
			$ls_column .= '[' . $lx_length . ']';
		}
		if (array_key_exists('defaultValue', $aa_data['new'])) {
			$ls_column .= '(' . $aa_data['new']['defaultValue'] . ')';
		}
		if (!empty($aa_data['new']['hasIndex'])) {
			$ls_column .= ':index';
		}


		//Force migrations for attributes to be stored in the custom directory, to not mess with the Awyiss migrations.
		$ls_folder = ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';


		$lb_bakeOldModel = FALSE;
		$ls_oldAttributesTable = NULL;

		//The scope of the attribute has changed.
		if ($lb_changedScope = isset($la_diff['scope'])) {
			//We not only need to add the attribute to the new table but also remove it from the old one
			$ls_oldAttributesTable = 'attributes_' . $aa_data['old']['scope'];

			/** @var TableSchemaInterface $lo_schema */
			$lo_schema = ConnectionManager::get('default')->getSchemaCollection()->describe($ls_oldAttributesTable);

			$ls_oldTableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($ls_oldAttributesTable) . 'Table.php';
			$ls_oldEntityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($ls_oldAttributesTable) . '.php';

			/*
			 * If there are three or fewer columns in the table, the old attributes-table is no longer required.
			 *
			 * Why 3, you ask?
			 * One is the attribute that's getting changed and the other two are the `id`- and the `(parent)_id`-column.
			 */
			if (count($lo_schema->columns()) <= 3) {
				//Bake a `drop`-migration
				$la_commands[] = 'bin/cake bake migration drop_' . $ls_oldAttributesTable . $ls_folder;

				//Remove both the table and the entity files from the custom directory.
				if (file_exists($ls_oldTableFile)) {
					$la_commands[] = 'unlink ' . $ls_oldTableFile;
				}
				if (file_exists($ls_oldEntityFile)) {
					$la_commands[] = 'unlink ' . $ls_oldEntityFile;
				}
			}
			else {
				if (file_exists($ls_oldTableFile)) {
					if (posix_getuid() != fileowner($ls_oldTableFile)) {
						throw new QueueException(sprintf('Cannot migrate `%s` in `%s`. Make sure all files have the same owner the cronjob runs with.', $ls_oldTableFile, self::class));
					}
				}

				if (file_exists($ls_oldEntityFile)) {
					if (posix_getuid() != fileowner($ls_oldEntityFile)) {
						throw new QueueException(sprintf('Cannot migrate `%s` in `%s`. Make sure all files have the same owner the cronjob runs with.', $ls_oldEntityFile, self::class));
					}
				}

				//Bake a `remove`-migration
				$la_commands[] = 'bin/cake bake migration remove_' . $aa_data['new']['identifier'] . '_from_' . $ls_oldAttributesTable . ' ' . $aa_data['old']['identifier'] . $ls_folder;
				$lb_bakeOldModel = TRUE;
			}

			/*
			 * Sleep for one second. Otherwise, the migration that removes the column from the old table
			 * has the same "version" as the migration that adds the column to the new table.
			*/
			$la_commands[] = 'sleep 1';
		}


		$ls_attributesTable = 'attributes_' . $aa_data['new']['scope'];
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		$lb_tableExists = in_array($ls_attributesTable, $la_tables);

		$ls_foreignKey = Inflector::singularize($aa_data['new']['scope']);
		$lb_scopeIsPageRole = FALSE;
		if (defined('PAGEROLE_' . strtoupper($ls_foreignKey))) {
			if ($ls_foreignKey !== 'page') {
				$ls_foreignKey = 'page';
				$lb_scopeIsPageRole = TRUE;
			}
		}
		$ls_foreignKey .= '_id';


		//The target attributes-table does not exist
		if ( ! $lb_tableExists) {
			//Bake a `create`-migration that also adds the parent id-column and the column for the attribute-entity
			$la_commands[] = 'bin/cake bake migration create_' . $ls_attributesTable . ' ' . $ls_foreignKey . ':integer[11] ' . $ls_column . $ls_folder;
		}
		else {
			$ls_tableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($ls_attributesTable) . 'Table.php';
			$ls_entityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($ls_attributesTable) . '.php';

			if (file_exists($ls_tableFile)) {
				if (posix_getuid() != fileowner($ls_tableFile)) {
					throw new QueueException(
						sprintf(
							'Cannot migrate `%s` in `%s`. Make sure all files have the same owner the cronjob runs with.',
							$ls_tableFile,
							self::class
						)
					);
				}
			}

			if (file_exists($ls_entityFile)) {
				if (posix_getuid() != fileowner($ls_entityFile)) {
					/** @noinspection PhpUndefinedVariableInspection */
					throw new QueueException(
						sprintf(
							'Cannot migrate `%s` in `%s`. Make sure all files have the same owner the cronjob runs with.',
							$ls_oldEntityFile,
							self::class
						)
					);
				}
			}


			//Column is renamed but only if the scope is still the same.
			if (!$lb_changedScope && isset($la_diff['identifier'])) {
				//The scope has not changed, but the identifier has: alter the column
				$la_commands[] = 'bin/cake bake migration alter_' . $aa_data['old']['identifier']. '_on_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
			}
			else {
				/** @var TableSchemaInterface $lo_schema */
				$lo_schema = ConnectionManager::get('default')->getSchemaCollection()->describe($ls_attributesTable);
				$lb_columnExists = $lo_schema->hasColumn($aa_data['new']['identifier']);

				if (!$lb_columnExists) {
					//The column does not exist in the target table? Add it.
					$la_commands[] = 'bin/cake bake migration add_' . $aa_data['new']['identifier'] . '_to_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
				}
				else {
					//The column does exist in the target table? Alter it.
					$la_commands[] = 'bin/cake bake migration alter_' . $aa_data['new']['identifier'] . '_on_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
				}
			}
		}


		if (!empty($la_commands)) {
			//Migrate all the newly baked migrations
			$la_commands[] = 'bin/cake migrations migrate --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock';
			//Clear the database schema
			$la_commands[] = 'bin/cake schema_cache clear';

			//And bake the model
			$ls_forPageRole = $lb_scopeIsPageRole ? (' --for-pagerole ' . $aa_data['new']['scope']) : NULL;
			$la_commands[] = 'bin/cake bake model ' . $ls_attributesTable . ' --namespace ' . CUSTOM_DIR . ' --no-fixture --no-test --update --force' . $ls_forPageRole;

			if ($lb_bakeOldModel && $ls_oldAttributesTable) {
				//If the old table was changed but still exists, bake the model for the old table as well.
				$ls_forPageRole = NULL;
				if (defined('PAGEROLE_' . strtoupper(Inflector::singularize($aa_data['old']['scope'])))) {
					$ls_forPageRole = ' --for-pagerole ' . $aa_data['old']['scope'];
				}
				$la_commands[] = 'bin/cake bake model ' . $ls_oldAttributesTable . ' --namespace ' . CUSTOM_DIR . ' --no-fixture --no-test --update --force' . $ls_forPageRole;
			}

			$la_commands[] = 'bin/cake bake seed --data Attributes --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --truncate';

			//Queue the job.
			$this->QueuedJobs->createJob('Queue.Execute', [
				'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
				'escape' => FALSE,
				'log' => TRUE,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'attributes::table_changes',
			]);
		}
	}


	/**
	 * Splits strings into valid phinx column types and length
	 *        "varchar(255)" => ['string', 255]
	 *        "int(10,4)" => ['integer', '10,4']
	 *        "tinyint" => ['tinyint', NULL]
	 *
	 * If no valid type is found, 'string' is returned
	 *
	 * @param NULL|string $as_type
	 *
	 * @return array
	 */
	public function getTypeAndLength (?string $as_type): array {
		$ls_type = $as_type ?: 'varchar(255)';

		if ( ! preg_match(AttributesTable::TYPE_PATTERN, $ls_type, $la_typeMatches, PREG_UNMATCHED_AS_NULL)) {
			return ['string', 255];
		}

		$lo_reflector = new ReflectionClass(AdapterInterface::class);
		$lo_collection = new Collection($lo_reflector->getConstants());

		$la_validTypes = $lo_collection->filter(function($value, $constant) {
			return str_starts_with($constant, 'PHINX_TYPE_');
		})->toArray();

		if (empty($la_typeMatches[1]) || ! in_array($la_typeMatches[1], $la_validTypes)) {
			if ($la_typeMatches[1] == 'int') {
				$la_typeMatches[1] = 'integer';
			}
			elseif ($la_typeMatches[1] == 'tinyint') {
				$la_typeMatches[1] = 'tinyinteger';
			}
			else {
				$la_typeMatches[1] = 'string';
			}
		}

		return [$la_typeMatches[1], $la_typeMatches[2] ?: NULL];
	}
}
