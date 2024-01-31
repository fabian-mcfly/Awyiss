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
	 * @param array $aa_properties
	 * @param array $aa_options
	 */
	public function __construct(array $aa_properties = [], array $aa_options = []) {
		$la_properties = static::mapFields($aa_properties, true);

		parent::__construct($la_properties, $aa_options);
	}


	/**
	 * @inheritDoc
	 */
	public function &get(string $as_field) {
		return parent::get(static::mapField($as_field));
	}


	/**
	 * @inheritDoc
	 */
	public function set(array|string $ax_field, mixed $ax_value = null, array $aa_options = []): EntityInterface {
		$lx_field = $ax_field;
		if (is_string($lx_field)) {
			$lx_field = static::mapField($lx_field);
		}
		elseif (is_array($ax_field) && $ax_field) {
			$lx_field = static::mapFields($ax_field, true);
		}


		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::set($lx_field, $ax_value, $aa_options);
	}


	/**
	 * @inheritDoc
	 */
	public function unset(array|string $ax_field): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::unset(static::mapFields((array)$ax_field));
	}


	/**
	 * @inheritDoc
	 */
	public function setHidden(array $aa_fields, bool $ab_merge = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setHidden(static::mapFields($aa_fields), $ab_merge);
	}


	/**
	 * @inheritDoc
	 */
	public function setVirtual(array $aa_fields, bool $ab_merge = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setVirtual(static::mapFields($aa_fields), $ab_merge);
	}


	/**
	 * @inheritDoc
	 * @param array|string $as_field
	 * @return bool
	 */
	public function has(array|string $as_field): bool {
		$la_fields = static::mapFields((array)$as_field);

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
	 * @param string $as_field
	 * @return bool
	 */
	public function hasOriginal(string $as_field): bool {
		return array_key_exists(static::mapField($as_field), $this->_original);
	}


	/**
	 * @inheritDoc
	 */
	public function getOriginal(string $as_field, bool $ab_allowFallback = false): mixed {
		return parent::getOriginal(static::mapField($as_field), $ab_allowFallback);
	}


	/**
	 * @inheritDoc
	 */
	public function extract(?array $aa_fields = [], bool $ab_onlyDirty = false, bool $ab_unmapped = true): array {
		$la_fields = $aa_fields ?: array_keys($this->_fields);
		$la_extracted = parent::extract(static::mapFields($la_fields), $ab_onlyDirty);

		if ($ab_unmapped) {
			$la_extracted = static::unmapFields($la_extracted, true);
		}


		return $la_extracted;
	}


	/**
	 * @inheritDoc
	 */
	public function extractOriginal(?array $aa_fields = [], bool $ab_unmapped = true): array {
		$la_fields = $aa_fields ?: array_keys($this->_fields);
		$la_extracted = parent::extractOriginal(static::mapFields($la_fields));

		if ($ab_unmapped) {
			$la_extracted = static::unmapFields($la_extracted, true);
		}


		return $la_extracted;
	}


	/**
	 * @inheritDoc
	 * @param array|null $aa_fields
	 * @param bool $ab_includeUnknownFields If set, returns fields that weren't part of the original entity
	 * @param bool $ab_unmapped
	 * @return array
	 */
	public function extractOriginalChanged(?array $aa_fields = [], bool $ab_includeUnknownFields = false, bool $ab_unmapped = true): array {
		$la_fields = $aa_fields ?: array_keys($this->_fields);
		$la_extracted = parent::extractOriginalChanged(static::mapFields($la_fields));

		//Include fields that aren't part of the entity but requested.
		if ($ab_includeUnknownFields) {
			foreach ($aa_fields as $ls_field) {
				if (
					!array_key_exists($ls_field, $la_extracted) &&
					!in_array($ls_field, $this->_originalFields)
				) {
					$la_extracted[ $ls_field ] = null;
				}
			}
		}

		if ($ab_unmapped) {
			$la_extracted = static::unmapFields($la_extracted, true);
		}


		return $la_extracted;
	}


	/**
	 * @inheritDoc
	 */
	public function setDirty(string $as_field, bool $ab_isDirty = true): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setDirty(static::mapField($as_field), $ab_isDirty);
	}


	/**
	 * @inheritDoc
	 */
	public function isDirty(?string $as_field = null): bool {
		return parent::isDirty(static::mapField($as_field));
	}


	/**
	 * @inheritDoc
	 */
	public function getError(string $as_field): array {
		return parent::getError(static::mapField($as_field));
	}


	/**
	 * @inheritDoc
	 */
	public function setErrors(array $aa_errors, bool $ab_overwrite = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setErrors(static::mapFields($aa_errors, true), $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 */
	public function setError(string $as_field, $ax_errors, bool $ab_overwrite = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setError(static::mapField($as_field), $ax_errors, $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 */
	public function getInvalidField(string $as_field): mixed {
		return parent::getInvalidField(static::mapField($as_field));
	}


	/**
	 * @inheritDoc
	 */
	public function setInvalid(array $aa_fields, bool $ab_overwrite = false): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setInvalid(static::mapFields($aa_fields, true), $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 */
	public function setInvalidField(string $as_field, mixed $ax_value): EntityInterface {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setInvalidField(static::mapField($as_field), $ax_value);
	}


	/**
	 * @inheritDoc
	 */
	public function setAccess(array|string $as_field, bool $ab_accessible): EntityInterface {
		if ($as_field === '*') {
			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return parent::setAccess($as_field, $ab_accessible);
		}


		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::setAccess(static::mapFields((array)$as_field), $ab_accessible);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible(string $as_field): bool {
		return parent::isAccessible(static::mapField($as_field));
	}


	/**
	 * @inheritDoc
	 */
	protected function _nestedErrors(string $as_field): array {
		return parent::_nestedErrors(static::mapField($as_field));
	}


	/**
	 * @param string $as_field
	 * @param string $as_mappedField
	 * @return void
	 */
	public static function addFieldMapping(string $as_field, string $as_mappedField): void {
		static::$fieldMap[ $as_field ] = $as_mappedField;
	}


	/**
	 * Transforms the given value column name to a field name, defined by static::$fieldMap
	 *
	 * @param string $as_field
	 * @return string|null
	 */
	public static function mapField(?string $as_field): ?string {
		if (empty($as_field)) {
			return $as_field;
		}


		return static::$fieldMap[ $as_field ] ?? $as_field;
	}


	/**
	 * Transforms the given array according to static::$fieldMap
	 *
	 * @param array $aa_fields
	 * @param bool $ab_mapKeys
	 * @return array
	 */
	public static function mapFields(array $aa_fields, bool $ab_mapKeys = false): array {
		$la_fields = [];

		foreach ($aa_fields as $lx_field => $lx_value) {
			$lx_mapped = ($ab_mapKeys ? 'lx_field' : 'lx_value');
			$$lx_mapped = static::mapField($$lx_mapped);

			$la_fields[ $lx_field ] = $lx_value;
		}


		return $la_fields;
	}


	/**
	 * Transforms the given value field name to a column name, defined by static::$fieldMap
	 *
	 * @param string $as_field
	 * @return string|null
	 */
	public static function unmapField(?string $as_field): ?string {
		if (empty($as_field)) {
			return $as_field;
		}

		$ls_key = array_search($as_field, static::$fieldMap);
		if ($ls_key !== false) {
			return $ls_key;
		}


		return $as_field;
	}


	/**
	 * Transforms the given array according to static::$fieldMap.
	 * This method reverses the transformation
	 *
	 * @param array $aa_fields
	 * @param bool $ab_mapKeys
	 * @return array
	 * @noinspection PhpUnused
	 */
	public static function unmapFields(array $aa_fields, bool $ab_mapKeys = false): array {
		$la_fields = [];

		foreach ($aa_fields as $lx_field => $lx_value) {
			$lx_mapped = ($ab_mapKeys ? 'lx_field' : 'lx_value');
			$$lx_mapped = static::unmapField($$lx_mapped);

			$la_fields[ $lx_field ] = $lx_value;
		}


		return $la_fields;
	}
}
