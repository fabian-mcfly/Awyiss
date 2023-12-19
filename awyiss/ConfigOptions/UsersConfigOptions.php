<?php declare(strict_types=1);


namespace Awyiss\ConfigOptions;


class UsersConfigOptions extends AbstractConfigOptions {
	protected static ?string $scope = 'users';


	public function initializeConfigOptions (): void {
		$this->add([
			'backend' => [
				new ConfigOption([
					'defaultValue' => TRUE,
					'name' => 'search',
					'type' => ConfigOption::TYPE_BOOL,
				]),
				'pagination' => [
					new ConfigOption([
						'defaultValue' => 99,
						'name' => 'limit',
						'type' => ConfigOption::TYPE_NUMBER,
					]),
				]
			]
		]);
	}
}