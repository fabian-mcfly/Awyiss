<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\BelongsTo as BaseBelongsTo;


/**
 * {@inheritDoc}
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class BelongsTo extends BaseBelongsTo {
	use ExistsTrait;
}
