<?php declare(strict_types=1);


namespace Awyiss\Model\Enum\Survey;


use Awyiss\Utility\Inflector;
use Cake\Database\Type\EnumLabelInterface;


/**
 * Survey NextAction Enum
 */
enum NextAction: string implements EnumLabelInterface {
	case NextQuestion = 'nextQuestion';
	case SpecificQuestion = 'specificQuestion';
	case SaveAndEnd = 'saveAndEnd';
	case ShowForm = 'showForm';
	case SaveAndShowForm = 'saveAndShowForm';
	case ShowFormAndSave = 'showFormAndSave';
	case Abort = 'abort';


	/**
	 * @return string
	 */
	public function label(): string {
		return __d('Surveys', 'next_action_' . Inflector::underscore($this->value));
	}
}
