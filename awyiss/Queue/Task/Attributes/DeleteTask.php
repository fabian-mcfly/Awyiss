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
		$attributesTableName = 'attributes_' . Inflector::underscore($data['identifier']);

		$tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table $attributesTable */
		$attributesTable = $tableLocator->get('Attributes');
		//Update all records
		$attributesTable->updateAll([
			'deleted' => true,
			'deletedBy' => $data['identityId'],
			'deletedOn' => DateTime::now(),
		], [
			'scope' => Inflector::camelize($data['identifier']),
		]);

		$i18nTable = $tableLocator->get('I18n');
		$i18nTable->deleteAll([
			'model' => Inflector::camelize($attributesTableName),
		]);

		$commands = [];

		$tableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . Inflector::camelize($attributesTableName) . 'Table.php';
		$entityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . Inflector::classify($attributesTableName) . '.php';

		//Remove both the table and the entity files from the custom directory.
		if (file_exists($tableFile)) {
			$commands[] = 'unlink ' . escapeshellarg($tableFile);
		}
		if (file_exists($entityFile)) {
			$commands[] = 'unlink ' . escapeshellarg($entityFile);
		}

		//Bake a `drop`-migration
		$commands[] = 'bin' . DS . 'cake bake migration ' . escapeshellarg('drop_' . $attributesTableName)
			. ' --folder ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Migrations')
		;

		//Migrate all the newly baked migrations
		$commands[] = 'bin' . DS . 'cake migrations'
			. ' migrate --source ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Migrations') . ' --no-lock'
		;

		//Clear the database schema
		$commands[] = 'bin' . DS . 'cake schema_cache clear';

		// Bake the seed for the attributes table and truncate it beforehand.
		$commands[] = 'bin' . DS . 'cake bake seed --data Attributes'
			. ' --folder ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Seeds') . ' --force --truncate'
		;

		//Queue the job.
		$this->QueuedJobs->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', $commands) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Attributes::tableChanges',
		]);
	}
}
