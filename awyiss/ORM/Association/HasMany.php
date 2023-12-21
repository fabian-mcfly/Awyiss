<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\HasMany as BaseHasMany;


/**
 * {@inheritDoc}
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class HasMany extends BaseHasMany {
	use ExistsTrait;
}
