<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\BelongsToMany as BaseBelongsToMany;


/**
 * Re-implemented so it'll use ExistsTrait
 *
 * {@inheritDoc}
 */
class BelongsToMany extends BaseBelongsToMany {
	use ExistsTrait;


	/**
	 * Returns whether this association has a `through` table
	 *
	 * @return bool
	 */
	public function hasThrough(): bool {
		return isset($this->_through);
	}
}
