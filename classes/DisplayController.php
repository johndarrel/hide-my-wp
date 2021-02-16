<?php
defined('ABSPATH') || die('Cheatin\' uh?');

/**
 * The class handles the theme part in WP
 */
class HMW_Classes_DisplayController {

    private static $cache;

    /**
     * echo the css link from theme css directory
     *
     * @param string $uri The name of the css file or the entire uri path of the css file
     * @param string $media
     *
     * @return void
     */
    public static function loadMedia($uri = '', $media = 'all') {
        $css_uri = '';
        $js_uri = '';

        if (HMW_Classes_Tools::isAjax()) {
            return;
        }

        if (isset(self::$cache[$uri]))
            return;

        self::$cache[$uri] = true;

        /* if is a custom css file */
        if (strpos($uri, '//') === false) {
            $name = strtolower($uri);
            if (file_exists(_HMW_THEME_DIR_ . 'css/' . $name . (HMW_DEBUG ? '' : '.min') . '.css')) {
                $css_uri = _HMW_THEME_URL_ . 'css/' . $name . (HMW_DEBUG ? '' : '.min') . '.css?ver=' . HMW_VERSION_ID;
            }
            if (file_exists(_HMW_THEME_DIR_ . 'css/' . $name . (HMW_DEBUG ? '' : '.min') . '.scss')) {
                $css_uri = _HMW_THEME_URL_ . 'css/' . $name . (HMW_DEBUG ? '' : '.min') . '.scss?ver=' . HMW_VERSION_ID;
            }
            if (file_exists(_HMW_THEME_DIR_ . 'js/' . $name . (HMW_DEBUG ? '' : '.min') . '.js')) {
                $js_uri = _HMW_THEME_URL_ . 'js/' . $name . (HMW_DEBUG ? '' : '.min') . '.js?ver=' . HMW_VERSION_ID;
            }
        } else {
            $name = strtolower(basename($uri));
            if (strpos($uri, '.css') !== FALSE) {
                $css_uri = $uri;
            } elseif (strpos($uri, '.scss') !== FALSE) {
                $css_uri = $uri;
            } elseif (strpos($uri, '.js') !== FALSE) {
                $js_uri = $uri;
            }
        }

        if ($css_uri <> '') {
            if (!wp_style_is($name)) {
                if (did_action('wp_print_styles')) {
                    echo "<link rel='stylesheet' id='$name-css'  href='$css_uri' type='text/css' media='all' />";
                } elseif (is_admin()) { //load CSS for admin or on triggered
                    wp_enqueue_style($name, $css_uri, null, HMW_VERSION_ID, $media);
                    wp_print_styles(array($name));
                }
            }
        }

        if ($js_uri <> '') {
            if (!wp_script_is($name)) {
                if (did_action('wp_print_scripts')) {
                    echo "<script type='text/javascript' src='$js_uri'></script>";
                } elseif (is_admin()) {
                    wp_enqueue_script($name, $js_uri, null, HMW_VERSION_ID, true);
                    wp_print_scripts(array($name));
                }
            }
        }
    }

    /**
     *
     * return the block content from theme directory
     *
     * @param $block
     * @param HMW_Classes_FrontController $view
     * @return null|string
     */
    public function getView($block, $view) {
        $output = null;

        if (file_exists(_HMW_THEME_DIR_ . $block . '.php')) {
            ob_start();
            include(_HMW_THEME_DIR_ . $block . '.php');
            $output .= ob_get_clean();
        }

        return $output;
    }

}