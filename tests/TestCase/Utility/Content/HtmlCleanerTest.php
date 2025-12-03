<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\HtmlCleaner;
use InvalidArgumentException;


/**
 * Test case for HtmlCleaner
 *
 * @see \Awyiss\Utility\Content\HtmlCleaner
 */
class HtmlCleanerTest extends TestCase {
	/**
	 * @noinspection HtmlDeprecatedAttribute
	 */
	protected string $exampleHtml = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After starting empty p-tags</p>
<p><br>Starting &lt;br&gt;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After two empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Before ending empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>&nbsp;</p>
<p><br> <br>&nbsp;<br><br> &nbsp;Lorem&nbsp;&nbsp;<br> <br> <br>&nbsp;ipsum with spaces before &lt;br&gt;.</p>
<p>&nbsp;</p>
<p>Spaces  &nbsp;  between </p>
<p>&nbsp;</p>
<p> Spaces around &nbsp; &nbsp; </p>
<p>&nbsp;</p>
<p>Spaces after &nbsp;</p>
<p>&nbsp;</p>
<p>Duis autem</p>
<p>&nbsp;</p>
<ul><li></li></ul>
<ul><li>&lt;br&gt; at the end<br></li>
<li><br>&lt;br&gt; at the start</li></ul>
<p>&nbsp;</p>
	<ul>
		<li>Many ending &lt;br&gt;<br><br><br><br><br><br></li>
		<li><br>Another starting &lt;br&gt;</li>
	</ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<ul><li>&nbsp; <br> &nbsp;</li></ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Space after!&nbsp;asdf</p>
<p>Space after?&nbsp;asdf</p>
<p>Space after.&nbsp;asdf</p>
<p>&nbsp;</p>
<p>At vero and then there is a ndash with a follow-up non-breaking space –&nbsp;just like this.</p>
<p>&nbsp;<br> <br></p>
<p>&nbsp;<br> <br><span>Spaces before span</span></p>
<p>&nbsp;<br> <br><span><br><span>&nbsp;</span></span></p>
<p>&nbsp;</p>
<p style="text-align:center;" align="right">&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>&nbsp; <br>&nbsp; &nbsp;&nbsp;  <br> &nbsp;<br><br>  </p>
<p>&nbsp;</p>
HTML;


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::CLEAN_NONE
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanNone(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = $this->exampleHtml;

		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_NONE);

		$this->assertSame($this->exampleHtml, $content->text, 'Content should not be changed with CLEAN_NONE');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::CLEAN_MODERATE
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanModerate(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = $this->exampleHtml;

		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_MODERATE);

