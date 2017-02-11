Stripe Credit Cards Extension for Magento One by District Commerce
==================================================================

## Installation instructions

Before installing the extension, make sure that Magento caching is disabled, and compilation is turned off. It is also
highly advised that the exntension is first installed in a test/staging environment, before deployment to production.

### Downloading the extension

Download the zip file from GitHub.

### Copying files to your Magento installation

Copy the conents of the zip file to your Magento installation directory taking care not to overwrite existing files.

### Enabling the extension

 1. Log into your Magento admin panel, and navigate to System > Configuration > Payment Methods.
 2. Expand the "Stripe Credit Cards" section and set the enabled option to "yes".
 3. Edit the title of the module to something appropriate for this method e.g. Credit or Debit Card
 4. Copy your Stripe API Secret and Publishable keys from your Stripe dashboard into the corresponding fields. Use the
 text credentials to check your integration before adding the live keys.
 5. Choose between "Authorize only" or "Authorize and Capture". Authorize only, only authorizes the card for the order
 total when the order is placed, and requires you to manaully invoice the ordere through Magento. Note: Stripe gives
 you a 7-day window to capture an order, after it has been successfully authorized, before it is released.
 6. Choose which credit cards you would like to accept. Note: Only U.S. businesses can currently accept Diners Club,
 Discover and JCB cards.
 7. Choose whether you want to allow credit cards to be saved. Note: Credit cards are not stored in your Magento
 database.
 8. If you already have jQuery enabled within another extension or theme, feel free to disabled them here. If you are
 unsure about this step, leave both options set to "yes", and you should be good to go.