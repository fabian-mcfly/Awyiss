<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


class I18nTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'access' => [
			'enabled' => FALSE,
		],
		/*'audit' => [
			'enabled' => FALSE,
		],*/
	];
}