<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Queue\Queue\Task;


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
	public $retries = 1;

	/**
	 * @param array<string, mixed> $aa_data The array passed to QueuedJobsTable::createJob()
	 * @param int $ai_jobId The id of the QueuedJob entity
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function run (array $aa_data, int $ai_jobId): void {
		/** @var \Awyiss\Model\Table\AttributesTable $lo_table */
		$lo_table = $this->getTableLocator()->get('Attributes');

		//$la_diff holds the entity's old values that differ from the new ones
		$la_diff = Hash::diff($aa_data['old'], $aa_data['new']);

		$ls_column = $aa_data['new']['name'];
		[$ls_type, $lx_length] = $lo_table->getTypeAndLength($aa_data['new']['type'] ?? NULL);

		//Type needs to be in the format of ':string?[50]', where ? marks the field as nullable and [50] sets the length
		$ls_column .= ':' . $ls_type;
		if (empty($aa_data['new']['required'])) {
			$ls_column .= '?';
		}
		if (!empty($lx_length)) {
			$ls_column .= '[' . $lx_length . ']';
		}
		if (!empty($aa_data['new']['has_index'])) {
			$ls_column .= ':index';
		}


		//Force migrations for attributes to be stored in the custom directory, to not fuck up the Awyiss migrations.
		$ls_folder = ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';


		$lb_bakeOldModel = FALSE;
		$ls_oldAttributesTable = NULL;

		//The scope of the attribute has changed.
		if ($lb_changedScope = isset($la_diff['scope'])) {
			//We not only need to add the attribute to the new table but also remove it from the old one
			$ls_oldAttributesTable = 'attributes_' . $aa_data['old']['scope'];

			/** @var TableSchemaInterface $lo_schema */
			$lo_schema = ConnectionManager::get('default')->getSchemaCollection()->describe($ls_oldAttributesTable);
			/*
			 * If there are three or fewer columns in the table, the old attributes-table is no longer required.
			 *
			 * Why 3, you ask?
			 * One is the attribute that's getting changed and the other two are the `id`- and the parent id-column.
			 */
			if (count($lo_schema->columns()) <= 3) {
				//Bake a `drop`-migration
				$la_commands[] = 'bin/cake bake migration drop_' . $ls_oldAttributesTable . $ls_folder;

				//Remove both the table and the entity files from the custom directory.
				if (file_exists($ls_tableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($ls_oldAttributesTable) . 'Table.php')) {
					$la_commands[] = 'unlink ' . $ls_tableFile;
				}
				if (file_exists($ls_entityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($ls_oldAttributesTable) . '.php')) {
					$la_commands[] = 'unlink ' . $ls_entityFile;
				}
			}
			else {
				//Bake a `remove`-migration
				$la_commands[] = 'bin/cake bake migration remove_' . $aa_data['new']['name'] . '_from_' . $ls_oldAttributesTable . ' ' . $aa_data['old']['name'] . $ls_folder;
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

		//The target attributes-table does not exist
		if (!$lb_tableExists) {
			//Bake a `create`-migration that also adds the parent id-column and the column for the attribute-entity
			$la_commands[] = 'bin/cake bake migration create_' . $ls_attributesTable . ' ' . Inflector::singularize($aa_data['new']['scope']) . '_id:integer[11] ' . $ls_column . $ls_folder;
		}
		else {
			//Column is renamed but only if the scope is still the same.
			if (!$lb_changedScope && isset($la_diff['name'])) {
				//The scope has not changed, but the name has: alter the column
				$la_commands[] = 'bin/cake bake migration alter_' . $aa_data['old']['name']. '_on_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
			}
			else {
				/** @var TableSchemaInterface $lo_schema */
				$lo_schema = ConnectionManager::get('default')->getSchemaCollection()->describe($ls_attributesTable);
				$lb_columnExists = $lo_schema->hasColumn($aa_data['new']['name']);

				if (!$lb_columnExists) {
					//The column does not exist in the target table? Add it.
					$la_commands[] = 'bin/cake bake migration add_' . $aa_data['new']['name'] . '_to_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
				}
				else {
					//The column does exist in the target table? Alter it.
					$la_commands[] = 'bin/cake bake migration alter_' . $aa_data['new']['name'] . '_on_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
				}
			}
		}


		if (!empty($la_commands)) {
			//Migrate all the newly baked migrations
			$la_commands[] = 'bin/cake migrations migrate --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock';
			//Clear the database schema
			$la_commands[] = 'bin/cake schema_cache clear';
			//And bake the model
			$la_commands[] = 'bin/cake bake model ' . $ls_attributesTable . ' --namespace ' . CUSTOM_DIR . ' --no-fixture --no-test --update --force';

			if ($lb_bakeOldModel && $ls_oldAttributesTable) {
				//If the old table was changed but still exists, bake the model for the old table as well.
				$la_commands[] = 'bin/cake bake model ' . $ls_oldAttributesTable . ' --namespace ' . CUSTOM_DIR . ' --no-fixture --no-test --update --force';
			}

			//Queue the job.
			$this->QueuedJobs->createJob('Queue.Execute', [
				'command' => implode(' && ', array_map('escapeshellcmd', $la_commands)),
				'escape' => FALSE,
			], [
				'reference' => 'attributes::table_changes',
				'priority' => 1
			]);
		}
	}


	/**
	 * @param NULL|string $as_data
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	/*public function add (?string $as_data): void {
		$this->io->out('Awyiss Attributes task.');
		$this->io->hr();

		if (!$as_data) {
			$this->io->out('This will run attribute-related migrations');
			$this->io->out('It requires the id of an Attributes-entity to perform changes');
			$this->io->out('to the database, depending on it\'s settings');
			$this->io->out(' ');
			$this->io->out('Call like this:');
			$this->io->out('    bin/cake queue add Attributes *base64-encoded array[id,old,new]*');
			$this->io->out(' ');

			return;
		}

		try {
			['id' => $li_id, 'old' => $la_oldValues, 'new' => $la_newValues] = json_decode(base64_decode($as_data), TRUE);

			if (empty($li_id)) {
				throw new \Exception('Invalid id');
			}

			$lo_table = $this->getTableLocator()->get('Attributes');
			$lo_entity = $lo_table->get($li_id, [
				'authorization' => [
					'skip' => TRUE
				],
			]);
		}
		catch (\Exception $lo_exception) {
			$this->io->err($lo_exception->getMessage());

			return;
		}

		$this->QueuedJobs->createJob('Attributes', [
			'id' => $lo_entity->id,
			'old' => $la_oldValues,
			'new' => $la_newValues
		], [
			'reference' => 'attributes::table_changes',
			'priority' => 1,
		]);
		$this->io->success('OK, job created for Attribute `' . $lo_entity->id . '`, now run the worker');
	}*/
}