<?php declare(strict_types=1);


namespace Awyiss\ORM\Rule;


use Awyiss\Model\Table;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Rule\ExistsIn as BaseExistsIn;
use Cake\ORM\Table as BaseTable;
use RuntimeException;


/**
 * Checks that the value provided in a field exists as the primary key of another
 * table.
 */
class ExistsIn extends BaseExistsIn {
	/**
	 * The repository where the field will be looked for
	 *
	 * @var BaseTable|Association|Table|string
	 */
	protected string|Association|Table|BaseTable $_repository;


	/**
	 * Re-implemented to
	 * - honor the `withMatchingAttributes`-finder that allows checking for the existence of entities using attributes
	 * 	using the `attributeFieldsAreDirty()` method
	 * - get the finder of the target association if it is not set in the options
	 * - pass the options to the `exists()` method
	 *
	 * @inheritDoc
	 */
	public function __invoke(EntityInterface $entity, array $options): bool {
		$this->setRepository($options['repository']);

		$lo_target = $this->_repository;
		// Get the real target and binding key
		if ($lo_target instanceof Association) {
			$la_bindingKey = (array)$lo_target->getBindingKey();
			$lo_realTarget = $lo_target->getTarget();
		}
		else {
			$la_bindingKey = (array)$lo_target->getPrimaryKey();
			$lo_realTarget = $lo_target;
		}

		// If the source table is the same as the target, we can skip the check
		// since the entity is already in the target table.
		if (($options['_sourceTable'] ?? null) === $lo_realTarget) {
			return true;
		}

		// If a repository is provided, use it as the source table
		$lo_source = $options['repository'] ?? $this->_repository;
		// If the source is an association, get the source table
		if ($lo_source instanceof Association) {
			$lo_source = $lo_source->getSource();
		}

		// If the relevant fields are clean...
		$la_fields = $this->_fields;
		if (!$entity->extract($la_fields, true)) {
			// ...and the attributes are as well, we can skip the check
			if (!$this->attributeFieldsAreDirty($lo_target)) {
				return true;
			}
		}

		// If the fields are null, we can skip the check
		if ($this->_fieldsAreNull($entity, $lo_source)) {
			return true;
		}

		// If the allowNullableNulls option is set, unset fields that are nullable and null
		if ($this->_options['allowNullableNulls']) {
			$lo_schema = $lo_source->getSchema();
			foreach ($la_fields as $li_i => $ls_field) {
				if ($lo_schema->getColumn($ls_field) && $lo_schema->isNullable($ls_field) && $entity->get($ls_field) === null) {
					unset($la_bindingKey[ $li_i ], $la_fields[ $li_i ]);
				}
			}
		}

		$la_primary = array_map(function ($key) use ($lo_realTarget) {
			// Prefix the key with the target alias and append ` IS`
			// in case the value is null.
			return $lo_realTarget->aliasField($key) . ' IS';
		}, $la_bindingKey);

		// Combine the primary keys with the values from the entity
		$la_conditions = array_combine($la_primary, $entity->extract($la_fields));

		// Remove unnecessary options
		$la_options = $this->_options;
		unset($la_options['allowNullableNulls']);

		// Set the finder if the target is an association and the options have no finder set
		if ($lo_target instanceof Association) {
			$la_options['finder'] ??= $lo_target->getFinder();
		}

		// Do the actual existence check and pass the options
		return $lo_target->exists($la_conditions, $la_options);
	}


	/**
	 * Returns whether the provides keys in the `withMatchingAttributes`
	 * are dirty in the attributes entity of the target association.
	 *
	 * @param \Cake\ORM\Association|\Awyiss\Model\Table $target
	 * @return bool
	 */
	protected function attributeFieldsAreDirty(Association|Table $target): bool {
		$lx_finder = $target instanceof Association ? $target->getFinder() : 'all';
		if (!is_array($lx_finder) || !isset($lx_finder['withMatchingAttributes'])) {
			return true;
		}

		/** @var \Awyiss\Model\Entity|null $lo_attributesEntity */
		$lo_attributesEntity = $lx_finder['withMatchingAttributes']['entity']?->get('attributes') ?? null;
		$la_keys = $lx_finder['withMatchingAttributes']['keys'] ?? [];

		$lo_realTarget = $target instanceof Association ? $target->getTarget() : $target;

		return !!$lo_attributesEntity?->extract($lo_realTarget->extractAttributeFields($la_keys, true), true);
	}


	/**
	 * @param mixed $repository
	 * @return void
	 */
	protected function setRepository(mixed $repository): void {
		if (!is_string($this->_repository)) {
			return;
		}

		if (!$repository->hasAssociation($this->_repository)) {
			throw new RuntimeException(
				sprintf(
					"ExistsIn rule for '%s' is invalid. '%s' is not associated with '%s'.",
					implode(', ', $this->_fields),
					$this->_repository,
					$repository::class
				)
			);
		}

		$this->_repository = $repository->getAssociation($this->_repository);
	}
}
