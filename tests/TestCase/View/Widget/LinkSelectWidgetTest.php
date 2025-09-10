<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Widget;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\StringTemplate;
use Awyiss\View\Widget\LinkSelectWidget;
use Cake\View\Form\ContextInterface;


/**
 * LinkSelectWidgetTest class
 */
class LinkSelectWidgetTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\View\Widget\LinkSelectWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRender(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new LinkSelectWidget($stringTemplate);

		$data = [
			'identifier' => 'test',
			'id' => 'TestId',
			'val' => 185,
			'options' => [
				1 => 'Option 1',
				2 => [
					'title' => 'Group 1',
					'link' => null,
					'levelPrefix' => null,
					'isGroupLabel' => true,
				],
				184 => [
					'title' => 'News',
					'link' => '/backend/de/news/overview/category:184/',
					'levelPrefix' => '',
					'isGrouped' => true,
				],
				185 => [
					'title' => 'Updates',
					'link' => '/backend/de/updates/overview/category:185/',
					'levelPrefix' => '',
					'isGrouped' => true,
					'additional-attribute' => 'additional-value',
				],
				186 => [
					'title' => 'Announcements',
					'link' => '/backend/de/announcements/overview/category:186/',
					'levelPrefix' => '- ',
					'isGrouped' => false,
				],
				6 => [
					'title' => 'Group 2',
					'link' => null,
					'levelPrefix' => null,
					'isGroupLabel' => true,
				],
				7 => [
					'title' => 'Events',
					'link' => '/backend/de/events/overview/category:187/',
					'levelPrefix' => '',
					'isGrouped' => true,
					'disabled' => true,
				],
				8 => [
					'title' => 'Meetings',
					'link' => '/backend/de/meetings/overview/category:188/',
					'levelPrefix' => '',
					'isGrouped' => true,
				],
				9 => [
					'title' => 'Conferences',
					'link' => '/backend/de/conferences/overview/category:189/',
					'levelPrefix' => '',
					'isGrouped' => false,
				],
			],
			'groupLabels' => [
				'Group 1' => 'Label of Group 1',
				'Group 2' => 'Label of Group 2',
			],
			'label' => 'Test Label',
			'templateVars' => [],
		];

		$expected = '<div class="LinkSelect LinkSelect-Test" id="TestId">' .
					'<label class="Label" tabindex="0"><strong>Test Label:</strong> Updates</label>' .
					'<ul class="List">' .
					'<li class="Item Item-Option1"><a href="" title="Option 1">Option 1</a></li>' .
					'<li class="Item Item-LabelOfGroup1 GroupLabel GroupLabelItem-LabelOfGroup1" title="Label of Group 1"><strong>Label of Group 1</strong></li>' .
					'<li class="Item Item-News IsGrouped"><a href="/backend/de/news/overview/category:184/" title="News">News</a></li>' .
					'<li additional-attribute="additional-value" class="Item Item-Updates Active IsGrouped"><a href="/backend/de/updates/overview/category:185/" title="Updates">Updates</a></li>' .
					'<li class="Item Item-Announcements"><a href="/backend/de/announcements/overview/category:186/" title="Announcements">- Announcements</a></li>' .
					'<li class="Item Item-LabelOfGroup2 GroupLabel GroupLabelItem-LabelOfGroup2" title="Label of Group 2"><strong>Label of Group 2</strong></li>' .
					'<li disabled="disabled" class="Item Item-Events IsGrouped"><a href="/backend/de/events/overview/category:187/" title="Events">Events</a></li>' .
					'<li class="Item Item-Meetings IsGrouped"><a href="/backend/de/meetings/overview/category:188/" title="Meetings">Meetings</a></li>' .
					'<li class="Item Item-Conferences"><a href="/backend/de/conferences/overview/category:189/" title="Conferences">Conferences</a></li>' .
					'</ul></div>';

		$result = $widget->render($data, $context);

		$this->assertSame($expected, $result);
	}
}
