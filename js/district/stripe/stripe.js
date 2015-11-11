var district = district || {};

district.stripeCc = (function($) {
  
  var self = {},
      $errorMsg = $('#stripe-error-messages'),
      $inputs = {},
      mageSave,
      address = {};
  
  /*
   * Initialize the form
   *
   */
  self.init = function() {
    
    //Get billing address
    self.getBillingAddress();
    
    //Shortcut to fields
    $inputs.cardNumber = $('#stripe-card-number');
    $inputs.cardExpiry = $('#stripe-cc-exp');
    $inputs.cardCVC = $('#stripe-cc-cvc');
    $inputs.cardToken = $('#stripe-token');

    //Set input mask for each field
    $inputs.cardNumber.payment('formatCardNumber');
    $inputs.cardExpiry.payment('formatCardExpiry');
    $inputs.cardCVC.payment('formatCardCVC');

    //Toggles error class based on validation result
    $.fn.toggleInputError = function(error) {
      this.parent().toggleClass('has-error', error);
      return this;
    };
    
    //Wrap magento save method
    Payment.prototype.save = Payment.prototype.save.wrap(self.validateForm);
    
  };
  
  /*
   * Get billing address
   *
   */
  self.getBillingAddress = function() {
    
    //Get billing address select element
    var $billingAddress = $('#billing-address-select');
    
    //If the element exists and the value is not empty
    if($billingAddress.length && $billingAddress.val != '') {
      $.ajax({
        url: billing.addressUrl + $billingAddress.val()
      }).done(function(data) {
        address.line1 = data.street1;
        address.zip = data.postcode;
        address.country = data.country_id;
      });
    } else {
      address.line1 = $('#billing\\:street1').val();
      address.zip = $('#billing\\:postcode').val();
      address.country = $('#billing\\:country_id').val();
    }
    
  };
    
  /*
   * Validate the form
   *
   */
  self.validateForm = function(save) {

    //Save ref to magento save function (we need it in stripe callback)
    mageSave = save;

    //Check that credit card details are valid
    var validCardNumber = $.payment.validateCardNumber($inputs.cardNumber.val());
    var validCardExpiry = $.payment.validateCardExpiry($.payment.cardExpiryVal($inputs.cardExpiry.val()));
    var validCardCVC = $.payment.validateCardCVC($inputs.cardCVC.val(), $.payment.cardType($inputs.cardNumber.val()));

    //Toggle error class for invalud fields
    $inputs.cardNumber.toggleInputError(!validCardNumber);
    $inputs.cardExpiry.toggleInputError(!validCardExpiry);
    $inputs.cardCVC.toggleInputError(!validCardCVC);

    //If valid, call original save method, else return
    if(validCardNumber && validCardExpiry && validCardCVC) {
      self.createToken();
    } else {
      return false;
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
      name: ''
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
      mageSave();
    }
    
  };
  
  return self;
  
}(district.$));

district.stripeCc.init();