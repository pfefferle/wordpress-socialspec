<?php
/**
 * OPML template for /.well-known/following.opml.
 *
 * @package Socialspec
 *
 * @var array $args {
 *     Template arguments.
 *
 *     @type array[] $outlines The sanitized outline attributes.
 * }
 */

echo '<?xml version="1.0" encoding="' . \esc_attr( \get_bloginfo( 'charset' ) ) . '"?>' . "\n";
?>
<opml version="2.0">
	<head>
		<title><?php echo \esc_xml( \sprintf( /* translators: %s: Site title. */ \__( '%s Follows', 'socialspec' ), \get_bloginfo( 'name' ) ) ); ?></title>
		<ownerId><?php echo \esc_xml( \home_url( '/' ) ); ?></ownerId>
		<docs>http://opml.org/spec2.opml</docs>
	</head>
	<body>
<?php foreach ( $args['outlines'] as $outline ) : ?>
		<outline <?php echo \implode( ' ', \array_map( static fn ( string $name, string $value ): string => $name . '="' . \htmlspecialchars( $value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, \get_bloginfo( 'charset' ) ) . '"', \array_keys( $outline ), $outline ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Names are validated, values are escaped. esc_xml() would keep CDATA sections. ?>/>
<?php endforeach; ?>
	</body>
</opml>
