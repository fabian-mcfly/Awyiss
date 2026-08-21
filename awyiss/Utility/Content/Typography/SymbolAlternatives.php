<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography;


use IntlChar;


/**
 * Resolves equivalent representations for a single symbol.
 *
 * The returned list always has a stable order:
 * - Unicode symbol
 * - Decimal numeric HTML entity
 * - Hex numeric HTML entity
 * - All known named HTML entities (if known)
 */
class SymbolAlternatives {
	/**
	 * Preferred named HTML entities mapped to their Unicode code points.
	 *
	 * This list intentionally uses one preferred name per symbol to keep
	 * output deterministic.
	 *
	 * @var array<string, int>
	 */
	protected const array NAMED_ENTITY_CODEPOINTS = [
		'quot' => 34,
		'amp' => 38,
		'apos' => 39,
		'lt' => 60,
		'gt' => 62,
		'nbsp' => 160,
		'excl' => 33,
		'num' => 35,
		'dollar' => 36,
		'percnt' => 37,
		'lpar' => 40,
		'rpar' => 41,
		'ast' => 42,
		'plus' => 43,
		'comma' => 44,
		'period' => 46,
		'slash' => 47,
		'colon' => 58,
		'semi' => 59,
		'quest' => 63,
		'commat' => 64,
		'lsqb' => 91,
		'bsol' => 92,
		'rsqb' => 93,
		'grave' => 96,
		'lcub' => 123,
		'vert' => 124,
		'rcub' => 125,
		'iexcl' => 161,
		'cent' => 162,
		'pound' => 163,
		'curren' => 164,
		'yen' => 165,
		'brvbar' => 166,
		'sect' => 167,
		'uml' => 168,
		'copy' => 169,
		'ordf' => 170,
		'laquo' => 171,
		'not' => 172,
		'shy' => 173,
		'reg' => 174,
		'macr' => 175,
		'deg' => 176,
		'plusmn' => 177,
		'sup2' => 178,
		'sup3' => 179,
		'acute' => 180,
		'micro' => 181,
		'para' => 182,
		'middot' => 183,
		'cedil' => 184,
		'sup1' => 185,
		'ordm' => 186,
		'raquo' => 187,
		'frac14' => 188,
		'frac12' => 189,
		'frac34' => 190,
		'iquest' => 191,
		'times' => 215,
		'divide' => 247,
		'fnof' => 402,
		'circ' => 710,
		'tilde' => 732,
		'ensp' => 8194,
		'emsp' => 8195,
		'thinsp' => 8201,
		'zwnj' => 8204,
		'zwj' => 8205,
		'lrm' => 8206,
		'rlm' => 8207,
		'ndash' => 8211,
		'mdash' => 8212,
		'lsquo' => 8216,
		'rsquo' => 8217,
		'sbquo' => 8218,
		'ldquo' => 8220,
		'rdquo' => 8221,
		'bdquo' => 8222,
		'dagger' => 8224,
		'Dagger' => 8225,
		'bull' => 8226,
		'hellip' => 8230,
		'permil' => 8240,
		'prime' => 8242,
		'Prime' => 8243,
		'lsaquo' => 8249,
		'rsaquo' => 8250,
		'oline' => 8254,
		'frasl' => 8260,
		'euro' => 8364,
		'image' => 8465,
		'weierp' => 8472,
		'real' => 8476,
		'trade' => 8482,
		'alefsym' => 8501,
		'larr' => 8592,
		'uarr' => 8593,
		'rarr' => 8594,
		'darr' => 8595,
		'harr' => 8596,
		'crarr' => 8629,
		'lArr' => 8656,
		'uArr' => 8657,
		'rArr' => 8658,
		'dArr' => 8659,
		'hArr' => 8660,
		'forall' => 8704,
		'part' => 8706,
		'exist' => 8707,
		'empty' => 8709,
		'nabla' => 8711,
		'isin' => 8712,
		'notin' => 8713,
		'ni' => 8715,
		'prod' => 8719,
		'sum' => 8721,
		'minus' => 8722,
		'lowast' => 8727,
		'radic' => 8730,
		'prop' => 8733,
		'infin' => 8734,
		'ang' => 8736,
		'and' => 8743,
		'or' => 8744,
		'cap' => 8745,
		'cup' => 8746,
		'int' => 8747,
		'there4' => 8756,
		'sim' => 8764,
		'cong' => 8773,
		'asymp' => 8776,
		'ne' => 8800,
		'equiv' => 8801,
		'le' => 8804,
		'ge' => 8805,
		'sub' => 8834,
		'sup' => 8835,
		'nsub' => 8836,
		'sube' => 8838,
		'supe' => 8839,
		'oplus' => 8853,
		'otimes' => 8855,
		'perp' => 8869,
		'sdot' => 8901,
		'lceil' => 8968,
		'rceil' => 8969,
		'lfloor' => 8970,
		'rfloor' => 8971,
		'lang' => 9001,
		'rang' => 9002,
		'loz' => 9674,
		'spades' => 9824,
		'clubs' => 9827,
		'hearts' => 9829,
		'diams' => 9830,
	];


