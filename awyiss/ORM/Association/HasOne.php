<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\HasOne as BaseHasOne;


/**
 * Re-implemented so it'll use ExistsTrait
 *
 * @inheritDoc
 */
class HasOne extends BaseHasOne {
	use ExistsTrait;
}
