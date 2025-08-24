<?php declare(strict_types=1);


namespace Awyiss\Migration;


use Cake\Utility\Hash;
use Migrations\Util\ColumnParser as BaseColumnParser;


/**
 * Extends the column parser with an optional default value.
 *
 * Currently unused.
 */
class ColumnParser extends BaseColumnParser {
	/**
	 * Regex used to parse the column definition passed through the shell
	 *
	 * @link https://regex101.com/r/aIrJ5T/1
	 * @var string
	 * @noinspection RegExpRedundantEscape
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
	 * @noinspection RegExpSingleCharAlternation
	 * @noinspection RegExpRedundantEscape
	 */
	protected string $regexpParseField = '/(\w+\??)(?=\[|\()(?:\[([0-9,]+)\])?(?:\((.*)\))?/';


	/**
	 * @inheritDoc
	 */
	public function parseFields(array $arguments): array {
		$la_fields = [];
		$la_arguments = $this->validArguments($arguments);
		foreach ($la_arguments as $ls_field) {
			preg_match($this->regexpParseColumn, $ls_field, $la_matches);
			$ls_field = $la_matches[1];
			$ls_type = Hash::get($la_matches, 2, '');
			$ls_indexType = Hash::get($la_matches, 3);

			$lb_typeIsPk = in_array($ls_type, ['primary', 'primary_key'], true);
			$lb_isPrimaryKey = false;
			if ($lb_typeIsPk || in_array($ls_indexType, ['primary', 'primary_key'], true)) {
				$lb_isPrimaryKey = true;

				if ($lb_typeIsPk) {
					$ls_type = 'primary';
				}
			}

			$lb_nullable = (bool)strpos($ls_type, '?');
			$ls_type = $lb_nullable ? str_replace('?', '', $ls_type) : $ls_type;

			[$ls_type, $lx_length, $lx_default] = $this->getTypeAndLengthAndDefault($ls_field, $ls_type);
			$la_fields[ $ls_field ] = [
				'columnType' => $ls_type,
				'options' => [
					'null' => $lb_nullable,
					'default' => $lx_default,
				],
			];

			if (!empty($lx_length)) {
				if (is_array($lx_length)) {
					[$la_fields[ $ls_field ]['options']['precision'], $la_fields[ $ls_field ]['options']['scale']] = $lx_length;
				}
				else {
					$la_fields[ $ls_field ]['options']['limit'] = $lx_length;
				}
			}

			if ($lb_isPrimaryKey === true && $ls_type === 'integer') {
				$la_fields[ $ls_field ]['options']['autoIncrement'] = true;
			}
		}


		return $la_fields;
	}


	/**
	 * @param string $field
	 * @param string $type
	 * @return array
	 */
	public function getTypeAndLengthAndDefault(string $field, string $type): array {
		$lx_default = null;

		if ($type && preg_match($this->regexpParseField, $type, $la_matches)) {
			if (str_contains($la_matches[2], ',')) {
				$la_matches[2] = explode(',', $la_matches[2]);
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$type = $la_matches[1];
			$li_length = $la_matches[2] ?? null ?: null;
			$lx_default = $la_matches[3] ?? null;
		}

		/** @var string $ls_fieldType */
		$ls_fieldType = $this->getType($field, $type);
		$li_length ??= $this->getLength($ls_fieldType);

		return [$ls_fieldType, $li_length, $lx_default];
	}
}
