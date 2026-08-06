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
		(?::default\[([^\]]+)\])?
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
		$fields = [];
		$arguments = $this->validArguments($arguments);
		foreach ($arguments as $field) {
			preg_match($this->regexpParseColumn, $field, $matches);
			$field = $matches[1];
			$type = Hash::get($matches, 2, '');
			$indexType = Hash::get($matches, 3);

			$typeIsPk = in_array($type, ['primary', 'primary_key'], true);
			$isPrimaryKey = false;
			if ($typeIsPk || in_array($indexType, ['primary', 'primary_key'], true)) {
				$isPrimaryKey = true;

				if ($typeIsPk) {
					$type = 'primary';
				}
			}

			// Handle references - convert to integer type
			$isReference = in_array($type, ['references', 'references?'], true);
			if ($isReference) {
				$type = str_contains($type, '?') ? 'integer?' : 'integer';
			}

			$nullable = str_contains($type, '?');
			$type = $nullable ? str_replace('?', '', $type) : $type;

			[$type, $length, $default] = $this->getTypeAndLengthAndDefault($field, $type);
			$fields[ $field ] = [
				'columnType' => $type,
				'options' => [
					'null' => $nullable,
					'default' => $default,
				],
			];

			if ($length !== null) {
				if (is_array($length)) {
					[$fields[ $field ]['options']['precision'], $fields[ $field ]['options']['scale']] = $length;
				}
				else {
					$fields[ $field ]['options']['limit'] = $length;
				}
			}

			if ($isPrimaryKey === true && $type === 'integer') {
				$fields[ $field ]['options']['autoIncrement'] = true;
			}
		}


		return $fields;
	}


	/**
	 * @param string $field
	 * @param string $type
	 * @return array
	 */
	public function getTypeAndLengthAndDefault(string $field, string $type): array {
		$default = null;

		if ($type && preg_match($this->regexpParseField, $type, $matches)) {
			if (str_contains($matches[2], ',')) {
				$matches[2] = explode(',', $matches[2]);
			}

			$type = $matches[1];
			$length = $matches[2] ?? null ?: null;
			$default = $matches[3] ?? null;
		}

		/** @var string $fieldType */
		$fieldType = match ($type) {
			'mediumtext' => 'mediumtext',
			'longtext' => 'longtext',
			default => $this->getType($field, $type)
		};

		$length ??= $this->getLength($fieldType);

		return [$fieldType, $length, $default];
	}
}
