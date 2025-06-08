<?php declare(strict_types=1);


namespace Awyiss\Model\Enum\Survey;


use Cake\Database\Type\EnumLabelInterface;


/**
 * Survey NextAction Enum
 */
enum NextAction: string implements EnumLabelInterface {
	case NextQuestion = 'next_question';
	case SpecificQuestion = 'specific_question';
	case SaveAndEnd = 'save_and_end';
	case ShowForm = 'show_form';
	case SaveAndShowForm = 'save_and_show_form';
	case ShowFormAndSave = 'show_form_and_save';
	case Abort = 'abort';


	/**
	 * @return string
	 */
	public function label(): string {
		return __d('surveys', 'next_action_' . $this->value);
	}
}
