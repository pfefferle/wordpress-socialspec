<?php
/**
 * FollowSpec: /.well-known/following.opml
 *
 * @link https://followspec.org/
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec\Documents;

use Socialspec\Document;

/**
 * The followed feeds as OPML 2.0.
 */
final class Following implements Document {

	/**
	 * Outline attributes that hold URLs.
	 */
	private const URL_ATTRIBUTES = array( 'htmlUrl', 'xmlUrl', 'url' );

	/**
	 * The handle of the script that shows OPML in the browser.
	 */
	private const SCRIPT = 'socialspec-opml';

	/**
	 * The media type.
	 *
	 * FollowSpec uses `text/x-opml` in its examples, but browsers download
	 * that. As XML they show it, with the script that turns it into a page.
	 * Feed readers parse it with any XML library either way.
	 *
	 * @return string The media type.
	 */
	public function get_content_type(): string {
		return 'application/xml';
	}

	/**
	 * Prints the OPML document, with an empty body if no feeds are followed.
	 *
	 * @return bool Always true.
	 */
	public function render(): bool {
		/**
		 * Filters the feeds listed in /.well-known/following.opml.
		 *
		 * Each feed is an array of OPML outline attributes and needs an `xmlUrl`:
		 *
		 *     array(
		 *         'type'    => 'rss',
		 *         'text'    => 'Kottke',
		 *         'htmlUrl' => 'https://kottke.org/',
		 *         'xmlUrl'  => 'https://feeds.kottke.org/main',
		 *     )
		 *
		 * @param array[] $outlines The outlines. Defaults to the Link Manager
		 *                          bookmarks with a feed, if the Link Manager is enabled.
		 */
		$outlines = \apply_filters( 'socialspec_following', $this->get_bookmarks() );
		$outlines = \array_values( \array_filter( \array_map( array( self::class, 'sanitize_outline' ), (array) $outlines ) ) );

		\load_template(
			SOCIALSPEC_PLUGIN_DIR . 'templates/following-opml.php',
			false,
			array(
				'outlines'     => $outlines,
				'scripts'      => self::get_script_urls(),
				'translations' => \load_script_textdomain( self::SCRIPT, 'socialspec' ),
			)
		);

		return true;
	}

	/**
	 * The URLs of the script that shows OPML in the browser and of its
	 * dependencies, in load order.
	 *
	 * The script is registered like any other, with wp-i18n for the
	 * translations. An XML document has no wp_head(), so the URLs are
	 * built from the registry like WP_Scripts does.
	 *
	 * @return string[] The script URLs.
	 */
	private static function get_script_urls(): array {
		$scripts = \wp_scripts();

		if ( ! $scripts->query( self::SCRIPT ) ) {
			\wp_register_script(
				self::SCRIPT,
				\plugins_url( 'assets/js/opml.js', SOCIALSPEC_PLUGIN_DIR . 'socialspec.php' ),
				array( 'wp-i18n' ),
				SOCIALSPEC_VERSION,
				array( 'strategy' => 'defer' )
			);
		}

		$scripts->all_deps( self::SCRIPT );
		$handles        = $scripts->to_do;
		$scripts->to_do = array();
		$urls           = array();

		foreach ( $handles as $handle ) {
			$script = $scripts->registered[ $handle ];

			// Aliases like "jquery" have no source of their own.
			if ( ! $script->src ) {
				continue;
			}

			$src = $script->src;

			if ( ! \preg_match( '|^(https?:)?//|', $src ) && ! ( $scripts->content_url && \str_starts_with( $src, $scripts->content_url ) ) ) {
				$src = $scripts->base_url . $src;
			}

			if ( null !== $script->ver ) {
				$src = \add_query_arg( 'ver', $script->ver ? $script->ver : $scripts->default_version, $src );
			}

			/** This filter is documented in wp-includes/class-wp-scripts.php */
			$urls[] = (string) \apply_filters( 'script_loader_src', $src, $handle );
		}

		return $urls;
	}

	/**
	 * The Link Manager bookmarks that have a feed.
	 *
	 * The Link Manager is hidden since WordPress 3.5, but stays enabled on
	 * sites that still have links.
	 *
	 * @return array[] The outlines.
	 */
	private function get_bookmarks(): array {
		if ( ! \get_option( 'link_manager_enabled' ) ) {
			return array();
		}

		$outlines = array();

		foreach ( \get_bookmarks() as $bookmark ) {
			if ( '' === $bookmark->link_rss ) {
				continue;
			}

			$outlines[] = \array_filter(
				array(
					'type'        => 'rss',
					'text'        => $bookmark->link_name,
					'description' => $bookmark->link_description,
					'htmlUrl'     => $bookmark->link_url,
					'xmlUrl'      => $bookmark->link_rss,
				),
				static fn ( string $value ): bool => '' !== $value
			);
		}

		return $outlines;
	}

	/**
	 * Sanitizes the attributes of an outline for the template.
	 *
	 * @param mixed $outline The outline attributes.
	 *
	 * @return array The sanitized attributes, or an empty array if the outline has no valid feed URL.
	 */
	private static function sanitize_outline( mixed $outline ): array {
		if ( ! \is_array( $outline ) ) {
			return array();
		}

		$attributes = array();

		foreach ( $outline as $name => $value ) {
			if ( ! \is_string( $name ) || ! \preg_match( '/^[A-Za-z_][A-Za-z0-9_.-]*$/', $name ) || ! \is_scalar( $value ) ) {
				continue;
			}

			// Values may be HTML encoded, like bookmark names. They are escaped again in the template.
			$value = \html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, \get_bloginfo( 'charset' ) );

			if ( \in_array( $name, self::URL_ATTRIBUTES, true ) ) {
				$value = \sanitize_url( $value );
			}

			// Control characters are not allowed in XML 1.0.
			$value = (string) \preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value );

			if ( '' !== $value ) {
				$attributes[ $name ] = $value;
			}
		}

		if ( ! isset( $attributes['xmlUrl'] ) ) {
			return array();
		}

		// OPML 2.0 requires a text attribute.
		$attributes['text'] ??= $attributes['title'] ?? ( \wp_parse_url( $attributes['xmlUrl'], PHP_URL_HOST ) ?: $attributes['xmlUrl'] );

		return $attributes;
	}
}
