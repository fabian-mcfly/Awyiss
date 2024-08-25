<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions\Trait;


use Awyiss\Core\App;
use Cake\Utility\Inflector;


/**
 * Provides a method to be used as a 'values'-callback.
 */
trait TableNamesTrait {
	protected static array $tableNames;


	/**
	 * @param string $table
	 * @return array
	 */
	protected function getTableNames(): array {
		if (isset(static::$tableNames)) {
			return static::$tableNames;
		}

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Model\Table\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', '*Table.php',]),
			'\Awyiss\Model\Table\\' => implode(DS, [ROOT, APP_DIR, 'Model', 'Table', '*Table.php']),
		];

		//Traverse both namespaces
		foreach ($la_paths as $ls_path) {
			//Look for files with name "*Table.php"
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_tableName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -9);

				if ($ls_tableName === 'GenericDatatables') {
					continue;
				}

				//If an entry exists or if the table does not allow attributes, skip it
				if (isset($this->attributeScopes[ $ls_tableName ])) {
					continue;
				}

				static::$tableNames[ $ls_tableName ] = $ls_tableName;
			}
		}

		//Get all page roles because we want them to have attributes too
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		/** @var \Awyiss\Model\Table\PageRolesTable $lo_table */
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_identifier = Inflector::pluralize(Inflector::camelize($le_pageRole->name));

			if ($ls_identifier === 'Pages' || isset(static::$tableNames[ $ls_identifier ])) {
				continue;
			}

			static::$tableNames[ $ls_identifier ] = $ls_identifier;
		}

		ksort(static::$tableNames);


		return static::$tableNames;
	}
}
