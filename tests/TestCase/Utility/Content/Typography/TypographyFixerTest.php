<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleRegistry;


/**
 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer
 */
class TypographyFixerTest extends TestCase {
	/**
	 * @noinspection HtmlDeprecatedAttribute
	 */
	protected string $exampleHtml = <<<'HTML'
<p>
	Das ist ein "falsches Zitat" und hier kommt ein &quot;weiteres falsches Zitat&quot;.<br>
	Korrekt wäre eigentlich „dieses Zitat“ - aber auch hier wurde der falsche Strich verwendet.<br>
</p>

<p>
	Achtung ! Das ist falsch .<br>
	Wirklich ? Ja !<br>
	Hier : ebenfalls falsch ;<br>
	und auch das ist falsch ,<br>
	obwohl es direkt am Satzzeichen stehen sollte.<br>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/id:555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/id:555/)</a>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/?id=555)</a>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit.php?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit.php?id=555)</a>
</p>

<p>
	Noch ein Beispiel : "Hallo Welt !" Und noch eines : "Wie geht es dir ?"<br>
</p>

<p>
	Das ist ein - Einschub - mit Bindestrichen .<br>
	Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text&quot; .<br>
	Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text" .<br>
	Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text) .<br>
</p>

<h1>
	Das ist ein eine Headline -<br>
	mit zweiter Zeile aber Bindestrich davor.
</h1>

<h1>
	Das ist ein eine Headline<br>
	- mit zweiter Zeile, die mit Bindestrich beginnt.
</h1>

<p>
	Von Montag - Freitag sind wir geöffnet.<br>
	Öffnungszeiten : Montag - Freitag, 08:00 - 17:00 Uhr.<br>
	Öffnungszeiten : Montag - Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Uhrzeiten.<br>
	Öffnungszeiten : Montag-Freitag, 08:00 - 17:00 Uhr ohne Leerzeichen zwischen den Wochentagen.<br>
	Öffnungszeiten : Montag-Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Wochentagen und den Uhrzeiten.<br>
</p>

<p>
	Der Zeitraum beträgt 10 - 15 Minuten.<br>
	Der Zeitraum beträgt 10-15 Minuten ohne Leerzeichen.<br>
	Die Strecke ist 5 - 10 km lang.<br>
	Die Strecke ist 5-10 km lang ohne Leerzeichen.<br>
	Das Ergebnis liegt bei 50 - 100 %.<br>
	Das Ergebnis liegt bei 50-100 % ohne Leerzeichen.<br>
</p>

<p>
	Unser Angebot gilt vom 01.09.2026 - 30.09.2026.<br>
	Der Preis liegt zwischen 99,00 € - 149,00 €.<br>
</p>

<p>
	Das ist ein &ndash; korrekt gesetzter Gedankenstrich –<br>
	und hier noch einer &mdash; allerdings als HTML-Entity.<br>
</p>

<p>
	Ein langer Satz mit einem "Zitat" - danach geht es weiter -<br>
	und hier noch ein - eingeschobener - Teil.<br>
</p>

<p>
	"Das ist falsch."<br>
	"Das ist auch falsch!"<br>
	"Und das ist falsch?"<br>
</p>

<p>
	"Zitat mit falschen Anführungszeichen"<br>
	&quot;Noch ein falsches Zitat&quot;<br>
	„Dieses ist korrekt.“<br>
	„Aber dieses "Zitat" ist gemischt.“<br>
</p>

<p>
	Er sagte : "Das funktioniert nicht."<br>
	Sie antwortete : "Warum nicht ?"<br>
	Darauf sagte er : "Weil es falsch gesetzt ist !"<br>
</p>

<p>
	Bitte beachten Sie : Die folgenden Angaben sind verbindlich.<br>
	Preis : 49,99 €<br>
	Rabatt : 20 %<br>
	Menge : 10 Stück<br>
</p>

<p>
	Das kostet 49,99€.<br>
	Das kostet 49,99 €.<br>
	Das kostet &euro;49,99.<br>
	Das kostet &euro; 49,99.<br>
</p>

<p>
	100 % Rabatt<br>
	100%<br>
	100 %<br>
	100 &percnt;<br>
</p>

<p>
	10 kg<br>
	10kg<br>
	10&nbsp;kg<br>
	10&nbsp;&nbsp;kg<br>
</p>

<p>
	10 Uhr<br>
	10Uhr<br>
	10&nbsp;Uhr<br>
	10&nbsp;&nbsp;Uhr<br>
</p>
<p>
	10 €<br>
	10€<br>
	10&nbsp;€<br>
	10&nbsp;&nbsp;€<br>
</p>

<p>
	Bitte lesen Sie die Hinweise !<br>
	Besonders wichtig : Abschnitt 3.<br>
	Haben Sie Fragen ?<br>
	Dann kontaktieren Sie uns !<br>
</p>

<p>
	Hallo , wie geht es dir ?<br>
	Das ist falsch ; ebenso wie das hier :<br>
	und auch dieses Beispiel , ist falsch.<br>
</p>

<p>
	Klammern ( falsch gesetzt )<br>
	[ ebenfalls falsch ]<br>
	/ auch hier falsch /<br>
</p>

<p>
	E-Mail : info @ muster.de<br>
	Tel. : 0931 / 123456 - 0<br>
	www . muster . de<br>
</p>

<p>
	Muster - GmbH<br>
	Muster GmbH<br>
	Service - Team<br>
	Service-Team<br>
	Sicherheits - konzept<br>
</p>

<p>
	"Muster &amp; Söhne" - Ihr Partner für Sicherheit.<br>
	&quot;Muster &amp; Söhne&quot; &ndash; Ihr Partner für Sicherheit.<br>
	„Muster & Söhne“ – Ihr Partner für Sicherheit.<br>
</p>

<p>
	Das Produkt kostet 1.299,99€ - inklusive 19% MwSt.<br>
	Der Preis beträgt 1.299,99 &euro; - zzgl. Versand.<br>
</p>

<p>
	Montag - Freitag<br>
	Montag–Freitag<br>
	Montag &ndash; Freitag<br>
	Montag &mdash; Freitag<br>
	Montag&nbsp;-&nbsp;Freitag<br>
</p>

<p>
	"Ein Zitat" - sagte Müller - "kann auch einen Einschub enthalten."<br>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
</p>

<p>
	Und hier kommen drei Punkte ...<br>
	und hier eine Ellipse …<br>
	und hier die Entity &hellip;<br>
	und hier "..." als vermeintliche Auslassung.<br>
</p>

