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
	 * Implemented 1:1 to
	 * - allow `accessibleFields` to be passed as string or array
	 * - pass `$options['setter']` to `$entity::set()`, to allow skipping setters when merging default values
	 * - skip the `beforeMarshal` and `afterMarshal` events if `$options['events']` is given and not `true`.
	 *
	 * @inheritDoc
	 */
	public function merge(EntityInterface $entity, array $data, array $options = []): EntityInterface {
		[$data, $options] = $this->_prepareDataAndOptions($data, $options);
		$dispatchEvents = $options['events'] ?? true;
		unset($options['events']);

		$isNew = $entity->isNew();
		$keys = [];

		if (!$isNew) {
			$keys = $entity->extract((array)$this->_table->getPrimaryKey());
		}

		if (isset($options['accessibleFields'])) {
			foreach ((array)$options['accessibleFields'] as $key => $value) {
				if (is_int($key) && is_string($value)) {
					// If the value is a string, use it as the key
					$key = $value;
					$value = true;
				}

				// Map the field name if the entity class has a `mapField()` method
				if (method_exists($entity, 'mapField')) {
					// Map the field name
					$key = $entity::mapField($key);
				}

				$entity->setAccess($key, $value);
			}
		}

		$fieldsToValidate = $options['strictFields'] ? (array)$options['fields'] : [];
		$context = [
			'entity' => $entity,
			'fields' => $fieldsToValidate,
		];

		$errors = $this->_validate($data + $keys, $options['validate'], $isNew, $context);
		$options['isMerge'] = true;
		$propertyMap = $this->_buildPropertyMap($data, $options);
		$properties = $this->buildProperties($entity, $propertyMap, $data, $errors);

		$entity->setErrors($errors);

		if (!isset($options['fields'])) {
			if (method_exists($entity, 'patch')) {
				$entity->patch($properties, ['setter' => $options['setter'] ?? true]);
			}
			else {
				$entity->set($properties, ['setter' => $options['setter'] ?? true]);
			}

			foreach ($properties as $field => $value) {
				if ($value instanceof EntityInterface) {
					$entity->setDirty($field, $value->isDirty());
				}
			}

			if ($dispatchEvents === true) {
				$this->dispatchAfterMarshal($entity, $data, $options);
			}

			return $entity;
		}

		$fields = (array)$options['fields'];
		// Map the property keys if the entity class has a `mapFields()` method
		if (method_exists($entity, 'mapFields')) {
			$fields = $entity::mapFields($fields);
			$properties = $entity::mapFields($properties, true);
		}

		foreach ($fields as $field) {
			assert(is_string($field));
			if (!array_key_exists($field, $properties)) {
				continue;
			}
			$entity->set($field, $properties[ $field ], ['setter' => $options['setter'] ?? true]);
			if ($properties[ $field ] instanceof EntityInterface) {
				$entity->setDirty($field, $properties[ $field ]->isDirty());
			}
		}

		if ($dispatchEvents === true) {
			$this->dispatchAfterMarshal($entity, $data, $options);
		}

		return $entity;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $propertyMap
	 * @param array $data
	 * @param array $errors
	 * @return array
	 */
	protected function buildProperties(EntityInterface $entity, array $propertyMap, array $data, array $errors): array {
		$properties = [];

		foreach ($data as $key => $value) {
			if (!empty($errors[ $key ])) {
				if ($entity instanceof InvalidPropertyInterface) {
					$entity->setInvalidField($key, $value);
				}
				continue;
			}

			if (isset($propertyMap[ $key ])) {
				$method = $propertyMap[ $key ];
				$value = $method($value, $entity);
			}

			$properties[ $key ] = $value;
		}

		return $properties;
	}


	/**
	 * Re-implemented to
	 * - skip the event if `$options['events']` is set to `false`.
	 * - unmap field names if the entity class has a `unmapField()` method.
	 *
	 * @inheritDoc
	 */
	protected function _prepareDataAndOptions(array $data, array $options): array {
		$options += ['validate' => true, 'fields' => null, 'strictFields' => false];

		$tableName = $this->_table->getAlias();
		if (isset($data[ $tableName ]) && is_array($data[ $tableName ])) {
			$data += $data[ $tableName ];
			unset($data[ $tableName ]);
		}

		if (($options['events'] ?? true) === true) {
			// Convert to ArrayObject to allow modification in the event
			$data = new ArrayObject($data);
			$options = new ArrayObject($options);
			$this->_table->dispatchEvent('Model.beforeMarshal', compact('data', 'options'));
		}

		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $this->_table->getEntityClass();

		// Convert back to arrays
		$data = (array)$data;
		$options = (array)$options;

		if (method_exists($entityClass, 'unmapField')) {
			foreach ($data as $field => $value) {
				$unmappedField = $entityClass::unmapField($field);

				if ($unmappedField === $field) {
					continue;
				}

				$data[ $unmappedField ] = $value;
				unset($data[ $field ]);
			}
		}

		return [$data, $options];
	}
}
