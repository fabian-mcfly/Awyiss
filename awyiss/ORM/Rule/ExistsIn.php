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
	 * Implemented from parent class 1:1 but pass the initial options to the `exists`-method
	 *
	 * @param EntityInterface $ao_entity The entity from where to extract the fields
	 * @param array<string, mixed> $aa_options Options passed to the check, where the `repository` key is required.
	 * @return bool
	 * @throws RuntimeException When the rule refers to an undefined association.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function __invoke(EntityInterface $ao_entity, array $aa_options): bool {
		$this->setRepository($aa_options['repository']);

		$la_fields = $this->_fields;
		$lo_source = $lo_target = $this->_repository;
		if ($lo_target instanceof Association) {
			$la_bindingKey = (array)$lo_target->getBindingKey();
			$lo_realTarget = $lo_target->getTarget();
		}
		else {
			$la_bindingKey = (array)$lo_target->getPrimaryKey();
			$lo_realTarget = $lo_target;
		}

		if (!empty($aa_options['_sourceTable']) && $lo_realTarget === $aa_options['_sourceTable']) {
			return true;
		}

		if (!empty($aa_options['repository'])) {
			$lo_source = $aa_options['repository'];
		}
		if ($lo_source instanceof Association) {
			$lo_source = $lo_source->getSource();
		}

		if (!$ao_entity->extract($la_fields, true)) {
			$lx_finder = $lo_target->getFinder();
			if (is_array($lx_finder) && isset($lx_finder['withMatchingAttributes'])) {
				$lo_entity = $lx_finder['withMatchingAttributes']['entity']?->attributes ?? null;
				$la_keys = $lx_finder['withMatchingAttributes']['keys'] ?? [];

				if (!$lo_entity || !$lo_entity->extract($lo_target->extractAttributeFields($la_keys, true), true)) {
					return true;
				}
			}
			else {
				return true;
			}
		}

		if ($this->_fieldsAreNull($ao_entity, $lo_source)) {
			return true;
		}

		if ($this->_options['allowNullableNulls']) {
			$lo_schema = $lo_source->getSchema();
			foreach ($la_fields as $li_i => $ls_field) {
				if ($lo_schema->getColumn($ls_field) && $lo_schema->isNullable($ls_field) && $ao_entity->get($ls_field) === null) {
					unset($la_bindingKey[ $li_i ], $la_fields[ $li_i ]);
				}
			}
		}

		$la_primary = array_map(function ($as_key) use ($lo_target) {
			return $lo_target->aliasField($as_key) . ' IS';
		}, $la_bindingKey);


		$la_conditions = array_combine($la_primary, $ao_entity->extract($la_fields));

		$la_options = array_diff_key($this->_options, ['allowNullableNulls' => null]);


		return $lo_target->exists($la_conditions, $la_options);
	}


	/**
	 * @param mixed $ax_repository
	 * @return void
	 */
	protected function setRepository(mixed $ax_repository): void {
		if (is_string($this->_repository)) {
			if (!$ax_repository->hasAssociation($this->_repository)) {
				throw new RuntimeException(
					sprintf(
						"ExistsIn rule for '%s' is invalid. '%s' is not associated with '%s'.",
						implode(', ', $this->_fields),
						$this->_repository,
						get_class($ax_repository)
					)
				);
			}

			$this->_repository = $ax_repository->getAssociation($this->_repository);
		}
	}
}
