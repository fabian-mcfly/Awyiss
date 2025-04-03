<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;


/**
 * Locks Model
 *
 * @method \Awyiss\Model\Entity\Lock newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class LocksTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'locks';
}
