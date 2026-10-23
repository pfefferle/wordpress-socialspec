/**
 * Shows an OPML document as a page in the browser.
 *
 * Browsers run this script when they get the OPML as XML with an XHTML
 * script element in it. It replaces the XML with HTML, see
 * https://jakearchibald.com/2025/making-xml-human-readable-without-xslt/
 *
 * The labels are translated in PHP and passed in a JSON script element with
 * the id "socialspec-opml-l10n".
 */
( function () {
	const XHTML = 'http://www.w3.org/1999/xhtml';
	const opml = document.documentElement;
	// The icon lives next to the script.
	const icon = document.currentScript ? new URL( '../images/opml.svg', document.currentScript.src ).href : null;
	// The JSON comes before the script, so it is already parsed.
	const l10n = JSON.parse( document.getElementById( 'socialspec-opml-l10n' ).textContent );

	/**
	 * Creates an HTML element. Text is added as text, never as markup.
	 *
	 * @param {string}           name       The tag name.
	 * @param {Object}           attributes The attributes.
	 * @param {...(Node|string)} children   The children.
	 * @return {Element} The element.
	 */
	const el = ( name, attributes = {}, ...children ) => {
		const element = document.createElementNS( XHTML, name );

		for ( const [ key, value ] of Object.entries( attributes ) ) {
			element.setAttribute( key, value );
		}

		element.append( ...children.filter( ( child ) => null !== child ) );

		return element;
	};

	/**
	 * Returns a URL if it is a web address, e.g. not "javascript:".
	 *
	 * @param {string|null} url The URL.
	 * @return {string|null} The absolute URL, or null.
	 */
	const webUrl = ( url ) => {
		try {
			const parsed = new URL( url, window.location.href );
			return [ 'http:', 'https:' ].includes( parsed.protocol ) ? parsed.href : null;
		} catch ( error ) {
			return null;
		}
	};

	/**
	 * A link to a URL, or the plain text if the URL is not a web address.
	 *
	 * @param {string|null} url  The URL.
	 * @param {string}      text The link text.
	 * @return {Node|string} The link or the text.
	 */
	const link = ( url, text ) => {
		const href = webUrl( url );
		return href ? el( 'a', { href }, text ) : text;
	};

	/**
	 * Parses markup of the plugin itself, e.g. a translation with a link.
	 *
	 * @param {string} html The markup.
	 * @return {Node[]} The nodes.
	 */
	const markup = ( html ) => [ ...new DOMParser().parseFromString( html, 'text/html' ).body.childNodes ];

	/**
	 * A line with one address and its format.
	 *
	 * @param {string} format The format label, e.g. "Feed".
	 * @param {string} url    The address.
	 * @return {Element|null} The line, or null without a web address.
	 */
	const address = ( format, url ) => {
		const href = webUrl( url );

		if ( ! href ) {
			return null;
		}

		return el(
			'p',
			{ class: 'address' },
			el( 'span', { class: 'format' }, format ),
			el( 'a', { class: 'url', href }, href )
		);
	};

	/**
	 * Renders an outline and its children.
	 *
	 * @param {Element} outline The outline element.
	 * @param {number}  level   The heading level.
	 * @return {Element} The rendered outline.
	 */
	const render = ( outline, level ) => {
		const heading = 'h' + Math.min( level, 6 );
		const text = outline.getAttribute( 'text' ) || outline.getAttribute( 'title' ) || '';
		const type = outline.getAttribute( 'type' );
		const description = outline.getAttribute( 'description' );
		const children = [ ...outline.children ].filter( ( child ) => 'outline' === child.localName );

		// A group of outlines, e.g. a category.
		if ( children.length ) {
			return el(
				'section',
				{ class: 'group' },
				el( heading, {}, text ),
				...children.map( ( child ) => render( child, level + 1 ) )
			);
		}

		// Another OPML document, or a web page.
		if ( 'include' === type || 'link' === type ) {
			const url = outline.getAttribute( 'url' );
			return el(
				'article',
				{},
				el( heading, {}, text ),
				address( 'include' === type ? 'OPML' : l10n.link, url )
			);
		}

		const feed = webUrl( outline.getAttribute( 'xmlUrl' ) );

		return el(
			'article',
			{},
			el(
				'div',
				{ class: 'title' },
				el( heading, {}, link( outline.getAttribute( 'htmlUrl' ), text ) ),
				feed ? el( 'a', { class: 'subscribe', href: 'feed:' + feed }, l10n.subscribe ) : null
			),
			description ? el( 'p', { class: 'meta' }, description ) : null,
			address( l10n.feed, feed )
		);
	};

	// Chrome ignores defer in XML documents and runs the script before the
	// rest of the document is parsed.
	document.addEventListener( 'DOMContentLoaded', () => {
		const title = opml.querySelector( 'head > title' )?.textContent || l10n.subscriptionList;
		const modified = opml.querySelector( 'head > dateModified' )?.textContent;
		const owner = opml.querySelector( 'head > ownerId' )?.textContent;
		const outlines = [ ...opml.querySelectorAll( 'body > outline' ) ];

		const style = `
			:root { color-scheme: light dark; --accent: #2271b1; --muted: #646970; --line: #dcdcde; --intro: #f0f6fc; }
			@media (prefers-color-scheme: dark) { :root { --accent: #72aee6; --muted: #a7aaad; --line: #3c434a; --intro: #1d2327; } }
			* { box-sizing: border-box; }
			body { --gap: 5vw; margin: 0; font-family: system-ui, sans-serif; line-height: 1.7; }
			a { color: var(--accent); }
			.container { max-width: 46rem; width: 95%; margin-inline: auto; }
			.intro { background: var(--intro); padding-block: calc(var(--gap) / 2); margin-bottom: var(--gap); }
			main { padding-bottom: var(--gap); }
			.intro .container { display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: center; }
			.intro img { width: 3rem; height: auto; justify-self: end; }
			@media (min-width: 40rem) { .intro .container { grid-template-columns: 4fr 1fr; } .intro img { width: 5rem; } }
			h1, h2, h3, h4, h5, h6 { margin: 0; line-height: 1.3; }
			h1 { font-size: 2rem; margin-bottom: var(--gap); }
			h1 a { color: inherit; text-decoration: none; }
			h1 a:hover { text-decoration: underline; }
			h1:has(+ .meta) { margin-bottom: 0.25rem; }
			h1 + .meta { margin-bottom: var(--gap); }
			article, .group { margin-bottom: calc(var(--gap) / 2); }
			article :is(h2, h3, h4, h5, h6) { font-size: 1.25rem; }
			.title { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
			.group > :first-child { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); border-bottom: 1px solid var(--line); padding-bottom: 0.4rem; margin-bottom: 1rem; }
			.meta { color: var(--muted); margin: 0.25rem 0; }
			.address { display: flex; align-items: baseline; gap: 0.6rem; margin: 0.25rem 0 0; }
			.format { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--muted); border: 1px solid var(--line); border-radius: 4px; padding: 0 0.4em; }
			.url { min-width: 0; color: var(--muted); text-decoration: none; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.85rem; word-break: break-all; }
			.url:hover { color: var(--accent); text-decoration: underline; }
			.subscribe { font-size: 0.75rem; line-height: 1.4; text-decoration: none; white-space: nowrap; border: 1px solid var(--accent); border-radius: 4px; padding: 0.1em 0.6em; }
			.subscribe:hover { background: var(--accent); color: Canvas; }
		`;

		const html = el(
			'html',
			l10n.lang ? { lang: l10n.lang } : {},
			el(
				'head',
				{},
				el( 'meta', { name: 'viewport', content: 'width=device-width, initial-scale=1' } ),
				el( 'title', {}, title ),
				el( 'style', {}, style )
			),
			el(
				'body',
				{},
				el(
					'header',
					{ class: 'intro' },
					el(
						'div',
						{ class: 'container' },
						el(
							'div',
							{},
							el( 'p', {}, ...markup( l10n.intro ) ),
							el( 'p', {}, ...markup( l10n.follows ) ),
							el(
								'p',
								{},
								el( 'small', {}, ...markup( l10n.import ) )
							)
						),
						icon ? el( 'img', { src: icon, alt: '' } ) : null
					)
				),
				el(
					'main',
					{ class: 'container' },
					el( 'h1', {}, link( owner, title ) ),
					modified ? el( 'p', { class: 'meta' }, modified ) : null,
					...( outlines.length
						? outlines.map( ( outline ) => render( outline, 2 ) )
						: [ el( 'p', {}, l10n.empty ) ] )
				)
			)
		);

		opml.replaceWith( html );
	} );
} )();
