<?php declare(strict_types=1);


namespace Awyiss\Form;


use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormConditionalRecipient;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\ComparisonOperator;
use BackedEnum;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use InvalidArgumentException;
use OutOfBoundsException;


/**
 * Class for conditional recipients
 */
class FormConditionalRecipients {
	/**
	 * The first rule that matches will be used.
	 */
	public const string PROCESS_STRATEGY_MATCH_FIRST = 'match_first';
	/**
	 * All rules must match.
	 * The last rule defines the recipient.
	 */
	public const string PROCESS_STRATEGY_MATCH_ALL = 'match_all';
	/**
	 * The last rule that matches will be used.
	 */
	public const string PROCESS_STRATEGY_MATCH_LAST = 'match_last';


	/**
	 * The current page the form was submitted on.
	 *
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected ?Page $currentPage;
	/**
	 * The form the conditional recipients are for.
	 *
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected Form $form;
	/**
	 * The strategy to process the rules.
	 *
	 * @var string
	 */
	protected string $processStrategy = self::PROCESS_STRATEGY_MATCH_FIRST;


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param \Awyiss\Model\Entity\Page|null $currentPage
	 */
	public function __construct(Form $form, ?Page $currentPage = null) {
		$this->currentPage = $currentPage;
		$this->form = $form;
	}


	/**
	 * Checks if the rule matches the request data.
	 * The strategy to process the rules is defined in the property `processStrategy`.
	 *
	 * @param array $conditionalRecipients
	 * @param array $requestData
	 * @return string|null
	 */
	public function getMatchingRecipient(array $conditionalRecipients, array $requestData): ?string {
		return match ($this->processStrategy) {
			self::PROCESS_STRATEGY_MATCH_FIRST => $this->getFirstMatchingRecipient($conditionalRecipients, $requestData),
			self::PROCESS_STRATEGY_MATCH_ALL => $this->getAllMatchingRecipient($conditionalRecipients, $requestData),
			self::PROCESS_STRATEGY_MATCH_LAST => $this->getLastMatchingRecipient($conditionalRecipients, $requestData),
		};
	}


	/**
	 * @param array<\Awyiss\Model\Entity\FormConditionalRecipient> $conditionalRecipients
	 * @param array $requestData
	 * @return string|null
	 */
	public function getFirstMatchingRecipient(array $conditionalRecipients, array $requestData): ?string {
		foreach ($conditionalRecipients as $lo_conditionalRecipient) {
			if ($this->ruleMatches($lo_conditionalRecipient, $requestData)) {
				return $lo_conditionalRecipient->recipient;
			}
		}

		return null;
	}


	/**
	 * @param array<\Awyiss\Model\Entity\FormConditionalRecipient> $conditionalRecipients
	 * @param array $requestData
	 * @return string|null
	 */
	public function getLastMatchingRecipient(array $conditionalRecipients, array $requestData): ?string {
		$la_conditionalRecipients = array_reverse($conditionalRecipients);

		foreach ($la_conditionalRecipients as $lo_conditionalRecipient) {
			if ($this->ruleMatches($lo_conditionalRecipient, $requestData)) {
				return $lo_conditionalRecipient->recipient;
			}
		}

		return null;
	}


	/**
	 * @param array<\Awyiss\Model\Entity\FormConditionalRecipient> $conditionalRecipients
	 * @param array $requestData
	 * @return string|null
	 */
	public function getAllMatchingRecipient(array $conditionalRecipients, array $requestData): ?string {
		$ls_recipient = '';

		foreach ($conditionalRecipients as $lo_conditionalRecipient) {
			if (!$this->ruleMatches($lo_conditionalRecipient, $requestData)) {
				return null;
			}

			$ls_recipient = $lo_conditionalRecipient->recipient;
		}

		return $ls_recipient;
	}


