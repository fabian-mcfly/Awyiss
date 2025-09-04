<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use Awyiss\Form\FormOptionsInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Routing\Router;
use Cake\I18n\DateTime;
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
class IpCheckFormProtection implements FormProtectionInterface {
	use LocatorAwareTrait;


	/**
	 * @var array
	 */
	protected array $defaultOptions = [
		'checkTimeout' => 300, // 5 minutes
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
			$this->formOptions->getProtectionOptions('ipCheck') ?? [],
		);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getHtml(string $templatePosition): ?string {
		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function validateData(array $data): string|true {
		$lo_request = Router::getRequest();
		$ls_clientIp = $lo_request->clientIp();

		$ls_ipHash = Security::hash($ls_clientIp . Security::getSalt());

		// Check if the data exists in the database already
		$lo_formEntriesTable = $this->fetchTable('FormEntries');

		$li_timeout = $this->options['checkTimeout'];
		$lo_timeoutDate = new DateTime();

		if (
			$lo_formEntriesTable->exists([
				'ip_hash' => $ls_ipHash,
				'created_on >=' => $lo_timeoutDate->subSeconds($li_timeout),
			])
		) {
			return __d('form', 'protection_method_ip_check_error_duplicate_found');
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
