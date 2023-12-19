<?php declare(strict_types=1);


namespace AwyissBake\Util;


use Cake\Utility\Hash;


/**
 * Extends the column parser with an optional default value.
 *
 * Currently not in use.
 */
class ColumnParser extends \Migrations\Util\ColumnParser {
	/**
	 * Regex used to parse the column definition passed through the shell
	 *
	 * @link https://regex101.com/r/aIrJ5T/1
	 * @var string
	 */
	protected string $regexpParseColumn = '/
		^
		(\w+)
		(?::(\w+\??
			(?:\[
				(?:[0-9]|[1-9][0-9]+)
				(?:,(?:[0-9]|[1-9][0-9]+))?
			\])?(?:\(.*?\))?
		))?
		(?::(\w+))?
		(?::(\w+))?
		$
		/x';
	/**
	 * Regex used to parse the field type and length
	 *
	 * @link https://regex101.com/r/9Poorq/2
	 * @var string
	 */
	protected string $regexpParseField = '/(\w+\??)(?=\[|\()(?:\[([0-9,]+)\])?(?:\((.*)\))?/';


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function parseFields (array $aa_arguments): array {
		$la_fields = [];
		$la_arguments = $this->validArguments($aa_arguments);
		foreach ($la_arguments as $ls_field) {
			preg_match($this->regexpParseColumn, $ls_field, $la_matches);
			$ls_field = $la_matches[1];
			$ls_type = Hash::get($la_matches, 2, '');
			$ls_indexType = Hash::get($la_matches, 3);

			$lb_typeIsPk = in_array($ls_type, ['primary', 'primary_key'], TRUE);
			$lb_isPrimaryKey = FALSE;
			if ($lb_typeIsPk || in_array($ls_indexType, ['primary', 'primary_key'], TRUE)) {
				$lb_isPrimaryKey = TRUE;

				if ($lb_typeIsPk) {
					$ls_type = 'primary';
				}
			}

			$lb_nullable = (bool) strpos($ls_type, '?');
			$ls_type = $lb_nullable ? str_replace('?', '', $ls_type) : $ls_type;

			[$ls_type, $lx_length, $lx_default] = $this->getTypeAndLengthAndDefault($ls_field, $ls_type);
			$la_fields[ $ls_field ] = [
				'columnType' => $ls_type,
				'options' => [
					'null' => $lb_nullable,
					'default' => $lx_default,
				],
			];

			if ($lx_length !== NULL) {
				if (is_array($lx_length)) {
					[$la_fields[ $ls_field ]['options']['precision'], $la_fields[ $ls_field ]['options']['scale']] = $lx_length;
				}
				else {
					$la_fields[ $ls_field ]['options']['limit'] = $lx_length;
				}
			}

			if ($lb_isPrimaryKey === TRUE && $ls_type === 'integer') {
				$la_fields[ $ls_field ]['options']['autoIncrement'] = TRUE;
			}
		}

		return $la_fields;
	}


	/**
	 * @param $as_field
	 * @param $as_type
	 *
	 * @return array
	 */
	public function getTypeAndLengthAndDefault ($as_field, $as_type): array {
		/*$aa_type = [];
		$aa_type[] = 'tinyinteger';
		$aa_type[] = 'tinyinteger?';
		$aa_type[] = 'tinyinteger(foo(bar)baz)';
		$aa_type[] = 'tinyinteger[123,3](foo(bar)baz)';
		$aa_type[] = 'tinyinteger[123,3]';
		$aa_type[] = 'tinyinteger?[123,3]';
		$aa_type[] = 'tinyinteger?(foo(bar)baz)';
		$aa_type[] = 'tinyinteger?[123,3](foo(bar)baz)';

		foreach ($aa_type AS $as_type) {
			preg_match($this->regexpParseField, $as_type, $la_matches);
			dump($la_matches);
		}
		exit;*/

		if ($as_type && preg_match($this->regexpParseField, $as_type, $la_matches)) {
			if (str_contains($la_matches[2], ',')) {
				$la_matches[2] = explode(',', $la_matches[2]);
			}

			return [$la_matches[1], $la_matches[2], $la_matches[3] ?? NULL];
		}

		/** @var string $ls_fieldType */
		$ls_fieldType = $this->getType($as_field, $as_type);
		$li_length = $this->getLength($ls_fieldType);

		return [$ls_fieldType, $li_length];
	}
}
