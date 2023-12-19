<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;
use Queue\Model\QueueException;
//use Queue\Queue\AddInterface;
use Queue\Queue\Task;


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

		/*try {
			//Make sure that entity exists
			$lo_table->get($aa_data['id'], [
				'access' => [
					'skip' => TRUE
				],
			]);
		}
		catch (\Exception $lo_exception) {
			throw new QueueException($lo_exception->getMessage());
		}*/

		//$la_diff holds the entity's old values that differ from the new values
		$la_diff = \Cake\Utility\Hash::diff($aa_data['old'], $aa_data['new']);


		$ls_column = $aa_data['new']['name'];
		[$ls_type, $lx_length] = $lo_table->getTypeAndLength($aa_data['new']['type'] ?? NULL);

		//Type needs to be in the format of ':string?[50]' where ? marks the field as nullable and [50] sets the length
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


		$ls_folder = ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';


		$lb_bakeOldModel = FALSE;
		$ls_oldAttributesTable = NULL;
		//The scope of the attribute has changed.
		if ($lb_changedScope = isset($la_diff['scope'])) {
			//We not only need to add the attribute to the new table but also remove it from the old one
			$ls_oldAttributesTable = 'attributes_' . $aa_data['old']['scope'];

			/** @var TableSchemaInterface $lo_schema */
			$lo_schema = ConnectionManager::get('default')->getSchemaCollection()->describe($ls_oldAttributesTable);
			if (count($lo_schema->columns()) <= 3) {
				$la_commands[] = 'bin/cake bake migration drop_' . $ls_oldAttributesTable . $ls_folder;

				if (file_exists($ls_tableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($ls_oldAttributesTable) . 'Table.php')) {
					$la_commands[] = 'unlink ' . $ls_tableFile;
				}
				if (file_exists($ls_entityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($ls_oldAttributesTable) . '.php')) {
					$la_commands[] = 'unlink ' . $ls_entityFile;
				}
			}
			else {
				$la_commands[] = 'bin/cake bake migration remove_' . $aa_data['new']['name'] . '_from_' . $ls_oldAttributesTable . ' ' . $aa_data['old']['name'] . $ls_folder;
				$lb_bakeOldModel = TRUE;
			}

			//Sleep for one second, otherwise the migration that removes the column from the old table
			//has the same "version" as the migration that adds the column to the new table
			$la_commands[] = 'sleep 1';
		}


		$ls_attributesTable = 'attributes_' . $aa_data['new']['scope'];
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		$lb_tableExists = in_array($ls_attributesTable, $la_tables);
		if (!$lb_tableExists) {
			$la_commands[] = 'bin/cake bake migration create_' . $ls_attributesTable . ' ' . Inflector::singularize($aa_data['new']['scope']) . '_id:integer[11] ' . $ls_column . $ls_folder;
		}
		else {
			//Column is renamed but only if the scope is still the same.
			if (!$lb_changedScope && isset($la_diff['name'])) {
				$la_commands[] = 'bin/cake bake migration alter_' . $aa_data['old']['name']. '_on_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
			}
			else {
				/** @var TableSchemaInterface $lo_schema */
				$lo_schema = ConnectionManager::get('default')->getSchemaCollection()->describe($ls_attributesTable);
				$lb_columnExists = $lo_schema->hasColumn($aa_data['new']['name']);
				if (!$lb_columnExists) {
					$la_commands[] = 'bin/cake bake migration add_' . $aa_data['new']['name'] . '_to_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
				}
				else {
					$la_commands[] = 'bin/cake bake migration alter_' . $aa_data['new']['name'] . '_on_' . $ls_attributesTable . ' ' . $ls_column . $ls_folder;
				}
			}
		}


		if (!empty($la_commands)) {
			$la_commands[] = 'bin/cake migrations migrate --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock';
			$la_commands[] = 'bin/cake schema_cache clear';
			$la_commands[] = 'bin/cake bake model ' . $ls_attributesTable . ' --namespace ' . CUSTOM_DIR . ' --no-fixture --no-test --update --force';

			if ($lb_bakeOldModel && $ls_oldAttributesTable) {
				$la_commands[] = 'bin/cake bake model ' . $ls_oldAttributesTable . ' --namespace ' . CUSTOM_DIR . ' --no-fixture --no-test --update --force';
			}

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
	 * @param null|string $as_data
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
				'access' => [
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