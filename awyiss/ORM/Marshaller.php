<?php declare(strict_types=1);


namespace Awyiss\ORM;


use ArrayObject;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\InvalidPropertyInterface;
use Cake\ORM\Marshaller as BaseMarshaller;
use Cake\ORM\PropertyMarshalInterface;
use InvalidArgumentException;


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
	 * Re-implemented 1:1 to override how the result of PropertyMarshalInterface::buildMarshalMap()
	 * is merged into the map built by Marshaller::_buildPropertyMap().
	 * Instead of using the `+` operator, we use `array_merge()`, to allow behaviors to override the default
	 * marshalling of properties provided by the table's schema and associations.
	 *
	 * @inheritDoc
	 */
	protected function _buildPropertyMap(array $data, array $options): array {
		$map = [];
		$schema = $this->_table->getSchema();

		// Is a concrete column?
		foreach (array_keys($data) as $prop) {
			$prop = (string)$prop;
			$columnType = $schema->getColumnType($prop);
			if ($columnType) {
				$map[ $prop ] = TypeFactory::build($columnType)->marshal(...);
			}
		}

		// Map associations
		$options['associated'] ??= [];
		$include = $this->_normalizeAssociations($options['associated']);
		foreach ($include as $key => $nested) {
			if (is_int($key) && is_scalar($nested)) {
				$key = $nested;
				$nested = [];
			}

			$stringifiedKey = (string)$key;
			// If the key is not a special field like _ids or _joinData
			// it is a missing association that we should error on.
			if (!$this->_table->hasAssociation($stringifiedKey)) {
				if (
					!str_starts_with($stringifiedKey, '_') && (!isset($options['junctionProperty']) || $options['junctionProperty'] !== $stringifiedKey)
				) {
					throw new InvalidArgumentException(
						sprintf(
							'Cannot marshal data for `%s` association. It is not associated with `%s`.',
							$stringifiedKey,
							$this->_table->getAlias(),
						)
					);
				}
				continue;
			}
			$assoc = $this->_table->getAssociation($stringifiedKey);

			if (isset($options['forceNew'])) {
				$nested['forceNew'] = $options['forceNew'];
			}
			if (isset($options['isMerge'])) {
				$callback = function (
					$value,
					EntityInterface $entity,
				) use (
					$assoc,
					$nested,
				): array|EntityInterface|null {
					$options = $nested + ['associated' => [], 'association' => $assoc];

					return $this->_mergeAssociation(
						$this->fieldValue($entity, $assoc->getProperty()),
						$assoc,
						$value,
						$options,
					);
				};
			}
			else {
				$callback = function ($value) use ($assoc, $nested): array|EntityInterface|null {
					$options = $nested + ['associated' => []];

					return $this->_marshalAssociation($assoc, $value, $options);
				};
			}
			$map[ $assoc->getProperty() ] = $callback;
		}

		$behaviors = $this->_table->behaviors();
		foreach ($behaviors->loaded() as $name) {
			$behavior = $behaviors->get($name);
			if ($behavior instanceof PropertyMarshalInterface) {
				$map = array_merge($map, $behavior->buildMarshalMap($this, $map, $options));
			}
		}

		return $map;
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

		// Convert back to arrays
		$data = (array)$data;
		$options = (array)$options;

		return [$data, $options];
	}
}
