<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Awyiss\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Migrations\BaseMigration;


/**
 * Add single-column indexes for source id columns on attributes_* tables.
 */
class AddIndexesOnAttributesSourceColumns extends BaseMigration {
	/**
	 * @return void
	 */
	public function change(): void {
		foreach ($this->getAttributesTables() as $tableName) {
			$columns = $this->getTableColumns($tableName);
			$column = $this->resolveSourceColumn($tableName, $columns);
			if ($column === null) {
				continue;
			}

			$table = $this->table($tableName);
			$indexName = 'BY_' . strtoupper($column);
			if ($table->hasIndex([$column]) || $table->hasIndexByName($indexName)) {
				continue;
			}

			$table
				->addIndex([$column], [
					'name' => $indexName,
					'unique' => false,
				])
				->update()
			;
		}
	}


	/**
	 * @return array<string>
	 */
	protected function getAttributesTables(): array {
		$tables = ConnectionManager::get('default')
			->getSchemaCollection()
			->listTables()
		;

		return array_values(
			array_filter(
				$tables,
				static fn(string $tableName): bool => str_starts_with($tableName, 'attributes_')
			)
		);
	}


	/**
	 * @param string $tableName
	 * @return array<string>
	 */
	protected function getTableColumns(string $tableName): array {
		/** @var \Cake\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$alias = 'Migration' . Inflector::camelize($tableName);
		$table = $tableLocator->get($alias, [
			'table' => $tableName,
		]);

		return $table->getSchema()->columns();
	}


	/**
	 * @param string $tableName
	 * @param array<string> $columns
	 * @return string|null
	 */
	protected function resolveSourceColumn(string $tableName, array $columns): ?string {
		$sourceTable = preg_replace('/^attributes_/', '', $tableName) ?: '';

		$singular = Inflector::singularize($sourceTable);
		$base = Inflector::variable($singular);
		$expectedColumn = $base . 'Id';

		if (in_array($expectedColumn, $columns, true)) {
			return $expectedColumn;
		}

		if (in_array('pageId', $columns, true)) {
			return 'pageId';
		}

		return null;
	}
}
