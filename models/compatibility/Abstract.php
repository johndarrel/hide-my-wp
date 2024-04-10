<?php
/**
 * Compatibility Class
 *
 * @file The Abstract Model file
 * @package HMWP/Compatibility/Abstract
 */

defined('ABSPATH') || die('Cheatin\' uh?');

abstract class HMWP_Models_Compatibility_Abstract
{

    public function __construct()
    {
	    if (is_admin() || is_network_admin()) {
			$this->hookAdmin();
	    }else{
		    $this->hookFrontend();
	    }

		if(HMWP_Classes_Tools::isAjax()){
			$this->hookAjax();
		}

	}

	/**
	 * Hook the ajax call
	 * @return void
	 */
	public function hookAjax(){}

	/**
	 * Hook the backend
	 * @return void
	 */
	public function hookAdmin(){}

	/**
	 * Hook the frontend
	 * @return void
	 */
	public function hookFrontend(){}

	/**
	 * Find Replace cache plguins
	 * Stop Buffer from loading
	 *
	 * @param  $content
	 * @return mixed
	 * @throws Exception
	 */
	public function findReplaceCache( $content)
	{
		//if called from cache plugins or hooks, stop the buffer replace
		add_filter('hmwp_process_buffer', '__return_false');

		return HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace($content);

	}


	/**
	 * Echo the changed HTML buffer
	 * @throws Exception
	 */
	public function findReplaceBuffer()
	{
		//Force to change the URL for xml content types
		$buffer = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace(ob_get_contents());

		ob_end_clean();
		echo $buffer;
	}

}
