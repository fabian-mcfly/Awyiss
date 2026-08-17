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
	 * Re-implemented 1:1 but accepts and passes an array of options a second parameter
	 *
	 * @inheritDoc
	 */
	public function exists(ExpressionInterface|Closure|array|string|null $conditions, array $options = []): bool {
		$conditions = $this
			->find()
			->where($conditions)
			->clause('where')
		;

		/** @var \Awyiss\Model\Table $table */
		$table = $this->getTarget();

		return $table->exists($conditions, $options);
	}
}
