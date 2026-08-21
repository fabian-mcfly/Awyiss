<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography;


use Awyiss\Model\Entity;
use Cake\Datasource\EntityInterface;
use Dom\HTMLDocument;
use Dom\Node;


/**
 * Forces language-specific typography rules (non-breaking spaces around currency symbols,
 * French guillemets, ...) onto the HTML content of an entity.
 *
 * Only text nodes are ever touched — HTML attribute values and the contents of
 * `<code>`, `<pre>`, `<script>`, and `<style>` tags are always left untouched.
 *
 * Rules are not applied to each text node in isolation. Within one block-level element
 * (a direct child of `<body>`, e.g. a `<p>` or `<li>`), all eligible text nodes are joined
 * into a single string before the rules run, so a construct that is split across an inline
 * tag — e.g. `"a <strong>very</strong> important" quote` — is still recognised as one unit.
 * Joining stops at block-level boundaries: a quote left unclosed in one `<p>` will not be
 * paired with a quote in a following, unrelated `<p>`.
 *
 * Which rules apply to which language is entirely defined by the
 * \Awyiss\Utility\Content\Typography\TypographyRuleRegistry and can be extended or
 * overridden at runtime without touching this class. The registry starts out empty —
 * call `TypographyRuleRegistry::registerDefaults()` once, e.g. from your application's
 * bootstrap, to opt into the built-in rules. Without it, `format()` and `formatHtml()`
 * simply do nothing for any language.
 */
class TypographyFixer {
	/**
	 * Private-use character used as a temporary, invisible marker for the original text node
	 * boundaries while a block's text is processed as a single string. Never written into the
	 * document — it only ever exists in a local PHP string between `implode()` and `explode()`.
	 */
	public const string NODE_BOUNDARY = "\u{E000}";
	/**
	 * Character class fragment for all supported Unicode whitespace characters used by typography rules.
	 */
	public const string WHITESPACE_CHAR_CLASS = " \x{00A0}\x{202F}\x{2009}";
	/**
	 * Regex fragment for supported whitespace tokens, including literal HTML entities.
	 */
	public const string WHITESPACE_TOKEN_PATTERN = '(?:[' . self::WHITESPACE_CHAR_CLASS . ']|&nbsp;|&#8239;)';
	/**
	 * Tags whose text content must never be touched by any rule
	 *
	 * @var array<int, string>
	 */
	protected const array SKIPPED_TAGS = ['CODE', 'PRE', 'SCRIPT', 'STYLE', 'AWYISS-RESPONSIVE-IMAGE'];


	/**
	 * The default fields to fix
	 *
	 * @var array<int, string>
	 */
	protected static array $defaultFields = ['text', 'textHtml', 'successMessage'];


	/**
	 * Applies the typography rules for the given language to all eligible fields of the given entity and its translations.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $language
	 * @param array $fields
	 * @return void
	 */
	public static function format(EntityInterface $entity, string $language, array $fields = []): void {
		$fields = $fields ?: static::$defaultFields;

		foreach ($fields as $field) {
			if (!static::fieldIsValid($entity, $field)) {
				continue;
			}

			$value = $entity->get($field);
			$entity->set($field, static::formatHtml($value, $language));
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $language => $translation) {
				static::format($translation, $language, $fields);
			}
		}
	}


	/**
	 * Applies the given rules to all eligible text nodes of the given HTML string
	 * and returns the resulting HTML string
	 *
	 * @param string $html
	 * @param string $language
	 * @return string
	 */
	public static function formatHtml(string $html, string $language): string {
		$rules = TypographyRuleRegistry::getRulesForLanguage($language);

		if ($rules === [] || trim($html) === '') {
			return $html;
		}

		$dom = static::getDom($html);
		$body = $dom->querySelector('body');

		foreach ($body->childNodes as $blockRoot) {
			static::formatBlock($blockRoot, $rules);
		}

		return static::getBody($dom);
	}


	/**
	 * Joins all eligible text nodes below (or, if it is one itself, including) the given node
	 * into a single string, runs the given rules over it, and distributes the result back to
	 * the original text nodes
	 *
	 * @param \Dom\Node $blockRoot
	 * @param array<int, \Awyiss\Utility\Content\Typography\TypographyRuleInterface> $rules
	 * @return void
	 */
	protected static function formatBlock(Node $blockRoot, array $rules): void {
		$textNodes = static::collectEligibleTextNodes($blockRoot);

		if ($textNodes === []) {
			return;
		}

		$joined = implode(
			static::NODE_BOUNDARY,
			array_map(static fn(Node $node): string => $node->nodeValue, $textNodes)
		);

		foreach ($rules as $rule) {
			$joined = $rule->apply($joined);
		}

		$parts = explode(static::NODE_BOUNDARY, $joined);

		// None of the built-in rules touch the boundary marker, so this should never happen.
		// If a custom rule does, skip distributing its result rather than risking corrupted content.
		if (count($parts) !== count($textNodes)) {
			return;
		}

		foreach ($textNodes as $index => $textNode) {
			$textNode->nodeValue = $parts[ $index ];
		}
	}


	/**
	 * Recursively collects all text node descendants of the given node (or returns the node
	 * itself, if it already is a text node), skipping the subtree of any
	 * \Awyiss\Utility\Content\Typography\TypographyFixer::SKIPPED_TAGS element
	 *
	 * @param \Dom\Node $node
	 * @return array<int, \Dom\Node>
	 */
	protected static function collectEligibleTextNodes(Node $node): array {
		if ($node->nodeName === '#text') {
			return [$node];
		}

		if (in_array($node->nodeName, static::SKIPPED_TAGS, true)) {
			return [];
		}

		$textNodes = [];

		foreach ($node->childNodes as $child) {
			array_push($textNodes, ...static::collectEligibleTextNodes($child));
		}

		return $textNodes;
	}


	/**
	 * Creates a \Dom\HTMLDocument from the given HTML string
	 *
	 * @param string $value
	 * @return \Dom\HTMLDocument
	 */
	protected static function getDom(string $value): HTMLDocument {
		// Load the HTML string into the \Dom\HTMLDocument
		return HTMLDocument::createFromString($value, LIBXML_NOERROR, 'UTF-8');
	}


	/**
	 * Returns the contents of the `<body>`-tag of the given \Dom\HTMLDocument as a string
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return string
	 */
	protected static function getBody(HTMLDocument $dom): string {
		$html = '';

		$body = $dom->querySelector('body');

		while ($body->firstChild) {
			$html .= $dom->saveHTML($body->firstChild);
			$body->removeChild($body->firstChild);
		}

		return $html;
	}


	/**
	 * Checks if the given field is valid for the entity
	 * or if it exists in the attributes of the entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return bool
	 */
	protected static function fieldIsValid(EntityInterface $entity, string $field): bool {
		if ($entity->has($field)) {
			return is_string($entity->get($field));
		}

		if (
			!$entity->has('attributes')
			|| !($entity->get('attributes') instanceof Entity)
		) {
			return false;
		}

		/**
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		return $entity->attributes->has($field) && is_string($entity->attributes->get($field));
	}
}
