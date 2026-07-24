<?php declare(strict_types=1);


use Migrations\BaseMigration;


class ExpandMediaResizedImagesNameAndPath extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('media_resized_images');
		$table->changeColumn('name', 'string', [
			'limit' => 200,
			'default' => null,
			'null' => false,
		]);
		$table->changeColumn('path', 'string', [
			'limit' => 1224,
			'default' => null,
			'null' => false,
		]);
		$table->update();
	}
}

