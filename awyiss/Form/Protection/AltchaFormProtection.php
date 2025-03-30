<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use Awyiss\Form\FormOptionsInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\View\View;


/**
 * Class IpCheckFormProtection
 *
 * Checks if any form has already been
 * submitted by the user within a certain time frame.
 */
class AltchaFormProtection implements FormProtectionInterface {
	use LocatorAwareTrait;


	/**
	 * @var array
	 */
	protected array $defaultOptions = [
		'htmlAttributes' => [
			'auto' => 'onfocus',
			'delay' => 0,
			'hidefooter' => true,
			'hidelogo' => false,
			'name' => '_altcha',
		],
		'maxNumber' => 300_000,
		'securityKey' => null,
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
	 * @inheritDoc
	 */
	public function initialize(Form $form, array $formElements, FormOptionsInterface $formOptions, View $view): static {
		$this->form = $form;
		$this->formElements = $formElements;
		$this->formOptions = $formOptions;
		$this->view = $view;

		$this->defaultOptions['htmlAttributes']['strings'] = [
			'ariaLinkLabel' => __d('form', 'altcha_aria_link_label'),
			'error' => __d('form', 'altcha_error'),
			'expired' => __d('form', 'altcha_expired'),
			'footer' => __d('form', 'altcha_footer'),
			'label' => __d('form', 'altcha_label'),
			'verified' => __d('form', 'altcha_verified'),
			'verifying' => __d('form', 'altcha_verifying'),
			'waitAlert' => __d('form', 'altcha_wait_alert'),
		];

		$this->options = Hash::merge(
			$this->formOptions->getProtectionOptions('ipCheck') ?? [],
			$this->defaultOptions,
		);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getHtml(string $templatePosition): ?string {
		if ($templatePosition === static::POSITION_BEFORE) {
			/** @var \Awyiss\View\Helper\AssetHelper $lo_assetHelper */
			$lo_assetHelper = $this->view->helpers()->get('Asset');
			$lo_assetHelper->add('Frontend/Captcha/altcha.js', ['realm' => 'Backend', 'type' => 'module']);
		}

		if ($templatePosition === static::POSITION_BEFORE_SUBMIT) {
			/** @var \Cake\View\Helper\HtmlHelper $lo_helper */
			$lo_helper = $this->view->helpers()->get('Html');

			if (empty($this->options['htmlAttributes']['challengejson'])) {
				$lo_altcha = new Altcha($this->options['securityKey'] ?? Security::getSalt());

				// Create a new challenge
				$lo_options = new ChallengeOptions(
					maxNumber: $this->options['maxNumber'] ?? 200_000,
				);

				$this->options['htmlAttributes']['challengejson'] = $lo_altcha->createChallenge($lo_options);
			}
			if (!is_string($this->options['htmlAttributes']['challengejson'])) {
				$this->options['htmlAttributes']['challengejson'] = json_encode($this->options['htmlAttributes']['challengejson']);
			}


			if (is_array($this->options['htmlAttributes']['strings'])) {
				$this->options['htmlAttributes']['strings'] = json_encode($this->options['htmlAttributes']['strings']);
			}

			$ls_attributes = $lo_helper->templater()->formatAttributes($this->options['htmlAttributes']);

			return '<altcha-widget ' . sprintf('%s', $ls_attributes) . '></altcha-widget>';
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function validateData(array $data): string|true {
		$ls_fieldName = $this->defaultOptions['htmlAttributes']['name'];

		if (empty($data[ $ls_fieldName ])) {
			return __d('form', 'altcha_error');
		}

		$lo_altcha = new Altcha($this->options['securityKey'] ?? Security::getSalt());
		if (!$lo_altcha->verifySolution($data[ $ls_fieldName ])) {
			return __d('form', 'altcha_error');
		}

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
		return $formEntry;
	}
}
