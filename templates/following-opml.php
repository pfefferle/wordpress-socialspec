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
<?php
/*
 * The script shows the OPML in browsers. The labels are translated here
 * and passed to the script in a JSON script element.
 */
$socialspec_l10n = array(
	'lang'             => \get_bloginfo( 'language' ),
	'intro'            => \sprintf(
		/* translators: %1$s: opening strong tag; %2$s: closing strong tag. */
		\esc_html__( '%1$sThis is an OPML subscription list.%2$s Feed readers use OPML to share and move a collection of subscriptions.', 'socialspec' ),
		'<strong>',
		'</strong>'
	),
	'follows'          => \sprintf(
		/* translators: 1: FollowSpec link, 2: SocialSpec link. */
		\esc_html__( 'It lists the blogs and podcasts this site follows, as described by %1$s. Feed readers that support %2$s find it on their own and show it next to the About page and the contact card of the site.', 'socialspec' ),
		'<a href="https://followspec.org/">FollowSpec</a>',
		'<a href="https://socialspec.org/">SocialSpec</a>'
	),
	'import'           => \sprintf(
		/* translators: %1$s: opening link tag to opml.org; %2$s: closing link tag. */
		\esc_html__( 'Copy the address of this page and use the import feature of your feed reader to add the whole list at once. %1$sLearn more about OPML%2$s', 'socialspec' ),
		'<a href="https://opml.org/">',
		'</a>'
	),
	'subscriptionList' => \__( 'Subscription list', 'socialspec' ),
	'subscribe'        => \__( 'Subscribe', 'socialspec' ),
	'feed'             => \__( 'Feed', 'socialspec' ),
	'link'             => \__( 'Link', 'socialspec' ),
	'empty'            => \__( 'This list is empty.', 'socialspec' ),
);

\printf(
	"\t" . '<script xmlns="http://www.w3.org/1999/xhtml" type="application/json" id="socialspec-opml-l10n">%s</script>' . "\n",
	\htmlspecialchars( \wp_json_encode( $socialspec_l10n ), ENT_XML1 | ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped for XML.
);

\printf(
	"\t" . '<script xmlns="http://www.w3.org/1999/xhtml" src="%s" defer=""/>' . "\n", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- An OPML document has no wp_head().
	\esc_url( \add_query_arg( 'ver', SOCIALSPEC_VERSION, \plugins_url( 'assets/js/opml.js', SOCIALSPEC_PLUGIN_DIR . 'socialspec.php' ) ) )
);
?>
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
