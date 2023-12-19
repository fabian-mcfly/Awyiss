<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


/**
 * {@inheritDoc}
 *
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class HasOne extends \Cake\ORM\Association\HasOne {
	use ExistsTrait;
}