<p>
	... am Anfang eines Satzes<br>
	...am Anfang eines Satzes ohne Leerzeichen<br>
	… am Anfang eines Satzes mit Ellipse<br>
	…am Anfang eines Satzes ohne Leerzeichen mit Ellipse<br>
</p>

<p>
	„Das war alles ...“,<br>
	„Das war alles…“,<br>
	„Das war alles …“,<br>
	&quot;Das war alles...&quot;,<br>
	&quot;Das war alles …&quot;.<br>
</p>
HTML;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		TypographyRuleRegistry::reset();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		TypographyRuleRegistry::reset();
		parent::tearDown();
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlFixesTypographyForGermanLanguage(): void {
		TypographyRuleRegistry::registerDefaults();

		$expected = <<<HTML
<p>
	Das ist ein „falsches Zitat“ und hier kommt ein „weiteres falsches Zitat“.<br>
	Korrekt wäre eigentlich „dieses Zitat“ – aber auch hier wurde der falsche Strich verwendet.<br>
</p>

<p>
	Achtung! Das ist falsch.<br>
	Wirklich? Ja!<br>
	Hier: ebenfalls falsch;<br>
	und auch das ist falsch,<br>
	obwohl es direkt am Satzzeichen stehen sollte.<br>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/id:555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/id:555/)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/?id=555)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit.php?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit.php?id=555)
</p>

<p>
	Noch ein Beispiel: „Hallo Welt !“ Und noch eines: „Wie geht es dir ?“<br>
</p>

<p>
	Das ist ein – Einschub – mit Bindestrichen.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text„.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text“.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text).<br>
</p>

<h1>
	Das ist ein eine Headline –<br>
	mit zweiter Zeile aber Bindestrich davor.
</h1>

<h1>
	Das ist ein eine Headline<br>
	- mit zweiter Zeile, die mit Bindestrich beginnt.
</h1>

<p>
	Von Montag – Freitag sind wir geöffnet.<br>
	Öffnungszeiten: Montag – Freitag, 08:00–17:00\u{202F}Uhr.<br>
	Öffnungszeiten: Montag – Freitag, 08:00–17:00\u{202F}Uhr ohne Leerzeichen zwischen den Uhrzeiten.<br>
	Öffnungszeiten: Montag-Freitag, 08:00–17:00\u{202F}Uhr ohne Leerzeichen zwischen den Wochentagen.<br>
	Öffnungszeiten: Montag-Freitag, 08:00–17:00\u{202F}Uhr ohne Leerzeichen zwischen den Wochentagen und den Uhrzeiten.<br>
</p>

<p>
	Der Zeitraum beträgt 10–15 Minuten.<br>
	Der Zeitraum beträgt 10–15 Minuten ohne Leerzeichen.<br>
	Die Strecke ist 5–10\u{202F}km lang.<br>
	Die Strecke ist 5–10\u{202F}km lang ohne Leerzeichen.<br>
	Das Ergebnis liegt bei 50–100\u{202F}%.<br>
	Das Ergebnis liegt bei 50–100\u{202F}% ohne Leerzeichen.<br>
</p>

<p>
	Unser Angebot gilt vom 01.09.2026–30.09.2026.<br>
	Der Preis liegt zwischen 99,00\u{202F}€ – 149,00\u{202F}€.<br>
</p>

<p>
	Das ist ein – korrekt gesetzter Gedankenstrich –<br>
	und hier noch einer — allerdings als HTML-Entity.<br>
</p>

<p>
	Ein langer Satz mit einem „Zitat“ – danach geht es weiter –<br>
	und hier noch ein – eingeschobener – Teil.<br>
</p>

<p>
	„Das ist falsch.“<br>
	„Das ist auch falsch!“<br>
	„Und das ist falsch?“<br>
</p>

<p>
	„Zitat mit falschen Anführungszeichen“<br>
	„Noch ein falsches Zitat“<br>
	„Dieses ist korrekt.“<br>
	„Aber dieses „Zitat“ ist gemischt.“<br>
</p>

<p>
	Er sagte: „Das funktioniert nicht.“<br>
	Sie antwortete: „Warum nicht ?“<br>
	Darauf sagte er: „Weil es falsch gesetzt ist !“<br>
</p>

<p>
	Bitte beachten Sie: Die folgenden Angaben sind verbindlich.<br>
	Preis: 49,99\u{202F}€<br>
	Rabatt: 20\u{202F}%<br>
	Menge: 10 Stück<br>
</p>

<p>
	Das kostet 49,99\u{202F}€.<br>
	Das kostet 49,99\u{202F}€.<br>
	Das kostet €49,99.<br>
	Das kostet € 49,99.<br>
</p>

<p>
	100\u{202F}% Rabatt<br>
	100\u{202F}%<br>
	100\u{202F}%<br>
	100\u{202F}%<br>
</p>

<p>
	10\u{202F}kg<br>
	10\u{202F}kg<br>
	10\u{202F}kg<br>
	10\u{202F}kg<br>
</p>

<p>
	10\u{202F}Uhr<br>
	10\u{202F}Uhr<br>
	10\u{202F}Uhr<br>
	10\u{202F}Uhr<br>
</p>
<p>
	10\u{202F}€<br>
	10\u{202F}€<br>
	10\u{202F}€<br>
	10\u{202F}€<br>
</p>

<p>
	Bitte lesen Sie die Hinweise!<br>
	Besonders wichtig: Abschnitt 3.<br>
	Haben Sie Fragen?<br>
	Dann kontaktieren Sie uns!<br>
</p>

<p>
	Hallo, wie geht es dir?<br>
	Das ist falsch; ebenso wie das hier:<br>
	und auch dieses Beispiel, ist falsch.<br>
</p>

<p>
	Klammern (falsch gesetzt)<br>
	[ebenfalls falsch]<br>
	/ auch hier falsch /<br>
</p>

<p>
	E-Mail: info @ muster.de<br>
	Tel.: 0931 / 123456–0<br>
	www. muster. de<br>
</p>

<p>
	Muster – GmbH<br>
	Muster GmbH<br>
	Service – Team<br>
	Service-Team<br>
	Sicherheits – konzept<br>
</p>

<p>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
</p>

<p>
	Das Produkt kostet 1.299,99\u{202F}€ – inklusive 19\u{202F}% MwSt.<br>
	Der Preis beträgt 1.299,99\u{202F}€ – zzgl. Versand.<br>
</p>

<p>
	Montag – Freitag<br>
	Montag–Freitag<br>
	Montag – Freitag<br>
	Montag — Freitag<br>
	Montag&nbsp;–&nbsp;Freitag<br>
</p>

<p>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
</p>

