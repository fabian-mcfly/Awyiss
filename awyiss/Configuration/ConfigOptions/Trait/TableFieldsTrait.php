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
		'deleted_by',
		'deleted_on',
	];


	/**
	 * @return array
	 */
	public function getTableFields(): array {
		static $la_tables;

		if (!isset($la_tables)) {
			$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		}

		if (method_exists($this, 'getDynamicScope')) {
			$ls_scope = $this->getDynamicScope();
		}
		else {
			$ls_scope = static::getScope();
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($ls_scope);
		$la_columns = [];

		if (!in_array($lo_table->getTable(), $la_tables)) {
			return [];
		}

		foreach ($lo_table->getSchema()->columns() as $ls_column) {
			if (in_array($ls_column, $this->blocklistedTableFields, true)) {
				continue;
			}

			if (in_array($ls_column, ['meta_title', 'meta_description', 'robots_index', 'robots_follow'])) {
				$la_columns[ $ls_column ] = __d('seo', $ls_column);

				continue;
			}

			if ($lo_table instanceof PagesTable && $lo_table->getAlias() !== 'Pages') {
				$ls_title = __df(Inflector::underscore($lo_table->getAlias()), 'generic_pages', $ls_column);

				if (str_contains($ls_title, '::')) {
					$ls_title = __d('system', $ls_column);
				}

				$la_columns[ $ls_column ] = $ls_title;

				continue;
			}

			$la_columns[ $ls_column ] = __d(Inflector::underscore($ls_scope), $ls_column);
		}

		/** @var \Awyiss\Model\Behavior\AttributesBehavior $lo_attributesBehavior */
		$lo_attributesBehavior = $lo_table->getBehavior('Attributes');
		foreach ($lo_attributesBehavior->getAttributes() as $lo_attribute) {
			if (in_array('attributes.' . $lo_attribute->identifier, $this->blocklistedTableFields, true)) {
				continue;
			}

			if ($lo_attribute->active) {
				$la_columns[ 'attributes.' . $lo_attribute->identifier ] = $lo_attribute->title;
			}
		}

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();

		return $ls_entityClass::mapFields($la_columns);
	}
}
