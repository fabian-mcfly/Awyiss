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
use DateInterval;
use DateTimeImmutable;


/**
 * Class AltchaFormProtection
 *
 * This class implements the Altcha form protection.
 * Altcha is a JavaScript-based CAPTCHA alternative that uses a
 * challenge-response mechanism to verify that the user is human.
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

		$this->options = Hash::merge(
			$this->defaultOptions,
			$this->formOptions->getProtectionOptions('altcha') ?? [],
		);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getHtml(string $templatePosition): ?string {
		if ($templatePosition === static::POSITION_BEFORE) {
			/** @var \Awyiss\View\Helper\AssetHelper $assetHelper */
			$assetHelper = $this->view->helpers()->get('Asset');
			$assetHelper->add('Frontend/Captcha/altcha.i18n.js', ['realm' => 'Backend', 'type' => 'module']);
		}

		if ($templatePosition === static::POSITION_BEFORE_SUBMIT) {
			/** @var \Awyiss\View\Helper\HtmlHelper $htmlHelper */
			$htmlHelper = $this->view->helpers()->get('Html');

			if (empty($this->options['htmlAttributes']['challengejson'])) {
				$altcha = new Altcha($this->options['securityKey'] ?? Security::getSalt());

				// Create a new challenge
				$options = new ChallengeOptions(
					expires: new DateTimeImmutable()->add(new DateInterval('PT20M')),
					maxNumber: $this->options['maxNumber'] ?? 200_000,
				);

				$this->options['htmlAttributes']['challengejson'] = $altcha->createChallenge($options);
			}
			if (!is_string($this->options['htmlAttributes']['challengejson'])) {
				$this->options['htmlAttributes']['challengejson'] = json_encode($this->options['htmlAttributes']['challengejson']);
			}

			$formattedAttributes = $htmlHelper->templater()->formatAttributes($this->options['htmlAttributes']);

			return '<altcha-widget ' . sprintf('%s', $formattedAttributes) . '></altcha-widget>';
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function validateData(array $data): string|true {
		$fieldName = $this->defaultOptions['htmlAttributes']['name'];

		if (empty($data[ $fieldName ])) {
			return __d('Form', 'altcha_error');
		}

		$altcha = new Altcha($this->options['securityKey'] ?? Security::getSalt());
		if (!$altcha->verifySolution($data[ $fieldName ])) {
			return __d('Form', 'altcha_error');
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