<p>
	Und hier kommen drei Punkte…<br>
	und hier eine Ellipse …<br>
	und hier die Entity …<br>
	und hier „…“ als vermeintliche Auslassung.<br>
</p>

<p>
	…am Anfang eines Satzes<br>
	…am Anfang eines Satzes ohne Leerzeichen<br>
	… am Anfang eines Satzes mit Ellipse<br>
	…am Anfang eines Satzes ohne Leerzeichen mit Ellipse<br>
</p>

<p>
	„Das war alles…“,<br>
	„Das war alles…“,<br>
	„Das war alles …“,<br>
	„Das war alles…“,<br>
	„Das war alles …“.<br>
</p>
HTML;

		$result = TypographyFixer::formatHtml($this->exampleHtml, 'de');

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlFixesTypographyForEnglishLanguage(): void {
		TypographyRuleRegistry::registerDefaults();

		$expected = <<<HTML
<p>
	Das ist ein “falsches Zitat” und hier kommt ein “weiteres falsches Zitat”.<br>
	Korrekt wäre eigentlich „dieses Zitat“ – aber auch hier wurde der falsche Strich verwendet.<br>
</p>

<p>
	Achtung! Das ist falsch.<br>
	Wirklich? Ja!<br>
	Hier: ebenfalls falsch;<br>
	und auch das ist falsch,<br>
	obwohl es direkt am Satzzeichen stehen sollte.<br>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/id:555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/id:555/)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/?id=555)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit.php?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit.php?id=555)
</p>

<p>
	Noch ein Beispiel: “Hallo Welt !” Und noch eines: “Wie geht es dir ?”<br>
</p>

<p>
	Das ist ein – Einschub – mit Bindestrichen.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text“.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text”.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text).<br>
</p>

<h1>
	Das ist ein eine Headline –<br>
	mit zweiter Zeile aber Bindestrich davor.
</h1>

<h1>
	Das ist ein eine Headline<br>
	- mit zweiter Zeile, die mit Bindestrich beginnt.
</h1>

<p>
	Von Montag – Freitag sind wir geöffnet.<br>
	Öffnungszeiten: Montag – Freitag, 08:00 – 17:00 Uhr.<br>
	Öffnungszeiten: Montag – Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Uhrzeiten.<br>
	Öffnungszeiten: Montag-Freitag, 08:00 – 17:00 Uhr ohne Leerzeichen zwischen den Wochentagen.<br>
	Öffnungszeiten: Montag-Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Wochentagen und den Uhrzeiten.<br>
</p>

<p>
	Der Zeitraum beträgt 10 – 15 Minuten.<br>
	Der Zeitraum beträgt 10-15 Minuten ohne Leerzeichen.<br>
	Die Strecke ist 5 – 10 km lang.<br>
	Die Strecke ist 5-10 km lang ohne Leerzeichen.<br>
	Das Ergebnis liegt bei 50 – 100%.<br>
	Das Ergebnis liegt bei 50-100% ohne Leerzeichen.<br>
</p>

<p>
	Unser Angebot gilt vom 01.09.2026 – 30.09.2026.<br>
	Der Preis liegt zwischen 99,00 € – 149,00 €.<br>
</p>

<p>
	Das ist ein – korrekt gesetzter Gedankenstrich –<br>
	und hier noch einer — allerdings als HTML-Entity.<br>
</p>

<p>
	Ein langer Satz mit einem “Zitat” – danach geht es weiter –<br>
	und hier noch ein – eingeschobener – Teil.<br>
</p>

<p>
	“Das ist falsch.”<br>
	“Das ist auch falsch!”<br>
	“Und das ist falsch?”<br>
</p>

<p>
	“Zitat mit falschen Anführungszeichen”<br>
	“Noch ein falsches Zitat”<br>
	„Dieses ist korrekt.“<br>
	„Aber dieses “Zitat” ist gemischt.“<br>
</p>

<p>
	Er sagte: “Das funktioniert nicht.”<br>
	Sie antwortete: “Warum nicht ?”<br>
	Darauf sagte er: “Weil es falsch gesetzt ist !”<br>
</p>

<p>
	Bitte beachten Sie: Die folgenden Angaben sind verbindlich.<br>
	Preis: 49,99 €<br>
	Rabatt: 20%<br>
	Menge: 10 Stück<br>
</p>

<p>
	Das kostet 49,99€.<br>
	Das kostet 49,99 €.<br>
	Das kostet €49,99.<br>
	Das kostet €49,99.<br>
</p>

<p>
	100% Rabatt<br>
	100%<br>
	100%<br>
	100%<br>
</p>

<p>
	10 kg<br>
	10kg<br>
	10&nbsp;kg<br>
	10&nbsp;&nbsp;kg<br>
</p>

<p>
	10 Uhr<br>
	10Uhr<br>
	10&nbsp;Uhr<br>
	10&nbsp;&nbsp;Uhr<br>
</p>
<p>
	10 €<br>
	10€<br>
	10&nbsp;€<br>
	10&nbsp;&nbsp;€<br>
</p>

<p>
	Bitte lesen Sie die Hinweise!<br>
	Besonders wichtig: Abschnitt 3.<br>
	Haben Sie Fragen?<br>
	Dann kontaktieren Sie uns!<br>
</p>

<p>
	Hallo, wie geht es dir?<br>
	Das ist falsch; ebenso wie das hier:<br>
	und auch dieses Beispiel, ist falsch.<br>
</p>

<p>
	Klammern (falsch gesetzt)<br>
	[ebenfalls falsch]<br>
	/ auch hier falsch /<br>
</p>

<p>
	E-Mail: info @ muster.de<br>
	Tel.: 0931 / 123456 – 0<br>
	www. muster. de<br>
</p>

<p>
	Muster – GmbH<br>
	Muster GmbH<br>
	Service – Team<br>
	Service-Team<br>
	Sicherheits – konzept<br>
</p>

<p>
	“Muster &amp; Söhne” – Ihr Partner für Sicherheit.<br>
	“Muster &amp; Söhne” – Ihr Partner für Sicherheit.<br>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
</p>

<p>
	Das Produkt kostet 1.299,99€ – inklusive 19% MwSt.<br>
	Der Preis beträgt 1.299,99 € – zzgl. Versand.<br>
</p>

<p>
	Montag – Freitag<br>
	Montag–Freitag<br>
	Montag – Freitag<br>
	Montag — Freitag<br>
	Montag&nbsp;–&nbsp;Freitag<br>
</p>

<p>
	“Ein Zitat” – sagte Müller – “kann auch einen Einschub enthalten.”<br>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
</p>

