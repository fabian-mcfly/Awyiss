<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use InvalidArgumentException;


/**
 * Adds attribute-specific logic to entities
 */
trait EntityOriginalsTrait {
	/**
	 * Holds all fields that have been initially set on instantiation resp. after marking it clean
	 *
	 * @var array<string>
	 */
	protected array $_originalFields = [];


	/**
	 * @inheritDoc
	 */
	public function set ($ax_field, $ax_value = NULL, array $aa_options = []) {
		if (is_string($ax_field) && $ax_field !== '') {
			$guard = FALSE;
			$la_fields = [$ax_field => $ax_value];
		}
		else {
			$guard = TRUE;
			$aa_options = (array) $ax_value;
			$la_fields = $ax_field;
		}

		if ( ! is_array($la_fields)) {
			throw new InvalidArgumentException('Cannot set an empty field');
		}
		$aa_options += ['setter' => TRUE, 'guard' => $guard];

		foreach ($la_fields as $ls_field => $lx_value) {
			$ls_field = (string) $ls_field;
			if ($aa_options['guard'] === TRUE && ! $this->isAccessible($ls_field)) {
				continue;
			}

			$this->setDirty($ls_field, TRUE);

			if ($aa_options['setter']) {
				$ls_setter = static::_accessor($ls_field, 'set');
				if ($ls_setter) {
					$lx_value = $this->{$ls_setter}($lx_value);
				}
			}

			if (
				$this->isOriginalField($ls_field) &&
				!array_key_exists($ls_field, $this->_original) &&
				array_key_exists($ls_field, $this->_fields) &&
				$lx_value !== $this->_fields[ $ls_field ]
			) {
				$this->_original[ $ls_field ] = $this->_fields[ $ls_field ];
			}

			$this->_fields[ $ls_field ] = $lx_value;
		}

		return $this;
	}


	/**
	 * Returns whether a field has an original value
	 *
	 * @param string $as_field
	 *
	 * @return bool
	 */
	public function hasOriginal (string $as_field): bool {
		return array_key_exists($as_field, $this->_original);
	}


	/**
	 * @inheritDoc
	 */
	public function getOriginal (string $as_field, bool $ab_allowFallback = TRUE): mixed {
		if ($as_field === '') {
			throw new InvalidArgumentException('Cannot get an empty field');
		}
		if (array_key_exists($as_field, $this->_original)) {
			return $this->_original[ $as_field ];
		}

		if ( ! $ab_allowFallback) {
			throw new InvalidArgumentException(sprintf('Cannot retrieve original value for field `%s`', $as_field));
		}

		return $this->get($as_field);
	}


	/**
	 * @inheritDoc
	 */
	public function getOriginalValues (): array {
		$la_originals = $this->_original;
		$la_originalKeys = array_keys($la_originals);
		foreach ($this->_fields as $ls_key => $lx_value) {
			if ( ! in_array($ls_key, $la_originalKeys, TRUE) && $this->isOriginalField($ls_key)) {
				$la_originals[ $ls_key ] = $lx_value;
			}
		}

		return $la_originals;
	}


	/**
	 * @inheritDoc
	 */
	public function isOriginalField (string $as_field): bool {
		return in_array($as_field, $this->_originalFields);
	}


	/**
	 * @inheritDoc
	 */
	public function getOriginalFields (): array {
		return $this->_originalFields;
	}


	/**
	 * @inheritDoc
	 */
	public function setOriginalField (string|array $ax_field, bool $merge = TRUE) {
		if ( ! $merge) {
			$this->_originalFields = (array) $ax_field;

			//WIP? Tests with assertEqual fail as long as the values of $this->_originalFields aren't in the same order.
			sort($this->_originalFields);

			return $this;
		}

		$la_fields = (array) $ax_field;
		foreach ($la_fields as $ls_field) {
			if ( ! $this->isOriginalField($ls_field)) {
				$this->_originalFields[] = $ls_field;
			}
		}

		//WIP? Tests with assertEqual fail as long as the values of $this->_originalFields aren't in the same order.
		sort($this->_originalFields);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function unset (array|string $ax_field) {
		return $this->_unset($ax_field);
	}


	/**
	 * @inheritDoc
	 */
	public function _unset (array|string $ax_field) {
		$la_fields = (array) $ax_field;
		foreach ($la_fields as $ls_field) {
			unset($this->_fields[ $ls_field ], $this->_dirty[ $ls_field ]);
		}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function extractOriginal (array $aa_fields): array {
		$la_originals = [];
		foreach ($aa_fields as $ls_field) {
			if ($this->hasOriginal($ls_field)) {
				$la_originals[ $ls_field ] = $this->getOriginal($ls_field);
			}
			elseif ($this->isOriginalField($ls_field)) {
				$la_originals[ $ls_field ] = $this->get($ls_field);
			}
		}

		return $la_originals;
	}


	/**
	 * @inheritDoc
	 */
	public function extractOriginalChanged (array $aa_fields): array {
		$la_originals = [];
		foreach ($aa_fields as $ls_field) {
			if ( ! $this->hasOriginal($ls_field)) {
				continue;
			}

			$original = $this->getOriginal($ls_field);
			if ($original !== $this->get($ls_field)) {
				$la_originals[ $ls_field ] = $original;
			}
		}

		return $la_originals;
	}


	/**
	 * @inheritDoc
	 */
	public function setDirty (string $as_field, bool $ab_isDirty = TRUE) {
		if ($ab_isDirty === FALSE) {
			$this->setOriginalField($as_field);

			unset($this->_dirty[ $as_field ], $this->_original[ $as_field ]);

			return $this;
		}

		$this->_dirty[ $as_field ] = TRUE;
		unset($this->_errors[ $as_field ], $this->_invalid[ $as_field ]);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function clean (): void {
		$this->_dirty = [];
		$this->_errors = [];
		$this->_invalid = [];
		$this->_original = [];
		$this->setOriginalField(array_keys($this->_fields), FALSE);
	}
}