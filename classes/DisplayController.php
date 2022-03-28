<?php
/**
 * The class handles the theme part in WP
 *
 * @package HMWP/Display
 * @file The Display View file
 *
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Classes_DisplayController
{

    private static $cache;

    /**
     * echo the css link from theme css directory
     *
     * @param string $uri        The name of the css file or the entire uri path of the css file
     * @param array  $dependency
     *
     * @return void
     */
    public static function loadMedia($uri = '', $dependency = null)
    {
        $css_uri = '';
        $js_uri = '';

        if (HMWP_Classes_Tools::isAjax()) {
            return;
        }

        if (isset(self::$cache[$uri])) {
            return;
        }

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        self::$cache[$uri] = true;

	    /* if is a custom css file */
	    $name = strtolower($uri);
	    $id = strtolower(_HMWP_NAMESPACE_) . '_' . $name;

	    if ($wp_filesystem->exists(_HMWP_ASSETS_DIR_ . 'css/' . $name .'.min.css')) {
		    $css_uri = _HMWP_ASSETS_URL_ . 'css/' . $name . '.min.css?ver=' . HMWP_VERSION_ID;
	    }
	    if ($wp_filesystem->exists(_HMWP_ASSETS_DIR_ . 'css/' . $name . '.min.scss')) {
		    $css_uri = _HMWP_ASSETS_URL_ . 'css/' . $name . '.min.scss?ver=' . HMWP_VERSION_ID;
	    }
	    if ($wp_filesystem->exists(_HMWP_ASSETS_DIR_ . 'js/' . $name . '.min.js')) {
		    $js_uri = _HMWP_ASSETS_URL_ . 'js/' . $name . '.min.js?ver=' . HMWP_VERSION_ID;
	    }

	    if ($css_uri <> '') {
		    if (!wp_style_is($id)) {
			    if (did_action('wp_print_styles')) {
				    echo "<link rel='stylesheet' id='$id-css'  href='$css_uri' type='text/css' media='all' />";
			    } elseif (is_admin() || is_network_admin()) { //load CSS for admin or on triggered
				    wp_enqueue_style($id, $css_uri, $dependency, HMWP_VERSION_ID);
				    wp_print_styles(array($id));
			    }
		    }
	    }

	    if ($js_uri <> '') {
		    if (!wp_script_is($id)) {
			    if (did_action('wp_print_scripts')) {
				    echo "<script type='text/javascript' src='$js_uri'></script>";
			    } elseif (is_admin() || is_network_admin()) { //load CSS for admin or on triggered
				    wp_enqueue_script($id, $js_uri, $dependency, HMWP_VERSION_ID, true);
				    wp_print_scripts(array($id));
			    }
		    }
	    }
    }

    /**
     * return the block content from theme directory
     *
     * @param  string $block
     * @param  HMWP_Classes_FrontController $view Used in the included file
     * @return null|string
     */
    public function getView($block, $view)
    {
        $output = null;

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        //Set the current view file from /view
        $file = _HMWP_THEME_DIR_ . $block . '.php';

        if ($wp_filesystem->exists($file)) {
            ob_start();
            include $file;
            $output .= ob_get_clean();
        }

        return $output;
    }

}
