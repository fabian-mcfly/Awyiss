<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * Configuration Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $name
 * @property string|NULL $value
 * @property string|NULL $language_shortcode
 * @property \Awyiss\Model\Entity\Language $language
 */
class Configuration extends Entity {
	/**
	 * @inheritDoc
	 */
	 protected $_accessible = [
		'scope' => TRUE,
		'name' => TRUE,
		'value' => TRUE,
		'language_shortcode' => TRUE,
	];

	/**
	 * @inheritDoc
	 */
	protected array $defaults = [
		'scope' => 'system',
	];


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setScope (string $as_scope): string {
		return mb_strtolower(Text::slug($as_scope, ['replacement' => '_']));
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
