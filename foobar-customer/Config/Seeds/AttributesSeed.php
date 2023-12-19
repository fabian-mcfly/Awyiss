<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Attributes seed.
 */
class AttributesSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
            [
                'id' => 1,
                'scope' => 'contents',
                'identifier' => 'background_color2',
                'title' => 'Hintergrundfarbe',
                'type' => 'varchar(50)',
                'has_index' => 0,
                'fieldset' => 'presentation',
                'input_type' => 'select',
                'default_value' => '',
                'required' => 0,
                'translatable' => 1,
                'system_order' => 1,
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => '2023-11-08 15:42:05',
                'changed_by' => 1,
                'changed_on' => '2023-11-09 09:31:37',
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
        ];

		$lo_table = $this->table('attributes');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
