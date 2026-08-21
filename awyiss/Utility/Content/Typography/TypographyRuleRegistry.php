<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography;


use Awyiss\Utility\Content\Typography\Rule\ApostropheRule;
use Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule;
use Awyiss\Utility\Content\Typography\Rule\CallableProxyRule;
use Awyiss\Utility\Content\Typography\Rule\DashRule;
use Awyiss\Utility\Content\Typography\Rule\EllipsisRule;
use Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule;
use Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule;
use Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule;


/**
 * Holds the ordered list of \Awyiss\Utility\Content\Typography\TypographyRuleInterface instances that are applied per target language.
 *
 * The registry starts out empty. Call `registerDefaults()` once, e.g. from your application's bootstrap, to opt into the built-in
 * rules for German, English, French, Spanish, and Italian. Consumers can then register, replace, or remove rules at runtime:
 *
 * ```
 * TypographyRuleRegistry::registerDefaults();
 *
 * // Add a symbol this package doesn't watch for by default
 * TypographyRuleRegistry::register('de', new SymbolSpacingRule(['₿'], 'after', "\u{202F}"));
 *
 * // Fully replace the default behavior for a language
 * TypographyRuleRegistry::clear('fr');
 * TypographyRuleRegistry::register('fr', new QuotationMarksRule('«', '»', "\u{202F}"));
 * ```
 */
class TypographyRuleRegistry {
	/**
	 * Currency symbols recognized by the default currency spacing rules.
	 *
	 * Deliberately limited to unambiguous glyphs. Letter-based currency abbreviations (`CHF`, `kr`, `zł`, `Kč`, ...) are left out,
	 * since they risk colliding with ordinary words, and can be registered separately where the risk is acceptable for a project.
	 *
	 * To work with those, a consumer can register a `SymbolSpacingRule` with `$requireWordBoundaryAfter` set to `true`, e.g.:
	 * ```
	 * TypographyRuleRegistry::register('de', new SymbolSpacingRule(['CHF', 'kr', 'zł', 'Kč'], 'after', "\u{202F}", true));
	 * ```
	 *
	 * @var array<int, string>
	 */
	protected const array CURRENCY_SYMBOLS = ['€', '$', '£', '¥', '₹', '₩', '₽', '₺', '₴', '₫', '₪'];
	/**
	 * SI unit symbols recognized by the default SI unit spacing rules.
	 *
	 * Deliberately limited to unambiguous glyphs.
	 *
	 * @var array<int, string>
	 */
	protected const array SI_UNITS = [
		'm',
		'm²',
		'm³',
		'km',
		'km²',
		'km³',
		'cm',
		'cm²',
		'cm³',
		'mm',
		'mm²',
		'mm³',
		'µm',
		'nm',
		'km/h',
		'm/s',
		'g',
		'kg',
		'mg',
		's',
		'min',
		'h',
		'A',
		'V',
		'W',
		'Hz',
		'K',
		'Pa',
		'J',
		'N',
	];


	/**
	 * Whether `registerDefaults()` has already run.
	 * Only consulted by `registerDefaults()` itself, so calling it more than once is harmless.
	 *
	 * @var bool
	 */
	protected static bool $defaultsRegistered = false;
	/**
	 * Registered rules, keyed by language code
	 *
	 * @var array<string, array<int, \Awyiss\Utility\Content\Typography\TypographyRuleInterface>>
	 */
	protected static array $rules = [];


	/**
	 * Registers a rule for the given language.
	 * Rules are applied in the order they were registered.
	 *
	 * Callables are wrapped in a proxy rule whose `apply()` method forwards the incoming text and returns the callable result.
	 *
	 * @param string $language
	 * @param \Awyiss\Utility\Content\Typography\TypographyRuleInterface|callable(string):string $rule
	 * @return void
	 */
	public static function register(string $language, TypographyRuleInterface|callable $rule): void {
		if (is_callable($rule)) {
			$rule = new CallableProxyRule($rule);
		}

		static::$rules[ $language ][] = $rule;
	}


	/**
	 * Returns all rules registered for the given language, in registration order.
	 * Returns an empty array for a language nothing was ever registered for.
	 *
	 * @param string $language
	 * @return array<int, \Awyiss\Utility\Content\Typography\TypographyRuleInterface>
	 */
	public static function getRulesForLanguage(string $language): array {
		return static::$rules[ $language ] ?? [];
	}


	/**
	 * Removes all rules registered for the given language.
	 * Useful to fully replace the default behavior for a language.
	 *
	 * @param string $language
	 * @return void
	 */
	public static function clear(string $language): void {
		unset(static::$rules[ $language ]);
	}


