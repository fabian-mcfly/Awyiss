<?php declare(strict_types=1);


namespace Awyiss\Form;


use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Validation\Validator;


/**
 * Interface for form options
 */
interface FormOptionsInterface {
	/**
	 * Returns the form validation object
	 * with the rules for the form.
	 *
	 * @param \Awyiss\Validation\Validator $validator
	 * @param \Awyiss\Model\Entity\Form $form
	 * @return \Awyiss\Validation\Validator
	 */
	public function getValidator(Validator $validator, Form $form): Validator;


	/**
	 * Allows to modify the form, based on the request data,
	 * either to change the options or to add additional data,
	 * before or after the form has been submitted (but not yet sent).
	 *
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return static
	 */
	public function modifyForm(Form $form, ?Page $page = null): static;


	/**
	 * Allows to modify the form elements, based on the request data,
	 * either to change the options or to add additional data,
	 * before or after the form has been submitted (but not yet sent).
	 *
	 * This method might set the following options for the form element:
	 *  - 'options' (array): The options for the form element.
	 *  - 'disabled' (array|bool): Whether the form element should be disabled,
	 *  or an array with the keys of the options that should be disabled.
	 *  - 'readonly' (array|bool): Whether the form element should be readonly,
	 *  or an array with the keys of the options that should be readonly.
	 *  - 'value' (mixed): The default value for the form element when the form was not submitted.
	 *
	 * If a free text element should be displayed in the email,
	 * set the `showInEmail`to `true`.
	 * You can also set the clean text version for plain text emails
	 * as the `textPlain`-property.
	 *
	 * If the values, and not the keys, of checkboxes, radios,
	 * or selects should be displayed in the email,
	 * set the `showValueInEmail`-property to `true`.
	 *
	 * @param \Awyiss\Model\Entity\FormElement $formElement
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return static
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, ?Page $page = null): static;


	/**
	 * Sets the conditional recipient based on the request data.
	 * This method should modify the form's `ownerEmail`-property.
	 *
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return string|null
	 */
	public function setConditionalRecipient(Form $form, ?Page $page = null): static;


	/**
	 * Returns the protection options for the provided identifier.
	 *
	 * The returned array should contain all non-default options
	 * for the given protection method.
	 *
	 * The structure itself isn't defined and can be freely chosen.
	 * Each protection method uses different keys, or none at all.
	 *
	 * @param string $identifier
	 * @return array|null
	 */
	public function getProtectionOptions(string $identifier): ?array;


	/**
	 * Indicates whether the real sender should be used as the sender (= empty value),
	 * or if the site owner's email should be used as the sender (= safe email address).
	 * This should ensure that no mailserver denies the email
	 * due to the sender not having the same origin as the site.
	 *
	 * @return string|null
	 */
	public function getSafeRealSender(): ?string;


	/**
	 * Sets the safe real sender.
	 *
	 * @param string|null $safeRealSender
	 * @return $this
	 */
	public function setSafeRealSender(?string $safeRealSender): static;
}
