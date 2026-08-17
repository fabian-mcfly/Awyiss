<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


use BackedEnum;
use BadMethodCallException;
use InvalidArgumentException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;


/**
 * @copyright https://github.com/allejo
 * @see https://github.com/twigphp/Twig/issues/3681#issuecomment-1162728959
 */
class EnumExtension extends AbstractExtension {
	/**
	 * @return array<TwigFunction>
	 */
	public function getFunctions(): array {
		return [
			new TwigFunction('enum', $this->createProxy(...)),
		];
	}


	/**
	 * Returns a list of tests to add to the existing list.
	 *
	 * @return array<\Twig\TwigTest>
	 */
	public function getTests(): array {
		return [
			new TwigTest('enum', function ($value): bool {
				return $value instanceof BackedEnum;
			}),
		];
	}


	/**
	 * @param string $enumFQN
	 * @return object
	 */
	public function createProxy(string $enumFQN): object {
		return new readonly class ($enumFQN) {
			/**
			 * @param string $enum
			 */
			public function __construct(private string $enum) {
				if (!enum_exists($this->enum)) {
					throw new InvalidArgumentException(
						sprintf('`%s` is not an Enum type and cannot be used in this function', $this->enum)
					);
				}
			}


			/**
			 * @param string $name
			 * @param array $arguments
			 * @return mixed
			 */
			public function __call(string $name, array $arguments): mixed {
				$enumFQN = sprintf('%s::%s', $this->enum, $name);

				if (defined($enumFQN)) {
					return constant($enumFQN);
				}

				if (method_exists($this->enum, $name)) {
					return $this->enum::$name(...$arguments);
				}

				throw new BadMethodCallException(sprintf('Case or method `%s` does not exist in `%s`', $name, $this->enum));
			}
		};
	}
}
