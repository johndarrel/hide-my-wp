<?php
/**
 * Recommended Plugins
 * Loaded in the Plugins Menu
 *
 * @file The Recommended Plugins file
 * @package HMWP/Plugins
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Plugins extends HMWP_Classes_FrontController
{

    public $plugins;

	/**
	 * Initializes the plugin by setting up plugin paths, loading necessary CSS files, and displaying the plugins page.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init()
    {
        //Add the Plugin Paths in variable
        $this->plugins = $this->getPlugins();

        //Load the css for Settings
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('popper');

        if (is_rtl() ) {
            HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('bootstrap.rtl');
            HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('rtl');
        } else {
            HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('bootstrap');
        }

        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('font-awesome');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('switchery');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('settings');

        $this->show('Plugins');
    }

	/**
	 * Retrieves an array of available plugins with their details.
	 *
	 * @return array An associative array where the keys are plugin identifiers and the values
	 *               are arrays containing plugin details such as title, banner, description,
	 *               path, and URL.
	 */
    public function getPlugins()
    {
        return array(
	        'advanced-pack' => array(
		        'title' => "WP Ghost - Advanced Pack",
		        'banner' => '//ps.w.org/hide-my-wp/assets/banner-1544x500.png',
		        'description' => "The advanced security features for WP Ghost plugin with Two-Factor Authentication (2FA), Temporary Login and more." . '<div class="text-success my-2">' . 'Security Plugin' . '</div>',
		        'path' => 'hide-my-wp-pack/index.php',
		        'url' => 'https://hidemywpghost.com/plugin/hide-my-wp-pack'
	        ),
	        'starbox' => array(
		        'title' => "Starbox",
		        'banner' => '//ps.w.org/starbox/assets/banner-1544x500.png',
		        'description' => "Starbox - the Author Box for Humans” has been translated into 9 locales." . '<div class="text-success my-2">' . 'Author Box' . '</div>',
		        'path' => 'starbox/starbox.php',
		        'url' => 'https://wordpress.org/plugins/starbox/'
	        ),
            'squirrly-seo' => array(
                'title' => "SEO SQUIRRLY",
                'banner' => '//ps.w.org/squirrly-seo/assets/banner-1544x500.png',
                'description' => "A.I.-based Private SEO Consultant. In a Plugin. Powered by Machine Learning and Cloud Services. Over 300 functionalities for SEO now available when you need them." . '<div class="text-success my-2">' . 'SEO Plugin' . '</div>',
                'path' => 'squirrly-seo/squirrly.php',
                'url' => 'https://hidemywpghost.com/plugin/squirrly-seo'
            ),
            'litespeed-cache' => array(
                'title' => "LiteSpeed Cache",
                'banner' => '//ps.w.org/litespeed-cache/assets/banner-1544x500.png',
                'description' => "LiteSpeed Cache for WordPress (LSCWP) is an all-in-one site acceleration plugin, featuring an exclusive server-level cache and a collection of optimization features." . '<div class="text-success my-2">' . 'Cache Plugin' . '</div>',
                'path' => 'litespeed-cache/litespeed-cache.php',
                'url' => 'https://wordpress.org/plugins/litespeed-cache'
            ),
            'ninjaforms' => array(
                'title' => "Ninja Forms",
                'banner' => '//ps.w.org/ninja-forms/assets/banner-1544x500.png',
                'description' => "Use Ninja Forms to create beautiful, user-friendly WordPress forms that will make you feel like a professional web developer" . '<div class="text-success my-2">' . 'Form Plugin' . '</div>',
                'path' => 'minify-html-markup/minify-html.php',
                'url' => 'https://hidemywpghost.com/plugin/ninja-forms'
            ),
            'better-wp-security' => array(
                'title' => "Solid Security",
                'banner' => '//ps.w.org/better-wp-security/assets/banner-1544x500.png',
                'description' => "Solid Security gives you over 30+ ways to secure and protect your WP site. WP sites can be an easy target for attacks because of plugin vulnerabilities, weak passwords and obsolete software." . '<div class="text-success my-2">' . 'Security Plugin' . '</div>',
                'path' => 'better-wp-security/better-wp-security.php',
                'url' => 'https://hidemywpghost.com/plugin/ithemes'
            ),
            'sucuri-scanner' => array(
                'title' => "Sucuri Security",
                'banner' => '//ps.w.org/sucuri-scanner/assets/banner-772x250.png',
                'description' => "The Sucuri WordPress Security plugin is a security toolset for security integrity monitoring, malware detection and security hardening." . '<div class="text-success my-2">' . 'Security Plugin' . '</div>',
                'path' => 'sucuri-scanner/sucuri.php',
                'url' => 'https://wordpress.org/plugins/sucuri-scanner/'
            ),
            'backupwordpress' => array(
                'title' => "Back Up WordPress",
                'banner' => '//ps.w.org/backupwordpress/assets/banner-772x250.jpg',
                'description' => "Simple automated backups of your WordPress-powered website. Back Up WordPress will back up your entire site including your database and all your files on a schedule that suits you." . '<div class="text-success my-2">' . 'Backup Plugin' . '</div>',
                'path' => 'backupwordpress/backupwordpress.php',
                'url' => 'https://wordpress.org/plugins/backupwordpress/'
            ),
            'elementor' => array(
                'title' => "Elementor Builder",
                'banner' => '//ps.w.org/elementor/assets/banner-772x250.png',
                'description' => "The most advanced frontend drag & drop page builder. Create high-end, pixel perfect websites at record speeds. Any theme, any page, any design." . '<div class="text-success my-2">' . 'Page Builder' . '</div>',
                'path' => 'elementor/elementor.php',
                'url' => 'https://hidemywpghost.com/plugin/elementor'
            ),
            'polylang' => array(
                'title' => "Polylang Multilingual",
                'banner' => '//ps.w.org/polylang/assets/banner-1544x500.png',
                'description' => "Polylang allows you to create a bilingual or multilingual WordPress site." . '<div class="text-success my-2">' . 'Multilingual' . '</div>',
                'path' => 'polylang/polylang.php',
                'url' => 'https://wordpress.org/plugins/polylang/'
            ),
            'facebook-pixel' => array(
                'title' => "Facebook Pixel",
                'banner' => '//ps.w.org/pixelyoursite/assets/banner-1544x500.jpg',
                'description' => "Manage your Facebook Pixel or Google Analytics code with a single plugin and add ANY other script (Head & Footer feature). The Pinterest Tag can be implemented via free add-on." . '<div class="text-success my-2">' . 'Tracking Plugin' . '</div>',
                'path' => 'pixelyoursite/pixelyoursite.php',
                'url' => 'https://wpplugins.tips/plugin/facebook-pixel'
            ),
            'maintenance' => array(
                'title' => "Maintenance",
                'banner' => '//ps.w.org/maintenance/assets/banner-772x250.png',
                'description' => "Maintenance plugin allows the WordPress site administrator to close the website for maintenance, set a temporary page with authorization, which can be edited via the plugin settings." . '<div class="text-success my-2">' . 'Tracking Plugin' . '</div>',
                'path' => 'add-to-any/add-to-any.php',
                'url' => 'https://wordpress.org/plugins/maintenance/'
            ),
        );
    }

}
