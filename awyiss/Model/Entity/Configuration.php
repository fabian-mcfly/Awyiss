<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * Configuration Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $name
 * @property string|null $value
 * @property string|null $languages_shortcode
 *
 * @property \Awyiss\Model\Entity\Language $language
 */
class Configuration extends \Awyiss\Model\Entity {
	protected array $defaults = [
		'scope' => 'system',
	];
	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'scope' => TRUE,
		'name' => TRUE,
		'value' => TRUE,
		'languages_shortcode' => TRUE,
		'language' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setLanguagesShortcode (string $as_languagesShortcode): ?string {
		return $as_languagesShortcode ?: NULL;
	}
}
