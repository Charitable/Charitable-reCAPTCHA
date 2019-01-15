var charitable_reCAPTCHA_onload = function() {
	var button = document.querySelector( '.charitable-submit-field button' );
	var form   = button.form;

	grecaptcha.render( button, {
		'sitekey' : CHARITABLE_RECAPTCHA.site_key,
		'callback' : function( token ) {
			form.insertAdjacentHTML( 'beforeend', '<input type="hidden" name="grecaptcha_token" value="' + token + '" />' );
			form.submit();
		},
		'size' : 'invisible',
		'isolated' : true,
	} );
}