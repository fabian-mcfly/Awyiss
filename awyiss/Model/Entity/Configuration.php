<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Cake\Utility\Text;


/**
 * Configuration Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $name
 * @property string|null $value
 * @property string|null $languages_shortcode
 * @property \Awyiss\Model\Entity\Language $language
 */
class Configuration extends \Awyiss\Model\Entity {
	/**
	 * @inheritDoc
	 */
	 protected $_accessible = [
		'scope' => TRUE,
		'name' => TRUE,
		'value' => TRUE,
		'languages_shortcode' => TRUE,
	];

	/**
	 * @inheritDoc
	 */
	protected array $defaults = [
		'scope' => 'system',
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setScope (string $as_scope): string {
		return mb_strtolower(Text::slug($as_scope, ['replacement' => '_']));
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setValue (string $as_value): ?string {
		return $as_value ?: '';
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setLanguagesShortcode (?string $as_languagesShortcode): ?string {
		return $as_languagesShortcode ?: NULL;
	}
}
