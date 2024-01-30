<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Cake\Datasource\EntityInterface;
use Cake\Datasource\InvalidPropertyInterface;
use Cake\ORM\Marshaller as BaseMarshaller;


class Marshaller extends BaseMarshaller {
	/**
	 * Implemented 1:1 but added a `has`-check before skipping columns and
	 * extracted that sequencee into `buildProperties()`
	 *
	 * @inheritDoc
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param array $aa_data
	 * @param array $aa_options
	 * @return \Cake\Datasource\EntityInterface
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function merge(EntityInterface $ao_entity, array $aa_data, array $aa_options = []): EntityInterface {
		[$la_data, $la_options] = $this->_prepareDataAndOptions($aa_data, $aa_options);

		$lb_isNew = $ao_entity->isNew();
		$la_keys = [];

		if (!$lb_isNew) {
			$la_keys = $ao_entity->extract((array)$this->_table->getPrimaryKey());
		}

		if (isset($la_options['accessibleFields'])) {
			foreach ((array)$la_options['accessibleFields'] as $key => $value) {
				$ao_entity->setAccess($key, $value);
			}
		}

		$la_errors = $this->_validate($la_data + $la_keys, $la_options['validate'], $lb_isNew);
		$la_options['isMerge'] = true;
		$la_propertyMap = $this->_buildPropertyMap($la_data, $la_options);

		$la_properties = $this->buildProperties($ao_entity, $la_propertyMap, $la_data, $la_errors);

		$ao_entity->setErrors($la_errors);
		if (!isset($la_options['fields'])) {
			$ao_entity->set($la_properties);

			foreach ($la_properties as $ls_field => $lx_value) {
				if ($lx_value instanceof EntityInterface) {
					$ao_entity->setDirty($ls_field, $lx_value->isDirty());
				}
			}
			$this->dispatchAfterMarshal($ao_entity, $la_data, $la_options);


			return $ao_entity;
		}

		foreach ((array)$la_options['fields'] as $ls_field) {
			assert(is_string($ls_field));
			if (!array_key_exists($ls_field, $la_properties)) {
				continue;
			}
			$ao_entity->set($ls_field, $la_properties[ $ls_field ]);
			if ($la_properties[ $ls_field ] instanceof EntityInterface) {
				$ao_entity->setDirty($ls_field, $la_properties[ $ls_field ]->isDirty());
			}
		}
		$this->dispatchAfterMarshal($ao_entity, $la_data, $la_options);


		return $ao_entity;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param array $aa_propertyMap
	 * @param array $aa_data
	 * @param array $aa_errors
	 * @return array
	 */
	protected function buildProperties(EntityInterface $ao_entity, array $aa_propertyMap, array $aa_data, array $aa_errors): array {
		$la_properties = [];

		foreach ($aa_data as $ls_key => $lx_value) {
			if (!empty($aa_errors[ $ls_key ])) {
				if ($ao_entity instanceof InvalidPropertyInterface) {
					$ao_entity->setInvalidField($ls_key, $lx_value);
				}
				continue;
			}

			if (isset($aa_propertyMap[ $ls_key ])) {
				$lx_value = $aa_propertyMap[ $ls_key ]($lx_value, $ao_entity);

				$lx_original = $ao_entity->get($ls_key);

				// Don't dirty scalar values and objects that didn't
				// change. Arrays will always be marked as dirty because
				// the original/updated list could contain references to the
				// same objects, even though those objects may have changed internally.
				if (
					$ao_entity->has($ls_key) &&
					(
						(
							is_scalar($lx_value) &&
							$lx_original === $lx_value
						) ||
						(
							$lx_value === null &&
							$lx_original === $lx_value
						) ||
						(
							is_object($lx_value) &&
							!($lx_value instanceof EntityInterface) &&
							$lx_original == $lx_value
						)
					)
				) {
					continue;
				}
			}
			$la_properties[ $ls_key ] = $lx_value;
		}


		return $la_properties;
	}
}
