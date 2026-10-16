# SocialSpec

- Contributors: pfefferle
- Donate link: https://notiz.blog/donate/
- Tags: socialspec, well-known, about, contact, opml
- Requires at least: 6.7
- Tested up to: 7.1
- Stable tag: 1.0.0
- Requires PHP: 8.1
- License: MIT
- License URI: https://opensource.org/licenses/MIT

Share who you are, how to reach you and what you follow with feed readers and social apps.

## Description

Your site probably has an About page already, maybe a contact page and a list of blogs you like to read. People can read these pages, but apps can not. This plugin publishes the same information in simple files that every feed reader and social app can understand. It follows [SocialSpec](https://socialspec.org/), an open set of three small formats.

A feed reader that knows SocialSpec can then show your site like a social profile: your posts, an "About" tab, a "Following" tab and a button to contact you.

### What gets published

* **About** at `/.well-known/about.md`: the text of your About page, as [Markdown](https://aboutspec.org/).
* **Contact** at `/.well-known/contact.vcf`: your name, website, short bio and picture, as a [contact card (vCard)](https://contactspec.org/).
* **Following** at `/.well-known/following.opml`: the blogs and podcasts you follow, as an [OPML list](https://followspec.org/).

The plugin uses what is already in WordPress. You do not have to write anything new.

### Setup

1. Install and activate the plugin.
2. Go to **Settings → SocialSpec**.
3. Choose who represents the site. Pick a user for a personal site, or keep "Nobody, use the site" for a magazine or a company.
4. Choose your About page, or keep "Automatic".
5. Save, then open the links at the bottom of the page to check the result.

### Where the About text comes from

The plugin looks for your About text in this order:

1. The page you chose in **Settings → SocialSpec**.
2. The page with the address `/about`. Many personal sites have one, it is one of the common [slash pages](https://slashpages.net/#about) like `/now` or `/uses`.
3. The "Biographical Info" of the user who represents the site, from their profile.

If none of these exists, there is no About file.

### Where the contact card comes from

If you chose a user in the settings, the card shows their display name, first and last name, website, "Biographical Info" and profile picture. Otherwise it shows the site title, tagline and site icon.

### Where the Following list comes from

WordPress has no list of blogs you follow, so other plugins add them. **Settings → SocialSpec** shows which plugins currently do. If you still use the old WordPress Link Manager, links with an RSS address are included too. Without any of these, the list is empty.

Open the list in your browser and you see a readable page, with a "Subscribe" button for every feed. Feed readers still get the plain OPML file.

## Frequently Asked Questions

### Is my email address published?

No. Neither the email address of a user nor the admin email of the site is published. A developer can add an address on purpose, see "For developers" below.

### My About page has a different address. What now?

Choose it in **Settings → SocialSpec**. The address does not matter then.

### Why is my Following list empty?

No plugin adds the blogs you follow yet. **Settings → SocialSpec** shows which plugins add feeds to the list.

### The links show a "Not Found" page of my server.

Your server does not pass addresses that start with `/.well-known/` to WordPress. Ask your host to pass them on. The plugin then works with every permalink setting.

### Does it work if WordPress lives in a subfolder?

Only partly. SocialSpec expects the files at the root of your domain, like `https://example.com/.well-known/about.md`. If your site is at `https://example.com/blog/`, the files are at `https://example.com/blog/.well-known/about.md`, and apps will probably not find them.

## For developers

All three files are sent with `Access-Control-Allow-Origin: *`, so apps can load them from the browser. They support conditional requests with `ETag` and `If-None-Match`.

### Add the feeds you follow

```php
add_filter( 'socialspec_following', function ( array $outlines ): array {
	$outlines[] = array(
		'type'    => 'rss',
		'text'    => 'Kottke',
		'htmlUrl' => 'https://kottke.org/',
		'xmlUrl'  => 'https://feeds.kottke.org/main',
	);

	return $outlines;
} );
```

Each feed is an array of OPML outline attributes and needs an `xmlUrl`. Your plugin then shows up in **Settings → SocialSpec**.

### Publish an email address

```php
add_filter( 'socialspec_vcard_properties', function ( array $properties, ?WP_User $user ): array {
	$properties[] = array( 'EMAIL;TYPE=INTERNET,PREF', 'hi@example.com' );

	return $properties;
}, 10, 2 );
```

### Disable an endpoint

For example, if your plugin serves `/.well-known/following.opml` itself:

```php
add_filter( 'socialspec_documents', function ( array $documents ): array {
	unset( $documents['following.opml'] );

	return $documents;
} );
```

Add the filter before `init`, because the rewrite rule is built there. Flush the rewrite rules once afterwards, for example on activation of your plugin.

### All filters

* `socialspec_documents`: add, remove or replace documents (`Socialspec\Document`).
* `socialspec_following`: the followed feeds, as OPML outline attributes.
* `socialspec_following_opml`: the OPML document.
* `socialspec_about_page`: the page published as `about.md`.
* `socialspec_about_md`: the Markdown document. It also gets the source (page or user) and the rendered HTML, for example to use another Markdown converter.
* `socialspec_vcard_properties`: the vCard properties and the user who represents the site.
* `socialspec_vcard`: the vCard document.

The settings are the options `socialspec_user` and `socialspec_about_page`. They are also available in the REST API.

## Changelog

### 1.0.0

* Initial release.
