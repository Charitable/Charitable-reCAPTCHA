<?php
/**
 * Plugin Name:       Charitable - reCAPTCHA
 * Plugin URI:        https://github.com/Charitable/Charitable-reCAPTCHA
 * Description:       Block bots. Add Invisible reCAPTCHA to Charitable's donation, registration, profile, password reset & retrieval forms.
 * Version:           1.0.6
 * Author:            WP Charitable
 * Author URI:        https://www.wpcharitable.com
 * Requires at least: 4.5
 * Tested up to:      5.5
 *
 * Text Domain:       charitable-recaptcha
 * Domain Path:       /languages/
 *
 * @package Charitable/reCAPTCHA/Core
 * @author  WP Charitable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'plugins_loaded',
	function() {

		/**
		 * Check that Charitable is installed.
		 */
		if ( ! class_exists( 'Charitable' ) ) {
			return;
		}

		/**
		 * Load required files.
		 */
		require_once( 'class-charitable-recaptcha-admin.php' );
		require_once( 'class-charitable-recaptcha-form.php' );

		/**
		 * Load admin class with settings.
		 */
		if ( is_admin() ) {
			new Charitable_ReCAPTCHA_Admin();
		}

		/**
		 * Load form integration if reCAPTCHA fields are set.
		 */
		if ( charitable_is_recaptcha_active() ) {
			new Charitable_ReCAPTCHA_Form();
		}

	},
	1
);

/**
 * Check whether reCAPTCHA is activated.
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_recaptcha_active() {
	return charitable_get_option( 'recaptcha_site_key' ) && charitable_get_option( 'recaptcha_secret_key' );
}
