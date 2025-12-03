<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ImageHandler;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\FrontendView;
use Cake\Datasource\FactoryLocator;


/**
 * Test case for ImageHandler
 *
 * @see \Awyiss\Utility\Content\ImageHandler
 */
class ImageHandlerTest extends TestCase {
	/**
	 * Get all media entities indexed by ID.
	 * This is called lazily only when tests actually run.
	 *
	 * @return array
	 */
	protected function getMediaArray(): array {
		return FactoryLocator::get('Table')->get('Media')->find('all')->all()->indexBy('id')->toArray();
	}


	/**
	 * Get FrontendView and MediaRenderOptions for tests.
	 * This is called lazily only when tests actually run.
	 *
	 * @return array{0: \Awyiss\View\FrontendView, 1: \Awyiss\Utility\Media\MediaRenderOptions}
	 */
	protected function getViewAndRenderOptions(): array {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		return [
			new FrontendView(),
			new MediaRenderOptions(baseWidth: 1440.00),
		];
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTags(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsWithUnknownImage(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="media/unknown-image.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content);

		$this->assertSame('<p>Test image: <img src="media/unknown-image.jpg" alt="Test Image"></p>', $content->text);

		$this->assertNull($content->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsWithTagAttributes(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image" width="100" height="100"></p>';

		ImageHandler::replaceImageTags($content);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","width":"100","height":"100","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsWithFields(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->title = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Test Image"></p>';
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content, ['title', 'text']);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"4"}</awyiss-responsive-image></p>', $content->title);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);

		$this->assertCount(2, $content->mediaAssignments);
		$this->assertArrayHasKey(0, $content->mediaAssignments);
		$this->assertSame(4, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
		$this->assertArrayHasKey(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[1]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[1]->scope);

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content, ['title']);

		// The `text` field should not be modified
		$this->assertSame('<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>', $content->text);

		// And the media assignments should not get set
		$this->assertNull($content->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInAttribute(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		// Text is not a property of the News entity, but an attribute
		$news->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($news);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * Replaces image tags in a field of an entity when the attribute input type is not texteditor
	 * since `replaceImageTagsInField` does no type checking.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInAttributeWhenAttributeInputTypeNotTexteditor(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		// Date is an attribute with input type date
		$news->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$news->date = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($news, ['text', 'date']);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->date);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * Replaces image tags in a field of an entity when the attribute input type is not texteditor
	 * since `replaceImageTagsInField` does no type checking.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInAttributeWhenAttributeInputTypeNotTexteditorAndNotExplicitelyProvided(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		// Date is an attribute with input type date
		$news->date = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$news->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($news);

		/**
		 * When no fields are provided, `getDefaultFields` will be used to determine the fields to replace.
		 * It will only use those fields from the attributes that have the input type `texteditor`.
		 *
		 * @see \Awyiss\Utility\Content\ImageHandler::getDefaultFields()
		 */
		$this->assertSame('<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>', $news->date);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInTranslations(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();

		$translation = $this->fetchTable('News')->newDefaultEntity();
		$translation->text = '<p>Image de test : <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Image de Test"></p>';
		$news->_translations = [
			'fr' => $translation,
		];

		ImageHandler::replaceImageTags($news);

		$this->assertSame('<p>Image de test : <awyiss-responsive-image>{"alt":"Image de Test","mediaId":"2"}</awyiss-responsive-image></p>', $news->_translations['fr']->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsMultipleInOneField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$content->text .= '<p>Another image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content);

		$this->assertSame(
			'<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>' .
			'<p>Another image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"4"}</awyiss-responsive-image></p>',
			$content->text
		);

		$this->assertCount(2, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
		$this->assertSame(4, $content->mediaAssignments[1]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[1]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsAvoidsDuplicateMediaAssignmentsInOneField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$content->text .= '<p>Another image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content);

		$this->assertSame(
			'<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>' .
			'<p>Another image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>',
			$content->text
		);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection
	 */
	public function testReplaceImageTagsAvoidsDuplicateMediaAssignmentsInTwoFields(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$content->teaser = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($content, ['text', 'teaser']);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->teaser);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsAvoidsDuplicateMediaAssignmentsInEntityAndAttributes(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		$news->title = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$news->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		ImageHandler::replaceImageTags($news, ['title', 'text']);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->title);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsAvoidsDuplicateMediaAssignmentsInEntityAndTranslation(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		$news->title = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$translation = $this->fetchTable('News')->newDefaultEntity();
		$translation->text = '<p>Image de test : <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Image de Test"></p>';
		$news->_translations = [
			'fr' => $translation,
		];

		ImageHandler::replaceImageTags($news, ['title', 'text']);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->title);
		$this->assertSame('<p>Image de test : <awyiss-responsive-image>{"alt":"Image de Test","mediaId":"2"}</awyiss-responsive-image></p>', $news->_translations['fr']->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute,
	 */
	public function testReplaceImageTagsWithExistingMediaAssignmentsKeepMediaAssignmentId(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();

		$this->assertNotNull($content);
		$this->assertNotNull($content->mediaAssignments['inlineImgTag']);
		$this->assertSame(2, $content->mediaAssignments['inlineImgTag'][2]->mediaId);
		$mediaAssignmentId = $content->mediaAssignments['inlineImgTag'][2]->id;

		$content->text = '<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p><p>Additional paragraph</p>';
		$content->mediaAssignments = [];

		ImageHandler::replaceImageTags($content);

		$this->assertSame('<p><awyiss-responsive-image>{"mediaId":"2"}</awyiss-responsive-image></p><p>Additional paragraph</p>', $content->text);

		// There should now be a new media assignment under the `0` key
		$this->assertCount(1, $content->mediaAssignments);
		$this->assertNotNull($content->mediaAssignments[0]);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
		$this->assertSame($mediaAssignmentId, $content->mediaAssignments[0]->id, 'Media assignment ID should be preserved');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsInField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$text = ImageHandler::replaceImageTagsInField($content, 'text');

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInFieldWithUnknownImage(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="media/unknown-image.jpg" alt="Test Image"></p>';

		$text = ImageHandler::replaceImageTagsInField($content, 'text');

		$this->assertSame('<p>Test image: <img src="media/unknown-image.jpg" alt="Test Image"></p>', $text);
		$this->assertSame('<p>Test image: <img src="media/unknown-image.jpg" alt="Test Image"></p>', $content->text);

		$this->assertNull($content->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInFieldWithTagAttributes(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image" width="100" height="100"></p>';

		$text = ImageHandler::replaceImageTagsInField($content, 'text');

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","width":"100","height":"100","mediaId":"2"}</awyiss-responsive-image></p>', $text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","width":"100","height":"100","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInFieldOfAttribute(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		// Text is not a property of the News entity, but an attribute
		$news->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$text = ImageHandler::replaceImageTagsInField($news, 'text');

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * Replaces image tags in a field of an entity when the attribute input type is not texteditor
	 * since `replaceImageTagsInField` does no type checking.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInFieldOfAttributeWhenAttributeInputTypeNotTexteditor(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		// Date is an attribute with input type date
		$news->date = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$date = ImageHandler::replaceImageTagsInField($news, 'date');

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $date);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->date);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsInFieldMultipleInOneField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$content->text .= '<p>Another image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png" alt="Test Image"></p>';

		$text = ImageHandler::replaceImageTagsInField($content, 'text');

		$this->assertSame(
			'<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>' .
			'<p>Another image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"4"}</awyiss-responsive-image></p>',
			$text
		);
		$this->assertSame(
			'<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>' .
			'<p>Another image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"4"}</awyiss-responsive-image></p>',
			$content->text
		);

		$this->assertCount(2, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
		$this->assertSame(4, $content->mediaAssignments[1]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[1]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceImageTagsInFieldAvoidsDuplicateMediaAssignmentsInOneField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$content->text .= '<p>Another image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$text = ImageHandler::replaceImageTagsInField($content, 'text');

		$this->assertSame(
			'<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>' .
			'<p>Another image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>',
			$text
		);
		$this->assertSame(
			'<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>' .
			'<p>Another image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>',
			$content->text
		);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection
	 */
	public function testReplaceImageTagsInFieldAvoidsDuplicateMediaAssignmentsInTwoFields(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->newDefaultEntity();
		$content->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$content->teaser = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$text = ImageHandler::replaceImageTagsInField($content, 'text');
		$teaser = ImageHandler::replaceImageTagsInField($content, 'teaser');

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $teaser);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $content->teaser);

		$this->assertCount(1, $content->mediaAssignments);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInFieldAvoidsDuplicateMediaAssignmentsInEntityAndAttributes(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		$news->title = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';
		$news->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$title = ImageHandler::replaceImageTagsInField($news, 'title');
		$text = ImageHandler::replaceImageTagsInField($news, 'text');

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $title);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->title);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $text);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceImageTagsInFieldAvoidsDuplicateMediaAssignmentsInEntityAndTranslation(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->newDefaultEntity();
		$news->title = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image"></p>';

		$translation = $this->fetchTable('News')->newDefaultEntity();
		$translation->text = '<p>Image de test : <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Image de Test"></p>';
		$news->_translations = [
			'fr' => $translation,
		];

		$title = ImageHandler::replaceImageTagsInField($news, 'title');
		$text = ImageHandler::replaceImageTagsInField($translation, 'text', null, $news);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $title);
		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","mediaId":"2"}</awyiss-responsive-image></p>', $news->title);
		$this->assertSame('<p>Image de test : <awyiss-responsive-image>{"alt":"Image de Test","mediaId":"2"}</awyiss-responsive-image></p>', $text);
		$this->assertSame('<p>Image de test : <awyiss-responsive-image>{"alt":"Image de Test","mediaId":"2"}</awyiss-responsive-image></p>', $news->_translations['fr']->text);

		$this->assertCount(1, $news->mediaAssignments);
		$this->assertSame(2, $news->mediaAssignments[0]->mediaId);
		$this->assertSame('news', $news->mediaAssignments[0]->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute,
	 */
	public function testReplaceImageTagsInFieldWithExistingMediaAssignmentsKeepMediaAssignmentId(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();

		$this->assertNotNull($content);
		$this->assertNotNull($content->mediaAssignments['inlineImgTag']);
		$this->assertSame(2, $content->mediaAssignments['inlineImgTag'][2]->mediaId);
		$mediaAssignmentId = $content->mediaAssignments['inlineImgTag'][2]->id;

		$content->text = '<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p><p>Additional paragraph</p>';
		$content->mediaAssignments = [];

		$text = ImageHandler::replaceImageTagsInField($content, 'text');

		$this->assertSame('<p><awyiss-responsive-image>{"mediaId":"2"}</awyiss-responsive-image></p><p>Additional paragraph</p>', $text);
		$this->assertSame('<p><awyiss-responsive-image>{"mediaId":"2"}</awyiss-responsive-image></p><p>Additional paragraph</p>', $content->text);

		// There should now be a new media assignment under the `0` key
		$this->assertCount(1, $content->mediaAssignments);
		$this->assertNotNull($content->mediaAssignments[0]);
		$this->assertSame(2, $content->mediaAssignments[0]->mediaId);
		$this->assertSame('contents', $content->mediaAssignments[0]->scope);
		$this->assertSame($mediaAssignmentId, $content->mediaAssignments[0]->id, 'Media assignment ID should be preserved');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTags(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();

		ImageHandler::rebuildSimpleImageTags($content);

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsWithUnknownMediaId(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();
		$content->text = '<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>';

		ImageHandler::rebuildSimpleImageTags($content);

		$this->assertSame('<p></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsWithFields(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();
		$content->title = $content->text;

		ImageHandler::rebuildSimpleImageTags($content, ['title', 'text']);

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->title);
		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->text);

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();
		$content->title = $content->text;

		ImageHandler::rebuildSimpleImageTags($content, ['title']);

		// The `text` field should not be modified
		$this->assertSame('<p><awyiss-responsive-image>{"mediaId":"2"}</awyiss-responsive-image></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInAttribute(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->find('all')->find('mediaAssignments')->where(['id' => 38])->first();

		ImageHandler::rebuildSimpleImageTags($news, ['title', 'teaser']);

		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p>', $news->teaser);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInTranslations(): void {
		/**
		 * @var \Awyiss\Model\Entity\Widget $widget
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$widget = $this->fetchTable('Widgets')->find('all')->find('translations')->find('mediaAssignments')->where(['id' => 18])->first();

		ImageHandler::rebuildSimpleImageTags($widget, ['title', 'text']);

		// Replace in the main entity
		$this->assertSame(
			'<p>Widget with inline img tag</p><p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p><p>between two paragraphs</p>',
			$widget->text
		);

		// Replace in the translation
		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame(
			'<p>Widget with inline img tag</p><p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p><p>between two paragraphs</p>',
			$widget->_translations['es']->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTags()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsMultipleInOneField(): void {
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 22])->first();

		ImageHandler::rebuildSimpleImageTags($widget);

		$this->assertSame(
			'<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p><p>Widget with two inline img</p><p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>',
			$widget->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInField(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();

		$text = ImageHandler::rebuildSimpleImageTagsInField($content, 'text');

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $text);
		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInFieldWithUnknownMediaId(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->find('all')->find('mediaAssignments')->where(['id' => 44])->first();
		$content->text = '<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>';

		$text = ImageHandler::rebuildSimpleImageTagsInField($content, 'text');

		$this->assertSame('<p></p>', $text);
		$this->assertSame('<p></p>', $content->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInFieldInAttribute(): void {
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->find('all')->find('mediaAssignments')->where(['id' => 38])->first();

		$teaser = ImageHandler::rebuildSimpleImageTagsInField($news, 'teaser');

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p>', $teaser);
		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p>', $news->teaser);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInField()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInFieldMultipleInOneField(): void {
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 22])->first();

		$text = ImageHandler::rebuildSimpleImageTagsInField($widget, 'text');

		$this->assertSame(
			'<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p><p>Widget with two inline img</p><p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>',
			$text
		);
		$this->assertSame(
			'<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p><p>Widget with two inline img</p><p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>',
			$widget->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInText()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInText(): void {
		$media = $this->getMediaArray();
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->get(44);

		$text = ImageHandler::rebuildSimpleImageTagsInText($content->text, $media);

		$this->assertSame('<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInText()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInTextWithUnknownMediaId(): void {
		$media = $this->getMediaArray();
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->fetchTable('Contents')->get(44);
		$content->text = '<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>';

		$text = ImageHandler::rebuildSimpleImageTagsInText($content->text, $media);

		$this->assertSame('<p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p>', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::rebuildSimpleImageTagsInText()
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testRebuildSimpleImageTagsInTextMultipleInOneField(): void {
		$media = $this->getMediaArray();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 22])->first();

		$text = ImageHandler::rebuildSimpleImageTagsInText($widget->text, $media);

		$this->assertSame(
			'<p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.png"></p><p>Widget with two inline img</p><p><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg"></p>',
			$text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTags(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testReplaceCustomImageTagsWithMediaRenderOptions(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions->withWidth(768)->withResponsive(false));

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w768].avif" data-srcset="_resized/dummypath/logo-awyiss-[w1536].avif 2x"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w768].avif" srcset="_resized/dummypath/logo-awyiss-[w1536].avif 2x"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.2; }</style>', $widget->text);

		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions->withBreakpoint(768));

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString(
			'<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">',
			$widget->text
		);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsWithUnknownImage(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$widget->text = '<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions);

		$this->assertSame(
			'<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			$widget->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsWithTagAttributes(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$widget->text = '<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4","alt":"Test Image","class":"Dummyclass"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="Test Image" class="Lazyload Dummyclass"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="Test Image" class="Dummyclass"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsWithFields(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$widget->title = $widget->text;

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions, ['title', 'text']);

		$this->assertSame($widget->title, $widget->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);

		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$widget->title = $widget->text;

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions, ['title']);

		$this->assertNotSame($widget->title, $widget->text);

		// The `text` field should not be modified
		$this->assertSame('<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceCustomImageTagsInAttribute(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->find('all')->find('mediaAssignments')->where(['id' => 38])->first();

		ImageHandler::replaceCustomImageTags($news, $view, $mediaRenderOptions);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $news->teaser);
		$this->assertStringContainsString('<picture>', $news->teaser);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $news->teaser);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $news->teaser);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $news->teaser);
		$this->assertStringContainsString('</picture>', $news->teaser);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceCustomImageTagsInTranslations(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/**
		 * @var \Awyiss\Model\Entity\Page $news
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$widget = $this->fetchTable('Widgets')->find('all')->find('translations')->find('mediaAssignments')->where(['id' => 18])->first();

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->_translations['it']->text);
		$this->assertStringContainsString('<picture>', $widget->_translations['it']->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->_translations['it']->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->_translations['it']->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->_translations['it']->text);
		$this->assertStringContainsString('</picture>', $widget->_translations['it']->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTags()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsMultipleInOneField(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/**
		 * @var \Awyiss\Model\Entity\Page $news
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$widget = $this->fetchTable('Widgets')->find('all')->find('translations')->find('mediaAssignments')->where(['id' => 22])->first();

		ImageHandler::replaceCustomImageTags($widget, $view, $mediaRenderOptions);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertEquals(2, mb_substr_count($widget->text, '<picture>'));
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="logo-awyiss.jpg"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="logo-awyiss.jpg"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertEquals(2, mb_substr_count($widget->text, '</picture>'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsInField(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		$text = ImageHandler::replaceCustomImageTagsInField($widget, $view, $mediaRenderOptions, 'text');

		$this->assertSame($text, $widget->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute
	 */
	public function testReplaceCustomImageTagsInFieldWithMediaRenderOptions(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		$text = ImageHandler::replaceCustomImageTagsInField($widget, $view, $mediaRenderOptions->withWidth(768)->withResponsive(false), 'text');

		$this->assertSame($text, $widget->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w768].avif" data-srcset="_resized/dummypath/logo-awyiss-[w1536].avif 2x"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w768].avif" srcset="_resized/dummypath/logo-awyiss-[w1536].avif 2x"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.2; }</style>', $widget->text);

		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();

		$text = ImageHandler::replaceCustomImageTagsInField($widget, $view, $mediaRenderOptions->withBreakpoint(768), 'text');

		$this->assertSame($text, $widget->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString(
			'<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">',
			$widget->text
		);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsInFieldWithUnknownImage(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$widget->text = '<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		$text = ImageHandler::replaceCustomImageTagsInField($widget, $view, $mediaRenderOptions, 'text');

		$this->assertSame($text, $widget->text);

		$this->assertSame(
			'<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"200"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			$widget->text
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsInFieldWithTagAttributes(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->fetchTable('Widgets')->find('all')->find('mediaAssignments')->where(['id' => 18])->first();
		$widget->text = '<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4","alt":"Test Image","class":"Dummyclass"}</awyiss-responsive-image></p><p>between two paragraphs</p>';

		$text = ImageHandler::replaceCustomImageTagsInField($widget, $view, $mediaRenderOptions, 'text');

		$this->assertSame($text, $widget->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertStringContainsString('<picture>', $widget->text);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="Test Image" class="Lazyload Dummyclass"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="Test Image" class="Dummyclass"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('</picture>', $widget->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget, PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function testReplaceCustomImageTagsInFieldInAttribute(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/** @var \Awyiss\Model\Entity\Page $news */
		$news = $this->fetchTable('News')->find('all')->find('mediaAssignments')->where(['id' => 38])->first();

		$teaser = ImageHandler::replaceCustomImageTagsInField($news, $view, $mediaRenderOptions, 'teaser');

		$this->assertSame($teaser, $news->teaser);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $news->teaser);
		$this->assertStringContainsString('<picture>', $news->teaser);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $news->teaser);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $news->teaser);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $news->teaser);
		$this->assertStringContainsString('</picture>', $news->teaser);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceCustomImageTagsInField()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testReplaceCustomImageTagsInFieldMultipleInOneField(): void {
		[$view, $mediaRenderOptions] = $this->getViewAndRenderOptions();
		/**
		 * @var \Awyiss\Model\Entity\Page $news
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$widget = $this->fetchTable('Widgets')->find('all')->find('translations')->find('mediaAssignments')->where(['id' => 22])->first();

		$text = ImageHandler::replaceCustomImageTagsInField($widget, $view, $mediaRenderOptions, 'text');

		$this->assertSame($text, $widget->text);

		$this->assertStringNotContainsString('<awyiss-responsive-image>', $widget->text);
		$this->assertEquals(2, mb_substr_count($widget->text, '<picture>'));
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertStringContainsString('<img data-src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="logo-awyiss.jpg"', $widget->text);
		$this->assertStringContainsString('<noscript><img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="logo-awyiss.jpg"', $widget->text);
		$this->assertStringContainsString(' { --imageAspectRatio: 1.78; }</style>', $widget->text);
		$this->assertEquals(2, mb_substr_count($widget->text, '</picture>'));
	}
}
