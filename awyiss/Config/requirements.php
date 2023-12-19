<?php declare(strict_types=1);

/*
 * You can empty out this file, if you are certain that you match all requirements.
 */

if (version_compare(PHP_VERSION, '8.0.0') < 0) {
	trigger_error('Your PHP version must be equal or higher than 8.0.0 to use Awyiss.', E_USER_ERROR);
}

if ( ! extension_loaded('intl')) {
	trigger_error('You must enable the intl extension to use Awyiss.', E_USER_ERROR);
}

/** @noinspection PhpUndefinedConstantInspection */
if (version_compare(INTL_ICU_VERSION, '50.1', '<')) {
	trigger_error('ICU >= 50.1 is needed to use Awyiss. Please update the `libicu` package of your system.' . PHP_EOL, E_USER_ERROR);
}

if ( ! extension_loaded('mbstring')) {
	trigger_error('You must enable the mbstring extension to use Awyiss.', E_USER_ERROR);
}

if ( ! extension_loaded('curl')) {
	trigger_error('You must enable the cURL extension to use Awyiss.', E_USER_ERROR);
}
