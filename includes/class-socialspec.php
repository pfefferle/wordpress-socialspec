<?php
/**
 * Routes and serves the SocialSpec documents.
 *
 * @link https://socialspec.org/
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec;

use Socialspec\Documents\About;
use Socialspec\Documents\Contact;
use Socialspec\Documents\Following;

/**
 * SocialSpec class.
 */
final class Socialspec {

	/**
	 * Registers the hooks.
	 */
	public static function init(): void {
		\add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		\add_filter( 'query_vars', array( self::class, 'query_vars' ) );
		\add_action( 'parse_request', array( self::class, 'parse_request' ) );
	}

	/**
	 * The documents, keyed by their file name in /.well-known/.
	 *
	 * @return array<string, Document> The documents.
	 */
	public static function get_documents(): array {
		/**
		 * Filters the documents served at /.well-known/.
		 *
		 * Remove an entry to disable an API, or add a `Socialspec\Document`.
		 * Rewrite rules are generated from the keys, so flush them after a change.
		 *
		 * @param array<string, Document> $documents File name => document.
		 */
		$documents = \apply_filters(
			'socialspec_documents',
			array(
				'following.opml' => new Following(),
				'about.md'       => new About(),
				'contact.vcf'    => new Contact(),
			)
		);

		return \array_filter( (array) $documents, static fn ( $document ): bool => $document instanceof Document );
	}

	/**
	 * Adds the /.well-known/ rewrite rule.
	 */
	public static function add_rewrite_rules(): void {
		// WP_Rewrite matches rules with "#" as delimiter.
		$names = \array_map( static fn ( $name ): string => \preg_quote( (string) $name, '#' ), \array_keys( self::get_documents() ) );

		if ( ! $names ) {
			return;
		}

		\add_rewrite_rule( '^\.well-known/(' . \implode( '|', $names ) . ')$', 'index.php?well-known=$matches[1]', 'top' );
	}

	/**
	 * Adds the `well-known` query var, shared with the WebFinger and host-meta plugins.
	 *
	 * @param string[] $vars The public query vars.
	 *
	 * @return string[] The filtered query vars.
	 */
	public static function query_vars( array $vars ): array {
		$vars[] = 'well-known';

		return $vars;
	}

	/**
	 * Serves the requested document before the main query runs.
	 *
	 * @param \WP $wp The WordPress request.
	 */
	public static function parse_request( \WP $wp ): void {
		$name = self::get_requested_name( $wp );

		if ( null === $name ) {
			return;
		}

		$document = self::get_documents()[ $name ] ?? null;

		// Not ours, for example /.well-known/webfinger.
		if ( null === $document ) {
			return;
		}

		\header( 'Access-Control-Allow-Origin: *' );

		$method = \strtoupper( \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) );

		if ( 'OPTIONS' === $method ) {
			\status_header( 204 );
			\header( 'Access-Control-Allow-Methods: GET, HEAD, OPTIONS' );
			\header( 'Access-Control-Allow-Headers: If-None-Match' );
			\header( 'Access-Control-Max-Age: 86400' );
			exit;
		}

		if ( 'GET' !== $method && 'HEAD' !== $method ) {
			\header( 'Allow: GET, HEAD, OPTIONS' );
			self::send_error( 405, 'Method Not Allowed' );
		}

		$body = $document->render();

		if ( null === $body ) {
			self::send_error( 404, 'Not Found' );
		}

		$etag = '"' . \md5( $body ) . '"';

		\header( 'Content-Type: ' . $document->get_content_type() . '; charset=' . \get_bloginfo( 'charset' ) );
		\header( 'ETag: ' . $etag );
		\header( 'Access-Control-Expose-Headers: ETag' );
		\header( 'X-Content-Type-Options: nosniff' );

		if ( self::etag_matches( $etag, \wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only compared.
			\status_header( 304 );
			exit;
		}

		\status_header( 200 );

		if ( 'HEAD' !== $method ) {
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the document.
		}

		exit;
	}

	/**
	 * The requested file name in /.well-known/.
	 *
	 * Uses the `well-known` query var of the rewrite rule, or the request path
	 * where the rule does not apply: with plain permalinks, or before the rules
	 * are flushed, e.g. on the sites of a network after network activation.
	 *
	 * @param \WP $wp The WordPress request.
	 *
	 * @return string|null The file name, or null if /.well-known/ is not requested.
	 */
	public static function get_requested_name( \WP $wp ): ?string {
		$name = $wp->query_vars['well-known'] ?? null;

		if ( \is_string( $name ) ) {
			return $name;
		}

		$path   = (string) \wp_parse_url( \wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only compared.
		$prefix = \trailingslashit( (string) \wp_parse_url( \home_url(), PHP_URL_PATH ) ) . '.well-known/';

		return \str_starts_with( $path, $prefix ) ? \substr( $path, \strlen( $prefix ) ) : null;
	}

	/**
	 * Whether an If-None-Match header matches an entity tag.
	 *
	 * Uses the weak comparison of RFC 9110, section 13.1.2.
	 *
	 * @param string $etag   The entity tag of the response.
	 * @param string $header The If-None-Match request header.
	 *
	 * @return bool True if the client has the current version.
	 */
	public static function etag_matches( string $etag, string $header ): bool {
		$header = \trim( $header );

		if ( '*' === $header ) {
			return true;
		}

		foreach ( \explode( ',', $header ) as $candidate ) {
			$candidate = \trim( $candidate );

			if ( \str_starts_with( $candidate, 'W/' ) ) {
				$candidate = \substr( $candidate, 2 );
			}

			if ( '' !== $candidate && $candidate === $etag ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds the rewrite rule and flushes the rules on activation.
	 */
	public static function activate(): void {
		self::add_rewrite_rules();
		\flush_rewrite_rules( false );
	}

	/**
	 * Drops the rewrite rules on deactivation.
	 *
	 * A flush would regenerate them with this plugin's rule still registered,
	 * so they are deleted and regenerated on the next request instead.
	 */
	public static function deactivate(): void {
		\delete_option( 'rewrite_rules' );
	}

	/**
	 * Sends a plain text error and exits.
	 *
	 * @param int    $status  The HTTP status code.
	 * @param string $message The error message.
	 */
	private static function send_error( int $status, string $message ): never {
		\status_header( $status );
		\header( 'Content-Type: text/plain; charset=' . \get_bloginfo( 'charset' ) );
		echo \esc_html( $message );
		exit;
	}
}
