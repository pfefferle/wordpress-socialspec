<?php
/**
 * ContactSpec: /.well-known/contact.vcf
 *
 * @link https://contactspec.org/
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec\Documents;

use Socialspec\Document;

/**
 * The site contact as vCard 3.0 (RFC 2426).
 */
final class Contact implements Document {

	/**
	 * Properties whose values are URIs and must not be escaped as text.
	 */
	private const URI_PROPERTIES = array( 'LOGO', 'PHOTO', 'SOUND', 'SOURCE', 'URL' );

	/**
	 * Properties written by the renderer itself.
	 */
	private const RESERVED_PROPERTIES = array( 'BEGIN', 'END', 'VERSION' );

	/**
	 * The media type.
	 *
	 * @return string The media type.
	 */
	public function get_content_type(): string {
		return 'text/vcard';
	}

	/**
	 * Prints the vCard.
	 *
	 * Describes the user who represents the site, or the site itself.
	 * No email address is published by default, the user and admin
	 * emails are private.
	 *
	 * @return bool False if a filter removed the vCard.
	 */
	public function render(): bool {
		$host = (string) \wp_parse_url( \home_url(), PHP_URL_HOST );
		$user = \get_userdata( (int) \get_option( 'socialspec_user' ) ) ?: null;

		$properties   = $user ? self::get_user_properties( $user ) : self::get_site_properties( $host );
		$properties[] = array( 'NICKNAME', '@' . $host );
		$properties[] = array( 'SOURCE', \home_url( '/.well-known/contact.vcf' ) );

		/**
		 * Filters the vCard properties of /.well-known/contact.vcf.
		 *
		 * Each property is a pair of the name, with optional group and
		 * parameters, and its value. Text values are escaped, URI values
		 * (URL, PHOTO, …) are not, and arrays are structured values:
		 *
		 *     $properties[] = array( 'EMAIL;TYPE=INTERNET,PREF', 'hi@example.com' );
		 *     $properties[] = array( 'ADR;TYPE=WORK', array( '', '', 'Street 1', 'City', '', '12345', 'Country' ) );
		 *
		 * @param array[]       $properties The properties.
		 * @param \WP_User|null $user       The user who represents the site, or null for the site.
		 */
		$properties = \apply_filters( 'socialspec_vcard_properties', $properties, $user );

		$lines = array( 'BEGIN:VCARD', 'VERSION:3.0' );

		foreach ( (array) $properties as $property ) {
			$line = self::content_line( $property );

			if ( null !== $line ) {
				$lines[] = self::fold( $line );
			}
		}

		$lines[] = 'END:VCARD';

		/**
		 * Filters the /.well-known/contact.vcf document.
		 *
		 * @param string|null $vcard      The vCard, null to send a 404.
		 * @param array[]     $properties The properties.
		 */
		$vcard = \apply_filters( 'socialspec_vcard', \implode( "\r\n", $lines ) . "\r\n", $properties );

		if ( ! \is_string( $vcard ) ) {
			return false;
		}

		echo $vcard; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped as vCard text.
		return true;
	}

	/**
	 * The properties of the site: title, URL, tagline and icon.
	 *
	 * @param string $host The site host, the name if the site has no title.
	 *
	 * @return array[] The properties.
	 */
	private static function get_site_properties( string $host ): array {
		$name       = self::decode( \get_bloginfo( 'name' ) );
		$properties = array(
			array( 'FN', '' !== $name ? $name : $host ),
			array( 'N', array( '', '', '', '', '' ) ),
			array( 'URL', \home_url( '/' ) ),
		);

		$description = self::decode( \get_bloginfo( 'description' ) );
		if ( '' !== $description ) {
			$properties[] = array( 'NOTE', $description );
		}

		$icon = \get_site_icon_url( 512 );
		if ( '' !== $icon ) {
			$properties[] = array( 'PHOTO;VALUE=uri', $icon );
		}

		return $properties;
	}

	/**
	 * The properties of a user: name, website, biographical info and avatar.
	 *
	 * @param \WP_User $user The user.
	 *
	 * @return array[] The properties.
	 */
	private static function get_user_properties( \WP_User $user ): array {
		$properties = array(
			array( 'FN', self::decode( $user->display_name ) ),
			array( 'N', array( self::decode( $user->last_name ), self::decode( $user->first_name ), '', '', '' ) ),
			array( 'URL', $user->user_url ? $user->user_url : \home_url( '/' ) ),
		);

		$description = \trim( self::decode( \wp_strip_all_tags( \get_the_author_meta( 'description', $user->ID ) ) ) );
		if ( '' !== $description ) {
			$properties[] = array( 'NOTE', $description );
		}

		// Only where the site shows avatars.
		$avatar = \get_option( 'show_avatars' ) ? \get_avatar_url( $user->ID, array( 'size' => 512 ) ) : false;
		if ( $avatar ) {
			$properties[] = array( 'PHOTO;VALUE=uri', $avatar );
		}

		return $properties;
	}

	/**
	 * Builds a content line from a property.
	 *
	 * @param mixed $property The property as array( name, value ).
	 *
	 * @return string|null The content line, or null if the property is invalid.
	 */
	private static function content_line( mixed $property ): ?string {
		if ( ! \is_array( $property ) || ! isset( $property[0], $property[1] ) || ! \is_string( $property[0] ) ) {
			return null;
		}

		[ $name, $value ] = $property;

		// The name, with an optional group prefix and parameters.
		if ( ! \preg_match( '/^(?:[A-Za-z0-9-]+\.)?([A-Za-z0-9-]+)(?:;[^:\r\n]+)?$/', $name, $matches ) ) {
			return null;
		}

		$type = \strtoupper( $matches[1] );

		if ( \in_array( $type, self::RESERVED_PROPERTIES, true ) ) {
			return null;
		}

		if ( \is_array( $value ) ) {
			$value = \implode( ';', \array_map( static fn ( $component ): string => \is_scalar( $component ) ? self::escape( (string) $component ) : '', $value ) );
		} elseif ( ! \is_scalar( $value ) ) {
			return null;
		} elseif ( \in_array( $type, self::URI_PROPERTIES, true ) ) {
			$value = \str_replace( array( "\r", "\n" ), '', (string) $value );
		} else {
			$value = self::escape( (string) $value );
		}

		return $name . ':' . $value;
	}

	/**
	 * Escapes a text value (RFC 2426, section 4).
	 *
	 * @param string $value The value.
	 *
	 * @return string The escaped value.
	 */
	private static function escape( string $value ): string {
		return \str_replace(
			array( '\\', ',', ';', "\r\n", "\r", "\n" ),
			array( '\\\\', '\\,', '\\;', '\\n', '\\n', '\\n' ),
			$value
		);
	}

	/**
	 * Folds a content line at 75 octets without splitting multibyte characters.
	 *
	 * @param string $line The content line.
	 *
	 * @return string The folded line.
	 */
	private static function fold( string $line ): string {
		$folded = '';
		$length = 0;

		// Split into UTF-8 characters, or bytes if the line is not valid UTF-8.
		$chars = \preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY ) ?: \str_split( $line );

		foreach ( $chars as $char ) {
			if ( $length + \strlen( $char ) > 75 ) {
				$folded .= "\r\n ";
				$length  = 1;
			}

			$folded .= $char;
			$length += \strlen( $char );
		}

		return $folded;
	}

	/**
	 * Decodes the HTML entities WordPress stores in site options.
	 *
	 * @param string $value The value.
	 *
	 * @return string The decoded value.
	 */
	private static function decode( string $value ): string {
		return \html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, \get_bloginfo( 'charset' ) );
	}
}
