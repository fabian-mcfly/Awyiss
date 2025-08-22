<?php declare(strict_types=1);


namespace Awyiss\Queue\Task\Attributes;


use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
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
class DeleteTask extends Task/* implements AddInterface*/ {
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
	 * @noinspection DuplicatedCode
	 */
	public function run(array $data, int $jobId): void {
		$ls_attributesTable = 'attributes_' . $data['identifier'];

		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table $lo_attributesTable */
		$lo_attributesTable = $lo_tableLocator->get('Attributes');
		//Update all records
		$lo_attributesTable->updateAll([
			'deleted' => true,
			'deleted_by' => $data['identityId'],
			'deleted_on' => DateTime::now(),
		], [
			'scope' => Inflector::tableize($data['identifier']),
		]);

		$lo_configuration = $lo_tableLocator->get('I18n');
		$lo_configuration->deleteAll([
			'model' => $ls_attributesTable,
		]);

		$la_commands = [];

		$ls_tableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($ls_attributesTable) . 'Table.php';
		$ls_entityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($ls_attributesTable) . '.php';

		//Remove both the table and the entity files from the custom directory.
		if (file_exists($ls_tableFile)) {
			$la_commands[] = 'unlink ' . $ls_tableFile;
		}
		if (file_exists($ls_entityFile)) {
			$la_commands[] = 'unlink ' . $ls_entityFile;
		}

		//Bake a `drop`-migration
		$la_commands[] = 'bin' . DS . 'cake bake migration drop_' . $ls_attributesTable . ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';

		//Migrate all the newly baked migrations
		$la_commands[] = 'bin' . DS . 'cake migrations migrate --source ../../' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock';

		//Clear the database schema
		$la_commands[] = 'bin' . DS . 'cake schema_cache clear';

		// Bake the seed for the attributes table and truncate it beforehand.
		$la_commands[] = 'bin' . DS . 'cake bake seed --data Attributes --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$this->QueuedJobs->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'attributes::table_changes',
		]);
	}
}
