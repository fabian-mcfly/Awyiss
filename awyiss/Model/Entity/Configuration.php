<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * Configuration Entity
 *
 * @property int $id
 * @property string $realm
 * @property string $scope
 * @property string $identifier
 * @property string|null $value
 * @property string|null $languageShortcode
 * @property Language $language
 */
class Configuration extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'realm' => true,
		'scope' => true,
		'identifier' => true,
		'value' => true,
		'languageShortcode' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
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
	 * @see \Awyiss\Model\Entity\Configuration::$scope
	 */
	protected function _setScope(string $as_scope): string {
		return Inflector::underscore(Inflector::pluralize($as_scope));
	}


	/**
	 * Make sure the scope is always camelBacked
	 *
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\Configuration::$identifier
	 */
	protected function _setIdentifier(string $as_identifier): string {
		return Inflector::underscore($as_identifier);
	}


	/**
	 * If the provided value is null or false, set the value to an empty string
	 *
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\Configuration::$value
	 */
	protected function _setValue(mixed $ax_value): mixed {
		return is_null($ax_value) || $ax_value === false ? null : $ax_value;
	}


	/**
	 * If the provided shortcode is an empty string, set it to null
	 *
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\Configuration::$language_shortcode
	 */
	protected function _setLanguageShortcode(?string $as_languageShortcode): ?string {
		return $as_languageShortcode ?: null;
	}
}