	/**
	 * Resets the registry to its initial, empty state.
	 * `registerDefaults()` must be called again to restore the built-in rules.
	 *
	 * Mainly useful for tests.
	 *
	 * @return void
	 */
	public static function reset(): void {
		static::$rules = [];
		static::$defaultsRegistered = false;
	}


	/**
	 * Registers the built-in default rules for German, English, French, Spanish, and Italian.
	 *
	 * Not called automatically by any other method. Safe to call more than once; later calls are ignored.
	 *
	 * @return void
	 */
	public static function registerDefaults(): void {
		if (static::$defaultsRegistered) {
			return;
		}

		static::$defaultsRegistered = true;

		static::registerGerman();
		static::registerEnglish();
		static::registerFrench();
		static::registerSpanish();
		static::registerItalian();
	}


	/**
	 * @return void
	 */
	protected static function registerGerman(): void {
		// Number followed by a non-breaking narrow space and a currency symbol, e.g. `19,99 €`, `19,99 $`
		static::register('de', new SymbolSpacingRule(static::CURRENCY_SYMBOLS, 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and symbols, e.g. `50 %`
		static::register('de', new SymbolSpacingRule(['%', '‰', '°C'], 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and an SI unit symbol, e.g. `10 m`, `5 kg`, `3 s`
		// but not `10 mögliche` (the latter is a false positive)
		static::register('de', new SymbolSpacingRule(array_merge(static::SI_UNITS, ['Uhr']), 'after', "\u{202F}", true));

		// Straight double quotes become German low-high quotes, e.g. `„Zitat“`
		static::register('de', new QuotationMarksRule('„', "\u{201C}"));

		// No spacing directly inside round, square, or angle brackets, e.g. `( asdf )` -> `(asdf)`
		static::register('de', new BracketInnerSpacingRule());

		// No spacing before `.`, `;`, `:`, `!`, and `?`; any existing spacing is removed
		static::register('de', new PunctuationSpacingRule(['.', ',', ';', ':', '!', '?']));

		// The unambiguous letter-apostrophe-letter case becomes a typographic apostrophe, e.g. `don't` -> `don't`
		static::register('de', new ApostropheRule());

		// Runs of dots become a proper ellipsis; spacing around existing or converted ellipses is preserved by default.
		static::register('de', new EllipsisRule("\u{201C}", "\u{201C}"));

		// Hyphen-as-dash and numeric ranges (`10-20`, `10 - 20`) become an en dash.
		static::register('de', new DashRule('–', true, true));
	}


	/**
	 * @return void
	 */
	protected static function registerEnglish(): void {
		// A currency symbol directly followed by the number, with any existing space removed, e.g. `$ 19.99` -> `$19.99`
		static::register('en', new SymbolSpacingRule(static::CURRENCY_SYMBOLS, 'before', ''));

		// Number directly followed by the percent sign, with any existing space removed, e.g. `50 %` -> `50%`
		static::register('en', new SymbolSpacingRule(['%', '‰'], 'after', ''));

		// Number followed by a non-breaking narrow space and an SI unit symbol, e.g. `10 m`, `5 kg`, `3 s`
		// but not `10 mögliche` (the latter is a false positive)
		static::register('de', new SymbolSpacingRule(static::SI_UNITS, 'after', "\u{202F}", true));

		// Straight double quotes become English curly quotes, e.g. `"quote"` -> `“quote”`
		static::register('en', new QuotationMarksRule('“', '”'));

		// No spacing directly inside round, square, or angle brackets
		static::register('en', new BracketInnerSpacingRule());

		// No spacing before `.`, `;`, `:`, `!`, and `?`; any existing spacing is removed
		static::register('en', new PunctuationSpacingRule(['.', ',', ';', ':', '!', '?']));

		// The unambiguous letter-apostrophe-letter case becomes a typographic apostrophe, e.g. `don't` -> `don't`
		static::register('en', new ApostropheRule());

		// Runs of dots become a proper ellipsis; spacing around existing or converted ellipses is preserved by default.
		static::register('en', new EllipsisRule());

		// The typewriter shortcut `--` becomes an unspaced em dash, e.g. `word--word` -> `word—word`
		static::register('en', new DashRule('—', false));

		// A hyphen surrounded by spaces becomes a spaced en dash, e.g. `word - word` -> `word – word`
		static::register('en', new DashRule('–'));
	}


	/**
	 * @return void
	 */
	protected static function registerFrench(): void {
		// Number followed by a non-breaking narrow space and a currency symbol, e.g. `19,99 €`, `19,99 $`
		static::register('fr', new SymbolSpacingRule(static::CURRENCY_SYMBOLS, 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and the percent sign, e.g. `50 %`
		static::register('fr', new SymbolSpacingRule(['%', '‰'], 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and an SI unit symbol, e.g. `10 m`, `5 kg`, `3 s`
		// but not `10 mögliche` (the latter is a false positive)
		static::register('de', new SymbolSpacingRule(static::SI_UNITS, 'after', "\u{202F}", true));

		// Straight double quotes become guillemets with a non-breaking narrow space inside, e.g. `« citation »`
		static::register('fr', new QuotationMarksRule('«', '»', "\u{202F}"));

		// No spacing directly inside round, square, or angle brackets
		static::register('fr', new BracketInnerSpacingRule());

		// No spacing before `.` and `,`; any existing spacing is removed
		static::register('fr', new PunctuationSpacingRule(['.', ',']));

		// A non-breaking narrow space is required before `;`, `:`, `!`, and `?`
		static::register('fr', new PunctuationSpacingRule([';', ':', '!', '?'], "\u{202F}"));

		// The unambiguous letter-apostrophe-letter case becomes a typographic apostrophe, e.g. `l'arbre` -> `l'arbre`
		static::register('fr', new ApostropheRule());

		// Runs of dots become a proper ellipsis; spacing around existing or converted ellipses is preserved by default.
		static::register('fr', new EllipsisRule());

		// A hyphen surrounded by spaces becomes a spaced en dash
		static::register('fr', new DashRule('–'));
	}


	/**
	 * @return void
	 */
	protected static function registerSpanish(): void {
		// Number followed by a non-breaking narrow space and a currency symbol, e.g. `19,99 €`, `19,99 $`
		static::register('es', new SymbolSpacingRule(static::CURRENCY_SYMBOLS, 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and the percent sign, e.g. `50 %`
		static::register('es', new SymbolSpacingRule(['%', '‰'], 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and an SI unit symbol, e.g. `10 m`, `5 kg`, `3 s`
		// but not `10 mögliche` (the latter is a false positive)
		static::register('de', new SymbolSpacingRule(static::SI_UNITS, 'after', "\u{202F}", true));

		// Straight double quotes become guillemets, without inner spacing (unlike French), e.g. `«cita»`
		static::register('es', new QuotationMarksRule('«', '»'));

		// No spacing directly inside round, square, or angle brackets
		static::register('es', new BracketInnerSpacingRule());

		// No spacing before `.`, `;`, `:`, `!`, and `?`; any existing spacing is removed
		static::register('es', new PunctuationSpacingRule(['.', ',', ';', ':', '!', '?']));

		// Runs of dots become a proper ellipsis; spacing around existing or converted ellipses is preserved by default.
		static::register('es', new EllipsisRule());

		// A hyphen surrounded by spaces becomes a spaced en dash
		static::register('es', new DashRule('–'));
	}


	/**
	 * @return void
	 */
	protected static function registerItalian(): void {
		// Number followed by a non-breaking narrow space and a currency symbol, e.g. `19,99 €`, `19,99 $`
		static::register('it', new SymbolSpacingRule(static::CURRENCY_SYMBOLS, 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and the percent sign, e.g. `50 %`
		static::register('it', new SymbolSpacingRule(['%', '‰'], 'after', "\u{202F}"));

		// Number followed by a non-breaking narrow space and an SI unit symbol, e.g. `10 m`, `5 kg`, `3 s`
		// but not `10 mögliche` (the latter is a false positive)
		static::register('de', new SymbolSpacingRule(static::SI_UNITS, 'after', "\u{202F}", true));

		// Straight double quotes become guillemets, without inner spacing, e.g. `«citazione»`
		static::register('it', new QuotationMarksRule('«', '»'));

		// No spacing directly inside round, square, or angle brackets
		static::register('it', new BracketInnerSpacingRule());

		// No spacing before `.`, `;`, `:`, `!`, and `?`; any existing spacing is removed
		static::register('it', new PunctuationSpacingRule(['.', ',', ';', ':', '!', '?']));

		// The unambiguous letter-apostrophe-letter case becomes a typographic apostrophe, e.g. `dell'anno` -> `dell'anno`
		static::register('it', new ApostropheRule());

		// Runs of dots become a proper ellipsis; spacing around existing or converted ellipses is preserved by default.
		static::register('it', new EllipsisRule());

		// A hyphen surrounded by spaces becomes a spaced en dash
		static::register('it', new DashRule('–'));
	}
}
