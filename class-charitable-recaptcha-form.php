<?php
/**
 * Add reCAPTCHA to Charitable forms.
 *
 * @package   Charitable/Classes/Charitable_ReCAPTCHA_Form
 * @author    Eric Daams
 * @copyright Copyright (c) 2018, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_ReCAPTCHA_Form' ) ) :

	/**
	 * Charitable_ReCAPTCHA_Form
	 *
	 * @since 1.0.0
	 */
	class Charitable_ReCAPTCHA_Form {

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 15 );
			add_action( 'charitable_form_after_fields', array( $this, 'add_invisible_div' ) );

			/**
			 * For the password retrieval, password reset, profile and registration
			 * forms, we check recaptcha before the regular form processor occurs.
			 *
			 * If the recaptcha check fails, we prevent further processing.
			 */
			add_action( 'charitable_retrieve_password', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_reset_password', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_update_profile', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_save_registration', array( $this, 'check_recaptcha_before_form_processing' ), 1 );

			/**
			 * For the donation form, validate recaptcha as part of the security check.
			 */
			add_filter( 'charitable_validate_donation_form_submission_security_check', array( $this, 'validate_recaptcha' ), 10, 2 );
		}

		/**
		 * Add reCAPTCHA script and our form submission handler.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function add_scripts() {
			$dir = plugin_dir_url( __FILE__ ) . 'assets/';

			wp_register_script( 'charitable-recaptcha', $dir . 'charitable-recaptcha-handler.js', array( 'jquery-core' ) );

			wp_enqueue_script( 'charitable-recaptcha' );

			wp_localize_script( 'charitable-recaptcha', 'CHARITABLE_RECAPTCHA', array(
				'site_key' => charitable_get_option( 'recaptcha_site_key' ),
			) );

			if ( class_exists( 'Charitable_Stripe' ) && version_compare( Charitable_Stripe::VERSION, '1.3.0', '<' ) ) {
				$wp_scripts = wp_scripts();

				if ( isset( $wp_scripts->registered['charitable-stripe-handler'] ) ) {
					$script      = $wp_scripts->registered['charitable-stripe-handler'];
					$script->src = $dir . 'stripe/charitable-stripe-handler.js';

					$wp_scripts->registered['charitable-stripe-handler'] = $script;
				}
			}
		}

		/**
		 * Returns an array of forms that reCAPTCHA can be enabled for.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_form_settings() {
			/**
			 * Returns an array of forms along with whether recaptcha
			 * is enabled for them. By default, all forms are enabled.
			 *
			 * @since 1.0.0
			 *
			 * @param array $forms All the supported forms in a key=>value array, where the value is either
			 *                     true (reCAPTCHA is enabled) or false (reCAPTCHA is disabled).
			 */
			return apply_filters( 'charitable_recaptcha_forms', array(
				'donation_form'           => true,
				'donation_amount_form'    => true,
				'registration_form'       => true,
				'password_reset_form'     => true,
				'password_retrieval_form' => true,
				'profile_form'            => true,
				'campaign_form'           => true,
			) );
		}

		/**
		 * Return the current form key based on the class name.
		 *
		 * @since  1.0.0
		 *
		 * @param  Charitable_Form $form A form object.
		 * @return string|null Form key if it's a supported form. Null otherwise.
		 */
		public function get_current_form_from_class( Charitable_Form $form ) {
			switch ( get_class( $form ) ) {
				case 'Charitable_Registration_Form':
					$form_key = 'registration_form';
					break;

				case 'Charitable_Profile_Form':
					$form_key = 'profile_form';
					break;

				case 'Charitable_Forgot_Password_Form':
					$form_key = 'password_retrieval_form';
					break;

				case 'Charitable_Reset_Password_Form':
					$form_key = 'password_reset_form';
					break;

				case 'Charitable_Donation_Form':
					$form_key = 'donation_form';
					break;

				case 'Charitable_Donation_Amount_Form':
					$form_key = 'donation_amount_form';
					break;

				case 'Charitable_Ambassadors_Campaign_Form':
					$form_key = 'campaign_form';
					break;

				default:
					$form_key = null;
			}

			return $form_key;
		}

		/**
		 * Return the form key based on the hook.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_current_form_from_hook() {
			switch ( current_filter() ) {
				case 'charitable_save_registration':
					$form_key = 'registration_form';
					break;

				case 'charitable_update_profile':
					$form_key = 'profile_form';
					break;

				case 'charitable_retrieve_password':
					$form_key = 'password_retrieval_form';
					break;

				case 'charitable_reset_password':
					$form_key = 'password_reset_form';
					break;

				case 'charitable_save_campaign':
					$form_key = 'campaign_form';
					break;

				default:
					$form_key = null;
			}

			return $form_key;
		}

		/**
		 * Returns whether reCAPTCHA is enabled for the given form.
		 *
		 * @since  1.0.0
		 *
		 * @param  string|null $form_key The key of the form, or NULL if it's not a supported one.
		 * @return boolean
		 */
		public function is_enabled_for_form( $form_key ) {
			if ( is_null( $form_key ) ) {
				return false;
			}

			$forms = $this->get_form_settings();

			return $forms[ $form_key ];
		}

		/**
		 * Add script before the end of the form.
		 *
		 * @since  1.0.0
		 *
		 * @param  Charitable_Form $form Form object.
		 * @return void
		 */
		public function add_invisible_div( Charitable_Form $form ) {
			if ( $this->is_enabled_for_form( $this->get_current_form_from_class( $form ) ) ) {
				echo '<div class="charitable-recaptcha"></div>';
				echo '<input type="hidden" name="grecaptcha_token" value="" />';
				echo '<script src="https://www.google.com/recaptcha/api.js?onload=charitable_reCAPTCHA_onload&render=explicit" async defer></script>';
			}
		}

		/**
		 * Check reCAPTCHA token validity before processing a form submission.
		 *
		 * If the reCAPTCHA check fails, form processing is blocked.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function check_recaptcha_before_form_processing() {
			$form_key = $this->get_current_form_from_hook();

			if ( $this->is_enabled_for_form( $form_key ) && ! $this->is_captcha_valid() ) {
				switch ( $form_key ) {
					case 'registration_form':
						remove_action( 'charitable_save_registration', array( 'Charitable_Registration_Form', 'save_registration' ) );
						break;

					case 'profile_form':
						remove_action( 'charitable_update_profile', array( 'Charitable_Profile_Form', 'update_profile' ) );
						break;

					case 'password_retrieval_form':
						remove_action( 'charitable_retrieve_password', array( 'Charitable_Forgot_Password_Form', 'retrieve_password' ) );
						break;

					case 'password_reset_form':
						remove_action( 'charitable_reset_password', array( 'Charitable_Reset_Password_Form', 'reset_password' ) );
						break;

					case 'campaign_form':
						remove_action( 'charitable_save_campaign', array( 'Charitable_Ambassadors_Campaign_Form', 'save_campaign' ) );
						break;
				}
			}
		}

		/**
		 * Validate the reCAPTCHA token.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean                  $ret  The result to be returned. True or False.
		 * @param  Charitable_Donation_Form $form The donation form object.
		 * @return boolean
		 */
		public function validate_recaptcha( $ret, Charitable_Donation_Form $form ) {
			if ( ! $ret ) {
				return $ret;
			}

			if ( ! $this->is_enabled_for_form( $this->get_current_form_from_class( $form ) ) ) {
				return $ret;
			}

			return $this->is_captcha_valid();
		}

		/**
		 * Returns whether the captcha is valid.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_captcha_valid() {
			if ( ! array_key_exists( 'grecaptcha_token', $_POST ) ) {
				charitable_get_notices()->add_error( __( 'Missing captcha token.', 'charitable' ) );
				return false;
			}

			$response = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'body' => array(
						'secret'   => charitable_get_option( 'recaptcha_secret_key' ),
						'response' => $_POST['grecaptcha_token'],
						'remoteip' => $_SERVER['REMOTE_ADDR'],
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				charitable_get_notices()->add_error( __( 'Failed to verify captcha.', 'charitable' ) );
				return false;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! $result['success'] ) {
				charitable_get_notices()->add_error( __( 'Captcha validation failed.', 'charitable' ) );
			}

			return $result['success'];
		}
	}

endif;
