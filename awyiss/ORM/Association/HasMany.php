<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\HasMany as BaseHasMany;


/**
 * Re-implemented so it'll use ExistsTrait
 *
 * @inheritDoc
 */
class HasMany extends BaseHasMany {
	use ExistsTrait;
}
