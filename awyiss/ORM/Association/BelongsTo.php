<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\BelongsTo as BaseBelongsTo;


/**
 * Re-implemented so it'll use ExistsTrait
 *
 * @inheritDoc
 */
class BelongsTo extends BaseBelongsTo {
	use ExistsTrait;
}
