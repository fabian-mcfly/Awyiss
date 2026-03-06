<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Designs seed.
 */
class DesignsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'identifier' => '35105eae51b2',
				'title' => 'Standard',
				'description' => null,
				'settings' => '{"fontNameMain":{"font":{"category":"sans-serif","id":"red-hat-text","name":"Red Hat Text","popularity":150,"variants":["300","regular","500","600","700","300italic","italic","500italic","600italic","700italic"],"version":"v18"},"variants":["300","300i","400","400i","700","700i"]},"fontStackFallbackMain":"Gill Sans, Arial, sans-serif","fontWeightMain":"300","fontStyleMain":"normal","fontSizeMain":"18","fontSizeMainUnit":"px","lineHeightMain":"1.5","lineHeightMainUnit":"rem","fontNameAlternative":{"font":{"category":"handwriting","id":"covered-by-your-grace","name":"Covered By Your Grace","popularity":438,"variants":["regular"],"version":"v16"},"variants":["400"]},"fontStackFallbackAlternative":"Lucida Handwriting, cursive","fontWeightAlternative":"400","fontStyleAlternative":"normal","fontSizeAlternative":"","fontSizeAlternativeUnit":"em","lineHeightAlternative":"","lineHeightAlternativeUnit":"","colorText":"#043a4f","colorDark":"#101820","colorMedium":"#686e77","colorLight":"#f2f5f6","colorBright":"#FFFFFF","colorMain":"#17bbe1","colorContrast":"#d22e45","pageWidth":"1440","pageWidthUnit":"px","pagePadding":"50","pagePaddingUnit":"px","columnMargin":"5","columnMarginUnit":"%","menuBreakpoint":"1024","menuBreakpointUnit":"px","singleColumnBreakpoint":"1024","singleColumnBreakpointUnit":"px"}',
				'css' => '',
				'inUse' => 0,
				'isPreview' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => 0,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'identifier' => '8f4c3c3b8c9d',
				'title' => 'Colors',
				'description' => null,
				'settings' => '{"colorDark":"#101820","colorMedium":"#686e77","colorLight":"#f2f5f6","colorBright":"#FFFFFF","colorMain":"#17bbe1"}',
				'css' => '',
				'inUse' => 0,
				'isPreview' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => 0,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('designs');
		$table->insert($data)->save();
	}
}
