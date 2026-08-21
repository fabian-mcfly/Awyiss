<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\TypographyRuleInterface;
use Closure;


/**
 * Adapts a callable(string): string to TypographyRuleInterface.
 */
class CallableProxyRule implements TypographyRuleInterface {
	/**
	 * @var \Closure(string): string
	 */
	protected Closure $callable;


	/**
	 * @param callable(string): string $callable
	 */
	public function __construct(callable $callable) {
		$this->callable = $callable(...);
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		return ($this->callable)($text);
	}
}