<p>
	Und hier kommen drei Punkte …<br>
	und hier eine Ellipse …<br>
	und hier die Entity …<br>
	und hier “…” als vermeintliche Auslassung.<br>
</p>

<p>
	… am Anfang eines Satzes<br>
	…am Anfang eines Satzes ohne Leerzeichen<br>
	… am Anfang eines Satzes mit Ellipse<br>
	…am Anfang eines Satzes ohne Leerzeichen mit Ellipse<br>
</p>

<p>
	„Das war alles …“,<br>
	„Das war alles…“,<br>
	„Das war alles …“,<br>
	“Das war alles…”,<br>
	“Das war alles …”.<br>
</p>
HTML;

		$result = TypographyFixer::formatHtml($this->exampleHtml, 'en');

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlFixesTypographyForFrenchLanguage(): void {
		TypographyRuleRegistry::registerDefaults();

		$expected = <<<HTML
<p>
	Das ist ein « falsches Zitat » und hier kommt ein « weiteres falsches Zitat ».<br>
	Korrekt wäre eigentlich „dieses Zitat“ – aber auch hier wurde der falsche Strich verwendet.<br>
</p>

<p>
	Achtung ! Das ist falsch.<br>
	Wirklich ? Ja !<br>
	Hier : ebenfalls falsch ;<br>
	und auch das ist falsch,<br>
	obwohl es direkt am Satzzeichen stehen sollte.<br>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/id:555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/id:555/)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/?id=555)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit.php?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit.php?id=555)
</p>

<p>
	Noch ein Beispiel : « Hallo Welt ! » Und noch eines : « Wie geht es dir ? »<br>
</p>

<p>
	Das ist ein – Einschub – mit Bindestrichen.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text«.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text ».<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text).<br>
</p>

<h1>
	Das ist ein eine Headline –<br>
	mit zweiter Zeile aber Bindestrich davor.
</h1>

<h1>
	Das ist ein eine Headline<br>
	- mit zweiter Zeile, die mit Bindestrich beginnt.
</h1>

<p>
	Von Montag – Freitag sind wir geöffnet.<br>
	Öffnungszeiten : Montag – Freitag, 08:00 – 17:00 Uhr.<br>
	Öffnungszeiten : Montag – Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Uhrzeiten.<br>
	Öffnungszeiten : Montag-Freitag, 08:00 – 17:00 Uhr ohne Leerzeichen zwischen den Wochentagen.<br>
	Öffnungszeiten : Montag-Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Wochentagen und den Uhrzeiten.<br>
</p>

<p>
	Der Zeitraum beträgt 10 – 15 Minuten.<br>
	Der Zeitraum beträgt 10-15 Minuten ohne Leerzeichen.<br>
	Die Strecke ist 5 – 10 km lang.<br>
	Die Strecke ist 5-10 km lang ohne Leerzeichen.<br>
	Das Ergebnis liegt bei 50 – 100 %.<br>
	Das Ergebnis liegt bei 50-100 % ohne Leerzeichen.<br>
</p>

<p>
	Unser Angebot gilt vom 01.09.2026 – 30.09.2026.<br>
	Der Preis liegt zwischen 99,00 € – 149,00 €.<br>
</p>

<p>
	Das ist ein – korrekt gesetzter Gedankenstrich –<br>
	und hier noch einer — allerdings als HTML-Entity.<br>
</p>

<p>
	Ein langer Satz mit einem « Zitat » – danach geht es weiter –<br>
	und hier noch ein – eingeschobener – Teil.<br>
</p>

<p>
	« Das ist falsch. »<br>
	« Das ist auch falsch ! »<br>
	« Und das ist falsch ? »<br>
</p>

<p>
	« Zitat mit falschen Anführungszeichen »<br>
	« Noch ein falsches Zitat »<br>
	„Dieses ist korrekt.“<br>
	„Aber dieses « Zitat » ist gemischt.“<br>
</p>

<p>
	Er sagte : « Das funktioniert nicht. »<br>
	Sie antwortete : « Warum nicht ? »<br>
	Darauf sagte er : « Weil es falsch gesetzt ist ! »<br>
</p>

<p>
	Bitte beachten Sie : Die folgenden Angaben sind verbindlich.<br>
	Preis : 49,99 €<br>
	Rabatt : 20 %<br>
	Menge : 10 Stück<br>
</p>

<p>
	Das kostet 49,99 €.<br>
	Das kostet 49,99 €.<br>
	Das kostet €49,99.<br>
	Das kostet € 49,99.<br>
</p>

<p>
	100 % Rabatt<br>
	100 %<br>
	100 %<br>
	100 %<br>
</p>

<p>
	10 kg<br>
	10kg<br>
	10&nbsp;kg<br>
	10&nbsp;&nbsp;kg<br>
</p>

<p>
	10 Uhr<br>
	10Uhr<br>
	10&nbsp;Uhr<br>
	10&nbsp;&nbsp;Uhr<br>
</p>
<p>
	10 €<br>
	10 €<br>
	10 €<br>
	10 €<br>
</p>

<p>
	Bitte lesen Sie die Hinweise !<br>
	Besonders wichtig : Abschnitt 3.<br>
	Haben Sie Fragen ?<br>
	Dann kontaktieren Sie uns !<br>
</p>

<p>
	Hallo, wie geht es dir ?<br>
	Das ist falsch ; ebenso wie das hier :<br>
	und auch dieses Beispiel, ist falsch.<br>
</p>

<p>
	Klammern (falsch gesetzt)<br>
	[ebenfalls falsch]<br>
	/ auch hier falsch /<br>
</p>

<p>
	E-Mail : info @ muster.de<br>
	Tel. : 0931 / 123456 – 0<br>
	www. muster. de<br>
</p>

<p>
	Muster – GmbH<br>
	Muster GmbH<br>
	Service – Team<br>
	Service-Team<br>
	Sicherheits – konzept<br>
</p>

<p>
	« Muster &amp; Söhne » – Ihr Partner für Sicherheit.<br>
	« Muster &amp; Söhne » – Ihr Partner für Sicherheit.<br>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
</p>

<p>
	Das Produkt kostet 1.299,99 € – inklusive 19 % MwSt.<br>
	Der Preis beträgt 1.299,99 € – zzgl. Versand.<br>
</p>

<p>
	Montag – Freitag<br>
	Montag–Freitag<br>
	Montag – Freitag<br>
	Montag — Freitag<br>
	Montag&nbsp;–&nbsp;Freitag<br>
</p>

<p>
	« Ein Zitat » – sagte Müller – « kann auch einen Einschub enthalten. »<br>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
</p>

