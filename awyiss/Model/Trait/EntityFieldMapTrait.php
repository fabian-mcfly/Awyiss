<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Cake\Datasource\EntityInterface;


/**
 * Adds logic that allows entity property names to differ from the database ones.
 *
 * All methods in this trait are copies from Cake's EntityTrait with the addition of mapping the provided data
 * using `static::mapFields` resp `static::mapField`
 *
 * It also offers the `unmapField`- and `unmapFields`-methods to be used whereever one needs the database name
 * of a field name.
 *
 * @see static::mapField()
 * @see static::mapFields()
 * @see \Cake\Datasource\EntityTrait
 */
trait EntityFieldMapTrait {
	/**
	 * Using this property, you can provide an array to map db columns to property names.
	 * Keys are the real names in the table and the values their names in the application.
	 * Column names not present in this array will be used as-is.
	 *
	 * @var array <string, string>
	 */
	protected static array $fieldMap = [];


	/**
	 * Constructor.
	 *
	 * @param array $properties
	 * @param array $options
	 */
	public function __construct(array $properties = [], array $options = []) {
		$la_properties = static::mapFields($properties, true);

		parent::__construct($la_properties, $options);
	}


	/**
	 * @inheritDoc
	 */
	public function &get(string $field) {
		return parent::get(static::mapField($field));
	}


	/**
	 * @inheritDoc
	 */
	public function set(array|string $field, mixed $value = null, array $options = []): EntityInterface {
		$lx_field = $field;
		if (is_string($lx_field)) {
			$lx_field = static::mapField($lx_field);
		}
		elseif (is_array($field) && $field) {
			$lx_field = static::mapFields($field, true);
		}


		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::set($lx_field, $value, $options);
	}


