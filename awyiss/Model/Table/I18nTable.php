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
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'i18n';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];
}
