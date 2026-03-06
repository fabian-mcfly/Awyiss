<?php declare(strict_types=1);


namespace Customer\Form\Protection;


use Awyiss\Form\FormOptionsInterface;
use Awyiss\Form\Protection\FormProtectionInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Cake\Utility\Hash;
use Cake\View\View;


/**
 * Class DummyFormProtection
 * Used for testing purposes.
 */
class DummyFormProtection implements FormProtectionInterface {
	/**
	 * @var array
	 */
	protected array $defaultOptions = [];
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
	 * @inheritDoc
	 */
	public function initialize(Form $form, array $formElements, FormOptionsInterface $formOptions, View $view): static {
		$this->form = $form;
		$this->formElements = $formElements;
		$this->formOptions = $formOptions;
		$this->view = $view;

		$this->options = Hash::merge(
			$this->formOptions->getProtectionOptions('hiddenInput') ?? [],
			$this->defaultOptions,
		);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getHtml(string $templatePosition): ?string {
		if ($templatePosition === static::POSITION_BEFORE) {
			return 'dummyBefore';
		}

		if ($templatePosition === static::POSITION_BEFORE_SUBMIT) {
			return 'dummyBeforeSubmit';
		}

		if ($templatePosition === static::POSITION_AFTER) {
			return 'dummyAfter';
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function validateData(array $data): string|true {
		return true;
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
		$formEntry->subjectConfirmation = 'Dummy Subject Confirmation';

		return $formEntry;
	}
}
