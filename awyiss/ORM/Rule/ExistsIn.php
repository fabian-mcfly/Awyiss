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
	 * Performs the existence check
	 *
	 * Reimplemented to honor the `withMatchingAttributes`-finder that allows checking for the existence
	 * of entities using attributes
	 *
	 * @param EntityInterface $entity The entity from where to extract the fields
	 * @param array<string, mixed> $options Options passed to the check, where the `repository` key is required.
	 * @return bool
	 * @throws RuntimeException When the rule refers to an undefined association.
	 */
	public function __invoke(EntityInterface $entity, array $options): bool {
		$this->setRepository($options['repository']);

		$la_fields = $this->_fields;
		$lo_source = $this->_repository;
		$lo_target = $this->_repository;
		if ($lo_target instanceof Association) {
			$la_bindingKey = (array)$lo_target->getBindingKey();
			$lo_realTarget = $lo_target->getTarget();
		}
		else {
			$la_bindingKey = (array)$lo_target->getPrimaryKey();
			$lo_realTarget = $lo_target;
		}

		if (!empty($options['_sourceTable']) && $lo_realTarget === $options['_sourceTable']) {
			return true;
		}

		if (!empty($options['repository'])) {
			$lo_source = $options['repository'];
		}
		if ($lo_source instanceof Association) {
			$lo_source = $lo_source->getSource();
		}

		if (!$entity->extract($la_fields, true)) {
			if ($this->attributeFieldsAreUnchanged($lo_target)) {
				return true;
			}
		}

		if ($this->_fieldsAreNull($entity, $lo_source)) {
			return true;
		}

		if ($this->_options['allowNullableNulls']) {
			$lo_schema = $lo_source->getSchema();
			foreach ($la_fields as $li_i => $ls_field) {
				if ($lo_schema->getColumn($ls_field) && $lo_schema->isNullable($ls_field) && $entity->get($ls_field) === null) {
					unset($la_bindingKey[ $li_i ], $la_fields[ $li_i ]);
				}
			}
		}

		$la_primary = array_map(function ($key) use ($lo_target) {
			return $lo_target->aliasField($key) . ' IS';
		}, $la_bindingKey);


		$la_conditions = array_combine($la_primary, $entity->extract($la_fields));

		$la_options = array_diff_key($this->_options, ['allowNullableNulls' => null]);
		$la_options['finder'] ??= $lo_target->getFinder();


		return $lo_target->exists($la_conditions, $la_options);
	}


	/**
	 * Returns whether the provides keys have changed in the entity's attributes
	 *
	 * @param \Cake\ORM\Association|\Awyiss\Model\Table $target
	 * @return bool
	 */
	protected function attributeFieldsAreUnchanged(Association|Table $target): bool {
		$lx_finder = $target instanceof Association ? $target->getFinder() : 'all';
		if (!is_array($lx_finder) || !isset($lx_finder['withMatchingAttributes'])) {
			return true;
		}

		$lo_attributesEntity = $lx_finder['withMatchingAttributes']['entity']?->attributes ?? null;
		$la_keys = $lx_finder['withMatchingAttributes']['keys'] ?? [];

		if (!$lo_attributesEntity || !$lo_attributesEntity->extract($target->extractAttributeFields($la_keys, true), true)) {
			return true;
		}


		return false;
	}


	/**
	 * @param mixed $repository
	 * @return void
	 */
	protected function setRepository(mixed $repository): void {
		if (is_string($this->_repository)) {
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
}
