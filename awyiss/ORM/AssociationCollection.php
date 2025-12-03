<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Cake\Datasource\EntityInterface;
use Cake\ORM\AssociationCollection as BaseAssociationCollection;
use Cake\ORM\Table;


/**
 * AssociationCollection
 */
class AssociationCollection extends BaseAssociationCollection {
	/**
	 * Reimplemented this method 1:1 from \Cake\ORM\AssociationCollection::saveParents,
	 * to unset the `asCopy` and `isCopy` options, when saving parents, as
	 * elements cannot be saved as copies from the perspective of a child entity
	 *
	 * @inheritDoc
	 */
	public function saveParents(Table $table, EntityInterface $entity, array $associations, array $options = []): bool {
		$options['asCopy'] = false;
		$options['isCopy'] = false;

		return parent::saveParents($table, $entity, $associations, $options);
	}
}
