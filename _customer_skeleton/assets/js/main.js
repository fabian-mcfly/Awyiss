import LazyLoad from 'LazyLoad/lazyload';


/**
 * Initialize the main functionality when the DOM is ready
 */
function initMainOnReady() {
	/**
	 * Create a new instance of LazyLoad and assign it to the global window object
	 * so that it can be accessed from other scripts.
	 *
	 * If you need to recheck the DOM, call
	 * `window.lazyLoad.update();`
	 */
	window.lazyLoad = new LazyLoad({
		class_loaded: 'Loaded',
		class_loading: 'Loading',
		class_entered: 'Visible',
		class_error: '',
		class_exited: '',
		elements_selector: '.Lazyload',
	});

	// Remove the "ready" event listener
	document.removeEventListener('DOMContentLoaded', initMainOnReady);
}

// If document is still loading, add the "ready" event listener
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initMainOnReady);
}
else {
	// DOMContentLoaded has already fired
	// noinspection JSIgnoredPromiseFromCall
	initMainOnReady();
}

console.log(
	'%cpowered by%cAW%cYISS%cVersion %s "%s"',
	'padding-right:20px; color:#202C39; line-height:50px;',
	'padding-left:20px; background-color:#131a21; color:#FFFFFF; font-family:\'2f media\', Bebas Neue, Impact, Arial Display; font-size:30px; line-height:50px; text-transform:uppercase;',
	'padding-right:20px; background-color:#131a21; color:#63d1a5; font-family:\'2f media\', Bebas Neue, Impact, Arial Display; font-size:30px; line-height:50px; text-transform:uppercase;',
	'padding-left:20px; color:#202C39; line-height:50px;',
	Awyiss.VERSION,
	Awyiss.VERSION_NAME,
);
