<?php declare(strict_types=1);


namespace Customer\Form\Protection;


use Awyiss\Form\Protection\HiddenInputFormProtection as BaseHiddenInputFormProtection;


/**
 * Class HiddenInputFormProtection
 * Test version of the hidden input form protection that adds
 * output for every call of `getHtml`
 */
class HiddenInputFormProtection extends BaseHiddenInputFormProtection {
	/**
	 * @inheritDoc
	 */
	public function getHtml(string $templatePosition): ?string {
		if ($templatePosition === static::POSITION_BEFORE) {
			return '<div class="hidden-input-protection">This is a test for the before position.</div>';
		}

		if ($templatePosition === static::POSITION_BEFORE_SUBMIT) {
			return '<div class="hidden-input-protection">This is a test for the before submit position.</div>';
		}

		if ($templatePosition === static::POSITION_AFTER) {
			return '<div class="hidden-input-protection">This is a test for the after position.</div>';
		}

		return null;
	}
}
