<?php declare(strict_types=1);


namespace Awyiss\Utility\Minify;


use MatthiasMullie\Minify\CSS as BaseCSS;


/**
 * CSS Minifier
 */
class Css extends BaseCSS {
	/**
	 * Replaced the original stripWhitespace to fix
	 * an issue with spaces removed from `:nth-child(1 of .Foobar)`
	 * Replaced (.*?) with ((?:(?!of).)*) in Line 757 of
	 * vendor/matthiasmullie/minify/src/CSS.php
	 *
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection RegExpRedundantEscape
	 */
	protected function stripWhitespace($content) {
		// remove leading & trailing whitespace
		$content = preg_replace('/^\s*/m', '', $content);
		$content = preg_replace('/\s*$/m', '', $content);

		// replace newlines with a single space
		$content = preg_replace('/\s+/', ' ', $content);

		// remove whitespace around meta characters
		// inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex
		$content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
		$content = preg_replace('/([\[(:>\+])\s+/', '$1', $content);
		$content = preg_replace('/\s+([\]\)>\+])/', '$1', $content);
		$content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);

		// whitespace around + and - can only be stripped inside some pseudo-
		// classes, like `:nth-child(3+2n)`
		// not in things like `calc(3px + 2px)`, shorthands like `3px -2px`, or
		// selectors like `div.weird- p`
		$pseudos = ['nth-child', 'nth-last-child', 'nth-last-of-type', 'nth-of-type'];
		$content = preg_replace('/:(' . implode('|', $pseudos) . ')\(\s*([+-]?)\s*(.+?)\s*([+-]?)\s*((?:(?!of).)*)\s*\)/', ':$1($2$3$4$5)', $content);

		// remove semicolon/whitespace followed by closing bracket
		$content = str_replace(';}', '}', $content);

		return trim($content);
	}
}
