<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption\Setting;


use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;


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
	 * @param array $config Configuration settings.
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}


	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->getConfig('type');
	}


	/**
	 * @param string $type
	 * @return SettingInterface
	 */
	public function setType(string $type): static {
		$this->setConfig('type', Inflector::underscore($type));


		return $this;
	}


	/**
	 * @return string
	 */
	public function getOptions(): string {
		return $this->getConfig('options');
	}


	/**
	 * @param array $options
	 * @return SettingInterface
	 */
	public function setOptions(array $options): static {
		$this->setConfig('options', $options);


		return $this;
	}
}
