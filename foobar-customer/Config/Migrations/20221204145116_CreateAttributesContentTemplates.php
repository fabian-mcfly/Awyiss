<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAttributesContentTemplates extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('attributes_content_templates');
        $table->addColumn('content_template_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('jason_test', 'json', [
            'default' => null,
            'null' => false,
        ]);
        $table->create();
    }
}
