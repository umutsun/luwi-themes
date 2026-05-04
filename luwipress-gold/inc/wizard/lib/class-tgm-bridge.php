<?php
/**
 * TGM Plugin Activation bridge.
 *
 * Required plugins for LuwiPress Gold:
 *   - Elementor (free)        — page builder
 *   - ElementsKit Lite (free) — header/footer/mega menu builder
 *   - WooCommerce (free)      — only flagged when the user picks a WC-enabled path
 *
 * This bridge is intentionally light — it does NOT bundle the TGMPA library
 * (we ask the wizard UI to install via WP's standard plugin installer). The
 * heavier TGMPA flow can be wired in later if a paid Pro version of LuwiPress
 * needs to bundle commercial plugins.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LuwiPress_Gold_TGM_Bridge {

	/**
	 * Required plugins manifest.
	 */
	public function manifest() {
		return [
			'elementor' => [
				'name'     => 'Elementor',
				'slug'     => 'elementor',
				'file'     => 'elementor/elementor.php',
				'wporg'    => 'elementor',
				'required' => true,
				'why'      => __( 'Page builder — required for the LuwiPress Gold templates.', 'luwipress-gold' ),
			],
			'elementskit-lite' => [
				'name'     => 'ElementsKit Lite',
				'slug'     => 'elementskit-lite',
				'file'     => 'elementskit-lite/elementskit-lite.php',
				'wporg'    => 'elementskit-lite',
				'required' => true,
				'why'      => __( 'Header builder + mega menu — bundled kit relies on this for the sticky header.', 'luwipress-gold' ),
			],
			'woocommerce' => [
				'name'     => 'WooCommerce',
				'slug'     => 'woocommerce',
				'file'     => 'woocommerce/woocommerce.php',
				'wporg'    => 'woocommerce',
				'required' => false, // optional — only if the store-flow path is chosen
				'why'      => __( 'E-commerce backbone — required for the shop, single product, cart, checkout, and account templates.', 'luwipress-gold' ),
			],
		];
	}

	/**
	 * Status of every required plugin: installed / active / missing, with install URL.
	 */
	public function required_plugins_status() {
		$out = [];
		foreach ( $this->manifest() as $key => $cfg ) {
			$installed = $this->is_plugin_installed( $cfg['file'] );
			$active    = is_plugin_active( $cfg['file'] );

			$install_url = wp_nonce_url(
				self_admin_url( 'update.php?action=install-plugin&plugin=' . $cfg['wporg'] ),
				'install-plugin_' . $cfg['wporg']
			);
			$activate_url = wp_nonce_url(
				self_admin_url( 'plugins.php?action=activate&plugin=' . $cfg['file'] ),
				'activate-plugin_' . $cfg['file']
			);

			$out[ $key ] = [
				'name'         => $cfg['name'],
				'slug'         => $cfg['slug'],
				'required'     => $cfg['required'],
				'why'          => $cfg['why'],
				'installed'    => $installed,
				'active'       => $active,
				'install_url'  => $installed ? null : $install_url,
				'activate_url' => $installed && ! $active ? $activate_url : null,
			];
		}
		return $out;
	}

	/**
	 * @param string $file e.g. "elementor/elementor.php"
	 */
	private function is_plugin_installed( $file ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		return isset( $plugins[ $file ] );
	}

	/**
	 * True if every required plugin is active.
	 */
	public function all_required_satisfied() {
		foreach ( $this->required_plugins_status() as $p ) {
			if ( $p['required'] && ! $p['active'] ) return false;
		}
		return true;
	}
}
