<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions\Trait;


use Awyiss\Core\App;
use Awyiss\Utility\Inflector;


/**
 * Provides a method to be used as a 'values'-callback.
 */
trait TableNamesTrait {
	protected static array $tableNames;


	/**
	 * @return array
	 */
	protected function getTableNames(): array {
		if (isset(static::$tableNames)) {
			return static::$tableNames;
		}

		$la_classes = App::classes('*', 'Model/Table', 'Table', null, null, ['GenericDatatablesTable']);

		//Traverse both namespaces
		foreach ($la_classes as $ls_tableName => $ls_className) {
			$ls_tableName = substr($ls_tableName, 0, -5);

			static::$tableNames[ $ls_tableName ] ??= $ls_tableName;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		// Get all page roles because we want them to have attributes too
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
