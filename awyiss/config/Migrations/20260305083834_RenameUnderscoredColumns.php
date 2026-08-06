<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Awyiss\Utility\Inflector;
use Cake\Command\Helper\ProgressHelper;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Migrations\BaseMigration;
use Symfony\Component\Process\Process;


/**
 * This migration renames all columns in the database that contain underscores to use camelCase instead.
 */
class RenameUnderscoredColumns extends BaseMigration {
	/**
	 * Find all tables in the database and rename any columns that contain underscores to use camelCase instead.
	 *
	 * @return void
	 */
	public function up(): void {
		$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		/** @var \Cake\Command\Helper\ProgressHelper $progressHelper */
		$progressHelper = $this->getIo()->helper('Progress');

		$progressHelper->output([
			'callback' => function (ProgressHelper $progressHelper) use (&$tables) {
				$tableName = array_shift($tables);

				if (in_array($tableName, ['cake_migrations', 'phinxlog', 'queue_phinxlog', 'queue_processes', 'queued_jobs'], true)) {
					$progressHelper->increment();
					return;
				}

				$table = $this->table($tableName);
				$fields = $table->getColumns();

				foreach ($fields as $field) {
					if (str_contains($field->getName(), '_')) {
						$table->renameColumn($field->getName(), Inflector::variable($field->getName()));
					}
				}

				$table->save();

				$progressHelper->increment();
			},
			'total' => count($tables),
		]);

		ConnectionManager::get('default')->getCacher()->clear();

		FactoryLocator::get('Table')->clear();

		$process = new Process([
			'bin' . DS . 'cake',
			'schema_cache',
			'clear',
		]);

		$process->run();
	}

	/**
	 * Revert the changes made by the up() method, renaming columns from camelCase back to underscore format.
	 *
	 * @return void
	 */
	public function down(): void {
		$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		/** @var \Cake\Command\Helper\ProgressHelper $progressHelper */
		$progressHelper = $this->getIo()->helper('Progress');

		$progressHelper->output([
			'callback' => function (ProgressHelper $progressHelper) use (&$tables) {
				$tableName = array_shift($tables);

				if (in_array($tableName, ['cake_migrations', 'phinxlog', 'queue_phinxlog', 'queue_processes', 'queued_jobs'], true)) {
					$progressHelper->increment();
					return;
				}

				$table = $this->table($tableName);
				$fields = $table->getColumns();

				foreach ($fields as $field) {
					if (preg_match('/[a-z][A-Z]/', $field->getName())) {
						$table->renameColumn($field->getName(), Inflector::underscore($field->getName()));
					}
				}

				$table->save();
				$progressHelper->increment();
			},
			'total' => count($tables),
		]);

		ConnectionManager::get('default')->getCacher()->clear();

		FactoryLocator::get('Table')->clear();

		$process = new Process([
			'bin' . DS . 'cake',
			'schema_cache',
			'clear',
		]);
		$process->run();
	}
}
