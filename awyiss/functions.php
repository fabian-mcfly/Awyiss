<?php declare(strict_types=1);


/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


use Awyiss\Routing\Router;
use Symfony\Component\VarDumper\Caster\ScalarStub;
use Symfony\Component\VarDumper\VarDumper;


/**
 * @param mixed ...$vars
 * @return mixed
 */
function nonceDump(mixed ...$vars): mixed {
	ob_start();

	$key = 0;
	if (array_key_exists(0, $vars) && count($vars) === 1) {
		VarDumper::dump($vars[0]);
	}
	else {
		foreach ($vars as $key => $value) {
			VarDumper::dump($value, (string)(is_int($key) ? 1 + $key : $key));
		}
	}

	$output = ob_get_clean();

	$scriptNonce = Router::getRequest()?->getAttribute('cspScriptNonce');
	$styleNonce = Router::getRequest()?->getAttribute('cspStyleNonce');
	// Add nonce to the script tag
	$output = str_replace('<script>', '<script nonce="' . $scriptNonce . '">', $output);
	$output = str_replace('<style>', '<style nonce="' . $styleNonce . '">', $output);

	echo $output;


	return $key;
}



/**
 * @param mixed ...$vars
 * @return mixed
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Alexandre Daubois <alex.daubois@gmail.com>
 */
function dump(mixed ...$vars): mixed {
	if (!$vars) {
		VarDumper::dump(new ScalarStub('🐛'));
		return null;
	}

	$key = nonceDump(...$vars);
	if (1 < count($vars)) {
		return $vars;
	}


	return $vars[ $key ];
}



/**
 * @param mixed ...$vars
 * @return never
 * @noinspection PhpFunctionNamingConventionInspection
 */
function dd(mixed ...$vars): never {
	if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && !headers_sent()) {
		header('HTTP/1.1 500 Internal Server Error');
	}

	nonceDump(...$vars);

	exit(1);
}
