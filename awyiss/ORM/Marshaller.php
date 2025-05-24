<?php declare(strict_types=1);


namespace Awyiss\ORM;


use ArrayObject;
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
		unset($la_options['events']);

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
			if (method_exists($entity, 'patch')) {
				$entity->patch($la_properties);
			}
			else {
				$entity->set($la_properties);
			}

			foreach ($la_properties as $ls_field => $lx_value) {
				if ($lx_value instanceof EntityInterface) {
					$entity->setDirty($ls_field, $lx_value->isDirty());
				}
			}

			if (($options['events'] ?? true) === true) {
				$this->dispatchAfterMarshal($entity, $la_data, $la_options);
			}


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

		if (($options['events'] ?? true) === true) {
			$this->dispatchAfterMarshal($entity, $la_data, $la_options);
		}


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
			}

			$la_properties[ $ls_key ] = $lx_value;
		}


		return $la_properties;
	}


	/**
	 * Re-implemented to skip the event,
	 * if `$options['events']` is set to `false`.
	 *
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function _prepareDataAndOptions(array $data, array $options): array {
		$options += ['validate' => true];

		$tableName = $this->_table->getAlias();
		if (isset($data[ $tableName ]) && is_array($data[ $tableName ])) {
			$data += $data[ $tableName ];
			unset($data[ $tableName ]);
		}

		if (($options['events'] ?? true) === true) {
			$data = new ArrayObject($data);
			$options = new ArrayObject($options);
			$this->_table->dispatchEvent('Model.beforeMarshal', compact('data', 'options'));
		}

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $this->_table->getEntityClass();

		$la_data = (array)$data;
		if (method_exists($ls_entityClass, 'unmapField')) {
			foreach ($data as $ls_field => $lx_value) {
				$ls_unmappedField = $ls_entityClass::unmapField($ls_field);

				if ($ls_unmappedField === $ls_field) {
					continue;
				}

				$la_data[ $ls_unmappedField ] = $lx_value;
				unset($la_data[ $ls_field ]);
			}
		}

		return [$la_data, (array)$options];
	}
}
