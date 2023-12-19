<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;


class UsergroupsConfigOptions extends AbstractConfigOptions {
	protected static string $scope = 'usergroups';


	public function initializeConfigOptions (): void {
		$this->add([
			'backend' => [
				new ConfigOption([
					'defaultValue' => TRUE,
					'localizable' => FALSE,
					'name' => 'search',
					'type' => ConfigOption::TYPE_BOOL,
				]),
				'pagination' => [
					new ConfigOption([
						'defaultValue' => 20,
						'localizable' => FALSE,
						'name' => 'limit',
						'nullable' => FALSE,
						'type' => ConfigOption::TYPE_INTEGER,
					]),
				]
			]
		]);
	}
}