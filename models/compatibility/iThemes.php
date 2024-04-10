<?php
/**
 * Compatibility Class
 *
 * @file The iThemes Model file
 * @package HMWP/Compatibility/iThemes
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_iThemes extends HMWP_Models_Compatibility_Abstract
{

	public function __construct() {
		parent::__construct();

		$settings = get_option('itsec-storage');
		if (isset($settings['hide-backend']['enabled']) && $settings['hide-backend']['enabled'] ) {
			if (isset($settings['hide-backend']['slug']) && $settings['hide-backend']['slug'] <> '' ) {
				defined('HMWP_DEFAULT_LOGIN') || define('HMWP_DEFAULT_LOGIN', $settings['hide-backend']['slug']);
				HMWP_Classes_Tools::$options['hmwp_login_url'] = HMWP_Classes_Tools::$default['hmwp_login_url'];
			}
		}

	}


}