<p>
	Und hier kommen drei Punkte …<br>
	und hier eine Ellipse …<br>
	und hier die Entity …<br>
	und hier « … » als vermeintliche Auslassung.<br>
</p>

<p>
	… am Anfang eines Satzes<br>
	…am Anfang eines Satzes ohne Leerzeichen<br>
	… am Anfang eines Satzes mit Ellipse<br>
	…am Anfang eines Satzes ohne Leerzeichen mit Ellipse<br>
</p>

<p>
	„Das war alles …“,<br>
	„Das war alles…“,<br>
	„Das war alles …“,<br>
	« Das war alles… »,<br>
	« Das war alles … ».<br>
</p>

<p class="Textsize-LikeH4">« Max Mustermann »</p>

<p class="Textsize-LikeH4">« Max Mustermann »</p>

<p class="Textsize-LikeH4">« Max Mustermann »</p>

<p class="Textsize-LikeH4">« Max Mustermann »</p>
HTML;

		$example = $this->exampleHtml . PHP_EOL . PHP_EOL;
		$example .= '<p class="Textsize-LikeH4">&laquo;Max Mustermann&raquo;</p>' . PHP_EOL . PHP_EOL;
		$example .= '<p class="Textsize-LikeH4">&laquo; Max Mustermann &raquo;</p>' . PHP_EOL . PHP_EOL;
		$example .= '<p class="Textsize-LikeH4">&laquo;&nbsp;Max Mustermann&nbsp;&raquo;</p>' . PHP_EOL . PHP_EOL;
		$example .= '<p class="Textsize-LikeH4">&laquo; Max Mustermann &raquo;</p>';

		$result = TypographyFixer::formatHtml($example, 'fr');

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlFixesTypographyForSpanishLanguage(): void {
		TypographyRuleRegistry::registerDefaults();

		$expected = <<<HTML
<p>
	Das ist ein «falsches Zitat» und hier kommt ein «weiteres falsches Zitat».<br>
	Korrekt wäre eigentlich „dieses Zitat“ – aber auch hier wurde der falsche Strich verwendet.<br>
</p>

<p>
	Achtung! Das ist falsch.<br>
	Wirklich? Ja!<br>
	Hier: ebenfalls falsch;<br>
	und auch das ist falsch,<br>
	obwohl es direkt am Satzzeichen stehen sollte.<br>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/id:555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/id:555/)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/?id=555)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit.php?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit.php?id=555)
</p>

<p>
	Noch ein Beispiel: «Hallo Welt !» Und noch eines: «Wie geht es dir ?»<br>
</p>

<p>
	Das ist ein – Einschub – mit Bindestrichen.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text«.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text».<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text).<br>
</p>

<h1>
	Das ist ein eine Headline –<br>
	mit zweiter Zeile aber Bindestrich davor.
</h1>

<h1>
	Das ist ein eine Headline<br>
	- mit zweiter Zeile, die mit Bindestrich beginnt.
</h1>

<p>
	Von Montag – Freitag sind wir geöffnet.<br>
	Öffnungszeiten: Montag – Freitag, 08:00 – 17:00 Uhr.<br>
	Öffnungszeiten: Montag – Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Uhrzeiten.<br>
	Öffnungszeiten: Montag-Freitag, 08:00 – 17:00 Uhr ohne Leerzeichen zwischen den Wochentagen.<br>
	Öffnungszeiten: Montag-Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Wochentagen und den Uhrzeiten.<br>
</p>

<p>
	Der Zeitraum beträgt 10 – 15 Minuten.<br>
	Der Zeitraum beträgt 10-15 Minuten ohne Leerzeichen.<br>
	Die Strecke ist 5 – 10 km lang.<br>
	Die Strecke ist 5-10 km lang ohne Leerzeichen.<br>
	Das Ergebnis liegt bei 50 – 100 %.<br>
	Das Ergebnis liegt bei 50-100 % ohne Leerzeichen.<br>
</p>

<p>
	Unser Angebot gilt vom 01.09.2026 – 30.09.2026.<br>
	Der Preis liegt zwischen 99,00 € – 149,00 €.<br>
</p>

<p>
	Das ist ein – korrekt gesetzter Gedankenstrich –<br>
	und hier noch einer — allerdings als HTML-Entity.<br>
</p>

<p>
	Ein langer Satz mit einem «Zitat» – danach geht es weiter –<br>
	und hier noch ein – eingeschobener – Teil.<br>
</p>

<p>
	«Das ist falsch.»<br>
	«Das ist auch falsch!»<br>
	«Und das ist falsch?»<br>
</p>

<p>
	«Zitat mit falschen Anführungszeichen»<br>
	«Noch ein falsches Zitat»<br>
	„Dieses ist korrekt.“<br>
	„Aber dieses «Zitat» ist gemischt.“<br>
</p>

<p>
	Er sagte: «Das funktioniert nicht.»<br>
	Sie antwortete: «Warum nicht ?»<br>
	Darauf sagte er: «Weil es falsch gesetzt ist !»<br>
</p>

<p>
	Bitte beachten Sie: Die folgenden Angaben sind verbindlich.<br>
	Preis: 49,99 €<br>
	Rabatt: 20 %<br>
	Menge: 10 Stück<br>
</p>

<p>
	Das kostet 49,99 €.<br>
	Das kostet 49,99 €.<br>
	Das kostet €49,99.<br>
	Das kostet € 49,99.<br>
</p>

<p>
	100 % Rabatt<br>
	100 %<br>
	100 %<br>
	100 %<br>
</p>

<p>
	10 kg<br>
	10kg<br>
	10&nbsp;kg<br>
	10&nbsp;&nbsp;kg<br>
</p>

<p>
	10 Uhr<br>
	10Uhr<br>
	10&nbsp;Uhr<br>
	10&nbsp;&nbsp;Uhr<br>
</p>
<p>
	10 €<br>
	10 €<br>
	10 €<br>
	10 €<br>
</p>

<p>
	Bitte lesen Sie die Hinweise!<br>
	Besonders wichtig: Abschnitt 3.<br>
	Haben Sie Fragen?<br>
	Dann kontaktieren Sie uns!<br>
</p>

<p>
	Hallo, wie geht es dir?<br>
	Das ist falsch; ebenso wie das hier:<br>
	und auch dieses Beispiel, ist falsch.<br>
</p>

<p>
	Klammern (falsch gesetzt)<br>
	[ebenfalls falsch]<br>
	/ auch hier falsch /<br>
</p>

<p>
	E-Mail: info @ muster.de<br>
	Tel.: 0931 / 123456 – 0<br>
	www. muster. de<br>
