<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


/**
 * {@inheritDoc}
 *
 * Re-implemented 1:1 so it'll use ExistsTrait
 */
class BelongsToMany extends \Cake\ORM\Association\BelongsToMany {
	use ExistsTrait;


	/**
	 * Gets the current join table, either the name of the Table instance or the instance itself.
	 *
	 * @return bool
	 */
	public function hasThrough (): bool {
		return isset($this->_through);
	}
}
