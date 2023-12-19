<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission\Setting;


use Cake\Core\InstanceConfigTrait;


class SingleChoiceSetting implements SettingInterface {
	use InstanceConfigTrait;


	public const TYPE_RADIO = 'radio';
	public const TYPE_SELECT = 'select';
	/**
	 * Default config for this object.
	 * - `fields` The fields to use to identify a user by.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
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
	 * @return \Awyiss\Authorization\Permission\Setting\SettingInterface
	 */
	public function setType (string $as_type): SettingInterface {
		$this->setConfig('type', \Cake\Utility\Inflector::underscore($as_type));

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
	 * @return \Awyiss\Authorization\Permission\Setting\SettingInterface
	 */
	public function setOptions (array $aa_options): SettingInterface {
		$this->setConfig('options', $aa_options);

		return $this;
	}


	/**
	 * @param \Cake\View\View $ao_view
	 * @param null|string $as_prePath
	 *
	 * @return string
	 * @throws \Exception
	 */
	/*public function render (\Cake\View\View $ao_view, ?string $as_prePath = NULL): string {
		$ls_prePath = trim($as_prePath ?? '', '/');
		if (!empty($ls_prePath)) $ls_prePath .= '/';

		$la_viewData = [
			'setting' => $this,
		];

		try {
			return $ao_view->element($ls_prePath . 'permissions/settings/' . $this->getConfig('type'), $la_viewData);
		}
		catch (\Exception $ex) {
			if (!empty($ls_prePath)) {
				try {
					return $ao_view->element('permissions/settings/' . $this->getConfig('type'), $la_viewData);
				}
				catch (\Exception $ex2) {
					throw $ex;
				}
			}
			else {
				throw $ex;
			}
		}
	}*/
}