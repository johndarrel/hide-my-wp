<?php
/**
 * Compatibility Class
 *
 * @file The Hummingbird Model file
 * @package HMWP/Compatibility/Hummingbird
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Hummingbird extends HMWP_Models_Compatibility_Abstract
{


    public function hookFrontend() {
        add_filter('wphb_cache_content', array($this, 'findReplaceCache'), PHP_INT_MAX);
        add_filter('template_redirect', array($this, 'removeHummingbirdComment'));
    }

    /**
     * Remove Hummingbird Comment
     */
    public function removeHummingbirdComment()
    {
        global $wphb_cache_config;
        if (isset($wphb_cache_config->cache_identifier) && $wphb_cache_config->cache_identifier ) {
            $wphb_cache_config->cache_identifier = false;
        }
    }


}
