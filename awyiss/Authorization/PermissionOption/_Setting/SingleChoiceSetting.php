<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption\Setting;


use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;


/**
 * Simple single choice setting class.
 * The possible choices are stored in the config with the key `options`.
 *
 * This class provides two possible options how the setting can be displayed:
 * - radio button
 * - select dropdown
 */
class SingleChoiceSetting implements SettingInterface {
	use InstanceConfigTrait;


	/**
	 * Default config for this object.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'options' => [-1, 0, 1],
		'preferredInput' => self::TYPE_RADIO,
	];


	/**
	 * Constructor
	 *
	 * @param array $aa_config Configuration settings.
	 */
	public function __construct (array $aa_config = []) {
		$this->setConfig($aa_config);
	}


	/**
	 * @return string
	 */
	public function getType (): string {
		return $this->getConfig('type');
	}


	/**
	 * @param string $as_type
	 *
	 * @return \Awyiss\Authorization\PermissionOption\Setting\SettingInterface
	 */
	public function setType (string $as_type): static {
		$this->setConfig('type', Inflector::underscore($as_type));

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOptions (): string {
		return $this->getConfig('options');
	}


	/**
	 * @param array $aa_options
	 *
	 * @return \Awyiss\Authorization\PermissionOption\Setting\SettingInterface
	 */
	public function setOptions (array $aa_options): static {
		$this->setConfig('options', $aa_options);

		return $this;
	}
}