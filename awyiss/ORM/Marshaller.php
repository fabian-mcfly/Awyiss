<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Cake\Datasource\EntityInterface;
use Cake\Datasource\InvalidPropertyInterface;
use Cake\ORM\Marshaller as BaseMarshaller;


/**
 * Custom Marshaller
 */
class Marshaller extends BaseMarshaller {
	/**
	 * Implemented 1:1 but added a `has`-check before skipping columns and
	 * extracted that sequencee into `buildProperties()`
	 * Also passes `$options['setter']` to `$entity::set`, to skip using
	 * setters for default values
	 *
	 * @inheritDoc
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $data
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function merge(EntityInterface $entity, array $data, array $options = []): EntityInterface {
		[$la_data, $la_options] = $this->_prepareDataAndOptions($data, $options);

		$lb_isNew = $entity->isNew();
		$la_keys = [];

		if (!$lb_isNew) {
			$la_keys = $entity->extract((array)$this->_table->getPrimaryKey());
		}

		if (isset($la_options['accessibleFields'])) {
			if (!is_array($la_options['accessibleFields'])) {
				$la_options['accessibleFields'] = [
					(string)$la_options['accessibleFields'] => true,
				];
			}

			foreach ($la_options['accessibleFields'] as $lx_key => $lx_value) {
				$entity->setAccess($lx_key, $lx_value);
			}
		}

		$la_errors = $this->_validate($la_data + $la_keys, $la_options['validate'], $lb_isNew);
		$la_options['isMerge'] = true;
		$la_propertyMap = $this->_buildPropertyMap($la_data, $la_options);

		$la_properties = $this->buildProperties($entity, $la_propertyMap, $la_data, $la_errors);

		$entity->setErrors($la_errors);

		if (!isset($la_options['fields'])) {
			$entity->set($la_properties);

			foreach ($la_properties as $ls_field => $lx_value) {
				if ($lx_value instanceof EntityInterface) {
					$entity->setDirty($ls_field, $lx_value->isDirty());
				}
			}

			$this->dispatchAfterMarshal($entity, $la_data, $la_options);


			return $entity;
		}

		foreach ((array)$la_options['fields'] as $ls_field) {
			assert(is_string($ls_field));
			if (!array_key_exists($ls_field, $la_properties)) {
				continue;
			}
			$entity->set($ls_field, $la_properties[ $ls_field ], ['setter' => $la_options['setter'] ?? true]);
			if ($la_properties[ $ls_field ] instanceof EntityInterface) {
				$entity->setDirty($ls_field, $la_properties[ $ls_field ]->isDirty());
			}
		}

		$this->dispatchAfterMarshal($entity, $la_data, $la_options);


		return $entity;
	}


	/**
	 * Adds `$entity->has($ls_key) &&` in the big if-statement to only skip fields that were present during initialization.
	 * Helpful for default and null values.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $propertyMap
	 * @param array $data
	 * @param array $errors
	 * @return array
	 */
	protected function buildProperties(EntityInterface $entity, array $propertyMap, array $data, array $errors): array {
		$la_properties = [];

		foreach ($data as $ls_key => $lx_value) {
			if (!empty($errors[ $ls_key ])) {
				if ($entity instanceof InvalidPropertyInterface) {
					$entity->setInvalidField($ls_key, $lx_value);
				}
				continue;
			}

			if (isset($propertyMap[ $ls_key ])) {
				$lx_value = $propertyMap[ $ls_key ]($lx_value, $entity);

				$lx_original = $entity->get($ls_key);

				// Don't dirty scalar values and objects that didn't
				// change. Arrays will always be marked as dirty because
				// the original/updated list could contain references to the
				// same objects, even though those objects may have changed internally.
				if (
					$entity->has($ls_key) &&
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
