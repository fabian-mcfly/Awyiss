<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission\Setting;


interface SettingInterface {
	/**
	 * @return string
	 */
	public function getType (): string;


	/**
	 * @param string $as_type
	 *
	 * @return $this
	 */
	public function setType (string $as_type): self;


	/**
	 * @return string
	 */
	public function getOptions (): string;


	/**
	 * @param array $aa_options
	 *
	 * @return $this
	 */
	public function setOptions (array $aa_options): self;


	/**
	 * @param \Cake\View\View $ao_view
	 * @param null|string $as_prePath
	 *
	 * @return string
	 */
	//public function render (\Cake\View\View $ao_view, ?string $as_prePath = NULL): string;
}