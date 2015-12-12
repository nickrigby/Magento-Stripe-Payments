/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   Copyright (c) 2015 District Commerce (http://districtcommerce.com)
 *
 */

var district = district || {};

district.stripeCc = (function($) {

    var self = {},
        $errorMsg = $('#stripe-error-messages'),
        $inputs = {},
        mageValidateParent,
        address = {};

    /*
   * Initialize the form
   *
   */
    self.init = function() {

        //Shortcut to fields
        $inputs.cardNumber = $('#stripe-cc-number');
        $inputs.cardExpiry = $('#stripe-cc-exp');
        $inputs.cardCVC = $('#stripe-cc-cvc');
        $inputs.cardToken = $('#stripe-token');
        $inputs.savedCard = $('#stripe-saved-card');

        //Set input mask for each field
        $inputs.cardNumber.payment('formatCardNumber');
        $inputs.cardExpiry.payment('formatCardExpiry');
        $inputs.cardCVC.payment('formatCardCVC');

        //Toggles error class based on validation result
        $.fn.toggleInputError = function(error) {
            this.parent().toggleClass('has-error', error);
            return this;
        };

        //Toggle new card form
        $('#stripe-saved-card').change(function() {
            if($(this).val() == '0') {
                $('#stripe-new-card').show();
            } else {
                $('#stripe-new-card').hide();
            }
        });

        //If frontend payment
        if(typeof Payment !== 'undefined') {

            //Get billing address
            self.getBillingAddressFrontend();

            //Wrap the payment save method
            Payment.prototype.save = Payment.prototype.save.wrap(self.validateForm);

        } else if(typeof AdminOrder !== 'undefined') { //Admin payment

            //Wrap submit method
            AdminOrder.prototype.submit = AdminOrder.prototype.submit.wrap(self.validateForm);

            //Wrap get payment data method
            AdminOrder.prototype.getPaymentData = AdminOrder.prototype.getPaymentData.wrap(self.paymentDataChange);

        }

    };

    /*
   * Runs when updating payment form in admin
   *
   */
    self.paymentDataChange = function(getPaymentData) {

        self.getBillingAddressAdmin();

        getPaymentData();

    };

    /*
   * Get billing address in frontend
   *
   */
    self.getBillingAddressFrontend = function() {

        //Get billing address select element
        var $billingAddress = $('#billing-address-select');

        //If the element exists and the value is not empty
        if($billingAddress.length && $billingAddress.val() != '') {
            $.ajax({
                url: billing.addressUrl + $billingAddress.val()
            }).done(function(data) {
                address.line1 = data.street1;
                address.zip = data.postcode;
                address.country = data.country_id;
                address.name = data.firstname + ' ' + data.lastname
            });
        } else {
            address.line1 = $('#billing\\:street1').val();
            address.zip = $('#billing\\:postcode').val();
            address.country = $('#billing\\:country_id').val();
            address.name = $('billing\\:firstname').val() + ' ' + $('billing\\:lastname').val();
        }

    };

    /*
   * Get billing address in admin
   *
   */
    self.getBillingAddressAdmin = function() {

        address.line1 = $('#order-billing_address_street0').val();
        address.zip = $('#order-billing_address_postcode').val();
        address.country = $('#order-billing_address_country_id').val();
        address.name = $('#order-billing_address_firstname').val() + ' ' + $('#order-billing_address_lastname').val();

    };

    /*
   * Validate the form
   *
   */
    self.validateForm = function(validateParent) {

        //Save ref to magento parent function (we need it in stripe callback)
        mageValidateParent = validateParent;

        if($inputs.savedCard.length && $inputs.savedCard.val() != '0') { //Existing card to be used

            $inputs.cardToken.val($inputs.savedCard.val());
            mageValidateParent();

        } else { //New card to be used

            //Check that credit card details are valid
            var validCardNumber = $.payment.validateCardNumber($inputs.cardNumber.val());
            var validCardExpiry = $.payment.validateCardExpiry($.payment.cardExpiryVal($inputs.cardExpiry.val()));
            var validCardCVC = $.payment.validateCardCVC($inputs.cardCVC.val(), $.payment.cardType($inputs.cardNumber.val()));

            //Toggle error class for invalid fields
            $inputs.cardNumber.toggleInputError(!validCardNumber);
            $inputs.cardExpiry.toggleInputError(!validCardExpiry);
            $inputs.cardCVC.toggleInputError(!validCardCVC);

            //If valid, create the token, else return
            if(validCardNumber && validCardExpiry && validCardCVC) {
                self.createToken();
            } else {
                return false;
            }

        }

    };

    /*
   * Creates stripe token
   *
   */
    self.createToken = function() {

        Stripe.card.createToken({
            number: $inputs.cardNumber.val(),
            exp: $inputs.cardExpiry.val(),
            cvc: $inputs.cardCVC.val(),
            address_country: address.country,
            address_line1: address.line1,
            address_zip: address.zip,
            name: address.name
        }, self.stripeResponseHandler);

    };

    /*
   * Handle response from stripe
   *
   */
    self.stripeResponseHandler = function(status, response) {

        if(response.error) {
            $errorMsg.html(response.error.message);
        } else {
            $inputs.cardToken.val(response.id);
            mageValidateParent();
        }

    };

    return self;

}(district.$));
