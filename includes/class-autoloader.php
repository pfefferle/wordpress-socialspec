<?php
/**
 * Autoloader for SocialSpec plugin classes.
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec;

/**
 * Loads classes following the WordPress file naming conventions.
 *
 * `Socialspec\Documents\About` is loaded from `includes/documents/class-about.php`.
 */
final class Autoloader {

	/**
	 * File type prefixes to search.
	 */
	private const TYPE_PREFIXES = array( 'class', 'interface', 'trait' );

	/**
	 * Constructor.
	 *
	 * @param string $prefix The namespace prefix.
	 * @param string $path   The path to the classes directory.
	 */
	private function __construct(
		private readonly string $prefix,
		private readonly string $path
	) {}

	/**
	 * Registers an autoloader for a namespace.
	 *
	 * @param string $prefix The namespace prefix.
	 * @param string $path   The path to the classes directory.
	 */
	public static function register_path( string $prefix, string $path ): void {
		\spl_autoload_register( array( new self( $prefix, \rtrim( $path, '/' ) . '/' ), 'load' ) );
	}

	/**
	 * Loads a class file.
	 *
	 * @param string $class_name The fully-qualified class name.
	 */
	public function load( string $class_name ): void {
		if ( ! \str_starts_with( $class_name, $this->prefix ) ) {
			return;
		}

		$relative = \strtolower( \str_replace( array( '\\', '_' ), array( '/', '-' ), \substr( $class_name, \strlen( $this->prefix ) ) ) );
		$sub_path = \dirname( $relative );
		$sub_path = '.' === $sub_path ? '' : $sub_path . '/';
		$name     = \basename( $relative );

		foreach ( self::TYPE_PREFIXES as $type ) {
			$file = $this->path . $sub_path . $type . '-' . $name . '.php';

			if ( \is_file( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
}
