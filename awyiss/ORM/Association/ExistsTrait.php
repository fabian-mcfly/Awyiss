<?php declare(strict_types=1);


namespace Awyiss\ORM\Association;


use Cake\Database\ExpressionInterface;
use Closure;


/**
 * Offers a simple overwrite of the exists() method found in `\Cake\ORM\Association`
 *
 * @see \Cake\ORM\Association::exists()
 */
trait ExistsTrait {
	/**
	 * Proxies the operation to the target table's exists method after
	 * appending the default conditions for this association
	 * Re-implemented 1:1 but accepts and passes an array of options a second parameter
	 *
	 * @param \Cake\Database\ExpressionInterface|\Closure|array|string|null $conditions The conditions to use for checking if any record matches.
	 * @param array $options Additional options to pass to the exists() method of the table
	 * @return bool
	 * @see Table::exists
	 */
	public function exists(ExpressionInterface|Closure|array|string|null $conditions, array $options = []): bool {
		$la_conditions = $this->find()->where($conditions)->clause('where');

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->getTarget();


		return $lo_table->exists($la_conditions, $options);
	}
}
