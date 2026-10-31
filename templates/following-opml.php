<?php
/**
 * OPML template for /.well-known/following.opml.
 *
 * @package Socialspec
 *
 * @var array $args {
 *     Template arguments.
 *
 *     @type array[]      $outlines     The sanitized outline attributes.
 *     @type string[]     $scripts      The URLs of the scripts that show the OPML in the browser.
 *     @type string|false $translations The translations of the script as JSON, or false.
 * }
 */

\defined( 'ABSPATH' ) || exit;

echo '<?xml version="1.0" encoding="' . \esc_attr( \get_bloginfo( 'charset' ) ) . '"?>' . "\n";
?>
<opml version="2.0">
<?php if ( $args['translations'] ) : ?>
	<script xmlns="http://www.w3.org/1999/xhtml" type="application/json" id="opml-translations"><?php echo \htmlspecialchars( $args['translations'], ENT_XML1 | ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped for XML. ?></script>
<?php endif; ?>
<?php foreach ( $args['scripts'] as $socialspec_src ) : ?>
	<script xmlns="http://www.w3.org/1999/xhtml" src="<?php echo \htmlspecialchars( $socialspec_src, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Escaped for XML, registered in Following. ?>" defer=""/>
<?php endforeach; ?>
	<head>
		<title><?php echo \esc_xml( \sprintf( /* translators: %s: Site title. */ \__( '%s Follows', 'socialspec' ), \get_bloginfo( 'name' ) ) ); ?></title>
		<ownerId><?php echo \esc_xml( \home_url( '/' ) ); ?></ownerId>
		<docs>http://opml.org/spec2.opml</docs>
<?php
/**
 * Fires at the end of the head of /.well-known/following.opml.
 *
 * Print OPML head elements, e.g. `<dateModified>` or `<ownerName>`, escaped for XML.
 */
\do_action( 'socialspec_following_opml_head' );
?>
	</head>
	<body>
<?php foreach ( $args['outlines'] as $outline ) : ?>
		<outline <?php echo \implode( ' ', \array_map( static fn ( string $name, string $value ): string => $name . '="' . \htmlspecialchars( $value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, \get_bloginfo( 'charset' ) ) . '"', \array_keys( $outline ), $outline ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Names are validated, values are escaped. esc_xml() would keep CDATA sections. ?>/>
<?php endforeach; ?>
<?php
/**
 * Fires at the end of the body of /.well-known/following.opml.
 *
 * Print more outlines, e.g. nested ones, escaped for XML.
 *
 * @param array[] $outlines The outlines printed before.
 */
\do_action( 'socialspec_following_opml_body', $args['outlines'] );
?>
	</body>
</opml>