</p>

<p>
	Muster – GmbH<br>
	Muster GmbH<br>
	Service – Team<br>
	Service-Team<br>
	Sicherheits – konzept<br>
</p>

<p>
	«Muster &amp; Söhne» – Ihr Partner für Sicherheit.<br>
	«Muster &amp; Söhne» – Ihr Partner für Sicherheit.<br>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
</p>

<p>
	Das Produkt kostet 1.299,99 € – inklusive 19 % MwSt.<br>
	Der Preis beträgt 1.299,99 € – zzgl. Versand.<br>
</p>

<p>
	Montag – Freitag<br>
	Montag–Freitag<br>
	Montag – Freitag<br>
	Montag — Freitag<br>
	Montag&nbsp;–&nbsp;Freitag<br>
</p>

<p>
	«Ein Zitat» – sagte Müller – «kann auch einen Einschub enthalten.»<br>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
</p>

<p>
	Und hier kommen drei Punkte …<br>
	und hier eine Ellipse …<br>
	und hier die Entity …<br>
	und hier «…» als vermeintliche Auslassung.<br>
</p>

<p>
	… am Anfang eines Satzes<br>
	…am Anfang eines Satzes ohne Leerzeichen<br>
	… am Anfang eines Satzes mit Ellipse<br>
	…am Anfang eines Satzes ohne Leerzeichen mit Ellipse<br>
</p>

<p>
	„Das war alles …“,<br>
	„Das war alles…“,<br>
	„Das war alles …“,<br>
	«Das war alles…»,<br>
	«Das war alles …».<br>
</p>
HTML;

		$result = TypographyFixer::formatHtml($this->exampleHtml, 'es');

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlFixesTypographyForItalianLanguage(): void {
		TypographyRuleRegistry::registerDefaults();

		$expected = <<<HTML
<p>
	Das ist ein «falsches Zitat» und hier kommt ein «weiteres falsches Zitat».<br>
	Korrekt wäre eigentlich „dieses Zitat“ – aber auch hier wurde der falsche Strich verwendet.<br>
</p>

<p>
	Achtung! Das ist falsch.<br>
	Wirklich? Ja!<br>
	Hier: ebenfalls falsch;<br>
	und auch das ist falsch,<br>
	obwohl es direkt am Satzzeichen stehen sollte.<br>
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/id:555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/id:555/)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit/?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit/?id=555)
</p>

<p>
	<a href="https://cms.de/backend/de/contents/edit.php?id=555/">Hier</a> ist ein Link mit falschen Anführungszeichen, die aber nicht
	korrigiert werden. (https://cms.de/backend/de/contents/edit.php?id=555)
</p>

<p>
	Noch ein Beispiel: «Hallo Welt !» Und noch eines: «Wie geht es dir ?»<br>
</p>

<p>
	Das ist ein – Einschub – mit Bindestrichen.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text«.<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text».<br>
	Und hier folgt ein Satz – mit einem Gedankenstrich – mitten im Text).<br>
</p>

<h1>
	Das ist ein eine Headline –<br>
	mit zweiter Zeile aber Bindestrich davor.
</h1>

<h1>
	Das ist ein eine Headline<br>
	- mit zweiter Zeile, die mit Bindestrich beginnt.
</h1>

<p>
	Von Montag – Freitag sind wir geöffnet.<br>
	Öffnungszeiten: Montag – Freitag, 08:00 – 17:00 Uhr.<br>
	Öffnungszeiten: Montag – Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Uhrzeiten.<br>
	Öffnungszeiten: Montag-Freitag, 08:00 – 17:00 Uhr ohne Leerzeichen zwischen den Wochentagen.<br>
	Öffnungszeiten: Montag-Freitag, 08:00-17:00 Uhr ohne Leerzeichen zwischen den Wochentagen und den Uhrzeiten.<br>
</p>

<p>
	Der Zeitraum beträgt 10 – 15 Minuten.<br>
	Der Zeitraum beträgt 10-15 Minuten ohne Leerzeichen.<br>
	Die Strecke ist 5 – 10 km lang.<br>
	Die Strecke ist 5-10 km lang ohne Leerzeichen.<br>
	Das Ergebnis liegt bei 50 – 100 %.<br>
	Das Ergebnis liegt bei 50-100 % ohne Leerzeichen.<br>
</p>

<p>
	Unser Angebot gilt vom 01.09.2026 – 30.09.2026.<br>
	Der Preis liegt zwischen 99,00 € – 149,00 €.<br>
</p>

<p>
	Das ist ein – korrekt gesetzter Gedankenstrich –<br>
	und hier noch einer — allerdings als HTML-Entity.<br>
</p>

<p>
	Ein langer Satz mit einem «Zitat» – danach geht es weiter –<br>
	und hier noch ein – eingeschobener – Teil.<br>
</p>

<p>
	«Das ist falsch.»<br>
	«Das ist auch falsch!»<br>
	«Und das ist falsch?»<br>
</p>

<p>
	«Zitat mit falschen Anführungszeichen»<br>
	«Noch ein falsches Zitat»<br>
	„Dieses ist korrekt.“<br>
	„Aber dieses «Zitat» ist gemischt.“<br>
</p>

<p>
	Er sagte: «Das funktioniert nicht.»<br>
	Sie antwortete: «Warum nicht ?»<br>
	Darauf sagte er: «Weil es falsch gesetzt ist !»<br>
</p>

<p>
	Bitte beachten Sie: Die folgenden Angaben sind verbindlich.<br>
	Preis: 49,99 €<br>
	Rabatt: 20 %<br>
	Menge: 10 Stück<br>
</p>

<p>
	Das kostet 49,99 €.<br>
	Das kostet 49,99 €.<br>
	Das kostet €49,99.<br>
	Das kostet € 49,99.<br>
</p>

<p>
	100 % Rabatt<br>
	100 %<br>
	100 %<br>
	100 %<br>
</p>

<p>
	10 kg<br>
	10kg<br>
	10&nbsp;kg<br>
	10&nbsp;&nbsp;kg<br>
</p>

<p>
	10 Uhr<br>
	10Uhr<br>
	10&nbsp;Uhr<br>
	10&nbsp;&nbsp;Uhr<br>
</p>
<p>
	10 €<br>
	10 €<br>
	10 €<br>
	10 €<br>
</p>

<p>
	Bitte lesen Sie die Hinweise!<br>
	Besonders wichtig: Abschnitt 3.<br>
	Haben Sie Fragen?<br>
	Dann kontaktieren Sie uns!<br>
</p>