	/**
	 * @inheritDoc
	 */
	public function unset(array|string $field): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::unset(static::mapFields((array)$field));
	}


	/**
	 * @inheritDoc
	 */
	public function setHidden(array $fields, bool $merge = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setHidden(static::mapFields($fields), $merge);
	}


	/**
	 * @inheritDoc
	 */
	public function setVirtual(array $fields, bool $merge = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setVirtual(static::mapFields($fields), $merge);
	}


	/**
	 * @inheritDoc
	 * @param array|string $field
	 * @return bool
	 */
	public function has(array|string $field): bool {
		$la_fields = static::mapFields((array)$field);

		foreach ($la_fields as $ls_field) {
			if (!array_key_exists($ls_field, $this->_fields) && !static::_accessor($ls_field, 'get')) {
				return false;
			}
		}


		return true;
	}


	/**
	 * Returns whether a field has an original value
	 *
	 * @param string $field
	 * @return bool
	 */
	public function hasOriginal(string $field): bool {
		return array_key_exists(static::mapField($field), $this->_original);
	}


	/**
	 * @inheritDoc
	 */
	public function getOriginal(string $field, bool $allowFallback = false): mixed {
		return parent::getOriginal(static::mapField($field), $allowFallback);
	}


	/**
	 * @inheritDoc
	 */
	public function extract(?array $fields = [], bool $onlyDirty = false, bool $unmapped = true): array {
		$la_fields = $fields ?: array_keys($this->_fields);
		$la_extracted = parent::extract(static::mapFields($la_fields), $onlyDirty);

		if ($unmapped) {
			$la_extracted = static::unmapFields($la_extracted, true);
		}


		return $la_extracted;
	}


	/**
	 * @inheritDoc
	 */
	public function extractOriginal(?array $fields = [], bool $unmapped = true): array {
		$la_fields = $fields ?: array_keys($this->_fields);
		$la_extracted = parent::extractOriginal(static::mapFields($la_fields));

		if ($unmapped) {
			$la_extracted = static::unmapFields($la_extracted, true);
		}


		return $la_extracted;
	}


	/**
	 * @inheritDoc
	 * @param array|null $fields
	 * @param bool $includeUnknownFields If set, returns fields that weren't part of the original entity
	 * @param bool $unmapped
	 * @return array
	 */
	public function extractOriginalChanged(?array $fields = [], bool $includeUnknownFields = false, bool $unmapped = true): array {
		$la_fields = $fields ?: array_keys($this->_fields);
		$la_extracted = parent::extractOriginalChanged(static::mapFields($la_fields));

		//Include fields that aren't part of the entity but requested.
		if ($includeUnknownFields) {
			foreach ($fields as $ls_field) {
				if (
					!array_key_exists($ls_field, $la_extracted) &&
					!in_array($ls_field, $this->_originalFields)
				) {
					$la_extracted[ $ls_field ] = null;
				}
			}
		}

		if ($unmapped) {
			$la_extracted = static::unmapFields($la_extracted, true);
		}


		return $la_extracted;
	}


	/**
	 * @inheritDoc
	 */
	public function setDirty(string $field, bool $isDirty = true): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setDirty(static::mapField($field), $isDirty);
	}


	/**
	 * @inheritDoc
	 */
	public function isDirty(?string $field = null): bool {
		return parent::isDirty(static::mapField($field));
	}


	/**
	 * @inheritDoc
	 */
	public function getError(string $field): array {
		return parent::getError(static::mapField($field));
	}


	/**
	 * @inheritDoc
	 */
	public function setErrors(array $errors, bool $overwrite = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setErrors(static::mapFields($errors, true), $overwrite);
	}


	/**
	 * @inheritDoc
	 */
	public function setError(string $field, array|string $errors, bool $overwrite = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setError(static::mapField($field), $errors, $overwrite);
	}


	/**
	 * @inheritDoc
	 */
	public function getInvalidField(string $field): mixed {
		return parent::getInvalidField(static::mapField($field));
	}


	/**
	 * @inheritDoc
	 */
	public function setInvalid(array $fields, bool $overwrite = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setInvalid(static::mapFields($fields, true), $overwrite);
	}


	/**
	 * @inheritDoc
	 */
	public function setInvalidField(string $field, mixed $value): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setInvalidField(static::mapField($field), $value);
	}


	/**
	 * @inheritDoc
	 */
	public function setAccess(array|string $field, bool $accessible): EntityInterface {
		if ($field === '*') {
			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return parent::setAccess($field, $accessible);
		}


		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setAccess(static::mapFields((array)$field), $accessible);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible(string $field): bool {
		return parent::isAccessible(static::mapField($field));
	}


	/**
	 * @inheritDoc
	 */
	protected function _nestedErrors(string $field): array {
		return parent::_nestedErrors(static::mapField($field));
	}


	/**
	 * @param string $field
	 * @param string $mappedField
	 * @return void
	 */
	public static function addFieldMapping(string $field, string $mappedField): void {
		static::$fieldMap[ $field ] = $mappedField;
	}


	/**
	 * Transforms the given value column name to a field name, defined by static::$fieldMap
	 *
	 * @param string $field
	 * @return string|null
	 */
	public static function mapField(?string $field): ?string {
		if (empty($field)) {
			return $field;
		}


		return static::$fieldMap[ $field ] ?? $field;
	}


	/**
	 * Transforms the given array according to static::$fieldMap
	 *
	 * @param array $fields
	 * @param bool $mapKeys
	 * @return array
	 */
	public static function mapFields(array $fields, bool $mapKeys = false): array {
		$la_fields = [];

		foreach ($fields as $lx_field => $lx_value) {
			$lx_mapped = ($mapKeys ? 'lx_field' : 'lx_value');
			$$lx_mapped = static::mapField($$lx_mapped);

			$la_fields[ $lx_field ] = $lx_value;
		}


		return $la_fields;
	}


	/**
	 * Transforms the given value field name to a column name, defined by static::$fieldMap
	 *
	 * @param string $field
	 * @return string|null
	 */
	public static function unmapField(?string $field): ?string {
		if (empty($field)) {
			return $field;
		}

		$ls_key = array_search($field, static::$fieldMap);
		if ($ls_key !== false) {
			return $ls_key;
		}


		return $field;
	}


	/**
	 * Transforms the given array according to static::$fieldMap.
	 * This method reverses the transformation
	 *
	 * @param array $fields
	 * @param bool $mapKeys
	 * @return array
	 * @noinspection PhpUnused
	 */
	public static function unmapFields(array $fields, bool $mapKeys = false): array {
		$la_fields = [];

		foreach ($fields as $lx_field => $lx_value) {
			$lx_mapped = ($mapKeys ? 'lx_field' : 'lx_value');
			$$lx_mapped = static::unmapField($$lx_mapped);

			$la_fields[ $lx_field ] = $lx_value;
		}


		return $la_fields;
	}
}
