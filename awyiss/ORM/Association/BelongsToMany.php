<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\ORM\Association\BelongsToMany as BaseBelongsToMany;


/**
 * {@inheritDoc}
 *
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class BelongsToMany extends BaseBelongsToMany {
	use ExistsTrait;


	/**
	 * Gets the current join table, either the name of the Table instance or the instance itself.
	 *
	 * @return bool
	 */
	public function hasThrough(): bool {
		return isset($this->_through);
	}
}
