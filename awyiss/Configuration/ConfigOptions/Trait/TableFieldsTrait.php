<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions\Trait;


use Awyiss\Model\Table\PagesTable;
use Awyiss\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;


/**
 * Provides a method to be used as a 'values'-callback.
 * It returns all possible fields from both the table and attributes
 */
trait TableFieldsTrait {
	/**
	 * @var array
	 */
	protected array $blocklistedTableFields = [
		'deleted',
		'deletedBy',
		'deletedOn',
	];


	/**
	 * @param string|null $scope
	 * @return array
	 */
	public function getTableFields(?string $scope = null): array {
		static $tables;

		if (!isset($tables)) {
			$tables = ConnectionManager::get('default')
				->getSchemaCollection()
				->listTables()
			;
		}

		$scope = $scope ?? (method_exists($this, 'getDynamicScope') ? $this->getDynamicScope() : static::getScope());

		/** @var \Awyiss\Model\Table $table */
		$table = FactoryLocator::get('Table')
			->get(Inflector::camelize($scope))
		;
		$columns = [];

		if (!in_array($table->getTable(), $tables)) {
			return [];
		}

		foreach (
			$table
				->getSchema()
				->columns() as $column
		) {
			if (in_array($column, $this->blocklistedTableFields, true)) {
				continue;
			}

			if (in_array($column, ['metaTitle', 'metaDescription', 'robotsIndex', 'robotsFollow'])) {
				$columns[ $column ] = __d('Seo', Inflector::underscore($column));

				continue;
			}

			if ($table instanceof PagesTable && $table->getAlias() !== 'Pages') {
				$title = __df($table->getAlias(), 'GenericPages', Inflector::underscore($column));

				if (str_contains($title, '::')) {
					$title = __d('System', Inflector::underscore($column));
				}

				$columns[ $column ] = $title;

				continue;
			}

			$columns[ $column ] = __d($scope, Inflector::underscore($column));
		}

		if ($table->hasBehavior('Attributes')) {
			/** @var \Awyiss\Model\Behavior\AttributesBehavior $attributesBehavior */
			$attributesBehavior = $table->getBehavior('Attributes');
			foreach ($attributesBehavior->getAttributes() as $attribute) {
				if (in_array('attributes.' . $attribute->identifier, $this->blocklistedTableFields, true)) {
					continue;
				}

				if ($attribute->active) {
					$columns[ 'attributes.' . $attribute->identifier ] = $attribute->title;
				}
			}
		}

		return $columns;
	}
}
