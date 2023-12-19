<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


/**
 * {@inheritDoc}
 *
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class BelongsToMany extends \Cake\ORM\Association\BelongsToMany {
	use ExistsTrait;
}
