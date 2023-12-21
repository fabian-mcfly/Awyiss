<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;


/**
 * Translation table
 */
class I18nTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'i18n';
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'audit' => [
			'enabled' => false,
		],
		'authorize' => [
			'enabled' => false,
		],
	];
	/*
	 * @inheritDoc
	 *
	public function initialize (array $aa_config): void {
		$this->setTable(static::TABLE);

		parent::initialize($aa_config);

		$this->setPrimaryKey('id');
	}*/
}
