<?php declare(strict_types=1);


namespace App\Database\Type;


use Cake\Core\Exception\CakeException;
use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use PDO;


/**
 * Binary hash type converter.
 *
 * Converts a binary hash/digest column (e.g. SHA-1, SHA-256, MD5) between its hexadecimal string representation in PHP and its
 * raw binary representation in the database. Unlike \Cake\Database\Type\BinaryType, this type never exposes a stream resource to PHP
 * - the entity field is always a plain, lowercase hex string, which is both session- and json_encode()-safe.
 */
class BinaryHashType extends BaseType {
	/**
	 * Convert a hex string into the raw binary representation for the database.
	 *
	 * @param mixed $value The value to convert.
	 * @param \Cake\Database\Driver $driver The driver instance to convert with.
	 * @return mixed
	 */
	public function toDatabase(mixed $value, Driver $driver): mixed {
		if ($value === null || is_resource($value)) {
			return $value;
		}

		if (!is_string($value)) {
			throw new CakeException(sprintf('Unable to convert `%s` into a binary hash.', gettype($value)));
		}

		if (!$this->looksLikeHex($value)) {
			// Already raw binary (e.g. round-tripped from another binary field) - pass through unchanged.
			return $value;
		}

		return $this->hexToBinary($value);
	}


	/**
	 * Convert the raw binary value from the database into a hex string.
	 *
	 * @param mixed $value The value to convert.
	 * @param \Cake\Database\Driver $driver The driver instance to convert with.
	 * @return string|null
	 * @throws \Cake\Core\Exception\CakeException
	 */
	public function toPHP(mixed $value, Driver $driver): mixed {
		if ($value === null) {
			return null;
		}
		if (is_resource($value)) {
			return $this->binaryToHex(stream_get_contents($value) ?: '');
		}
		if (is_string($value)) {
			return $this->binaryToHex($value);
		}

		throw new CakeException(sprintf('Unable to convert `%s` into a binary hash.', gettype($value)));
	}


	/**
	 * @inheritDoc
	 */
	public function toStatement(mixed $value, Driver $driver): int {
		return PDO::PARAM_LOB;
	}


	/**
	 * Marshals flat data into PHP objects.
	 *
	 * Request data is expected to already be a hex string, so it is normalized (trimmed, lowercased)
	 * but otherwise passed through unchanged.
	 *
	 * @param mixed $value The value to convert.
	 * @return mixed Converted value.
	 */
	public function marshal(mixed $value): mixed {
		if (!is_string($value)) {
			return $value;
		}

		return strtolower(trim($value));
	}


	/**
	 * Checks whether a string looks like a hex-encoded binary value.
	 *
	 * @param string $value The value to inspect.
	 * @return bool
	 */
	protected function looksLikeHex(string $value): bool {
		return $value !== '' && strlen($value) % 2 === 0 && ctype_xdigit($value);
	}


	/**
	 * Converts a hex string to its raw binary representation.
	 *
	 * @param string $hex The hex string to convert.
	 * @return string Converted value.
	 * @throws \Cake\Core\Exception\CakeException
	 */
	protected function hexToBinary(string $hex): string {
		$binary = hex2bin($hex);
		if ($binary === false) {
			throw new CakeException(sprintf('Unable to convert `%s` into a binary hash.', $hex));
		}

		return $binary;
	}


	/**
	 * Converts a raw binary value to its hex string representation.
	 *
	 * @param string $binary The raw binary value to convert.
	 * @return string Converted value.
	 */
	protected function binaryToHex(string $binary): string {
		return bin2hex($binary);
	}
}
