<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Global;


use Awyiss\Event\Global\PagesListener;
use Awyiss\Test\TestSuite\TestCase;


/**
 * PagesListener Test Case
 *
 * @see \Awyiss\Event\Global\PagesListener
 */
class PagesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Global\PagesListener
	 */
	protected PagesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new PagesListener();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\PagesListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Pages.beforeFind' => 'beforeFind',
			'Model.Newscategories.beforeFind' => 'beforeFind',
			'Model.News.beforeFind' => 'beforeFind',
			'Model.Products.beforeFind' => 'beforeFind',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\PagesListener::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithPageRoleCheck(): void {
		$tableLocator = $this->getTableLocator();
		$pagesTable = $tableLocator->get('Pages');

		$result = $pagesTable->find()->all();

		$this->assertCount(33, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\PagesListener::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithoutPageRoleCheck(): void {
		$tableLocator = $this->getTableLocator();
		$pagesTable = $tableLocator->get('Pages');

		$result = $pagesTable->find('all', skipPageRoleCheck: true)->all();

		$this->assertCount(43, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\PagesListener::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWithoutPageRoleCheckOrdersByPageRoleEnumFirst(): void {
		$tableLocator = $this->getTableLocator();
		$pagesTable = $tableLocator->get('Pages');

		$result = $pagesTable->find('all', skipPageRoleCheck: true)->all();
		$pages = $result->combine('id', 'title')->toArray();

		$this->assertSame([
			1 => 'Startseite',
			3 => 'Unternehmensgeschichte',
			9 => 'Seefracht',
			15 => 'Übersicht der Schiffe',
			20 => 'Anmeldung/Registrierung',
			25 => 'Offene Stellen',
			50 => 'Startseite (Spanish)',
			2 => 'Über uns',
			4 => 'Mission und Vision',
			10 => 'Luftfracht',
			16 => 'Technische Daten',
			26 => 'Ausbildungsprogramme',
			5 => 'Teamvorstellung',
			8 => 'Dienstleistungen',
			11 => 'Landtransport',
			17 => 'Sicherheitsstandards',
			22 => 'Dokumentenverwaltung',
			27 => 'Mitarbeiterbenefits',
			6 => 'Zertifikate und Auszeichnungen',
			12 => 'Lagerung und Logistik',
			14 => 'Flotte',
			18 => 'Umweltfreundlichkeit',
			23 => 'Rechnungsübersicht',
			28 => 'Bewerbungsprozess',
			7 => 'Aktuelles',
			13 => 'Zollabwicklung',
			19 => 'Kundenbereich',
			24 => 'Karriere',
			29 => 'Kontakt',
			30 => 'Impressum',
			31 => 'Datenschutzrichtlinien',
			32 => 'Fehler 404',
			33 => 'Fehler 410',
			36 => 'Branchennews',
			59 => 'Jobnews',
			34 => 'Fachartikel',
			35 => 'Unternehmensnews',
			40 => 'Nicht noch ne Katze',
			21 => 'Sendungsverfolgung',
			37 => 'Neues CMS auf CakePHP-Basis revolutioniert Webentwicklung',
			41 => 'asdf',
			39 => 'Dummynews #2',
			38 => 'Dummynews #1',
		], $pages);
	}
}
