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
	protected string|Association|Table|BaseTable $_repository; // phpcs:ignore


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

		$target = $this->_repository;
		// Get the real target and binding key
		if ($target instanceof Association) {
			$bindingKey = (array)$target->getBindingKey();
			$realTarget = $target->getTarget();
		}
		else {
			$bindingKey = (array)$target->getPrimaryKey();
			$realTarget = $target;
		}

		// If the source table is the same as the target, we can skip the check
		// since the entity is already in the target table.
		if (($options['_sourceTable'] ?? null) === $realTarget) {
			return true;
		}

		// If a repository is provided, use it as the source table
		$source = $options['repository'] ?? $this->_repository;
		// If the source is an association, get the source table
		if ($source instanceof Association) {
			$source = $source->getSource();
		}

		// If the relevant fields are clean...
		$fields = $this->_fields;
		if (!$entity->extract($fields, true)) {
			// ...and the attributes are as well, we can skip the check
			if (!$this->attributeFieldsAreDirty($target)) {
				return true;
			}
		}

		// If the fields are null, we can skip the check
		if ($this->_fieldsAreNull($entity, $source)) {
			return true;
		}

		// If the allowNullableNulls option is set, unset fields that are nullable and null
		if ($this->_options['allowNullableNulls']) {
			$schema = $source->getSchema();
			foreach ($fields as $i => $field) {
				if ($schema->getColumn($field) && $schema->isNullable($field) && $entity->get($field) === null) {
					unset($bindingKey[ $i ], $fields[ $i ]);
				}
			}
		}

		$primary = array_map(function ($key) use ($realTarget) {
			// Prefix the key with the target alias and append ` IS`
			// in case the value is null.
			return $realTarget->aliasField($key) . ' IS';
		}, $bindingKey);

		// Combine the primary keys with the values from the entity
		$conditions = array_combine($primary, $entity->extract($fields));

		// Remove unnecessary options
		$options = $this->_options;
		unset($options['allowNullableNulls']);

		// Set the finder if the target is an association and the options have no finder set
		if ($target instanceof Association) {
			$options['finder'] ??= $target->getFinder();
		}

		// Do the actual existence check and pass the options
		return $target->exists($conditions, $options);
	}


	/**
	 * Returns whether the provides keys in the `withMatchingAttributes`
	 * are dirty in the attributes entity of the target association.
	 *
	 * @param \Cake\ORM\Association|\Awyiss\Model\Table $target
	 * @return bool
	 */
	protected function attributeFieldsAreDirty(Association|Table $target): bool {
		$finder = $target instanceof Association ? $target->getFinder() : 'all';
		if (!is_array($finder) || !isset($finder['withMatchingAttributes'])) {
			return true;
		}

		/** @var \Awyiss\Model\Entity|null $attributesEntity */
		$attributesEntity = $finder['withMatchingAttributes']['entity']?->get('attributes') ?? null;
		$fields = $finder['withMatchingAttributes']['fields'] ?? [];

		$realTarget = $target instanceof Association ? $target->getTarget() : $target;

		return !!$attributesEntity?->extract($realTarget->extractAttributeFields($fields, true), true);
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
