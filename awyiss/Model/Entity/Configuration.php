<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * Configuration Entity
 *
 * @property int $id
 * @property string|null $realm
 * @property string|null $scope
 * @property string|null $identifier
 * @property string|null $value
 * @property string|null $languageShortcode
 * @property \Awyiss\Model\Entity\Language $language
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
	 * Make sure the identifier is always underscored
	 *
	 * @param string|null $as_identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Configuration::$identifier
	 */
	protected function _setIdentifier(?string $as_identifier): ?string {
		if ($as_identifier === null) {
			return null;
		}


		return Inflector::underscore($as_identifier);
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $as_scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Configuration::$scope
	 */
	protected function _setScope(?string $as_scope): ?string {
		if ($as_scope === null) {
			return null;
		}

		return Inflector::underscore(Inflector::pluralize($as_scope));
	}


	/**
	 * If the provided value is false, set the value to 0
	 *
	 * @param mixed $ax_value
	 * @return mixed
	 * @see \Awyiss\Model\Entity\Configuration::$value
	 */
	protected function _setValue(mixed $ax_value): mixed {
		return $ax_value === false ? 0 : $ax_value;
	}
}
