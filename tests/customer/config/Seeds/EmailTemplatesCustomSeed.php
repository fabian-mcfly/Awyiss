<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Email Templates seed.
 */
class EmailTemplatesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Default',
				'textHtml' => '<p>Folgende Daten wurden gesendet:</p><p>{{$data}}</p>',
				'textPlain' => 'Folgende Daten wurden gesendet:

{{$data}}',
				'fileName' => 'dummy',
				'layout' => 'default.twig',
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'title' => 'Confirmation',
				'textHtml' => '<p><strong>{{$salutation|Hallo}},</strong></p>
<p><strong>vielen Dank f&uuml;r Ihre Anfrage.</strong> Folgende Daten wurden an uns &uuml;bermittelt:</p>
<p>{{$data}}</p>
<p>Mit freundlichen Gr&uuml;&szlig;en</p>
<p><strong>Max Mustermann</strong><br>Musterstra&szlig;e 123<br>12345 Musterstadt</p>
<p>Telefon: <a href="tel:+491234567890"><strong>0123-456789-0</strong></a><br>E-Mail: <a href="mailto:max@mustermann.de">max@mustermann.de</a></p>
<p>&nbsp;</p>
<p><a href="de/impressum">Impressum</a> | <a href="de/datenschutz">Datenschutz</a></p>',
				'textPlain' => '{{$salutation|Hallo}},

vielen Dank für Ihre Anfrage. Folgende Daten wurden an uns übermittelt:


{{$data}}


Mit freundlichen Grüßen

Max Mustermann
Musterstraße 123
12345 Musterstadt

Telefon: 0123-456789-0
E-Mail: max@mustermann.de



Impressum: {{$baseUrl}}de/impressum
Datenschutz: {{$baseUrl}}de/datenschutz',
				'fileName' => 'dummy',
				'layout' => 'default.twig',
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('email_templates');
		$table->insert($data)->save();
	}
}
