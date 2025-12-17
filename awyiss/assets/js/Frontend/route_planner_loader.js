import RoutePlanner from 'Frontend/RoutePlanner';

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		window.routePlanner = new RoutePlanner()
	});
}
else {
	window.routePlanner = new RoutePlanner();
}
