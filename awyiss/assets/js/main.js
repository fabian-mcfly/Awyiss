//noinspection NpmUsedModulesInstalled

import Audit from 'Audit/Audit';
import ButtonArea from 'ButtonArea';
import ButtonHandler from 'ButtonHandler';
import ColorPicker from 'ColorPicker';
import EventHandler from 'EventHandler';
import FieldsetManager from 'FieldsetManager';
import FooterReveal from 'FooterReveal';
import FormLeaveConfirmation from 'FormLeaveConfirmation';
import FormUpdater from 'FormUpdater';
import IdentifierAutofill from 'IdentifierAutofill';
import InputListManager from 'InputListManager';
import MediaElements from 'Media/Elements';
import MediaOverlay from 'Media/Overlay';
import MediaProgressChecker from 'Media/ProgressChecker';
import NestedListHandler from 'NestedListHandler';
import Observer from 'Observer';
import OverflowMenu from 'OverflowMenu';
import OverlayForm from 'OverlayForm';
import PasswordReveal from 'PasswordReveal';
import TitleSetter from 'TitleSetter';
import TranslatableTexts from 'TranslatableTexts';


/**
 * Dynamically load a controller class and create a new instance of it
 * @param {string} controllerClass - The name of the controller class to load
 * @returns {Promise<void>}
 */
export async function loadControllerClass(controllerClass) {
	const isGenericPage = document.documentElement.classList.contains('IsGenericPage');

	try {
		// Dynamically import the controller class
		const module = await import(controllerClass);
		if (typeof module.default === 'function') {
			// Create a new instance of the controller class and save it to a global variable (camelCased)
			const controllerClassVariable = controllerClass.charAt(0).toLowerCase() + controllerClass.slice(1);
			window[controllerClassVariable] = new module.default();
		}
	} catch (error) {
		if (
			error instanceof TypeError &&
			(
				error.message.includes(`Resolution of specifier “${controllerClass}”`) ||
				error.message.includes(`Failed to resolve module specifier '${controllerClass}'`)
			)
		) {
			if (isGenericPage && controllerClass !== 'PagesController') {
				// Try to load the generic controller class
				await loadControllerClass('PagesController');
			}
		}
		else {
			console.error('Error loading controller class:', error, typeof error);
		}
	}
}

/**
 * Add a click event listener to the dark mode switcher
 * This event listener toggles the class "🌚" on the HTML tag
 * and saves the user's preference in the database
 */
export function addDarkModeSwitcherEvent() {
	// Get all .DarkModeSwitch-Link elements
	const darkModeSwitchLinks = Array.from(document.querySelectorAll('.DarkModeSwitch-Link'));

	// Add a click event listener to each .DarkModeSwitch-Link element
	window.eventHandler.add('click', function (event) {
		const target = event.target;

		// Check if the target is one of the .DarkModeSwitch-Link elements
		if (!darkModeSwitchLinks.includes(target)) {
			return;
		}

		event.preventDefault();

		// Send a fetch request to the URL of the item itself
		fetch(target.href, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-Requested-With': 'XMLHttpRequest',
			},
		})
		.then(response => response.json())
		.then(response => {
			if (!response.success) {
				throw new Error('Network response was not ok');
			}

			target.closest('#DarkModeSwitch').querySelector('.Active')?.classList.remove('Active');

			target.parentElement.classList.add('Active');

			// If the fetch request is successful, toggle the class "🌚" on the HTML tag
			document.documentElement.classList.toggle('🌚', target.classList.contains('DarkModeSwitch-Link-On'));

			document.getElementById('clr-picker')?.classList.toggle('clr-dark', target.classList.contains('DarkModeSwitch-Link-On'));
		})
		.catch(error => {
			console.error('There has been a problem with your fetch operation:', error);
		});
	}, window, true);
}

/**
 * Add a click event listener to the language switcher
 * This event listener toggles the visibility of the language switcher
 */
