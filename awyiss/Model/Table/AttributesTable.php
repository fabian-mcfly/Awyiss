<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Datasource\EntityInterface;


/**
 * Attributes Model
 *
 * @method \Awyiss\Model\Entity\Attribute newDefaultEntity()
 * @method \Awyiss\Model\Entity\Attribute patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class AttributesTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setDisplayField('parent_id');
		$this->setPrimaryKey('parent_id');
	}
}
