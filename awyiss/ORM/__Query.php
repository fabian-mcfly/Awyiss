<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\Model\Table;
use Cake\Utility\Inflector;


class __Query extends \Cake\ORM\Query {
	public static function subquery (Table|\Cake\ORM\Table $table): static {
		$subquery = parent::subquery($table);
		dd($subquery, __FILE__, __LINE__);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function select ($ax_fields = [], bool $ab_overwrite = FALSE): static {
		$lx_fields = $ax_fields;

		if (is_string($lx_fields)) {
			$lx_fields = $this->underscoreField($lx_fields);
		}
		elseif (is_array($lx_fields)) {
			$lx_fields = $this->underscoreFields($lx_fields);
		}

		return parent::select($lx_fields, $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function selectAllExcept ($ao_table, array $aa_excludedFields, bool $ab_overwrite = FALSE): static {
		dd('selectAllExcept ', __FILE__, __LINE__);

		return parent::selectAllExcept($ao_table, $aa_excludedFields, $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function where ($ax_fields = NULL, array $aa_types = [], bool $ab_overwrite = FALSE) {
		$lx_fields = $ax_fields;

		if (is_string($lx_fields)) {
			$lx_fields = $this->underscoreField($lx_fields);
		}
		elseif (is_array($lx_fields)) {
			$lx_fields = $this->underscoreFields($lx_fields);
		}


		return parent::where($lx_fields, $aa_types, $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function whereNotNull ($fields) {
		dd('whereNotNull ', __FILE__, __LINE__);
		return parent::whereNotNull($fields);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function whereNull ($fields) {
		dd('whereNull ', __FILE__, __LINE__);
		return parent::whereNull($fields);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function whereInList (string $field, array $values, array $options = []) {
		dd('whereInList ', __FILE__, __LINE__);
		return parent::whereInList($field, $values, $options);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function whereNotInList (string $field, array $values, array $options = []) {
		dd('whereNotInList ', __FILE__, __LINE__);
		return parent::whereNotInList($field, $values, $options);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function whereNotInListOrNull (string $field, array $values, array $options = []) {
		dd('whereNotInListOrNull ', __FILE__, __LINE__);
		return parent::whereNotInListOrNull($field, $values, $options);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function andWhere ($conditions, array $aa_types = []) {
		dd('andWhere ', __FILE__, __LINE__);
		return parent::andWhere($conditions, $types);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function order ($ax_fields, $ab_overwrite = FALSE) {
		$lx_fields = $ax_fields;

		if (is_string($lx_fields)) {
			$lx_fields = $this->underscoreField($lx_fields);
		}
		elseif (is_array($lx_fields)) {
			$lx_fields = $this->underscoreFields($lx_fields, TRUE);
		}

		return parent::order($lx_fields, $ab_overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function orderAsc ($field, $ab_overwrite = FALSE) {
		dd('orderAsc ', __FILE__, __LINE__);
		return parent::orderAsc($field, $overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function orderDesc ($field, $ab_overwrite = FALSE) {
		dd('orderDesc ', __FILE__, __LINE__);
		return parent::orderDesc($field, $overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function group ($fields, $ab_overwrite = FALSE) {
		dd('group ', __FILE__, __LINE__);
		return parent::group($fields, $overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function having ($conditions = NULL, $types = [], $ab_overwrite = FALSE) {
		dd('having ', __FILE__, __LINE__);
		return parent::having($conditions, $types, $overwrite);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function andHaving ($conditions, $types = []) {
		dd('andHaving ', __FILE__, __LINE__);
		return parent::andHaving($conditions, $types);
	}


	/**
	 * Transforms the given array so that keys or values (depending on $ab_variableKey)
	 * are written in camelBacked-format.
	 *
	 * @param array $aa_fields
	 * @param bool $ab_variableKey
	 *
	 * @return array
	 */
	protected function underscoreFields (array $aa_fields, bool $ab_variableKey = FALSE): array {
		dd(__LINE__, __FILE__);
		$la_fields = [];

		foreach ($aa_fields as $lx_field => $lx_value) {
			if ($ab_variableKey) {
				$lx_field = $this->underscoreField($lx_field);
			}
			else {
				$lx_value = $this->underscoreField($lx_value);
			}

			$la_fields[ $lx_field ] = $lx_value;
		}

		return $la_fields;
	}


	/**
	 * @param mixed $ax_field
	 *
	 * @return mixed
	 */
	protected function underscoreField (mixed $ax_field): mixed {
		dd(__LINE__, __FILE__);
		if ( ! $ax_field || ! is_string($ax_field) || str_starts_with($ax_field, '_')) {
			return $ax_field;
		}

		if (($li_lastPos = strrpos($ax_field, '.')) !== FALSE) {
			$ls_prefix = substr($ax_field, 0, $li_lastPos);
			$ls_field = substr($ax_field, $li_lastPos + 1);

			return $ls_prefix . '.' . Inflector::underscore($ls_field);
		}

		return Inflector::underscore($ax_field);
	}
}