export function addLanguageSwitcherEvent() {
	const languageSwitcher = document.querySelector('.LanguageSwitcher');
	if (!languageSwitcher) {
		return;
	}

	// Toggle the visible classn on .Languages when clicking .LanguageSwitcherLabel;
	// Remove it when clicking outside .Languages
	window.eventHandler.add('click', function (event) {
		const languageSwitcherLabel = languageSwitcher.querySelector('.LanguageSwitcherLabel');
		const languages = languageSwitcher.querySelector('.Languages');

		// Toggle the visible class on .Languages when clicking .LanguageSwitcherLabel or its children
		if (event.target === languageSwitcherLabel || languageSwitcherLabel.contains(event.target)) {
			languageSwitcherLabel.classList.toggle('Visible');
			languages.classList.toggle('Visible');
		}
		else if (!event.target.closest('.Languages')) {
			languageSwitcherLabel.classList.remove('Visible');
			languages.classList.remove('Visible');
		}
	});

	// Find all images and create a duplicate of them as background images
	const images = languageSwitcher.querySelectorAll('img');
	images.forEach((image) => {
		const clone = image.cloneNode();
		clone.classList.add('BackgroundImage');

		// Add the background image to the parent element
		image.parentElement.appendChild(clone);
	});
}

/**
 * Add a mouseenter event listener to the link select elements
 * This event listener scrolls to the active item in the link select
 * @returns {void}
 */
export function addLinkSelectMouseEvent() {
	// Scroll to the active item in the link select
	window.eventHandler.add('mouseenter', function (event) {
		// Bail early if event.target does not match '.LinkSelect'
		if (!(event.target instanceof Element && event.target.matches('.LinkSelect'))) {
			return;
		}

		let linkSelect = event.target;
		let activeItem = linkSelect.querySelector('.List .Item.Active');
		if (activeItem && !activeItem.dataset.scrolledIntoView) {
			activeItem.scrollIntoView({behavior: 'smooth', block: 'nearest'});
			activeItem.dataset.scrolledIntoView = 'true';
		}
	}, window, true);
}

/**
 * Add a keydown event listener to the document
 * This event listener sets the value of an input or textarea to its placeholder when the tab key is pressed
 * @returns {void}
 */
export function addTabAutocompleteEvent() {
	// Add a keydown event listener to the document
	window.eventHandler.add('keydown', function (event) {
		// Bail early if the event target is not an input or textarea or the key is not the tab key
		if (event.target.tagName.toLowerCase() !== 'input' && event.target.tagName.toLowerCase() !== 'textarea' || event.key !== 'Tab') {
			return;
		}

		// Bail early if the element does not have a placeholder or its value is not empty
		if (!event.target.placeholder || event.target.value !== '') {
			return;
		}

		// Prevent the default action of the tab key
		event.preventDefault();

		// Set the value of the element to its placeholder
		event.target.value = event.target.placeholder;

		// Trigger the 'input' event on the element
		event.target.dispatchEvent(new Event('input', {bubbles: true}));
	});
}

/**
 * Add a change event listener to the pagination form
 * This event listener submits the form when the value of the select element for items per page changes
 */
export function handlePaginationForm() {
	const paginateForm = document.querySelector('.ListControl-Item-ListLimit form');
	if (!paginateForm) {
		return;
	}

	window.eventHandler.add('change', function (event) {
		event.preventDefault();
		const target = event.target;
		if (target instanceof HTMLSelectElement) {
			const formLeaveConfirmation = window.formLeaveConfirmation;
			formLeaveConfirmation.isFormSubmitting = true;

			target.form.submit();
		}
	}, paginateForm);
}

/**
 * Load the configured rich text editor
 * @param {string} editor - The identifier of the rich text editor to load
 * @returns {Promise<void>}
 */
