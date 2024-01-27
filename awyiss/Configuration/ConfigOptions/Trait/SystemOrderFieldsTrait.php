<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions\Trait;


use Cake\Datasource\FactoryLocator;


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

		if (method_exists($this, 'getPageRole')) {
			$ls_scope = $this->getPageRole();
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($ls_scope);
		$la_columns = $lo_table->getSchema()->columns();

		/** @var \Awyiss\Model\Behavior\AttributesBehavior $lo_attributesBehavior */
		$lo_attributesBehavior = $lo_table->getBehavior('Attributes');
		foreach ($lo_attributesBehavior->getAttributes() as $lo_attribute) {
			if ($lo_attribute->active) {
				$la_columns[] = 'attributes.' . $lo_attribute->identifier;
			}
		}

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();

		return $ls_entityClass::mapFields($la_columns);
	}
}
