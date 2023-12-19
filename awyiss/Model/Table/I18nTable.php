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
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'i18n';


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'authorization' => [
			'enabled' => FALSE,
		],
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