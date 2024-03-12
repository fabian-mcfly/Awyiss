<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions\Trait;


use Cake\Datasource\FactoryLocator;
use Cake\Utility\Inflector;


/**
 * Provides a method to be used as a 'values'-callback.
 * It returns all possible fields from both the table and attributes
 */
trait SystemOrderFieldsTrait {
	/**
	 * @var array
	 */
	protected array $blocklistedFields = [];


	/**
	 * @return array
	 */
	public function getSystemOrderFields(): array {
		$ls_scope = $this->getScope();

		if (method_exists($this, 'getDynamicScope')) {
			$ls_scope = $this->getDynamicScope();
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($ls_scope);
		$la_columns = [];

		foreach ($lo_table->getSchema()->columns() as $ls_column) {
			$la_columns[ $ls_column ] = __d(Inflector::underscore($ls_scope), $ls_column);
		}

		/** @var \Awyiss\Model\Behavior\AttributesBehavior $lo_attributesBehavior */
		$lo_attributesBehavior = $lo_table->getBehavior('Attributes');
		foreach ($lo_attributesBehavior->getAttributes() as $lo_attribute) {
			if ($lo_attribute->active) {
				$la_columns[ 'attributes.' . $lo_attribute->identifier ] = $lo_attribute->title;
			}
		}

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();

		return $ls_entityClass::mapFields($la_columns);
	}
}
