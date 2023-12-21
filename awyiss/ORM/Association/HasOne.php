<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\HasOne as BaseHasOne;


/**
 * {@inheritDoc}
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class HasOne extends BaseHasOne {
	use ExistsTrait;
}
