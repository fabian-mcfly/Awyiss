<?php declare(strict_types=1);


namespace Awyiss\Utility;


use Cake\Core\Configure;
use Cake\Core\Plugin;
use DebugKit\DebugTimer as DebugKitDebugTimer;


/**
 * DebugTimer Wrapper
 * Wraps DebugKit's DebugTimer with debug mode and plugin checks.
 * Only executes when debug mode is enabled and DebugKit plugin is loaded.
 */
class DebugTimer {
	/**
	 * @var bool
	 */
	protected static bool $debugEnabled;

	/**
	 * Check if debugging is enabled
	 *
	 * @return bool
	 */
	protected static function isDebugEnabled(): bool {
		if (isset(static::$debugEnabled)) {
			return static::$debugEnabled;
		}

		return Configure::read('debug') && Plugin::isLoaded('DebugKit');
	}


	/**
	 * Start a benchmarking timer.
	 *
	 * @param string|null $name The name of the timer to start.
	 * @param string|null $message A message for your timer
	 * @return bool Always true
	 */
	public static function start(?string $name = null, ?string $message = null): bool {
		if (!static::isDebugEnabled()) {
			return false;
		}

		return DebugKitDebugTimer::start($name, $message);
	}


	/**
	 * Stop a benchmarking timer.
	 * $name should be the same as the $name used in startTimer().
	 *
	 * @param string|null $name The name of the timer to end.
	 * @return bool true if timer was ended, false if timer was not started.
	 */
	public static function stop(?string $name = null): bool {
		if (!static::isDebugEnabled()) {
			return false;
		}

		return DebugKitDebugTimer::stop($name);
	}


	/**
	 * Get all timers that have been started and stopped.
	 * Calculates elapsed time for each timer. If clear is true, will delete existing timers
	 *
	 * @param bool $clear false
	 * @return array
	 */
	public static function getAll(bool $clear = false): array {
		if (!static::isDebugEnabled()) {
			return [];
		}

		return DebugKitDebugTimer::getAll($clear);
	}


	/**
	 * Clear all existing timers
	 *
	 * @return bool true
	 */
	public static function clear(): bool {
		if (!static::isDebugEnabled()) {
			return true;
		}

		return DebugKitDebugTimer::clear();
	}


	/**
	 * Get the difference in time between the timer start and timer end.
	 *
	 * @param string $name the name of the timer you want elapsed time for.
	 * @param int $precision the number of decimal places to return, defaults to 5.
	 * @return float number of seconds elapsed for timer name, 0 on missing key
	 */
	public static function elapsedTime(string $name = 'default', int $precision = 5): float {
		if (!static::isDebugEnabled()) {
			return 0.0;
		}

		return DebugKitDebugTimer::elapsedTime($name, $precision);
	}


	/**
	 * Get the total execution time until this point
	 *
	 * @return float elapsed time in seconds since script start.
	 */
	public static function requestTime(): float {
		if (!static::isDebugEnabled()) {
			return 0.0;
		}

		return DebugKitDebugTimer::requestTime();
	}


	/**
	 * Get the time the current request started.
	 *
	 * @return float time of request start
	 */
	public static function requestStartTime(): float {
		if (!static::isDebugEnabled()) {
			return 0.0;
		}

		return DebugKitDebugTimer::requestStartTime();
	}
}
