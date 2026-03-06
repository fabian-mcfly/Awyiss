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
use Stringable;


/**
 * Class for conditional recipients
 */
class FormConditionalRecipients {
	/**
	 * The first rule that matches will be used.
	 */
	public const string PROCESS_STRATEGY_MATCH_FIRST = 'matchFirst';
	/**
	 * All rules must match.
	 * The last rule defines the recipient.
	 */
	public const string PROCESS_STRATEGY_MATCH_ALL = 'matchAll';
	/**
	 * The last rule that matches will be used.
	 */
	public const string PROCESS_STRATEGY_MATCH_LAST = 'matchLast';


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
		foreach ($conditionalRecipients as $conditionalRecipient) {
			if ($this->ruleMatches($conditionalRecipient, $requestData)) {
				return $conditionalRecipient->recipient;
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
		$conditionalRecipients = array_reverse($conditionalRecipients);

		foreach ($conditionalRecipients as $conditionalRecipient) {
			if ($this->ruleMatches($conditionalRecipient, $requestData)) {
				return $conditionalRecipient->recipient;
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
		$recipient = '';

		foreach ($conditionalRecipients as $conditionalRecipient) {
			if (!$this->ruleMatches($conditionalRecipient, $requestData)) {
				return null;
			}

			$recipient = $conditionalRecipient->recipient;
		}

		return $recipient;
	}


	/**
	 * @param \Awyiss\Model\Entity\FormConditionalRecipient $conditionalRecipient
	 * @param array $requestData
	 * @return bool
	 */
	protected function ruleMatches(FormConditionalRecipient $conditionalRecipient, array $requestData): bool {
		try {
			$value = $this->getFieldValue($conditionalRecipient->type, $conditionalRecipient->field, $requestData);
		}
		catch (OutOfBoundsException) {
			return false;
		}

		[$compareValue, $value] = $this->alignTypes($conditionalRecipient, $value);

		return match ($conditionalRecipient->operator->value) {
			'=' => $this->compareEqualTo($value, $compareValue),
			'!=' => $this->compareEqualTo($value, $compareValue, true),
			'<' => $this->compareGreaterThan($value, $compareValue, false, true),
			'<=' => $this->compareGreaterThan($value, $compareValue, true, true),
			'>' => $this->compareGreaterThan($value, $compareValue),
			'>=' => $this->compareGreaterThan($value, $compareValue, true),
			'between' => $this->compareBetween($value, $compareValue),
			'notBetween' => $this->compareBetween($value, $compareValue, true),
			'lengthEqual' => $this->compareLengthEqualTo($value, $compareValue),
			'lengthNotEqual' => $this->compareLengthEqualTo($value, $compareValue, true),
			'shorterThan' => $this->compareLongerThan($value, $compareValue, false, true),
			'shorterThanOrEqual' => $this->compareLongerThan($value, $compareValue, true, true),
			'longerThan' => $this->compareLongerThan($value, $compareValue),
			'longerThanOrEqual' => $this->compareLongerThan($value, $compareValue, true),
			'in' => $this->compareIn($value, $compareValue),
			'notIn' => $this->compareIn($value, $compareValue, true),
			'contains' => $this->compareContains($value, $compareValue),
			'notContains' => $this->compareContains($value, $compareValue, true),
			'startsWith' => $this->compareStartsWith($value, $compareValue),
			'notStartsWith' => $this->compareStartsWith($value, $compareValue, true),
			'endsWith' => $this->compareEndsWith($value, $compareValue),
			'notEndsWith' => $this->compareEndsWith($value, $compareValue, true),
			'regexp' => $this->compareRegexp($value, $compareValue),
		};
	}


	/**
	 * @param string $type
	 * @param string $field
	 * @param array $requestData
	 * @return mixed
	 */
	protected function getFieldValue(string $type, string $field, array $requestData): mixed {
		if ($type === 'elementIdentifier') {
			if (!array_key_exists($field, $requestData)) {
				throw new OutOfBoundsException('Field not found in request data');
			}

			if (!$this->form->formElements) {
				throw new OutOfBoundsException('Form elements not found');
			}

			$elements = $this->form->formElements->listNested()->filter(function ($formElement) {
				return !in_array($formElement->type, ['fieldset', 'hidden', 'freeText', 'submit']);
			})->indexBy('identifier')->toArray();

			if (!array_key_exists($field, $elements)) {
				throw new OutOfBoundsException('Field not found in form elements');
			}

			/** @var \Awyiss\Model\Entity\FormElement $formElement */
			$formElement = $elements[ $field ];
			if ($formElement->type === 'date') {
				return !empty($requestData[ $field ]) ? new Date($requestData[ $field ]) : null;
			}
			elseif ($formElement->type === 'time') {
				return !empty($requestData[ $field ]) ? new Time($requestData[ $field ]) : null;
			}
			elseif ($formElement->type === 'datetime') {
				return !empty($requestData[ $field ]) ? new DateTime($requestData[ $field ]) : null;
			}

			return $requestData[ $field ];
		}

		if ($type === 'currentPage') {
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
	protected function compareEqualTo(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (empty($value) && empty($compareValue)) {
			return !$not;
		}

		if (is_string($value) && is_string($compareValue)) {
			$value = strtolower($value);
			$compareValue = strtolower($compareValue);

			if ($not) {
				return $value != $compareValue;
			}

			return $value == $compareValue;
		}

		if (is_array($value) && is_array($compareValue)) {
			$value = array_map('strtolower', $value);
			$compareValue = array_map('strtolower', $compareValue);

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
	protected function compareGreaterThan(mixed $value, mixed $compareValue, bool $orEqual = false, bool $not = false): bool {
		if (
			!(is_numeric($value) || $this->isDateOrTime($value)) ||
			!(is_numeric($compareValue) || $this->isDateOrTime($value))
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
	protected function compareBetween(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			!is_array($compareValue) ||
			count($compareValue) !== 2
		) {
			return false;
		}

		$compareValues = array_values($compareValue);

		// If any value of the compare values is not numeric and not a date, time or datetime instance, the rule is invalid
		if (
			array_filter(
				$compareValues,
				function (mixed $value): bool {
					return !(is_numeric($value) || $this->isDateOrTime($value));
				}
			)
		) {
			return false;
		}

		if (!(is_numeric($value) || $this->isDateOrTime($value))) {
			return false;
		}

		if ($not) {
			return $value < $compareValues[0] || $value > $compareValues[1];
		}

		return $value >= $compareValues[0] && $value <= $compareValues[1];
	}


	/**
	 * Compare if the length of the value is equal to the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	protected function compareLengthEqualTo(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (!is_scalar($value) && !is_array($value)) {
			return false;
		}

		$valueLength = is_array($value) ? count($value) : strlen((string)$value);
		$compareValueLength = (int)$compareValue;

		if ($not) {
			return $valueLength != $compareValueLength;
		}

		return $valueLength == $compareValueLength;
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
	protected function compareLongerThan(mixed $value, mixed $compareValue, bool $orEqual = false, bool $not = false): bool {
		if (!is_scalar($value) && !is_array($value)) {
			return false;
		}

		$valueLength = is_array($value) ? count($value) : strlen((string)$value);
		$compareValueLength = (int)$compareValue;

		if ($orEqual) {
			if ($not) {
				return $valueLength <= $compareValueLength;
			}

			return $valueLength >= $compareValueLength;
		}

		if ($not) {
			return $valueLength < $compareValueLength;
		}

		return $valueLength > $compareValueLength;
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
	protected function compareIn(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (!is_array($compareValue)) {
			return false;
		}

		if (!is_scalar($value) && !is_array($value)) {
			return false;
		}

		$compareValues = array_map('strtolower', $compareValue);

		if (is_scalar($value)) {
			$value = strtolower((string)$value);

			if ($not) {
				return !in_array($value, $compareValues);
			}

			return in_array($value, $compareValues);
		}

		$value = array_map('strtolower', $value);

		if ($not) {
			return array_intersect($value, $compareValues) != $value;
		}

		return array_intersect($value, $compareValues) == $value;
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
	protected function compareContains(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			(
				!is_scalar($value) &&
				!is_array($value)
			) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		$compareValue = strtolower($compareValue);

		if (is_scalar($value)) {
			$value = strtolower((string)$value);

			if ($not) {
				return !str_contains($value, $compareValue);
			}

			return str_contains($value, $compareValue);
		}

		$value = array_map('strtolower', $value);

		if ($not) {
			return !in_array($compareValue, $value);
		}

		return in_array($compareValue, $value);
	}


	/**
	 * Compare if the value starts with the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	protected function compareStartsWith(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			!is_scalar($value) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		$value = strtolower((string)$value);
		$compareValue = strtolower((string)$compareValue);

		if ($not) {
			return !str_starts_with($value, $compareValue);
		}

		return str_starts_with($value, $compareValue);
	}


	/**
	 * Compare if the value ends with the compare value.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @param bool $not
	 * @return bool
	 */
	protected function compareEndsWith(mixed $value, mixed $compareValue, bool $not = false): bool {
		if (
			!is_scalar($value) ||
			!is_scalar($compareValue)
		) {
			return false;
		}

		$value = strtolower((string)$value);
		$compareValue = strtolower((string)$compareValue);

		if ($not) {
			return !str_ends_with($value, $compareValue);
		}

		return str_ends_with($value, $compareValue);
	}


	/**
	 * Compare if the value matches the compare value as a regular expression.
	 *
	 * @param mixed $value
	 * @param mixed $compareValue
	 * @return bool
	 */
	protected function compareRegexp(mixed $value, mixed $compareValue): bool {
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
		$compareValue = $conditionalRecipient->value;

		if (
			in_array($conditionalRecipient->operator, [
				ComparisonOperator::Between,
				ComparisonOperator::NotBetween,
				ComparisonOperator::In,
				ComparisonOperator::NotIn,
			]) ||
			(
				is_array($value) &&
				in_array($conditionalRecipient->operator, [
					ComparisonOperator::Equal,
					ComparisonOperator::NotEqual,
				])
			)
		) {
			$compareValue ??= [];
			$compareValue = !is_array($compareValue) ? explode(',', $compareValue) : $compareValue;

			// Trim all values
			$compareValue = array_map(function (mixed $value): mixed {
				return is_string($value) ? trim($value) : $value;
			}, $compareValue);
		}

		if (!is_object($value)) {
			return [$compareValue, $value];
		}

		if ($value instanceof BackedEnum) {
			$value = (string)$value->value;
		}
		elseif ($value instanceof Date && $compareValue) {
			if (is_array($compareValue)) {
				// Convert all values to Date instances
				$compareValue = array_map(function (mixed $value): Date {
					if ($value instanceof Date) {
						return $value;
					}

					return new Date($value);
				}, $compareValue);
			}
			elseif (!$compareValue instanceof Date) {
				$compareValue = new Date($compareValue);
			}
		}
		elseif ($value instanceof Time && $compareValue) {
			if (is_array($compareValue)) {
				// Convert all values to Time instances
				$compareValue = array_map(function (mixed $value): Time {
					if ($value instanceof Time) {
						return $value;
					}

					return new Time($value);
				}, $compareValue);
			}
			elseif (!$compareValue instanceof Time) {
				$compareValue = new Time($compareValue);
			}
		}
		elseif ($value instanceof DateTime && $compareValue) {
			if (is_array($compareValue)) {
				// Convert all values to DateTime instances
				$compareValue = array_map(function (mixed $value): DateTime {
					if ($value instanceof DateTime) {
						return $value;
					}

					return new DateTime($value);
				}, $compareValue);
			}
			elseif (!$compareValue instanceof DateTime) {
				$compareValue = new DateTime($compareValue);
			}
		}

		return [$compareValue, $value];
	}


	/**
	 * @param mixed $value
	 * @return bool
	 */
	protected function isDateOrTime(mixed $value): bool {
		if ($value instanceof Date || $value instanceof Time || $value instanceof DateTime) {
			return true;
		}

		if (!is_scalar($value) && !($value instanceof Stringable)) {
			return false;
		}

		$value = (string)$value;

		// If the value matches \d{4}-\d{2}-\d{2} it is a date
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
			return true;
		}

		// If the value matches \d{2}:\d{2}(:\d{2})? it is a time
		if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
			return true;
		}

		// If the value matches \d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})? it is a datetime
		if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
			return true;
		}

		return false;
	}
}
