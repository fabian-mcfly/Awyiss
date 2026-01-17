<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Awyiss\Model\Table;


/**
 * Proxy methods for AttributesBehavior
 */
trait AttributesBehaviorProxyTrait {
	/**
	 * Get all attribute fields from an array.
	 *
	 * @param array $fields
	 * @param bool $includeBaseFields
	 * @return array
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::extractAttributeFields()
	 */
	public function extractAttributeFields(array $fields, bool $includeBaseFields = false): array {
		return $this->getBehavior('Attributes')->extractAttributeFields($fields, $includeBaseFields);
	}


	/**
	 * Get all attributes for this table.
	 *
	 * @return array
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributes()
	 */
	public function getAttributes(): array {
		return $this->getBehavior('Attributes')->getAttributes();
	}


	/**
	 * Get the attributes table instance.
	 *
	 * @return \Awyiss\Model\Table
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributesTable()
	 */
	public function getAttributesTable(): Table {
		return $this->getBehavior('Attributes')->getAttributesTable();
	}


	/**
	 * Get the attributes table name.
	 *
	 * @param bool $camelized
	 * @return string
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributesTableName()
	 */
	public function getAttributesTableName(bool $camelized = false): string {
		return $this->getBehavior('Attributes')->getAttributesTableName($camelized);
	}


	/**
	 * Check if this table has attributes.
	 *
	 * @return bool
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::hasAttributes()
	 */
	public function hasAttributes(): bool {
		return $this->getBehavior('Attributes')->hasAttributes();
	}
}
