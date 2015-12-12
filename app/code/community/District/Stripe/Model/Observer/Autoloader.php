<?php
/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   Copyright (c) 2015 District Commerce (http://districtcommerce.com)
 *
 */

class District_Stripe_Model_Observer_Autoloader extends Varien_Event_Observer
{
    /**
     * This an observer function for the event 'controller_front_init_before'.
     * It prepends our autoloader, so we can load the extra libraries.
     *
     * @param Varien_Event_Observer $event
     */
    public function controllerFrontInitBefore( $event )
    {
        spl_autoload_register( array($this, 'load'), true, true );
    }

    /**
     * Autoload the Stripe library
     *
     * @param string $class
     */
    public static function load()
    {
        require_once( Mage::getBaseDir('lib') . '/Stripe/' . 'init.php' );
    }
}
