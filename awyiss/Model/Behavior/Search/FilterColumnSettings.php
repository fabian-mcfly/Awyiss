<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Search;


/**
 * DTO for filter column settings in SearchBehavior
 */
readonly class FilterColumnSettings {
	/**
	 * @param array|null $disabledOperators
	 * @param bool $nullable
	 * @param int|null $maxLength
	 * @param string|null $operator
	 * @param string $type
	 * @param mixed $value
	 * @param array|null $values
	 * @param string|null $title
	 */
	public function __construct(
		public ?array $disabledOperators,
		public bool $nullable,
		public ?int $maxLength,
		public ?string $operator,
		public string $type,
		public mixed $value,
		public ?array $values,
		public ?string $title = null,
	) {
	}
}
