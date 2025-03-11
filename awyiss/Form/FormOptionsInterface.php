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
	 * @param array $requestData
	 * @param bool $submitted
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	public function modifyForm(Form $form, array $requestData, bool $submitted, Page $page): void;


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
	 * @param array $requestData
	 * @param bool $submitted
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, array $requestData, bool $submitted, Page $page): void;


	/**
	 * Sets the conditional recipient based on the request data.
	 * This method should modify the form's `ownerEmail`-property.
	 *
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param array $requestData
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return string|null
	 */
	public function setConditionalRecipient(Form $form, array $requestData, Page $page): static;


	/**
	 * Returns the timeout for the duplicate check in seconds.
	 * A form can only be sent every x seconds with the same values.
	 *
	 * @return int|null
	 */
	public function getDuplicateCheckTimeout(): ?int;


	/**
	 * Sets the timeout for the duplicate check in seconds.
	 *
	 * @param int|null $duplicateCheckTimeout
	 * @return $this
	 */
	public function setDuplicateCheckTimeout(?int $duplicateCheckTimeout): static;


	/**
	 * Returns the timeout for the IP check in seconds.
	 * The same IP address can only send a form every x seconds.
	 *
	 * @return int|null
	 */
	public function getIpCheckTimeout(): ?int;


	/**
	 * Sets the timeout for the IP check in seconds.
	 *
	 * @param int|null $ipCheckTimeout
	 * @return $this
	 */
	public function setIpCheckTimeout(?int $ipCheckTimeout): static;


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
