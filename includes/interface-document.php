<?php
/**
 * Document interface.
 *
 * @package Socialspec
 */

declare( strict_types = 1 );

namespace Socialspec;

/**
 * A document served at /.well-known/.
 */
interface Document {

	/**
	 * The media type, without charset.
	 *
	 * @return string The media type.
	 */
	public function get_content_type(): string;

	/**
	 * Prints the document.
	 *
	 * @return bool False if there is nothing to publish, then nothing is printed.
	 */
	public function render(): bool;
}
