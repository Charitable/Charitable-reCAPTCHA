# Charitable reCAPTCHA

Block bots.

Use Google reCAPTCHA v2 Invisible to challenge suspicious form submissions on Charitable forms.

## Minimum requirements

- Charitable 1.6.9+
- PHP 5.3+

## Usage

After installing and activating this plugin, you need to add your Site Key and Secret Key to enable the plugin. Once you have done this, reCAPTCHA Invisible will automatically be enabled for all your Charitable forms (donation form, login, registration, etc.).

### Setup

#### Get API Keys from Google

You can find your Site Key and Secret Key by going to www.google.com/recaptcha/admin.

If you have not already added your site as a reCAPTCHA site, fill out the "Register a new site" form. In the section where you choose the type of reCAPTCHA, select Invisible (under reCAPTCHA v2). In the Domains field, enter you site domain. Agree to the Terms of Service and finally click on Register.

Next, the page will display setup instructions. You can ignore most of this, but will need the "Site key" and "Secret key" to complete setup within Charitable.

#### Add API Keys to Charitable

Go to Charitable > Settings > Advanced and enter your Site Key and Secret Key in the relevant settings.

### Advanced

#### Disable reCAPTCHA on a particular form

You can disable reCAPTCHA for one or more forms with the `charitable_recaptcha_forms` filter.

```
/**
 * Disable reCAPTCHA for one or more forms.
 *
 * reCAPTCHA is enabled for all forms by default.
 *
 * Form keys to use:
 *
 * donation_form
 * donation_amount_form
 * registration_form
 * password_reset_form
 * password_retrieval_form
 * profile_form
 * campaign_form -- Front-end campaign submission form available with Ambassadors.
 *
 * @param  array $forms All the supported forms in a key=>value array, where the value is either
 *                      true (reCAPTCHA is enabled) or false (reCAPTCHA is disabled).
 * @return array
 */
function ed_charitable_disable_recaptcha_on_form( $forms ) {
	/**
	 * In this example, we're disabling reCAPTCHA for the donation form and profile form.
	 */
	$forms['donation_form'] = false;
	$forms['profile_form'] = false;

	return $forms;
}

add_filter( 'charitable_recaptcha_forms', 'ed_charitable_disable_recaptcha_on_form' );
```