export async function loadTextEditor(editor) {
	if (editor === 'jodit') {
		// Load the Loader class
		const {default: JoditLoader} = await import('Jodit/Loader');

		// Create a new instance of the Loader class
		new JoditLoader();
	}
	else if (editor === 'tinymce') {
		// Load the Loader class
		const {default: TinyMCELoader} = await import('TinyMCE/Loader');

		// Create a new instance of the Loader class
		new TinyMCELoader();
	}
}

/**
 * Initialize the main functionality when the DOM is ready
 * This function creates instances of the main classes and attaches them to the window object
 * It also loads the controller class if it exists
 * @returns {Promise<void>}
 */
export async function initMainOnReady() {
	//Make sure the observer is created before any other classes that use it
	const observer = new Observer();
	observer.observe(document.body, {childList: true, subtree: true});
	/**
	 * Attach observer to the window object for global access
	 * @global
	 * @type {Observer}
	 */
	window.observer = observer;

	/*
	 * Create an instance of the EventHandler class, so that it can be used globally
	 * @global
	 * @type {EventHandler}
	 */
	window.eventHandler = new EventHandler();

	/**
	 * @global
	 * @type {Audit}
	 */
	window.audit = new Audit();

	/**
	 * @global
	 * @type {ButtonHandler}
	 */
	window.buttonHandler = new ButtonHandler([
		{
			elementSelector: '#Menu-System li.Level1',
			hoverSelector: 'a.Level1, span.Level1'
		},
		'button',
		'.Button',
		'legend',
		'.Pagination-List .Number',
		'.Pagination-List .Arrow',
		'.LanguageSwitcherLabel',
	]);

	/**
	 * @global
	 * @type {ColorPicker}
	 */
	window.colorPicker = new ColorPicker();

	/**
	 * @global
	 * @type {FieldsetManager}
	 */
	window.fieldsetManager = new FieldsetManager();

	/**
	 * @global
	 * @type {FormLeaveConfirmation}
	 */
	window.formLeaveConfirmation = new FormLeaveConfirmation();

	/**
	 * @global
	 * @type {FormUpdater}
	 */
	window.formUpdater = new FormUpdater();

	/**
	 * @global
	 * @type {IdentifierAutofill}
	 */
	window.identifierAutofill = new IdentifierAutofill();

	/**
	 * @global
	 * @type {InputListManager}
	 */
	window.inputListManager = new InputListManager();

	/**
	 * @global
	 * @type {MediaElements}
	 */
	window.mediaElements = new MediaElements();

	/**
	 * @global
	 * @type {MediaOverlay}
	 */
	window.mediaOverlay = new MediaOverlay();

	/**
	 * @global
	 * @type {NestedListHandler}
	 */
	window.nestedListHandler = new NestedListHandler('.NestedList');

	/**
	 * @global
	 * @type {OverlayForm}
	 */
	window.overlayForm = new OverlayForm();

	/**
	 * @global
	 * @type {PasswordReveal}
	 */
	window.passwordReveal = new PasswordReveal();

	/**
	 * @global
	 * @type {TitleSetter}
	 */
	window.titleSetter = new TitleSetter('DataItem');

	/**
	 * @global
	 * @type {TranslatableTexts}
	 */
	window.translatableTexts = new TranslatableTexts();

	// Add the dark mode switcher event
	addDarkModeSwitcherEvent();

	// Add the language switcher event
	addLanguageSwitcherEvent();

	// Scroll to active item in link selects on mouseenter
	addLinkSelectMouseEvent();

	// Tab autocomplete for placeholders
	addTabAutocompleteEvent();

	// Handle pagination form
	handlePaginationForm();

	// Load the configured rich text editor
	// noinspection ES6MissingAwait
	loadTextEditor(editor);

	// Check if the controller class exists
	const controllerClass = Array.from(document.documentElement.classList).find(cls => cls.endsWith('Controller'));
	if (controllerClass) {
		await loadControllerClass(controllerClass);
	}

	// Remove the "ready" event listener
	document.removeEventListener('DOMContentLoaded', initMainOnReady);
}