	/**
	 * @param \Awyiss\Model\Entity\FormConditionalRecipient $conditionalRecipient
	 * @param array $requestData
	 * @return bool
	 */
	public function ruleMatches(FormConditionalRecipient $conditionalRecipient, array $requestData): bool {
		try {
			$lx_value = $this->getFieldValue($conditionalRecipient->type, $conditionalRecipient->field, $requestData);
		}
		catch (OutOfBoundsException) {
			return false;
		}

		[$lx_compareValue, $lx_value] = $this->alignTypes($conditionalRecipient, $lx_value);

		return match ($conditionalRecipient->operator->value) {
			'=' => $this->compareEqualTo($lx_value, $lx_compareValue),
			'!=' => $this->compareEqualTo($lx_value, $lx_compareValue, true),
			'<' => $this->compareGreaterThan($lx_value, $lx_compareValue, false, true),
			'<=' => $this->compareGreaterThan($lx_value, $lx_compareValue, true, true),
			'>' => $this->compareGreaterThan($lx_value, $lx_compareValue),
			'>=' => $this->compareGreaterThan($lx_value, $lx_compareValue, true),
			'between' => $this->compareBetween($lx_value, $lx_compareValue),
			'not_between' => $this->compareBetween($lx_value, $lx_compareValue, true),
			'length_equal' => $this->compareLengthEqualTo($lx_value, $lx_compareValue),
			'length_not_equal' => $this->compareLengthEqualTo($lx_value, $lx_compareValue, true),
			'shorter_than' => $this->compareLongerThan($lx_value, $lx_compareValue, false, true),
			'shorter_than_or_equal' => $this->compareLongerThan($lx_value, $lx_compareValue, true, true),
			'longer_than' => $this->compareLongerThan($lx_value, $lx_compareValue),
			'longer_than_or_equal' => $this->compareLongerThan($lx_value, $lx_compareValue, true),
			'in' => $this->compareIn($lx_value, $lx_compareValue),
			'not_in' => $this->compareIn($lx_value, $lx_compareValue, true),
			'contains' => $this->compareContains($lx_value, $lx_compareValue),
			'not_contains' => $this->compareContains($lx_value, $lx_compareValue, true),
			'starts_with' => $this->compareStartsWith($lx_value, $lx_compareValue),
			'not_starts_with' => $this->compareStartsWith($lx_value, $lx_compareValue, true),
			'ends_with' => $this->compareEndsWith($lx_value, $lx_compareValue),
			'not_ends_with' => $this->compareEndsWith($lx_value, $lx_compareValue, true),
			'regexp' => $this->compareRegexp($lx_value, $lx_compareValue),
		};
	}


	/**
	 * @param string $type
	 * @param string $field
	 * @param array $requestData
	 * @return mixed
	 */
	public function getFieldValue(string $type, string $field, array $requestData): mixed {
		if ($type === 'element_identifier') {
			if (!array_key_exists($field, $requestData)) {
				throw new OutOfBoundsException('Field not found in request data');
			}

			if (!$this->form->formElements) {
				throw new OutOfBoundsException('Form elements not found');
			}

			$la_elements = $this->form->formElements->listNested()->filter(function ($formElement) {
				return !in_array($formElement->type, ['fieldset', 'hidden', 'free_text', 'submit']);
			})->indexBy('identifier')->toArray();

			if (!array_key_exists($field, $la_elements)) {
				throw new OutOfBoundsException('Field not found in form elements');
			}

			/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
			$lo_formElement = $la_elements[ $field ];
			if ($lo_formElement->type === 'date') {
				return !empty($requestData[ $field ]) ? new Date($requestData[ $field ]) : null;
			}
			elseif ($lo_formElement->type === 'time') {
				return !empty($requestData[ $field ]) ? new Time($requestData[ $field ]) : null;
			}
			elseif ($lo_formElement->type === 'datetime') {
				return !empty($requestData[ $field ]) ? new DateTime($requestData[ $field ]) : null;
			}

			return $requestData[ $field ];
		}

		if ($type === 'current_page') {
			if (!$this->currentPage) {
				return null;
			}

			if ($this->currentPage->has($field)) {
				return $this->currentPage->get($field);
			}

			if ($this->currentPage->has('attributes') && $this->currentPage->attributes->has($field)) {
				return $this->currentPage->attributes->get($field);
			}

			throw new OutOfBoundsException('Field not found in request data');
		}

		throw new InvalidArgumentException('Invalid field type');
	}


