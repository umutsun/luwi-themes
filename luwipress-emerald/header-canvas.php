<?php
/**
 * LuwiPress Emerald — header-canvas.php
 *
 * Minimal head for the canvas/full-bleed template (no topbar, no
 * header, no nav). Elementor Pro Theme Builder takes over from here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'emerald-site emerald-canvas' ); ?>>
<?php wp_body_open(); ?>
