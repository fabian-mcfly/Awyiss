<?php declare(strict_types=1);


namespace Awyiss\Utility\Mail;


use Awyiss\Core\App;
use Cake\Core\Configure;


/**
 * MailSender class
 * Handles sending mail by loading mail elements from the frontend view
 * and rendering them with provided data and mail settings.
 * Unlike FormSender, this class does not require forms or mail templates
 * from the database. Instead, it loads mail elements as Twig templates.
 */
class MailSender {
	/**
	 * The mail layout (without path, e.g. 'default')
	 *
	 * @var string
	 */
	protected string $layoutName = 'default';
	/**
	 * The mail format ('html', 'text' or 'both')
	 *
	 * @var string
	 */
	protected string $mailFormat = 'both';
	/**
	 * The sender mail address (can be a user-provided address)
	 *
	 * @var string
	 */
	protected string $senderEmail = '';
	/**
	 * The sender name
	 *
	 * @var string|null
	 */
	protected ?string $senderName = null;
	/**
	 * The real safe sender (from site's domain)
	 *
	 * @var string|null
	 */
	protected ?string $safeRealSender = null;
	/**
	 * The recipient mail address
	 *
	 * @var string
	 */
	protected string $recipientEmail = '';
	/**
	 * The recipient name
	 *
	 * @var string|null
	 */
	protected ?string $recipientName = null;
	/**
	 * The mail subject
	 *
	 * @var string
	 */
	protected string $subject = '';
	/**
	 * The data to pass to the mail element
	 *
	 * @var array
	 */
	protected array $data = [];
	/**
	 * CC recipients
	 *
	 * @var array
	 */
	protected array $cc = [];
	/**
	 * BCC recipients
	 *
	 * @var array
	 */
	protected array $bcc = [];
	/**
	 * The mail element name (without path, e.g. 'password-reset')
	 *
	 * @var string
	 */
	protected string $template;
	/**
	 * The mail element path (e.g. 'Users' . DS . 'Emails')
	 *
	 * @var string
	 */
	protected string $templatePath;
	/**
	 * The transport profile to use for sending the mail
	 *
	 * @var string
	 */
	protected string $transportProfile = 'default';


	/**
	 * Constructor
	 *
	 * @param string $mailerProfile Mailer profile name
	 */
	public function __construct(string $mailerProfile = 'default') {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className($mailerProfile);

		$domain = $mailer->getDomain();
		if (substr_count($domain, '.') > 1) {
			$domainParts = explode('.', $domain);
			$domain = implode('.', array_slice($domainParts, -2));
		}

		$this->safeRealSender = 'noreply@' . $domain;
	}


	/**
	 * Set the sender email address
	 *
	 * @param string $email Sender mail address
	 * @return $this
	 */
	public function setSenderEmail(string $email): self {
		$this->senderEmail = $email;

		return $this;
	}


	/**
	 * Set the sender name
	 *
	 * @param string|null $name Sender name
	 * @return $this
	 */
	public function setSenderName(?string $name): self {
		$this->senderName = $name;

		return $this;
	}


	/**
	 * Set the recipient email address
	 *
	 * @param string $email Recipient mail address
	 * @return $this
	 */
	public function setRecipientEmail(string $email): self {
		$this->recipientEmail = $email;

		return $this;
	}


	/**
	 * Set the recipient name
	 *
	 * @param string|null $name Recipient name
	 * @return $this
	 */
	public function setRecipientName(?string $name): self {
		$this->recipientName = $name;

		return $this;
	}


	/**
	 * Set the mail subject
	 *
	 * @param string $subject Mail subject
	 * @return $this
	 */
	public function setSubject(string $subject): self {
		$this->subject = $subject;

		return $this;
	}


	/**
	 * Set the transport profile
	 *
	 * @param string $profile Transport profile name
	 * @return $this
	 */
	public function setTransportProfile(string $profile): self {
		$this->transportProfile = $profile;

		return $this;
	}


	/**
	 * Set the mail layout
	 *
	 * @param string $layout Layout name (e.g. 'default')
	 * @return $this
	 */
	public function setLayout(string $layout): self {
		$this->layoutName = $layout;

		return $this;
	}


	/**
	 * Set the mail format
	 *
	 * @param string $format Format: 'both', 'html', or 'text'
	 * @return $this
	 */
	public function setFormat(string $format): self {
		$this->mailFormat = $format;

		return $this;
	}


	/**
	 * Set the data to pass to the mail element
	 *
	 * @param array $data The data array
	 * @return $this
	 */
	public function setData(array $data): self {
		$this->data = $data;

		return $this;
	}


	/**
	 * Add a CC recipient
	 *
	 * @param string $email Mail address
	 * @param string|null $name Recipient name
	 * @return $this
	 */
	public function addCc(string $email, ?string $name = null): self {
		$this->cc[] = ['email' => $email, 'name' => $name];

		return $this;
	}


	/**
	 * Add a BCC recipient
	 *
	 * @param string $email Mail address
	 * @param string|null $name Recipient name
	 * @return $this
	 */
	public function addBcc(string $email, ?string $name = null): self {
		$this->bcc[] = ['email' => $email, 'name' => $name];

		return $this;
	}


	/**
	 * Send the mail
	 *
	 * @return bool
	 */
	public function send(): bool {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('default');

		$mailer->setTransport($this->transportProfile);

		// Use safeRealSender if fromEmail is empty
		$senderEmail = !empty($this->senderEmail) ? $this->senderEmail : $this->safeRealSender;

		$mailer
			->setFrom($senderEmail, $this->senderName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'))
			->setSubject($this->subject)
			->setTo($this->recipientEmail, $this->recipientName);

		foreach ($this->cc as $cc) {
			$mailer->addCc($cc['email'], $cc['name']);
		}

		foreach ($this->bcc as $bcc) {
			$mailer->addBcc($bcc['email'], $bcc['name']);
		}

		// Handle safe sender
		if ($this->safeRealSender && $senderEmail !== $this->safeRealSender) {
			$mailer
				->setFrom($this->safeRealSender, $this->senderName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'))
				->setReplyTo($senderEmail, $this->senderName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'));
		}

		$mailer->setRenderer(new MailRenderer());

		$mailer
			->viewBuilder()
			->setTemplate($this->template)
			->setTemplatePath($this->templatePath)
			->setLayout('email/' . $this->layoutName)
			->setVars($this->data);


		if ($this->mailFormat !== 'both') {
			$mailer->setEmailFormat($this->mailFormat);
		}

		$sendData = $mailer->deliver();

		return !!$sendData;
	}


	/**
	 * @param string $elementName
	 * @return $this
	 */
	public function setTemplate(string $elementName): static {
		$this->template = $elementName;

		return $this;
	}


	/**
	 * @param string $path
	 * @param string $prefix
	 * @return $this
	 */
	public function setTemplatePath(string $path, string $prefix = 'Frontend' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR): static {
		$this->templatePath = $prefix . $path;

		return $this;
	}
}
