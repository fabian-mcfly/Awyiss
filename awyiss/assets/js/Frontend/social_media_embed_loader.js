import SocialMediaEmbed from 'Frontend/SocialMediaEmbed';

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		window.socialMediaEmbed = new SocialMediaEmbed()
	});
}
else {
	window.socialMediaEmbed = new SocialMediaEmbed();
}
