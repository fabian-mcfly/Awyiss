<?php declare(strict_types=1);


use Migrations\BaseMigration;


class AddCssToGlobalContents extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('global_contents');
		$table->addColumn('css', 'text', [
            'default' => null,
            'null' => true,
			'after' => 'css_class',
        ]);
		if ($table->hasIndexByName('BY_CSS')) {
			$table->removeIndexByName('BY_CSS');
		}
		$table->update();
	}
}
