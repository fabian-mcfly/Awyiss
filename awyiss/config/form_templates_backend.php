<?php declare(strict_types=1);

/**
 * @noinspection HtmlUnknownAttribute
 * @noinspection HtmlWrongAttributeValue
 */
return [
	// Wrapper container for checkboxes.
	'checkboxWrapper' => '<div class="FormInput FormInputType-Checkbox FormInputName-{{identifier}} {{containerClass}}">{{label}}{{additionalContent}}</div>',
	// Error message wrapper elements.
	'error' => '<div class="Error">{{content}}</div>',
	// Container for error items.
	'errorList' => '<ul class="ErrorMessages">{{content}}</ul>',
	// Error item wrapper.
	'errorItem' => '<li class="ErrorMessage">{{text}}</li>',
	// Fieldset element used by allControls().
	'fieldset' => '<fieldset{{attrs}}>{{content}}</fieldset>',
	// Wrapper content used to hide other content.
	'hiddenBlock' => '<div class="Hidden">{{content}}</div>',
	// Generic input element.
	'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
	// Container element used by control().
	'inputContainer' => '<div class="FormInput FormInputType-{{type}} FormInputName-{{identifier}}{{required}}{{columnSpan}} {{containerClass}}"{{containerAttrs}}>{{content}}{{additionalContent}}</div>',
	// Container element used by control() when a field has an error.
	'inputContainerError' => '<div class="FormInput FormInputType-{{type}} FormInputName-{{identifier}}{{required}}{{columnSpan}} {{containerClass}} Error"{{containerAttrs}}>{{content}}{{error}}{{additionalContent}}</div>',
	// Wrapper for inputs in an inputList or inputKeyValueList
	'inputListItem' => '<div class="FormInputType-ListItem">{{content}}</div>',
	// Wrapper for inputs in an inputList or inputKeyValueList
	'inputListItemDefault' => '<div class="FormInputType-ListItem FormInputType-ListItem-Default">{{content}}</div>',
	// Label element when inputs are not nested inside the label.
	'label' => '<label class="Label{{labelClass}}"{{attrs}}>{{text}}</label>',
	// Legends created by allControls()
	'legend' => '<legend class="Legend">{{text}}</legend>',
	// Link select element, used for selecting from a list of links.
	'linkSelect' => '<div{{attrs}}><label class="Label" tabindex="0"><strong>{{label}}:</strong> {{selectedOption}}</label><ul class="List">{{options}}</ul></div>',
	// Link select option element
	'linkSelectOption' => '<li{{attrs}}><a href="{{link}}" title="{{title}}">{{levelPrefix}}{{title}}</a></li>',
	// Link select option element when the option is disabled.
	'linkSelectOptionDisabled' => '<li{{attrs}} title="{{title}}">{{levelPrefix}}{{title}}</li>',
	// Link select group label element.
	'linkSelectGroupLabel' => '<li{{attrs}} title="{{title}}"><strong>{{title}}</strong></li>',
	// Link select selected option element. Usually no link as it is already selected.
	'linkSelectSelectedOption' => '{{title}}',
	// Multi-Checkbox input set title element.
	'multicheckboxTitle' => '<legend class="Legend">{{text}}</legend>',
	// Label element used for radio and multi-checkbox inputs.
	'nestingLabel' => '{{hidden}}{{input}}<label class="Label"{{attrs}}>{{text}}</label>',
	// Option element used in select pickers.
	'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
	// Option group element used in select pickers.
	'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
	// Wrapping container for radio input/label,
	'radioWrapper' => '{{label}}',
	// Select element,
	'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
	// Textarea input element,
	'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
	// Translatable text container.
	'translatableText' => '<div class="TranslatableTexts" data-button-title="{{buttonTitle}}" data-dialog-title="{{dialogTitle}}" data-dialog-apply="{{dialogApply}}" data-dialog-cancel="{{dialogCancel}}">{{controls}}</div>',
	// Container for submit buttons.
	'submitContainer' => '<div class="Submit">{{content}}</div>',
	// Error
	'errorClass' => 'Error',
	// selected class
	'selectedClass' => 'Selected',
];
