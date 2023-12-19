<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


class I18nTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'i18n';


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


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
	}
}