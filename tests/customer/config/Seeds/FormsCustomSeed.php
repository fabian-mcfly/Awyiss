<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Forms seed.
 */
class FormsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'title' => 'Kontaktformular',
				'identifier' => 'contact',
				'send_email' => 1,
				'email_template_id' => 1,
				'send_confirmation_email' => 1,
				'confirmation_email_template_id' => 3,
				'owner_email' => 'awyiss@cms.de',
				'owner_name' => null,
				'user_email' => '$email',
				'user_name' => '$vorname $nachname',
				'cc' => '[]',
				'bcc' => '[]',
				'subject' => null,
				'subject_confirmation' => 'Betreff für Bestätigung',
				'salutation' => null,
				'salutation_confirmation' => 'Hallo $vorname $nachname',
				'success_message' => '<h1 style="text-align: center;">Vielen Dank für Ihre Anfrage{{,<br>$firstname $lastname|}}.</h1>
<p style="text-align: center;">Wir werden uns zeitnah mit Ihnen, {{$vorname $nachname|lieber Kunde oder Kunde-to-be}}, in Verbindung setzen. Versprochen.</p>',
				'multistep' => 0,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$lo_table = $this->table('forms');
		$lo_table->insert($la_data)->save();
	}
}
