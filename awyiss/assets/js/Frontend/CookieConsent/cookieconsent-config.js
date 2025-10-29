(function (customConfig) {
	const languageResolver = async(lang, localeFile) => {
		let url = `${baseUrl}awyiss/assets/js/Frontend/CookieConsent/locale/${lang}.json`;

		if (localeFile) {
			url = `${baseUrl}${localeFile}.json`;
		}

		const res = await fetch(url);
		const json = await res.json();

		// Traverse through json.preferencesModal.sections (if present)
		// and remove every object that has a `linkedCategory` property
		// that is not present in the categories object
		const categories = window.cookieBannerConfig.categories || {};
		const sections = json.preferencesModal.sections || [];

		json.preferencesModal.sections = sections.filter(section => {
			if (!section.linkedCategory) {
				return true;
			}

			return categories[section.linkedCategory];
		});

		return json;
	}

	const logConsent = () => {
		// Retrieve all the fields
		const cookie = CookieConsent.getCookie();
		const preferences = CookieConsent.getUserPreferences();
		const userConsent = {
			consentId: cookie.consentId,
			acceptType: preferences.acceptType,
			acceptedCategories: preferences.acceptedCategories,
			rejectedCategories: preferences.rejectedCategories
		};

		fetch(`${baseUrl}_third-party-consent`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(userConsent)
		});
	}

	const defaultConfig = {
		guiOptions: {
			consentModal: {
				layout: 'cloud inline',
				position: 'bottom right',
				equalWeightButtons: false,
				flipButtons: false
			},
			preferencesModal: {
				layout: 'box',
				position: 'right',
				equalWeightButtons: false,
				flipButtons: false
			}
		},
		language: {
			autoDetect: 'document',
			default: 'en',
			translations: {
				'de': async() => { return languageResolver('de', customConfig.localeFile ?? null) },
				'en': async() => { return languageResolver('en', customConfig.localeFile ?? null) },
				'es': async() => { return languageResolver('es', customConfig.localeFile ?? null) },
				'fr': async() => { return languageResolver('fr', customConfig.localeFile ?? null) },
				'it': async() => { return languageResolver('it', customConfig.localeFile ?? null) },
			}
		},
		onFirstConsent: () => {
			logConsent();
		},

		onChange: () => {
			logConsent();
		}
	};

	// Merge the provided config with the default one
	const config = Object.assign(defaultConfig, customConfig);
	const categories = config.categories || {};

	if (customConfig.autoDetectCategories) {
		const scriptTags = Array.from(document.getElementsByTagName('script'));
		scriptTags.forEach(tag => {
			if (tag.type !== 'text/plain') {
				// Check if the data-category is "necessary"
				// If it is, add it to the categories object
				const category = tag.getAttribute('data-category');

				if (category === 'necessary') {
					categories[ category ] = {
						enabled: true,
						readOnly: true,
					};
				}

				return;
			}

			const category = tag.getAttribute('data-category');

			if (!category || categories[category]) {
				return;
			}

			categories[category] = {
				enabled: ['legitimate-interest'].includes(category),
				readOnly: false,
			}

			if (category === 'analytics') {
				categories[category].autoClear = {
					cookies: [{ name: /^(_ga)/ }, { name: '_gid'}],
				}
			}
		});

		config.categories = categories;
	}

	// Only run the CookieConsent script if there are categories defined
	if (Object.keys(categories).length === 0) {
		return;
	}

	window.cookieBannerConfig = config;

	CookieConsent.run(config);

	if (config.darkMode) {
		document.documentElement.classList.add('cc--darkmode');
	}
})(cookieBannerConfig);
