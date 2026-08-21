<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography;


/**
 * A single, self-contained typography rule.
 *
 * Implementations must only operate on plain text. The \Awyiss\Utility\Content\Typography\TypographyFixer
 * guarantees that `apply()` is never called with markup, HTML attribute values, or the contents
 * of `<code>`/`<pre>`/`<script>`/`<style>` tags.
 */
interface TypographyRuleInterface {
	/**
	 * Applies the rule to the given plain text fragment and returns the result
	 *
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string;
}
