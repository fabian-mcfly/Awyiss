<?php declare(strict_types=1);


namespace Awyiss\Test\TestSuite;


/**
 * Interface CallableMock
 *
 * This interface defines a mock object that can be called like a function.
 * It allows for dynamic method calls and can be used in testing scenarios.
 */
interface CallableMock {
	/**
	 * @return mixed
	 */
	public function __invoke(): mixed;


	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $name, array $arguments): mixed;
}
