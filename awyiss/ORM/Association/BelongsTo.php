<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


/**
 * {@inheritDoc}
 *
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class BelongsTo extends \Cake\ORM\Association\BelongsTo {
	use ExistsTrait;
}
