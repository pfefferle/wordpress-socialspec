<?php
/**
 * AboutSpec: /.well-known/about.md
 *
 * @link https://aboutspec.org/
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec\Documents;

use Socialspec\Document;
use Socialspec\Markdown;

/**
 * The About page as Markdown.
 */
final class About implements Document {

	/**
	 * The media type.
	 *
	 * @return string The media type.
	 */
	public function get_content_type(): string {
		return 'text/markdown';
	}

	/**
	 * Renders the Markdown document.
	 *
	 * Uses the page selected in the settings, the /about slash page
	 * (https://slashpages.net/#about), or the biographical info of the user
	 * who represents the site, in this order.
	 *
	 * @return string|null The Markdown document, or null if there is nothing to publish.
	 */
	public function render(): ?string {
		$page = self::get_public_page( (int) \get_option( 'socialspec_about_page' ) )
			?? self::get_public_page( \get_page_by_path( 'about' ) );

		/**
		 * Filters the page published as /.well-known/about.md.
		 *
		 * @param \WP_Post|int|null $page The page selected in the settings, the /about page, or null.
		 */
		$page = self::get_public_page( \apply_filters( 'socialspec_about_page', $page ) );

		if ( $page ) {
			$source = $page;
			$title  = \get_the_title( $page );
			$html   = $this->get_content( $page );
		} else {
			$source = \get_userdata( (int) \get_option( 'socialspec_user' ) );

			if ( ! $source ) {
				return null;
			}

			$title = \esc_html( $source->display_name );
			$html  = \wpautop( \get_the_author_meta( 'description', $source->ID ) );
		}

		$title = Markdown::from_html( '<h1>' . $title . '</h1>' ) ?? '';

		// HTML is valid Markdown, so it is the fallback if the HTML API bails.
		$markdown = Markdown::from_html( $html ) ?? \trim( \wp_kses_post( $html ) );

		if ( '' === $markdown && $source instanceof \WP_User ) {
			return null;
		}

		if ( '' !== $title ) {
			$markdown = \rtrim( $title . "\n\n" . $markdown );
		}

		/**
		 * Filters the /.well-known/about.md document.
		 *
		 * @param string|null        $markdown The Markdown document, null to send a 404.
		 * @param \WP_Post|\WP_User  $source   The About page, or the user whose biographical info is used.
		 * @param string             $html     The rendered content.
		 */
		$markdown = \apply_filters( 'socialspec_about_md', $markdown . "\n", $source, $html );

		return \is_string( $markdown ) ? $markdown : null;
	}

	/**
	 * Returns the page if it is published and not password protected.
	 *
	 * @param mixed $page A page ID, post object or null.
	 *
	 * @return \WP_Post|null The public page, or null.
	 */
	private static function get_public_page( mixed $page ): ?\WP_Post {
		// get_post( 0 ) and get_post( null ) would return the global post.
		$page = $page ? \get_post( $page ) : null;

		if ( ! $page instanceof \WP_Post || 'publish' !== $page->post_status || '' !== $page->post_password ) {
			return null;
		}

		return $page;
	}

	/**
	 * Renders the page content like the theme would.
	 *
	 * Blocks and shortcodes may read the global post, so it is set up for the
	 * duration of the_content.
	 *
	 * @param \WP_Post $page The page.
	 *
	 * @return string The rendered content.
	 */
	private function get_content( \WP_Post $page ): string {
		$previous = $GLOBALS['post'] ?? null;

		$GLOBALS['post'] = $page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		\setup_postdata( $page );

		$html = (string) \apply_filters( 'the_content', $page->post_content );

		$GLOBALS['post'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		\wp_reset_postdata();

		return $html;
	}
}
