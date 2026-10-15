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
	 * Renders the document.
	 *
	 * @return string|null The document, or null if there is nothing to publish.
	 */
	public function render(): ?string;
}
