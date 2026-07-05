<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Cake\Datasource\EntityInterface;
use Cake\ORM\AssociationCollection as BaseAssociationCollection;
use Cake\ORM\Table;
use InvalidArgumentException;


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


	/**
	 * Reimplemented this method 1:1 from \Cake\ORM\AssociationCollection::_saveAssociations,
	 * to unset the `asCopy` and `isCopy` options, when saving associations that are not HasOne or HasMany, as
	 * elements cannot be saved as copies from the perspective of a child entity
	 *
	 * @inheritDoc
	 */
	protected function _saveAssociations(Table $table, EntityInterface $entity, array $associations, array $options, bool $owningSide): bool {
		unset($options['associated']);
		foreach ($associations as $alias => $nested) {
			if (is_int($alias)) {
				$alias = $nested;
				$nested = [];
			}

			$relation = $this->get($alias);
			if (!$relation) {
				throw new InvalidArgumentException(
					sprintf(
						'Cannot save `%s`, it is not associated to `%s`.',
						$alias,
						$table->getAlias(),
					)
				);
			}

			if ($relation->isOwningSide($table) !== $owningSide) {
				continue;
			}

			$saveOptions = $options;
			// Unset the `asCopy` and `isCopy` options, when saving associations that are not HasOne or HasMany, as
			// elements cannot be saved as copies from the perspective of a child entity
			if (
				!$relation instanceof HasOne &&
				!$relation instanceof HasMany
			) {
				unset($saveOptions['asCopy'], $saveOptions['isCopy']);
			}

			if (!$this->_save($relation, $entity, $nested, $saveOptions)) {
				return false;
			}
		}

		return true;
	}
}
