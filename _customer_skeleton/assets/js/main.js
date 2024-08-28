import Headroom from 'Headroom/headroom';
import LazyLoad from 'LazyLoad/lazyload';
import Lightbox from 'Lightbox/Lightbox';


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

	const header = document.getElementById('HeaderArea');
	if (header) {
		var headroom = new Headroom(header, {
			offset: header.offsetHeight + 40,
			tolerance: 5,
			classes: {
				// When element is initialised
				initial: 'Headroom',
				// When scrolling up
				pinned: 'Pinned',
				// When scrolling down
				unpinned: 'Unpinned',
				// When above offset
				top: 'Top',
				// When below offset
				notTop: 'Scrolled',
				// When at bottom of scroll area
				bottom: "Bottom",
				// When not at bottom of scroll area
				notBottom: "NotBottom",
				// When frozen method has been called
				frozen: "Frozen",
			}
		});
		headroom.init();
	}

	window.lightbox = new Lightbox({
		baseUrl: baseUrl,
		currentUrl: currentUrl,
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