	/**
	 * Reverse lookup cache from code point to all known named entities.
	 *
	 * @var array<int, array<int, string>>|null
	 */
	protected static ?array $codepointToNamedEntities = null;


	/**
	 * Returns a stable list of equivalent representations for one symbol.
	 *
	 * @param string $symbol Unicode symbol, named entity (`&laquo;`), decimal numeric entity (`&#171;`) or hex numeric entity (`&#xAB;`)
	 * @return array<int, string>
	 */
	public static function getAlternatives(string $symbol): array {
		$codepoint = static::extractCodepoint($symbol);
		if ($codepoint === null) {
			return [$symbol];
		}

		$unicodeSymbol = static::unicodeFromCodepoint($codepoint);
		if ($unicodeSymbol === null) {
			return [$symbol];
		}

		$alternatives = [
			$unicodeSymbol,
			static::decimalEntityFromCodepoint($codepoint),
			static::hexEntityFromCodepoint($codepoint),
		];

		foreach (static::namedEntitiesFromCodepoint($codepoint) as $namedEntity) {
			$alternatives[] = '&' . $namedEntity . ';';
		}

		return array_values(array_unique($alternatives));
	}


	/**
	 * @param string $symbol
	 * @return int|null
	 */
	protected static function extractCodepoint(string $symbol): ?int {
		if (preg_match('/^&([A-Za-z][A-Za-z0-9]+);$/', $symbol, $match) === 1) {
			$entityName = $match[1];
			if (array_key_exists($entityName, static::NAMED_ENTITY_CODEPOINTS)) {
				return static::NAMED_ENTITY_CODEPOINTS[ $entityName ];
			}

			$decoded = html_entity_decode($symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($decoded !== $symbol) {
				return static::extractCodepoint($decoded);
			}

			return null;
		}

		if (preg_match('/^&#(\d+);$/', $symbol, $match) === 1) {
			return (int)$match[1];
		}

		if (preg_match('/^&#[xX]([0-9A-Fa-f]+);$/', $symbol, $match) === 1) {
			/** @var int|false $value */
			$value = hexdec($match[1]);

			return $value === false ? null : $value;
		}

		$codepoint = IntlChar::ord($symbol);
		if (!is_int($codepoint) || $codepoint < 0) {
			return null;
		}

		return $codepoint;
	}


	/**
	 * @param int $codepoint
	 * @return string
	 */
	protected static function decimalEntityFromCodepoint(int $codepoint): string {
		return '&#' . $codepoint . ';';
	}


	/**
	 * @param int $codepoint
	 * @return string
	 */
	protected static function hexEntityFromCodepoint(int $codepoint): string {
		return '&#x' . strtoupper(dechex($codepoint)) . ';';
	}


	/**
	 * @param int $codepoint
	 * @return array<int, string>
	 */
	protected static function namedEntitiesFromCodepoint(int $codepoint): array {
		if (static::$codepointToNamedEntities === null) {
			static::$codepointToNamedEntities = [];
			foreach (static::NAMED_ENTITY_CODEPOINTS as $name => $value) {
				static::$codepointToNamedEntities[ $value ][] = $name;
			}
		}

		return static::$codepointToNamedEntities[ $codepoint ] ?? [];
	}


	/**
	 * @param int $codepoint
	 * @return string|null
	 */
	protected static function unicodeFromCodepoint(int $codepoint): ?string {
		if ($codepoint < 0 || $codepoint > 0x10FFFF) {
			return null;
		}

		if ($codepoint >= 0xD800 && $codepoint <= 0xDFFF) {
			return null;
		}

		$symbol = IntlChar::chr($codepoint);
		if (!is_string($symbol) || $symbol === '') {
			return null;
		}

		return $symbol;
	}
}