	/**
	 * Compare if the values are equal (not identical).
	 * An empty string will match null,
	 * 'john' will match 'John'.
	 *
	 * If value and compare value are both arrays,
	 * the values of the arrays will be compared while ignoring the order.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareEqualTo(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (empty($value) && empty($compareValue)) {
			return !$not;
		}

		if (is_string($value) && is_string($compareValue)) {
			if ($not) {
				return strtolower($value) != strtolower($compareValue);
			}

			return strtolower($value) == strtolower($compareValue);
		}

		if (is_array($value) && is_array($compareValue)) {
			if ($not) {
				return array_diff($value, $compareValue) != [];
			}

			return array_diff($value, $compareValue) == [];
		}

		if ($not) {
			return $value != $compareValue;
		}

		return $value == $compareValue;
	}


	/**
	 * Compare if the value is greater than the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $orEqual
	 * @param bool $not
	 * @return bool
	 */
	public function compareGreaterThan(mixed $value, mixed $compareValue, bool $orEqual = false, bool $not = false): bool {
		if (
			(!is_numeric($value) || !is_numeric($compareValue)) &&
			!($value instanceof Date) &&
			!($value instanceof Time) &&
			!($value instanceof DateTime)
		) {
			if (is_null($compareValue)) {
				return !$not;
			}

			return false;
		}

		if ($orEqual) {
			if ($not) {
				return $value <= $compareValue;
			}

			return $value >= $compareValue;
		}

		if ($not) {
			return $value < $compareValue;
		}

		return $value > $compareValue;
	}


	/**
	 * Compare if the value is between the compare values.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareBetween(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			!is_array($compareValue) ||
			count($compareValue) !== 2
		) {
			return false;
		}

		$la_compareValues = array_values($compareValue);

		// If any value of the compare values is not numeric and not a date, time or datetime instance, the rule is invalid
		if (
			array_filter(
				$la_compareValues,
				fn($value) => !is_numeric($value) && !($value instanceof Date) && !($value instanceof Time) && !($value instanceof DateTime)
			)
		) {
			return false;
		}

		if (
			!is_numeric($value) &&
			!($value instanceof Date) &&
			!($value instanceof Time) &&
			!($value instanceof DateTime)
		) {
			return false;
		}

		if ($not) {
			return $value < $la_compareValues[0] || $value > $la_compareValues[1];
		}

		return $value >= $la_compareValues[0] && $value <= $la_compareValues[1];
	}


	/**
	 * Compare if the length of the value is equal to the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareLengthEqualTo(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (!is_scalar($value) && !is_array($value)) {
			return false;
		}

		$li_valueLength = is_array($value) ? count($value) : strlen((string)$value);
		$li_compareValueLength = (int)$compareValue;

		if ($not) {
			return $li_valueLength != $li_compareValueLength;
		}

		return $li_valueLength == $li_compareValueLength;
	}


	/**
	 * Compare if the value is longer than the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $orEqual
	 * @param bool $not
	 * @return bool
	 */
	public function compareLongerThan(mixed $value, mixed $compareValue, bool $orEqual = false, bool $not = false): bool {
		if (!is_scalar($value) && !is_array($value)) {
			return false;
		}

		$li_valueLength = is_array($value) ? count($value) : strlen((string)$value);
		$li_compareValueLength = (int)$compareValue;

		if ($orEqual) {
			if ($not) {
				return $li_valueLength <= $li_compareValueLength;
			}

			return $li_valueLength >= $li_compareValueLength;
		}

		if ($not) {
			return $li_valueLength < $li_compareValueLength;
		}

		return $li_valueLength > $li_compareValueLength;
	}


