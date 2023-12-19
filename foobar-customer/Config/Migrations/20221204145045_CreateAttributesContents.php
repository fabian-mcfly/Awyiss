<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAttributesContents extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('attributes_contents');
        $table->addColumn('content_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('background_color', 'string', [
            'default' => null,
            'limit' => 30,
            'null' => false,
        ]);
        $table->create();
    }
}
