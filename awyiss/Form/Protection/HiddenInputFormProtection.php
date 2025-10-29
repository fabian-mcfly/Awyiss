<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use Awyiss\Form\FormOptionsInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Cake\Utility\Hash;
use Cake\View\View;


/**
 * Class HiddenInputFormProtection
 * Adds a hidden input field to the form to prevent
 * spambots from submitting the form
 */
class HiddenInputFormProtection implements FormProtectionInterface {
	/**
	 * @var array
	 */
	protected array $defaultOptions = [
		'elementName' => 'email_confirmation',
	];
	/**
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected Form $form;
	/**
	 * @var array<\Awyiss\Model\Entity\FormElement>
	 */
	protected array $formElements;
	/**
	 * @var \Awyiss\Form\FormOptionsInterface
	 */
	protected FormOptionsInterface $formOptions;
	/**
	 * Settings for the protection.
	 *
	 * @var array<string, mixed>
	 */
	protected array $options;
	/**
	 * @var \Cake\View\View
	 */
	protected View $view;


	/**
	 * @return string
	 */
	public function getFieldName(): string {
		$ls_elementName = $this->options['elementName'];

		// 'emailConfirmation' is the last resort since form elements are all underscored
		$la_alternatives = ['email_confirmation', 'mail_confirmation', 'e_mail_confirmation', 'mail', 'e_mail', 'emailConfirmation'];

		// Check if the name is already used by form elements and try alternatives until a free name is found
		while (!$ls_elementName || array_key_exists($ls_elementName, $this->formElements)) {
			$ls_elementName = array_shift($la_alternatives);
		}

		return $ls_elementName;
	}


	/**
	 * @inheritDoc
	 */
	public function initialize(Form $form, array $formElements, FormOptionsInterface $formOptions, View $view): static {
		$this->form = $form;
		$this->formElements = $formElements;
		$this->formOptions = $formOptions;
		$this->view = $view;

		$this->options = Hash::merge(
			$this->defaultOptions,
			$this->formOptions->getProtectionOptions('hiddenInput') ?? [],
		);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getHtml(string $templatePosition): ?string {
		if ($templatePosition === static::POSITION_BEFORE) {
			/** @noinspection PhpUnhandledExceptionInspection */
			$ls_randomString = 'c' . bin2hex(random_bytes(8));

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$ls_nonce = $this->view->helpers()->get('Asset')->getStyleNonce();
			$ls_css = '<style nonce="' . $ls_nonce . '">.c' . $ls_randomString . ' { position:absolute; visibility:hidden; }</style>';

			return $ls_css . '<input type="email" name="' . $this->getFieldName() . '" value="" class="c' . $ls_randomString . '">';
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function validateData(array $data): string|true {
		$ls_fieldName = $this->getFieldName();

		return empty($data[ $ls_fieldName ]) ? true : __d('form', 'protection_method_hidden_input_error_field_empty');
	}


	/**
	 * @inheritDoc
	 */
	public function modifyForm(Form $form): void {
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormEntry(FormEntry $formEntry): FormEntry|bool {
		return $formEntry;
	}
}