<p>
	Hallo, wie geht es dir?<br>
	Das ist falsch; ebenso wie das hier:<br>
	und auch dieses Beispiel, ist falsch.<br>
</p>

<p>
	Klammern (falsch gesetzt)<br>
	[ebenfalls falsch]<br>
	/ auch hier falsch /<br>
</p>

<p>
	E-Mail: info @ muster.de<br>
	Tel.: 0931 / 123456 – 0<br>
	www. muster. de<br>
</p>

<p>
	Muster – GmbH<br>
	Muster GmbH<br>
	Service – Team<br>
	Service-Team<br>
	Sicherheits – konzept<br>
</p>

<p>
	«Muster &amp; Söhne» – Ihr Partner für Sicherheit.<br>
	«Muster &amp; Söhne» – Ihr Partner für Sicherheit.<br>
	„Muster &amp; Söhne“ – Ihr Partner für Sicherheit.<br>
</p>

<p>
	Das Produkt kostet 1.299,99 € – inklusive 19 % MwSt.<br>
	Der Preis beträgt 1.299,99 € – zzgl. Versand.<br>
</p>

<p>
	Montag – Freitag<br>
	Montag–Freitag<br>
	Montag – Freitag<br>
	Montag — Freitag<br>
	Montag&nbsp;–&nbsp;Freitag<br>
</p>

<p>
	«Ein Zitat» – sagte Müller – «kann auch einen Einschub enthalten.»<br>
	„Ein Zitat“ – sagte Müller – „kann auch einen Einschub enthalten.“<br>
</p>

<p>
	Und hier kommen drei Punkte …<br>
	und hier eine Ellipse …<br>
	und hier die Entity …<br>
	und hier «…» als vermeintliche Auslassung.<br>
</p>

<p>
	… am Anfang eines Satzes<br>
	…am Anfang eines Satzes ohne Leerzeichen<br>
	… am Anfang eines Satzes mit Ellipse<br>
	…am Anfang eines Satzes ohne Leerzeichen mit Ellipse<br>
</p>

<p>
	„Das war alles …“,<br>
	„Das war alles…“,<br>
	„Das war alles …“,<br>
	«Das war alles…»,<br>
	«Das war alles …».<br>