	/**
	 * Compare if the value is in the compare values.
	 *
	 * If the value is an array, all values must be in the compare values.
	 * The order of the values and compare values are irrelevant.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareIn(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (!is_array($compareValue)) {
			return false;
		}

		if (!is_scalar($value) && !is_array($value)) {
			return false;
		}

		$lx_value = is_scalar($value) ? strtolower($value) : array_map('strtolower', $value);
		$la_compareValues = array_map('strtolower', $compareValue);

		if (is_scalar($value)) {
			if ($not) {
				return !in_array($lx_value, $la_compareValues);
			}

			return in_array($lx_value, $la_compareValues);
		}

		if ($not) {
			return array_intersect($lx_value, $la_compareValues) != $lx_value;
		}

		return array_intersect($lx_value, $la_compareValues) == $lx_value;
	}


	/**
	 * Compare if the value contains the compare value.
	 * If the value is a scalar, the comparison uses str_contains.
	 * If the value is an array, in_array is used.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareContains(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			(
				!is_scalar($value) &&
				!is_array($value)
			) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		$lx_value = is_scalar($value) ? strtolower($value) : array_map('strtolower', $value);
		$lx_compareValue = strtolower($compareValue);

		if (is_scalar($value)) {
			if ($not) {
				return !str_contains($lx_value, $lx_compareValue);
			}

			return str_contains($lx_value, $lx_compareValue);
		}

		if ($not) {
			return !in_array($lx_compareValue, $lx_value);
		}

		return in_array($lx_compareValue, $lx_value);
	}


	/**
	 * Compare if the value starts with the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareStartsWith(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			!is_scalar($value) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		$lx_value = strtolower($value);
		$lx_compareValue = strtolower($compareValue);

		if ($not) {
			return !str_starts_with($lx_value, $lx_compareValue);
		}

		return str_starts_with($lx_value, $lx_compareValue);
	}


	/**
	 * Compare if the value ends with the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	public function compareEndsWith(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			!is_scalar($value) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		$lx_value = strtolower($value);
		$lx_compareValue = strtolower($compareValue);

		if ($not) {
			return !str_ends_with($lx_value, $lx_compareValue);
		}

		return str_ends_with($lx_value, $lx_compareValue);
	}


	/**
	 * Compare if the value matches the compare value as a regular expression.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @return bool
	 */
	public function compareRegexp(mixed $value, mixed $compareValue): bool {
		if (
			!is_scalar($value) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		return (bool)preg_match($compareValue, $value);
	}


	/**
	 * @return string
	 */
	public function getProcessStrategy(): string {
		return $this->processStrategy;
	}


	/**
	 * @param string $processStrategy
	 * @return $this
	 */
	public function setProcessStrategy(string $processStrategy): static {
		if (!in_array($processStrategy, [self::PROCESS_STRATEGY_MATCH_FIRST, self::PROCESS_STRATEGY_MATCH_ALL, self::PROCESS_STRATEGY_MATCH_LAST])) {
			throw new InvalidArgumentException('Invalid process strategy');
		}

		$this->processStrategy = $processStrategy;

		return $this;
	}


	/**
	 * @param \Awyiss\Model\Entity\FormConditionalRecipient $conditionalRecipient
	 * @param mixed $value
	 * @return array
	 */
	protected function alignTypes(FormConditionalRecipient $conditionalRecipient, mixed $value): array {
		$lx_value = $value;

		$lx_compareValue = $conditionalRecipient->value;

		if (
			in_array($conditionalRecipient->operator, [
				ComparisonOperator::Between,
				ComparisonOperator::NotBetween,
				ComparisonOperator::In,
				ComparisonOperator::NotIn,
			]) ||
			(
				is_array($lx_value) &&
				in_array($conditionalRecipient->operator, [
					ComparisonOperator::Equal,
					ComparisonOperator::NotEqual,
				])
			)
		) {
			$lx_compareValue = is_null($lx_compareValue) ? [] : explode(',', $lx_compareValue);

			// Trim all values
			$lx_compareValue = array_map('trim', $lx_compareValue);
		}

		if (is_object($lx_value)) {
			if ($lx_value instanceof BackedEnum) {
				$lx_value = (string)$lx_value->value;
			}
			elseif ($lx_value instanceof Date && $lx_compareValue) {
				if (is_array($lx_compareValue)) {
					// Convert all values to Date instances
					$lx_compareValue = array_map(fn($value) => new Date($value), $lx_compareValue);
				}
				else {
					$lx_compareValue = new Date($lx_compareValue);
				}
			}
			elseif ($lx_value instanceof Time && $lx_compareValue) {
				if (is_array($lx_compareValue)) {
					// Convert all values to Time instances
					$lx_compareValue = array_map(fn($value) => new Time($value), $lx_compareValue);
				}
				else {
					$lx_compareValue = new Time($lx_compareValue);
				}
			}
			elseif ($lx_value instanceof DateTime && $lx_compareValue) {
				if (is_array($lx_compareValue)) {
					// Convert all values to DateTime instances
					$lx_compareValue = array_map(fn($value) => new DateTime($value), $lx_compareValue);
				}
				else {
					$lx_compareValue = new DateTime($lx_compareValue);
				}
			}
		}

		return [$lx_compareValue, $lx_value];
	}
}
