( function( $ ) {

    var $body = $( 'body' );
    var process_id;

    /**
     * Add a pending process to the helper.
     *
     * This provides backwards compatibility for versions of Charitable
     * that just used the pause_processing flag and did not support the
     * more flexible add_pending_process/remove_pending_process methods.
     */
    var add_pending_process = function( helper ) {
        if ( helper.__proto__.hasOwnProperty( 'add_pending_process' ) ) {
            process_id = helper.add_pending_process( 'stripe' );
        } else {
            helper.pause_processing = true;
        }
    }

    /**
     * Remove a pending process to the helper.
     */
    var remove_pending_process = function( helper ) {
        if ( helper.__proto__.hasOwnProperty( 'remove_pending_process' ) ) {
            process_id = helper.remove_pending_process( 'stripe' );
        } else {
            helper.pause_processing = false;
        }
    }

    /**
     * Handle Stripe Checkout donations.
     *
     * @param Event
     */
    var stripe_checkout_handler = function( event ) {

        var helper;
        var submitted = false;

        var handler = StripeCheckout.configure({
                key: CHARITABLE_STRIPE_VARS.key,
                image: '',
                locale: 'auto',
                token: function(token) {
                    // Use the token to create the charge with a server-side script.
                    // You can access the token ID with `token.id`
                    submitted = true;
                    helper.get_input( 'stripe_token' ).val( token.id );

                    remove_pending_process( helper );
                },
                closed: function() {
                    if ( true !== submitted ) {
                        helper.hide_processing();
                    }
                }
            }
        );

        /**
         * Validate form submission.
         *
         * @param   Event
         * @param   Donation_Form
         */
        var validate = function( event, target ) {

            helper = target;

            // If we're not using Stripe, do not process any further
            if ( 'stripe' !== helper.get_payment_method() ) {
                return;
            }

            // If we have found no errors, create a token with Stripe
            if ( helper.errors.length === 0 ) {

                // Convert the amount to cents
                var amount = CHARITABLE_STRIPE_VARS.zero_decimal ? helper.get_amount() : helper.get_amount() * 100;

                // Pause processing
                add_pending_process( helper );

                // Open Checkout with further options
                handler.open({
                    name: CHARITABLE_STRIPE_VARS.site_name,
                    description: helper.get_description(),
                    email: helper.get_email(),
                    currency: CHARITABLE_STRIPE_VARS.currency,
                    amount: amount
                });

            }
        }

        $body.on( 'charitable:form:validate', validate );
    }

    /**
     * Handle Stripe JS donations.
     */
    var stripe_js_handler = function() {

        /**
         * Override the default is_valid_card_number() function and use Stripe's instead.
         */
        CHARITABLE.Donation_Form.prototype.is_valid_card_number = function() {
            return Stripe.card.validateCardNumber( this.get_cc_number().replace( / /g,'' ) );
        };

        /**
         * Process the Stripe response.
         */
        var process_response = function( response, helper ) {

            if ( response.error ) {
                helper.add_error( response.error.message );
            } else {
                helper.get_input( 'stripe_token' ).val( response.id );
                remove_pending_process( helper );
            }

        }

        /**
         * Validate form submission.
         */
        var validate = function( event, helper ) {

            var cc_number = helper.get_cc_number(),
                cc_cvc = helper.get_cc_cvc(),
                cc_expiry_month = helper.get_cc_expiry_month(),
                cc_expiry_year = helper.get_cc_expiry_year();

            // Remove credit card field names
            helper.clear_cc_fields();

            // If we're not using Stripe, do not process any further
            if ( 'stripe' !== helper.get_payment_method() ) {
                return;
            }

            event.preventDefault();

            if ( ! Stripe.card.validateExpiry( cc_expiry_month, cc_expiry_year ) ) {
                helper.add_error( CHARITABLE_VARS.error_invalid_cc_expiry );
            }

            // If we have found no errors, create a token with Stripe
            if ( helper.errors.length === 0 ) {

                add_pending_process( helper );

                Stripe.card.createToken({
                    number: cc_number,
                    cvc: cc_cvc,
                    exp_month: cc_expiry_month,
                    exp_year: cc_expiry_year
                }, function( status, response ) {
                    process_response( response, helper );
                });

            }
        }

        Stripe.setPublishableKey( CHARITABLE_STRIPE_VARS.key );

        $body.on( 'charitable:form:validate', validate );

    }

    /**
     * Initialize the Stripe handlers.
     *
     * The 'charitable:form:initialize' event is only triggered once.
     */
    $body.on( 'charitable:form:initialize', function( event ) {

        /* CHARITABLE_STRIPE_VARS.mode is set to either 'checkout' or 'js'.*/
        if ( 'checkout' === CHARITABLE_STRIPE_VARS.mode ) {
            stripe_checkout_handler( event );
        } else {
            stripe_js_handler( event );
        }
    });


})( jQuery );
