var charitable_reCAPTCHA_onload = function() {

	( function( $ ) {
		var $body      = $( 'body' );
		var $recaptcha = $( '.charitable-recaptcha' );
		var input      = $recaptcha[0].nextSibling;
		var form       = input.form;
		var recaptcha_id;

		/**
		 * Remove a pending process by name from the helper.
		 *
		 * This provides backwards compatibility for versions of Charitable
		 * that don't have the remove_pending_process_by_name method.
		 */
		var remove_pending_process_by_name = function( helper ) {
			if ( helper.__proto__.hasOwnProperty( 'remove_pending_process_by_name' ) ) {
				process_id = helper.remove_pending_process_by_name( 'recaptcha' );
			} else {
				var index = this.pending_processes.indexOf( process );
        		return -1 !== index && this.remove_pending_process( index );
			}
		}

		/**
		 * For donation form submissions, execute reCAPTCHA as part of the
		 * validation process, before firing off the rest of the donation
		 * form processing.
		 */
		var donation_form_handler = function() {
			var helper;

			recaptcha_id = grecaptcha.render( $recaptcha[0], {
				'sitekey' : CHARITABLE_RECAPTCHA.site_key,
				'callback' : function( token ) {
					input.setAttribute( 'value', token );

					remove_pending_process_by_name( helper );
				},
				'size' : 'invisible',
				'isolated' : true,
			} );

			$body.on( 'charitable:form:validate', function( event, target ) {
				helper = target;

				if ( helper.errors.length === 0 ) {
					helper.add_pending_process( 'recaptcha' );

					grecaptcha.execute( recaptcha_id );
				}
			} );
		}

		/**
		 * For regular form submissions (not the donation form), execute reCAPTCHA
		 * when the form is submitted.
		 * */
		var default_form_handler = function() {
			recaptcha_id = grecaptcha.render( $recaptcha[0], {
				'sitekey' : CHARITABLE_RECAPTCHA.site_key,
				'callback' : function( token ) {
					input.setAttribute( 'value', token );
					form.submit();
				},
				'size' : 'invisible',
				'isolated' : true,
			} );

			form.onsubmit = function() {
				grecaptcha.execute( recaptcha_id );
				return false;
			}
		}

		if ( form.classList.contains( 'charitable-donation-form' ) ) {
			donation_form_handler();
		} else {
			default_form_handler();
		}
	})( jQuery );
}