/**
 * Initialize the main functionality when the window is loaded
 * This function creates instances of the main classes and attaches them to the window object
 * It also calls the initOnLoad method of the controller class if it exists
 * @returns {void}
 */
export function initMainOnLoad() {
	// Call the OverflowMenu class, only if the menu exists
	if (document.querySelector('#Menu-System')) {
		/**
		 * @global
		 * @type {OverflowMenu}
		 */
		window.overflowMenu = new OverflowMenu('#Menu-System', 'li.Level1');
	}

	/**
	 * @global
	 * @type {ButtonArea}
	 */
	window.buttonArea = new ButtonArea();

	/**
	 * @global
	 * @type {FooterReveal}
	 */
	window.footerReveal = new FooterReveal();

	/**
	 * @global
	 * @type {MediaProgressChecker}
	 */
	window.mediaProgressChecker = new MediaProgressChecker();

	// Check if the controller class exists
	const controllerClass = Array.from(document.documentElement.classList).find(cls => cls.endsWith('Controller'));
	const controllerClassVariable = controllerClass.charAt(0).toLowerCase() + controllerClass.slice(1);
	// Call the initOnLoad method of the controller class, if it exists
	// noinspection JSUnresolvedReference
	if (window[controllerClassVariable] && typeof window[controllerClassVariable].initOnLoad === 'function') {
		// noinspection JSUnresolvedReference
		window[controllerClassVariable].initOnLoad();
	}
	else {
		const isGenericPage = document.documentElement.classList.contains('IsGenericPage');
		if (isGenericPage) {
			const controllerClassVariable = 'pagesController';
			// Call the initOnLoad method of the controller class, if it exists
			// noinspection JSUnresolvedReference
			if (window[controllerClassVariable] && typeof window[controllerClassVariable].initOnLoad === 'function') {
				// noinspection JSUnresolvedReference
				window[controllerClassVariable].initOnLoad();
			}
		}
	}

	// Add event listener to the "Save as copy" label
	const labels = document.querySelectorAll('label[for="SaveAsCopy"]');
	labels.forEach(label => {
		window.eventHandler.add('click', (event) => {
			// Prevent the default action of the label, so the browser won't jump to the input
			event.preventDefault();

			// Toggle the checkbox
			const checkbox = document.getElementById('SaveAsCopy');
			checkbox.checked = !checkbox.checked;

			// And send and event to the checkbox
			checkbox.dispatchEvent(new Event('change', {bubbles: true}));
		}, label);
	});

	// Remove the load event listener
	window.removeEventListener('load', initMainOnLoad);
}

// Check if the main.js is imported in the custom main.js
//noinspection JSUnresolvedReference
if (!window.mainJsIsImported) {
	// If document is still loading, add the "ready" event listener
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initMainOnReady);
	}
	else {
		// DOMContentLoaded has already fired
		// noinspection JSIgnoredPromiseFromCall
		initMainOnReady();
	}

	window.addEventListener('load', initMainOnLoad);
}

console.log(
	'%cAW%cYISS%cVersion %s "%s"',
	'padding-top:3px; padding-left:20px; border-radius:5px 0 0 5px; background-color:#131a21; color:#FFFFFF; font-family:\'2f media\', Bebas Neue, Impact, Arial Display; font-size:30px; line-height:45px; text-transform:uppercase;',
	'padding-top:3px; padding-right:20px; border-radius:0 5px 5px 0;  background-color:#131a21; color:#63d1a5; font-family:\'2f media\', Bebas Neue, Impact, Arial Display; font-size:30px; line-height:45px; text-transform:uppercase;',
	'padding-left:20px; color:#202C39; line-height:45px;',
	Awyiss.VERSION,
	Awyiss.VERSION_NAME,
);