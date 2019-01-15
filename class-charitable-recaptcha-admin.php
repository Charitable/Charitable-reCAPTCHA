<?php
/**
 * Add reCAPTCHA settings to the admin area.
 *
 * @package   Charitable/Classes/Charitable_ReCAPTCHA_Admin
 * @author    Eric Daams
 * @copyright Copyright (c) 2018, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Charitable_ReCAPTCHA_Admin' ) ) :

	/**
	 * Charitable_ReCAPTCHA_Admin
	 *
	 * @since 1.0.0
	 */
	class Charitable_ReCAPTCHA_Admin {

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'charitable_settings_tab_fields_advanced', array( $this, 'add_settings' ) );
		}

		/**
		 * Add settings to the Advanced Settings page.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $settings Existing settings on the Advanced Settings page.
		 * @return array
		 */
		public function add_settings( $settings ) {
			$settings['section_recaptcha'] = array(
				'title'    => __( 'reCAPTCHA', 'charitable' ),
				'type'     => 'heading',
				'priority' => 30,
			);

			$settings['recaptcha_site_key'] = array(
				'title'    => __( 'Site Key', 'charitable' ),
				'type'     => 'text',
				'class'    => 'wide',
				'help'     => __( 'Your reCAPTCHA "Site key" setting. Find this at <a href="https://www.google.com/recaptcha/admin" target="_blank">www.google.com/recaptcha/admin</a>.', 'charitable' ),
				'priority' => 34,
			);

			$settings['recaptcha_secret_key'] = array(
				'title'    => __( 'Secret Key', 'charitable' ),
				'type'     => 'text',
				'class'    => 'wide',
				'help'     => __( 'Your reCAPTCHA "Secret key" setting. Find this at <a href="https://www.google.com/recaptcha/admin" target="_blank">www.google.com/recaptcha/admin</a>.', 'charitable' ),
				'priority' => 36,
			);

			return $settings;
		}
	}

endif;
