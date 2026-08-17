<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions\Trait;


use Awyiss\Core\App;
use Awyiss\Utility\Inflector;


/**
 * Provides a method to be used as a 'values'-callback.
 */
trait TableNamesTrait {
	/**
	 * @var array
	 */
	protected static array $tableNames;


	/**
	 * @return array
	 */
	protected function getTableNames(): array {
		if (isset(static::$tableNames)) {
			return static::$tableNames;
		}

		$classes = App::classes('*', 'Model/Table', 'Table', null, null, ['GenericDatatablesTable']);

		//Traverse both namespaces
		foreach ($classes as $tableName => $className) {
			$tableName = substr($tableName, 0, -5);

			static::$tableNames[ $tableName ] ??= $tableName;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		// Get all page roles because we want them to have attributes too
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$identifier = Inflector::pluralize(Inflector::camelize($pageRole->name));

			if ($identifier === 'Pages' || isset(static::$tableNames[ $identifier ])) {
				continue;
			}

			static::$tableNames[ $identifier ] = $identifier;
		}

		ksort(static::$tableNames);


		return static::$tableNames;
	}
}
