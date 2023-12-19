<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * Configuration Entity
 *
 * @property int         $id
 * @property string      $realm
 * @property string      $scope
 * @property string      $identifier
 * @property string|NULL $value
 * @property string|NULL $languageShortcode
 * @property Language    $language
 */
class Configuration extends Entity {
	/**
	 * @inheritDoc
	 */
	 protected array $_accessible = [
		'realm' => TRUE,
		'scope' => TRUE,
		'identifier' => TRUE,
		'value' => TRUE,
		'languageShortcode' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaults = [
		'realm' => Awyiss::REALM_FRONTEND,
		'scope' => 'system',
	];
	protected static array $fieldMap = [
		'language_shortcode' => 'languageShortcode',
	];


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setScope (string $as_scope): string {
		return Inflector::underscore(Inflector::pluralize($as_scope));
	}


	/**
	 * Make sure the scope is always camelBacked
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setIdentifier (string $as_identifier): string {
		return Inflector::variable($as_identifier);
	}


	/**
	 * If the provided value is NULL or FALSE, set the value to an empty string
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setValue (mixed $ax_value): mixed {
		return $ax_value ?: '';
	}


	/**
	 * If the provided shortcode is an empty string, set it to NULL
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setLanguageShortcode (?string $as_languageShortcode): ?string {
		return $as_languageShortcode ?: NULL;
	}
}