</p>
HTML;

		$result = TypographyFixer::formatHtml($this->exampleHtml, 'it');

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlChangesNothingWithNoRules(): void {
		$result = TypographyFixer::formatHtml($this->exampleHtml, 'de');

		$this->assertSame($this->exampleHtml, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlChangesNothingWithNoRulesForLanguage(): void {
		TypographyRuleRegistry::registerDefaults();
		$this->assertCount(13, TypographyRuleRegistry::getRulesForLanguage('de'));

		$result = TypographyFixer::formatHtml($this->exampleHtml, 'xx');

		$this->assertSame($this->exampleHtml, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::formatHtml()
	 */
	public function testFormatHtmlSkipsUnsupportedElementsIncludingResponsiveImageTag(): void {
		TypographyRuleRegistry::registerDefaults();
		$this->assertCount(13, TypographyRuleRegistry::getRulesForLanguage('de'));

		$input = <<<'HTML'
<p>Außen "Zitat" ! Preis : 49,99€ und 20 %.</p>
<code>Code "Zitat" ! Preis : 49,99€ und 20 %.</code>
<pre>Pre "Zitat" ! Preis : 49,99€ und 20 %.</pre>
<script>const msg = '"Zitat" ! Preis : 49,99€ und 20 %.';</script>
<style>.x::before { content: '"Zitat" ! Preis : 49,99€ und 20 %.'; }</style>
<awyiss-responsive-image>Responsive "Zitat" ! Preis : 49,99€ und 20 %.</awyiss-responsive-image>
HTML;

		$expected = <<<'HTML'
<p>Außen „Zitat“! Preis: 49,99 € und 20 %.</p>
<code>Code "Zitat" ! Preis : 49,99€ und 20 %.</code>
<pre>Pre "Zitat" ! Preis : 49,99€ und 20 %.</pre>
<script>const msg = '"Zitat" ! Preis : 49,99€ und 20 %.';</script>
<style>.x::before { content: '"Zitat" ! Preis : 49,99€ und 20 %.'; }</style>
<awyiss-responsive-image>Responsive "Zitat" ! Preis : 49,99€ und 20 %.</awyiss-responsive-image>
HTML;

		$result = TypographyFixer::formatHtml($input, 'de');

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::format()
	 */
	public function testFormatFixesTypographyForProvidedFieldsOnly(): void {
		TypographyRuleRegistry::registerDefaults();
		$this->assertCount(13, TypographyRuleRegistry::getRulesForLanguage('de'));

		$text = <<<HTML
<p>Das ist ein "falsches Zitat" ! Und hier : noch eines &quot;mit Fehler&quot; .</p>
<p>Von Montag - Freitag: 08:00 - 17:00 Uhr. Zeitraum: 01.09.2026 - 30.09.2026.</p>
<p>Das kostet 49,99€ - statt 59,99 &euro;. Rabatt: 20%. Temperatur: 20°C.</p>
<p>10kg, 5 m/s, 100 % und 10&nbsp;&nbsp;€ sind ebenfalls Kandidaten.</p>
<p>Das ist ein - Einschub - und hier - noch einer - mit falschen Bindestrichen.</p>
<p>Öffnungszeiten : Montag-Freitag, 08:00-17:00 Uhr.</p>
<p>Hallo , wie geht es dir ? Achtung ! Wirklich ? Ja !</p>
<p>Klammern ( falsch gesetzt ) [ ebenfalls falsch ] / auch hier falsch /</p>
<p>E-Mail : info @ muster.de | Tel. : 0931 / 123456 - 0 | www . muster . de</p>
<p>„Das war alles ...“, &quot;Zitat...&quot; – und hier kommt eine Ellipse &hellip; am Anfang.</p>
HTML;

		/**
		 * @var \Awyiss\Model\Entity\Page $news /
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$news = $this->fetchTable('News')->newDefaultEntity();
		$news->text = $text;
		$news->anotherText = $text;
		$news->andAnotherText = $text;

		$expectedText = <<<HTML
<p>Das ist ein „falsches Zitat“! Und hier: noch eines „mit Fehler“.</p>
<p>Von Montag – Freitag: 08:00–17:00 Uhr. Zeitraum: 01.09.2026–30.09.2026.</p>
<p>Das kostet 49,99 € – statt 59,99 €. Rabatt: 20 %. Temperatur: 20 °C.</p>
<p>10 kg, 5 m/s, 100 % und 10 € sind ebenfalls Kandidaten.</p>
<p>Das ist ein – Einschub – und hier – noch einer – mit falschen Bindestrichen.</p>
<p>Öffnungszeiten: Montag-Freitag, 08:00–17:00 Uhr.</p>
<p>Hallo, wie geht es dir? Achtung! Wirklich? Ja!</p>
<p>Klammern (falsch gesetzt) [ebenfalls falsch] / auch hier falsch /</p>
<p>E-Mail: info @ muster.de | Tel.: 0931 / 123456–0 | www. muster. de</p>
<p>„Das war alles…“, „Zitat…“ – und hier kommt eine Ellipse … am Anfang.</p>
HTML;

		TypographyFixer::format($news, 'de', ['text', 'andAnotherText']);

		$this->assertSame($expectedText, $news->text);
		$this->assertSame($text, $news->anotherText);
		$this->assertSame($expectedText, $news->andAnotherText);
	}


	/**
	 * @return void
	 * @covers \Awyiss\Utility\Content\Typography\TypographyFixer::format()
	 */
	public function testFormatFixesTypographyForTranslationsBasedOnTranslationLanguage(): void {
		TypographyRuleRegistry::registerDefaults();

		$text = <<<HTML
<p>Das ist ein "falsches Zitat" ! Und hier : noch eines &quot;mit Fehler&quot; .</p>
<p>Von Montag - Freitag: 08:00 - 17:00 Uhr. Zeitraum: 01.09.2026 - 30.09.2026.</p>
<p>Das kostet 49,99€ - statt 59,99 &euro;. Rabatt: 20%. Temperatur: 20°C.</p>
<p>10kg, 5 m/s, 100 % und 10&nbsp;&nbsp;€ sind ebenfalls Kandidaten.</p>
<p>Das ist ein - Einschub - und hier - noch einer - mit falschen Bindestrichen.</p>
<p>Öffnungszeiten : Montag-Freitag, 08:00-17:00 Uhr.</p>
<p>Hallo , wie geht es dir ? Achtung ! Wirklich ? Ja !</p>
<p>Klammern ( falsch gesetzt ) [ ebenfalls falsch ] / auch hier falsch /</p>
<p>E-Mail : info @ muster.de | Tel. : 0931 / 123456 - 0 | www . muster . de</p>
<p>„Das war alles ...“, &quot;Zitat...&quot; – und hier kommt eine Ellipse &hellip; am Anfang.</p>
HTML;

		/**
		 * @var \Awyiss\Model\Entity\Page $news /
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$news = $this->fetchTable('News')->newDefaultEntity();
		$news->text = $text;

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$translationEn = $this->fetchTable('News')->newDefaultEntity();
		$translationEn->text = $text;

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$translationFr = $this->fetchTable('News')->newDefaultEntity();
		$translationFr->text = $text;

		/** @noinspection PhpUndefinedFieldInspection */
		$news->_translations = [
			'en' => $translationEn,
			'fr' => $translationFr,
		];

		$expectedGerman = <<<HTML
<p>Das ist ein „falsches Zitat“! Und hier: noch eines „mit Fehler“.</p>
<p>Von Montag – Freitag: 08:00–17:00 Uhr. Zeitraum: 01.09.2026–30.09.2026.</p>
<p>Das kostet 49,99 € – statt 59,99 €. Rabatt: 20 %. Temperatur: 20 °C.</p>
<p>10 kg, 5 m/s, 100 % und 10 € sind ebenfalls Kandidaten.</p>
<p>Das ist ein – Einschub – und hier – noch einer – mit falschen Bindestrichen.</p>
<p>Öffnungszeiten: Montag-Freitag, 08:00–17:00 Uhr.</p>
<p>Hallo, wie geht es dir? Achtung! Wirklich? Ja!</p>
<p>Klammern (falsch gesetzt) [ebenfalls falsch] / auch hier falsch /</p>
<p>E-Mail: info @ muster.de | Tel.: 0931 / 123456–0 | www. muster. de</p>
<p>„Das war alles…“, „Zitat…“ – und hier kommt eine Ellipse … am Anfang.</p>
HTML;

		$expectedEnglish = <<<HTML
<p>Das ist ein “falsches Zitat”! Und hier: noch eines “mit Fehler”.</p>
<p>Von Montag – Freitag: 08:00 – 17:00 Uhr. Zeitraum: 01.09.2026 – 30.09.2026.</p>
<p>Das kostet 49,99€ – statt 59,99 €. Rabatt: 20%. Temperatur: 20°C.</p>
<p>10kg, 5 m/s, 100% und 10&nbsp;&nbsp;€ sind ebenfalls Kandidaten.</p>
<p>Das ist ein – Einschub – und hier – noch einer – mit falschen Bindestrichen.</p>
<p>Öffnungszeiten: Montag-Freitag, 08:00-17:00 Uhr.</p>
<p>Hallo, wie geht es dir? Achtung! Wirklich? Ja!</p>
<p>Klammern (falsch gesetzt) [ebenfalls falsch] / auch hier falsch /</p>
<p>E-Mail: info @ muster.de | Tel.: 0931 / 123456 – 0 | www. muster. de</p>
<p>„Das war alles …“, “Zitat…” – und hier kommt eine Ellipse … am Anfang.</p>
HTML;

		$expectedFrench = <<<HTML
<p>Das ist ein « falsches Zitat » ! Und hier : noch eines « mit Fehler ».</p>
<p>Von Montag – Freitag : 08:00 – 17:00 Uhr. Zeitraum : 01.09.2026 – 30.09.2026.</p>
<p>Das kostet 49,99 € – statt 59,99 €. Rabatt : 20 %. Temperatur : 20°C.</p>
<p>10kg, 5 m/s, 100 % und 10 € sind ebenfalls Kandidaten.</p>
<p>Das ist ein – Einschub – und hier – noch einer – mit falschen Bindestrichen.</p>
<p>Öffnungszeiten : Montag-Freitag, 08:00-17:00 Uhr.</p>
<p>Hallo, wie geht es dir ? Achtung ! Wirklich ? Ja !</p>
<p>Klammern (falsch gesetzt) [ebenfalls falsch] / auch hier falsch /</p>
<p>E-Mail : info @ muster.de | Tel. : 0931 / 123456 – 0 | www. muster. de</p>
<p>„Das war alles …“, « Zitat… » – und hier kommt eine Ellipse … am Anfang.</p>
HTML;


		TypographyFixer::format($news, 'de');

		$this->assertSame($expectedGerman, $news->text);
		$this->assertSame($expectedEnglish, $translationEn->text);
		$this->assertSame($expectedFrench, $translationFr->text);
	}
}
