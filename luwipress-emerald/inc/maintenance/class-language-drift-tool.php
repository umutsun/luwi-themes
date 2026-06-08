<?php
/**
 * Language Drift Sweep — find translated posts whose body is still in the
 * source language (the silent failure mode that makes /translation/missing
 * report 100% coverage even when the body is broken English) and re-fire
 * the AI translation pipeline against them.
 *
 * Wraps the LuwiPress core endpoints `/translation/language-drift` and
 * `/translation/force-retranslate`. The wrapper lives in the theme so the
 * operator can run it from the Theme Tools tab alongside `wpml_term_repair`,
 * `menu_translation_propagate`, etc.
 *
 * Snapshots the pre-execution body of every targeted translation post into
 * the bridge's backup ledger; restore writes those bodies back so operators
 * can roll back if a fresh AI translation regressed brand names / headings.
 *
 * @package luwipress-emerald
 * @since   1.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LuwiPress_Emerald_Language_Drift_Tool {

	/**
	 * Default-language code resolved via WPML (fallback `en`). Used to filter
	 * the translation set so we only score actual translation posts.
	 */
	private static function default_lang() {
		$d = apply_filters( 'wpml_default_language', null );
		return $d ?: 'en';
	}

	/**
	 * Active non-source languages — the languages we sweep when the operator
	 * doesn't pass an explicit list.
	 */
	private static function target_langs() {
		$active = apply_filters( 'wpml_active_languages', null );
		if ( ! is_array( $active ) ) {
			return array();
		}
		$default = self::default_lang();
		return array_values( array_diff( array_keys( $active ), array( $default ) ) );
	}

	public static function scan( $args = array(), $tool = array() ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! class_exists( 'LuwiPress_Translation' ) ) {
			return array(
				'candidates' => array(),
				'count'      => 0,
				'meta'       => array(
					'wpml_active'  => defined( 'ICL_SITEPRESS_VERSION' ),
					'core_loaded'  => class_exists( 'LuwiPress_Translation' ),
					'note'         => 'WPML and LuwiPress core required for drift detection.',
				),
			);
		}

		$post_types = array_filter( array_map( 'sanitize_key', (array) ( $args['post_types'] ?? array( 'post', 'page' ) ) ) );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}
		$threshold = isset( $args['threshold'] ) ? (float) $args['threshold'] : 0.45;
		if ( $threshold <= 0 || $threshold >= 1 ) { $threshold = 0.45; }
		$limit     = max( 1, min( 500, (int) ( $args['limit'] ?? 200 ) ) );
		$languages = isset( $args['languages'] ) ? (array) $args['languages'] : self::target_langs();
		$languages = array_values( array_filter( array_map( 'sanitize_text_field', $languages ) ) );
		if ( empty( $languages ) ) {
			return array( 'candidates' => array(), 'count' => 0, 'meta' => array( 'note' => 'No target languages active in WPML.' ) );
		}

		$candidates_by_source = array();
		$counts_by_lang       = array_fill_keys( $languages, 0 );
		$total_drifts         = 0;

		foreach ( $post_types as $pt ) {
			$req = new WP_REST_Request( 'GET', '/luwipress/v1/translation/language-drift' );
			$req->set_param( 'post_type', $pt );
			$req->set_param( 'languages', implode( ',', $languages ) );
			$req->set_param( 'limit', $limit );
			$req->set_param( 'threshold', $threshold );
			$resp = LuwiPress_Translation::get_instance()->get_language_drift( $req );
			if ( is_wp_error( $resp ) ) { continue; }
			$data = $resp->get_data();
			if ( empty( $data['items'] ) ) { continue; }
			foreach ( $data['items'] as $it ) {
				$sid = (int) $it['source_id'];
				if ( ! isset( $candidates_by_source[ $sid ] ) ) {
					$candidates_by_source[ $sid ] = array(
						'id'    => $sid,
						'title' => (string) $it['source_title'],
						'meta'  => '',
						'_data' => array(
							'post_type' => $pt,
							'langs'     => array(),
							'tids'      => array(),
						),
					);
				}
				$candidates_by_source[ $sid ]['_data']['langs'][] = $it['language'];
				$candidates_by_source[ $sid ]['_data']['tids'][ $it['language'] ] = (int) $it['translation_id'];
				$counts_by_lang[ $it['language'] ] = ( $counts_by_lang[ $it['language'] ] ?? 0 ) + 1;
				$total_drifts++;
			}
		}

		// Render per-source meta line: "ES (3%) · IT (5%) · FR (12%)" so the
		// operator scans the worst offenders first.
		foreach ( $candidates_by_source as $sid => &$row ) {
			$bits = array();
			foreach ( $row['_data']['langs'] as $lc ) {
				$bits[] = strtoupper( $lc );
			}
			$row['meta'] = sprintf( 'pt=%s · drifted langs: %s', $row['_data']['post_type'], implode( ',', array_unique( $bits ) ) );
		}
		unset( $row );

		return array(
			'candidates' => array_values( $candidates_by_source ),
			'count'      => count( $candidates_by_source ),
			'meta'       => array(
				'total_drifted_translations' => $total_drifts,
				'per_language'               => $counts_by_lang,
				'threshold'                  => $threshold,
				'languages'                  => $languages,
				'post_types'                 => $post_types,
				'note'                       => 'Execute clears the elementor "already-translated" guard meta on every drifted translation post and re-fires the AI translation pipeline. Backups capture the pre-execution body so restore can roll back if a fresh AI run regresses content.',
			),
		);
	}

	public static function execute( $args = array(), $tool = array() ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! class_exists( 'LuwiPress_Translation' ) ) {
			return new WP_Error( 'wpml_required', 'WPML + LuwiPress core required.', array( 'status' => 412 ) );
		}

		$ids = array_map( 'intval', (array) ( $args['post_ids'] ?? array() ) );
		$ids = array_values( array_filter( $ids ) );
		if ( ! $ids ) {
			return new WP_Error( 'no_ids', 'Provide source post IDs (default-language).', array( 'status' => 400 ) );
		}

		$languages = isset( $args['languages'] ) ? (array) $args['languages'] : self::target_langs();
		$languages = array_values( array_filter( array_map( 'sanitize_text_field', $languages ) ) );
		if ( empty( $languages ) ) {
			return new WP_Error( 'no_languages', 'No target languages provided.', array( 'status' => 400 ) );
		}

		// Snapshot every translation post body BEFORE we kick off the AI pipeline.
		// Restore will write these back if the operator regrets the new translation.
		$backup = array();
		foreach ( $ids as $sid ) {
			$post = get_post( $sid );
			if ( ! $post ) { continue; }
			$element_type = 'post_' . $post->post_type;
			$trid = apply_filters( 'wpml_element_trid', null, $sid, $element_type );
			if ( ! $trid ) { continue; }
			$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
			if ( ! is_array( $translations ) ) { continue; }
			foreach ( $languages as $lc ) {
				if ( empty( $translations[ $lc ]->element_id ) ) { continue; }
				$tid = (int) $translations[ $lc ]->element_id;
				$tpost = get_post( $tid );
				if ( ! $tpost ) { continue; }
				$backup[ $tid ] = array(
					'source_id'      => $sid,
					'language'       => $lc,
					'post_title'     => $tpost->post_title,
					'post_content'   => $tpost->post_content,
					'post_excerpt'   => $tpost->post_excerpt,
					'elementor_data' => get_post_meta( $tid, '_elementor_data', true ),
				);
			}
		}

		// Fire force-retranslate via REST. Async path is automatic when work
		// units > 5 (see force_retranslate() in core).
		$req = new WP_REST_Request( 'POST', '/luwipress/v1/translation/force-retranslate' );
		$req->set_param( 'post_ids', $ids );
		$req->set_param( 'languages', $languages );
		$req->set_param( 'async', true );
		$resp = LuwiPress_Translation::get_instance()->force_retranslate( $req );

		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = is_object( $resp ) && method_exists( $resp, 'get_data' ) ? $resp->get_data() : array();

		return array(
			'mutated'         => count( $backup ),
			'sources'         => count( $ids ),
			'languages'       => $languages,
			'mode'            => $data['mode'] ?? 'async',
			'work_units'      => $data['work_units'] ?? 0,
			'dispatched'      => $data['dispatched'] ?? 0,
			'note'            => ( ( $data['mode'] ?? 'async' ) === 'async' )
				? 'Queued on wp_cron — translations land within minutes. Re-scan to verify drift cleared.'
				: 'Translation completed inline.',
			'_backup_payload' => $backup,
		);
	}

	public static function restore( $args = array(), $tool = array() ) {
		$bridge = LuwiPress_Theme_Bridge::get_instance();
		$entry  = $bridge->load_backup( sanitize_text_field( $args['backup_id'] ?? '' ) );
		if ( ! $entry || $entry['tool_id'] !== 'language_drift_sweep' ) {
			return new WP_Error( 'backup_not_found', 'Backup not found.', array( 'status' => 404 ) );
		}
		$payload  = is_array( $entry['payload'] ) ? $entry['payload'] : array();
		$restored = 0;
		foreach ( $payload as $tid => $snap ) {
			$tid = (int) $tid;
			if ( ! get_post( $tid ) ) { continue; }
			wp_update_post( array(
				'ID'           => $tid,
				'post_title'   => $snap['post_title']   ?? '',
				'post_content' => $snap['post_content'] ?? '',
				'post_excerpt' => $snap['post_excerpt'] ?? '',
			), true );
			if ( ! empty( $snap['elementor_data'] ) ) {
				update_post_meta( $tid, '_elementor_data', $snap['elementor_data'] );
			}
			// Clear the guard meta so the post can be re-detected by drift scan
			// if the operator wants to try a different translation strategy.
			delete_post_meta( $tid, '_luwipress_elementor_translated' );
			clean_post_cache( $tid );
			$restored++;
		}
		return array( 'restored' => $restored );
	}
}
