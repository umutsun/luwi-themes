<?php
/**
 * Sapphire contact form handler.
 *
 * A minimal, self-contained handler so the Contact page works out of the
 * box without a form plugin. Validates a nonce + honeypot, sanitises the
 * fields and emails the site admin via wp_mail(), then redirects back to
 * the form with a status flag. Operators who prefer Contact Form 7 /
 * WPForms can drop the relevant shortcode into the Contact page content
 * — page-contact.php renders that instead when present.
 *
 * @package luwipress-sapphire
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sapphire_handle_contact' ) ) {
	function sapphire_handle_contact() {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = home_url( '/' );
		}

		// Nonce.
		if ( ! isset( $_POST['sapphire_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sapphire_contact_nonce'] ) ), 'sapphire_contact' ) ) {
			wp_safe_redirect( add_query_arg( 'sapphire_contact', 'error', $redirect ) );
			exit;
		}

		// Honeypot — bots fill hidden fields. Silently "succeed".
		if ( ! empty( $_POST['sapphire_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'sapphire_contact', 'sent', $redirect ) );
			exit;
		}

		$name    = isset( $_POST['sapphire_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sapphire_name'] ) ) : '';
		$email   = isset( $_POST['sapphire_email'] ) ? sanitize_email( wp_unslash( $_POST['sapphire_email'] ) ) : '';
		$phone   = isset( $_POST['sapphire_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['sapphire_phone'] ) ) : '';
		$interest = isset( $_POST['sapphire_interest'] ) ? sanitize_text_field( wp_unslash( $_POST['sapphire_interest'] ) ) : '';
		$message = isset( $_POST['sapphire_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sapphire_message'] ) ) : '';

		if ( '' === $name || ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'sapphire_contact', 'error', $redirect ) );
			exit;
		}

		$to      = (string) get_option( 'admin_email' );
		$subject = sprintf(
			/* translators: %s site name */
			__( '[%s] New enquiry from the website', 'luwipress-sapphire' ),
			wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body = array(
			__( 'Name:', 'luwipress-sapphire' ) . ' ' . $name,
			__( 'Email:', 'luwipress-sapphire' ) . ' ' . $email,
			__( 'Phone:', 'luwipress-sapphire' ) . ' ' . $phone,
			__( 'Interested in:', 'luwipress-sapphire' ) . ' ' . $interest,
			'',
			__( 'Message:', 'luwipress-sapphire' ),
			$message,
		);
		$headers = array();
		if ( is_email( $email ) ) {
			$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
		}

		$sent = wp_mail( $to, $subject, implode( "\n", $body ), $headers );

		wp_safe_redirect( add_query_arg( 'sapphire_contact', $sent ? 'sent' : 'error', $redirect ) );
		exit;
	}
	add_action( 'admin_post_sapphire_contact', 'sapphire_handle_contact' );
	add_action( 'admin_post_nopriv_sapphire_contact', 'sapphire_handle_contact' );
}
