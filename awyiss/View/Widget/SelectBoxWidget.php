<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\View\Widget\SelectBoxWidget as BaseSelectBoxWidget;


/**
 * @inheritDoc
 */
class SelectBoxWidget extends BaseSelectBoxWidget {
	/**
	 * Re-implemented to
	 * - limit the length of the text
	 * - add the original text as the title
	 *
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function _renderOptions(
		iterable $options,
		?array $disabled,
		mixed $selected,
		array $templateVars,
		bool $escape,
	): array {
		$out = [];
		foreach ($options as $key => $val) {
			// Option groups
			$isIterable = is_iterable($val);
			/** @var \ArrayAccess|array $val */
			if (
				(
					!is_int($key) &&
					$isIterable
				) ||
				(
					is_int($key) &&
					$isIterable &&
					(
						isset($val['options']) ||
						!isset($val['value'])
					)
				)
			) {
				/** @var \ArrayAccess<string, mixed>|array<string, mixed> $val */
				$out[] = $this->_renderOptgroup((string)$key, $val, $disabled, $selected, $templateVars, $escape);
				continue;
			}

			// Basic options
			$optAttrs = [
				'value' => $key,
				'text' => $val,
				'templateVars' => [],
				'title' => $val,
			];
			if (is_array($val) && isset($val['text'], $val['value'])) {
				/** @var array<string, mixed> $optAttrs */
				$optAttrs = $val;
				$key = $optAttrs['value'];
			}
			$optAttrs['templateVars'] ??= [];
			if ($this->_isSelected((string)$key, $selected)) {
				$optAttrs['selected'] = true;
			}
			if ($this->_isDisabled((string)$key, $disabled)) {
				$optAttrs['disabled'] = true;
			}
			if ($templateVars) {
				$optAttrs['templateVars'] = array_merge($templateVars, $optAttrs['templateVars']);
			}
			$optAttrs['escape'] = $escape;

			$out[] = $this->_templates->format('option', [
				'value' => $escape ? h($optAttrs['value']) : $optAttrs['value'],
				'text' => mb_substr((string)($escape ? h($optAttrs['text']) : $optAttrs['text']), 0, 100),
				'templateVars' => $optAttrs['templateVars'],
				'attrs' => $this->_templates->formatAttributes($optAttrs, ['text', 'value']),
			]);
		}

		return $out;
	}
}