		/** @noinspection HtmlDeprecatedAttribute */
		$this->assertSame(
			<<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After starting empty p-tags</p>
<p>&nbsp;</p>
<p>Starting &lt;br&gt;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After two empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Before ending empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Lorem&nbsp;<br> <br> <br>&nbsp;ipsum with spaces before &lt;br&gt;.</p>
<p>&nbsp;</p>
<p>Spaces&nbsp;between</p>
<p>&nbsp;</p>
<p>Spaces around</p>
<p>&nbsp;</p>
<p>Spaces after</p>
<p>&nbsp;</p>
<p>Duis autem</p>
<p>&nbsp;</p>
<ul><li></li></ul>
<ul><li>&lt;br&gt; at the end</li>
<li>&lt;br&gt; at the start</li></ul>
<p>&nbsp;</p>
	<ul>
		<li>Many ending &lt;br&gt;</li>
		<li>Another starting &lt;br&gt;</li>
	</ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<ul><li>&nbsp;</li></ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Space after! asdf</p>
<p>Space after? asdf</p>
<p>Space after. asdf</p>
<p>&nbsp;</p>
<p>At vero and then there is a ndash with a follow-up non-breaking space –&nbsp;just like this.</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><span>Spaces before span</span></p>
<p><span><span>&nbsp;</span></span></p>
<p>&nbsp;</p>
<p style="text-align:center;" align="right">&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
HTML,
			$content->text,
			'Content should be cleaned with CLEAN_MODERATE'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::CLEAN_STRICT
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanStrict(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = $this->exampleHtml;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_STRICT);

		$this->assertSame(
			<<<'HTML'
<p>After starting empty p-tags</p>
<p>&nbsp;</p>
<p>Starting &lt;br&gt;</p>
<p>&nbsp;</p>
<p>After two empty p-tags</p>
<p>&nbsp;</p>
<p>Before ending empty p-tags</p>
<p>&nbsp;</p>
<p>Lorem<br>ipsum with spaces before &lt;br&gt;.</p>
<p>&nbsp;</p>
<p>Spaces&nbsp;between</p>
<p>&nbsp;</p>
<p>Spaces around</p>
<p>&nbsp;</p>
<p>Spaces after</p>
<p>&nbsp;</p>
<p>Duis autem</p>
<p>&nbsp;</p>
<ul><li></li></ul>
<ul><li>&lt;br&gt; at the end</li>
<li>&lt;br&gt; at the start</li></ul>
<p>&nbsp;</p>
	<ul>
		<li>Many ending &lt;br&gt;</li>
		<li>Another starting &lt;br&gt;</li>
	</ul>
<p>&nbsp;</p>
<p>Space after! asdf</p>
<p>Space after? asdf</p>
<p>Space after. asdf</p>
<p>&nbsp;</p>
<p>At vero and then there is a ndash with a follow-up non-breaking space –&nbsp;just like this.</p>
<p>&nbsp;</p>
<p><span>Spaces before span</span></p>
HTML,
			$content->text,
			'Content should be cleaned with CLEAN_STRICT'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::CLEAN_STRICT
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 * @see \Awyiss\Utility\Content\HtmlCleaner::removeLeadingAndTrailingBrTags()
	 */
	public function testRemoveLeadingAndTrailingBrTagsMovesTrailingBrTagsInlineTags(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = <<<'HTML'
<table>
<tbody>
<tr>
<td><strong>First<br></strong>(Subtitle):</td>
<td>30 &euro;/Day</td>
</tr>
<tr>
<td><strong>Second<br></strong><br>(Another Subtitle):</td>
<td>35 &euro;/Day</td>
</tr>
<tr>
<td><strong>Third<br>incl this and that:<br></strong></td>
<td>45 &euro;/Day</td>
</tr>
<tr>
<td><strong>Fourth</strong><br>(Subtitle):</td>
<td>50 &euro;/Day</td>
</tr>
<tr>
<td><strong>Fifth<br></strong></td>
<td>25 &euro;/Day</td>
</tr>
<tr>
<td><strong>Sixth<br></strong><br></td>
<td>25 &euro;/Day</td>
</tr>
</tbody>
</table>
HTML;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_STRICT);

		$this->assertStringContainsString('<td><strong>First</strong><br>(Subtitle):</td>', $content->text);
		$this->assertStringContainsString('<td><strong>Second</strong><br>(Another Subtitle):</td>', $content->text);
		$this->assertStringContainsString('<td><strong>Third<br>incl this and that:</strong></td>', $content->text);
		$this->assertStringContainsString('<td><strong>Fourth</strong><br>(Subtitle):</td>', $content->text);
		$this->assertStringContainsString('<td><strong>Fifth</strong></td>', $content->text);
		$this->assertStringContainsString('<td><strong>Sixth</strong></td>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::CLEAN_STRICT
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanStrictNotRemovesLeadingAndTrailingTagsWhenNoTextButLinkAndImgTagInside(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		/** @noinspection HtmlRequiredAltAttribute, HtmlUnknownTarget */
		$content->text = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><a href="https://www.dummy.de"><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></a></p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>Text content</p>
<p>&nbsp;</p>
<p><a href="https://www.dummy.de"><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></a></p>
<p>&nbsp;</p>
HTML;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_STRICT);

		/** @noinspection HtmlRequiredAltAttribute, HtmlUnknownTarget */
		$this->assertSame(
			<<<'HTML'
<p><a href="https://www.dummy.de"><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></a></p>
<p>&nbsp;</p>
<p>Text content</p>
<p>&nbsp;</p>
<p><a href="https://www.dummy.de"><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></a></p>
HTML,
			$content->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::CLEAN_STRICT
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanStrictNotRemovesLeadingAndTrailingTagsWhenNoTextButLinkAndAwyissImgTagInside(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		/** @noinspection HtmlRequiredAltAttribute, HtmlUnknownTarget */
		$content->text = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><a href="https://www.dummy.de"><awyiss-responsive-image></awyiss-responsive-image></a></p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>Text content</p>
<p>&nbsp;</p>
<p><awyiss-responsive-image></awyiss-responsive-image></p>
<p>&nbsp;</p>
HTML;

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_STRICT);

		/** @noinspection HtmlRequiredAltAttribute, HtmlUnknownTarget */
		$this->assertSame(
			<<<'HTML'
<p><a href="https://www.dummy.de"><awyiss-responsive-image></awyiss-responsive-image></a></p>
<p>&nbsp;</p>
<p>Text content</p>
<p>&nbsp;</p>
<p><awyiss-responsive-image></awyiss-responsive-image></p>
HTML,
			$content->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanWithInvalidMethod(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = $this->exampleHtml;

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid clean method. Expected one of `none`, `moderate`, `strict`. `UnknownMethod` given.');

		HtmlCleaner::clean($content, 'UnknownMethod');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanWithFields(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->title = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Paragraph in between empty paragraphs</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
HTML;
		$content->text = $this->exampleHtml;

		HtmlCleaner::clean($content, HtmlCleaner::CLEAN_STRICT, ['title']);

		$this->assertSame('<p>Paragraph in between empty paragraphs</p>', $content->title, 'Title should be cleaned to a single paragraph');
		$this->assertSame($this->exampleHtml, $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanInAttribute(): void {
		/**
		 * @var \Awyiss\Model\Entity\Page $news/
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$news = $this->fetchTable('News')->newDefaultEntity();
		/** @noinspection PhpUndefinedFieldInspection */
		$news->text = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Paragraph in between empty paragraphs</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
HTML;

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->assertSame($news->attributes->text, $news->text, 'Attribute should be the same as text before cleaning');

		HtmlCleaner::clean($news, HtmlCleaner::CLEAN_STRICT, ['text']);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->assertSame('<p>Paragraph in between empty paragraphs</p>', $news->attributes->text, 'Attribute should be cleaned to a single paragraph');
		$this->assertSame('<p>Paragraph in between empty paragraphs</p>', $news->text, 'Attribute should be cleaned to a single paragraph');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\HtmlCleaner::clean()
	 */
	public function testCleanInTranslations(): void {
		/**
		 * @var \Awyiss\Model\Entity\Page $news /
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$news = $this->fetchTable('News')->newDefaultEntity();

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$translation = $this->fetchTable('News')->newDefaultEntity();
		$translation->text = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Paragraph in between empty paragraphs</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
HTML;
		/** @noinspection PhpUndefinedFieldInspection */
		$news->_translations = [
			'fr' => $translation,
		];

		HtmlCleaner::clean($news, HtmlCleaner::CLEAN_STRICT, ['text']);

		$this->assertSame('<p>Paragraph in between empty paragraphs</p>', $news->_translations['fr']->text, 'Text should be cleaned to a single paragraph');
	}
}
