<?php
/**
 * Overview Class
 * Called on plugin overview
 *
 * @file The Settings Overview
 * @package HMWP/Overview
 * @since 6.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Overview extends HMWP_Classes_FrontController
{

    /**
     * Called on Menu hook
     * Init the Settings page
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {


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
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('alert');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('settings');

        //Show connect for activation
        if (!HMWP_Classes_Tools::getOption('hmwp_token')) {
            $this->show('Connect');
            return;
        }

        wp_enqueue_script('postbox');
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');


        //Check compatibilities with other plugins
        HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->getAlerts();

        //Show errors on top
        HMWP_Classes_ObjController::getClass('HMWP_Classes_Error')->hookNotices();

        //Show connect for activation
        echo '<noscript><div class="alert-danger text-center py-3">'. sprintf(esc_html__("Javascript is disabled on your browser! You need to activate the javascript in order to use %s plugin.", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name')).'</div></noscript>';
        $this->show('Overview');
	    $this->show('blocks/Upgrade');

    }

    public function getFeatures()
    {
        $features = array(
            array(
                'title' => esc_html__("Secure WP Paths", 'hide-my-wp'),
                'description' => esc_html__("Customize & Secure all WordPress paths from hacker bots attacks.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => (HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default'),
                'optional' => false,
                'connection' => false,
                'logo' => 'dashicons-before dashicons-shield-alt',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-my-wp-ghost-tutorial/#safemode',
                'show' => true,
            ),
            //--
            array(
                'title' => esc_html__("Wordfence Security", 'hide-my-wp'),
                'description' => esc_html__("Compatible with Wordfence Security plugin. Use them together for Malware Scan, Firewall, Brute Force protection.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'dashicons-before dashicons-shield-alt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("All In One WP Security", 'hide-my-wp'),
                'description' => esc_html__("Compatible with All In One WP Security plugin. Use them together for Virus Scan, Firewall, Brute Force protection.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'dashicons-before dashicons-shield-alt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Sucuri Security", 'hide-my-wp'),
                'description' => esc_html__("Compatible with Sucuri Security plugin. Use them together for Virus Scan, Firewall, File Integrity Monitoring.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'dashicons-before dashicons-shield-alt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("iThemes Security", 'hide-my-wp'),
                'description' => esc_html__("Compatible with iThemes Security plugin. Use them together for Site Scanner, File Change Detection, Brute Force Protection.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'dashicons-before dashicons-shield-alt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            //--
            array(
                'title' => esc_html__("Autoptimizer", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Autoptimizer cache plugin. Works best with the the option Optimize/Aggregate CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-use-autoptimize-with-hide-my-wp-ghost/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Hummingbird", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Hummingbird cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("WP Super Cache", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with WP Super Cache cache plugin.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Cache Enabler", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Cache Enabler plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("WP Rocket", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with WP-Rocket cache plugin. Works best with the the option Minify/Combine CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => 'https://hidemywpghost.com/how-to-use-wp-rocket-with-hide-my-wp-ghost/',
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-use-wp-rocket-with-hide-my-wp-ghost/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("WP Fastest Cache", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with WP Fastest Cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("W3 Total Cache", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with W3 Total Cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("LiteSpeed Cache", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with LiteSpeed Cache plugin. Works best with the the option Minify CSS and JS files.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("JCH Optimize Cache", 'hide-my-wp'),
                'description' => esc_html__("Compatible with JCH Optimize Cache plugin. Works with all the options to optimize for CSS and JS.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-bolt',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            //--
            array(
                'title' => esc_html__("Elementor", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Elementor Website Builder plugin. Works best together with a cache plugin", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-pencil-square-o',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Oxygen", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Oxygen Builder plugin. Works best together with a cache plugin.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-pencil-square-o',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Beaver Builder", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Beaver Builder plugin. Works best together with a cache plugin.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-pencil-square-o',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("WPBakery Page Builder", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Beaver Builder plugin. Works best together with a cache plugin.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-pencil-square-o',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Fusion Builder", 'hide-my-wp'),
                'description' => esc_html__("Fully compatible with Fusion Builder plugin by Avada. Works best together with a cache plugin.", 'hide-my-wp'),
                'free' => true,
                'option' => false,
                'active' => true,
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-pencil-square-o',
                'link' => false,
                'details' => 'https://hidemywpghost.com/hide-my-wp-compatibility-plugins-list/',
                'show' => false,
            ),
            //--
            array(
                'title' => esc_html__("Hide WP Common Paths", 'hide-my-wp'),
                'description' => esc_html__("Hide the old /wp-content, /wp-include paths once they are changed with the new ones.", 'hide-my-wp'),
                'free' => false,
                'option' => 'hmwp_hide_oldpaths',
                'active' => HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths'),
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-file-word-o',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks#tab=core', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_common_paths',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Hide WP Common Files", 'hide-my-wp'),
                'description' => esc_html__("Hide wp-config.php, wp-config-sample.php, readme.html, license.txt, upgrade.php and install.php files..", 'hide-my-wp'),
                'free' => false,
                'option' => 'hmwp_hide_commonfiles',
                'active' => HMWP_Classes_Tools::getOption('hmwp_hide_commonfiles'),
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-file-word-o',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks#tab=core', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_common_files',
                'show' => true,
            ),
            array(
                'title' => esc_html__("XML-RPC Security", 'hide-my-wp'),
                'description' => esc_html__("Disable the external calls to xml-rpc.php file and prevent Brute Force attacks.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_disable_xmlrpc',
                'active' => HMWP_Classes_Tools::getOption('hmwp_disable_xmlrpc'),
                'optional' => false,
                'connection' => false,
                'logo' => 'fa fa-lock',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks#tab=api', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/activate-security-tweaks/#disable_xml_rpc_access',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Text Mapping", 'hide-my-wp'),
                'description' => esc_html__("Customize the IDs and Class names in your website body.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_mapping_text_show',
                'active' => HMWP_Classes_Tools::getOption('hmwp_mapping_text_show'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-eye-slash',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_mapping#tab=text', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#text_mapping',
                'show' => true,
            ),
            array(
                'title' => esc_html__("URL Mapping", 'hide-my-wp'),
                'description' => esc_html__("Customize the CSS and JS URLs in your website body.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_mapping_url_show',
                'active' => HMWP_Classes_Tools::getOption('hmwp_mapping_url_show'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-refresh',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_mapping#tab=url', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#url_mapping',
                'show' => true,
            ),
            array(
                'title' => esc_html__("CDN", 'hide-my-wp'),
                'description' => esc_html__("Integration with other CDN plugins and custom CDN URLs.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_mapping_cdn_show',
                'active' => HMWP_Classes_Tools::getOption('hmwp_mapping_cdn_show'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-link',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_mapping#tab=cdn', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/url-mapping-text-mapping/#cdn_urls',
                'show' => true,
            ),
            array(
                'title' => esc_html__("User Events Log", 'hide-my-wp'),
                'description' => esc_html__("Track and Log the website events and receive security alerts by email.", 'hide-my-wp'),
                'free' => false,
                'option' => 'hmwp_activity_log',
                'active' => HMWP_Classes_Tools::getOption('hmwp_activity_log'),
                'optional' => true,
                'connection' => true,
                'logo' => 'fa fa-calendar',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_log#tab=log', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/users-activity-log/',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Brute Force Protection", 'hide-my-wp'),
                'description' => esc_html__("Protects your website against brute force login attacks.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_bruteforce',
                'active' => HMWP_Classes_Tools::getOption('hmwp_bruteforce'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-ban',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_brute#tab=brute', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/',
                'show' => true,
            ),
            array(
                'title' => esc_html__("WooCommerce Safe Login", 'hide-my-wp'),
                'description' => esc_html__("Protects your WooCommerce shop against brute force login attacks.", 'hide-my-wp'),
                'free' => false,
                'option' => 'hmwp_bruteforce_woocommerce',
                'active' => HMWP_Classes_Tools::getOption('hmwp_bruteforce_woocommerce'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-ban',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_brute#tab=brute', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/',
                'show' => false,
            ),
            array(
                'title' => esc_html__("Login & Logout Redirects", 'hide-my-wp'),
                'description' => esc_html__("Set Login & Logout Redirects based on User Roles.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_do_redirects',
                'active' => HMWP_Classes_Tools::getOption('hmwp_do_redirects'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-code-fork',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=redirects', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/activate-security-tweaks/#redirect_on_login',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Header Security", 'hide-my-wp'),
                'description' => esc_html__("Add Headers Security against XSS and Code Injection Attacks.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_security_header',
                'active' => HMWP_Classes_Tools::getOption('hmwp_security_header'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-code',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks#tab=firewall', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_security_headers',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Firewall", 'hide-my-wp'),
                'description' => esc_html__("Activate the firewall and prevent many types of SQL Injection and URL hacks.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_sqlinjection',
                'active' => HMWP_Classes_Tools::getOption('hmwp_sqlinjection'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-bug',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks#tab=firewall', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#firewall_script_injection',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Feed Security", 'hide-my-wp'),
                'description' => esc_html__("Change paths in RSS feed for all images.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_hide_in_feed',
                'active' => HMWP_Classes_Tools::getOption('hmwp_hide_in_feed'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-sitemap',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=sitemap', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/activate-security-tweaks/#fix_sitemap_xml',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Sitemap Security", 'hide-my-wp'),
                'description' => esc_html__("Change paths in Sitemap XML files and remove the plugin author and styles.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_hide_in_sitemap',
                'active' => HMWP_Classes_Tools::getOption('hmwp_hide_in_sitemap'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-sitemap',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=sitemap', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/activate-security-tweaks/#fix_sitemap_xml',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Robots Security", 'hide-my-wp'),
                'description' => esc_html__("Hide WordPress paths such as wp-admin, wp-content, and more from robots.txt file.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_robots',
                'active' => HMWP_Classes_Tools::getOption('hmwp_robots'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-android',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=changes', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/activate-security-tweaks/#fix_robots_txt',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Admin Toolbar", 'hide-my-wp'),
                'description' => esc_html__("Hide Admin Toolbar for users roles to prevent dashboard access.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_hide_admin_toolbar',
                'active' => HMWP_Classes_Tools::getOption('hmwp_hide_admin_toolbar'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-window-maximize',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=hide', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/activate-security-tweaks/#admin_toolbar',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Disable Right-Click", 'hide-my-wp'),
                'description' => esc_html__("Disable the right-click action on your website.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_disable_click',
                'active' => HMWP_Classes_Tools::getOption('hmwp_disable_click'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-mouse-pointer',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=disable', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/knowledge-base/',
                'show' => true,
            ),
            array(
                'title' => esc_html__("Disable Copy/Paste", 'hide-my-wp'),
                'description' => esc_html__("Disable the copy/paste action on your website.", 'hide-my-wp'),
                'free' => true,
                'option' => 'hmwp_disable_copy_paste',
                'active' => HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste'),
                'optional' => true,
                'connection' => false,
                'logo' => 'fa fa-keyboard-o',
                'link' => HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=disable', true),
                'details' => HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/knowledge-base/',
                'show' => true,
            ),


        );

        //for PHP 7.3.1 version
        $features = array_filter($features);

        foreach ($features as &$feature){
            switch ($feature['title']){
            case 'Firewall':
                if(HMWP_Classes_Tools::isNginx()) {
                    $feature['show'] = false;
                }
                break;
            case 'URL Mapping':
                if(HMWP_Classes_Tools::isWpengine()) {
                    $feature['show'] = false;
                }
                break;
            case 'Wordfence Security':
                if(HMWP_Classes_Tools::isPluginActive('wordfence/wordfence.php') ) {
                    $feature['show'] = true;
                }
                break;
            case 'All In One WP Security':
                if(HMWP_Classes_Tools::isPluginActive('all-in-one-wp-security-and-firewall/wp-security.php') ) {
                    $feature['show'] = true;
                }
                break;
            case 'Sucuri Security':
                if(HMWP_Classes_Tools::isPluginActive('sucuri-scanner/sucuri.php') ) {
                    $feature['show'] = true;
                }
                break;
            case 'iThemes Security':
                if(HMWP_Classes_Tools::isPluginActive('better-wp-security/better-wp-security.php') ) {
                    $feature['show'] = true;
                }
                break;
            case 'Autoptimizer':
                if(HMWP_Classes_Tools::isPluginActive('autoptimize/autoptimize.php') ) {
                    $feature['show'] = true;
                }
                break;
            case 'Hummingbird':
                if(HMWP_Classes_Tools::isPluginActive('hummingbird-performance/wp-hummingbird.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'WP Super Cache':
                if(HMWP_Classes_Tools::isPluginActive('wp-super-cache/wp-cache.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'Cache Enabler':
                if(HMWP_Classes_Tools::isPluginActive('cache-enabler/cache-enabler.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'WP Rocket':
                if(HMWP_Classes_Tools::isPluginActive('wp-rocket/wp-rocket.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'WP Fastest Cache':
                if(HMWP_Classes_Tools::isPluginActive('wp-fastest-cache/wpFastestCache.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'W3 Total Cache':
                if(HMWP_Classes_Tools::isPluginActive('w3-total-cache/w3-total-cache.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'LiteSpeed Cache':
                if(HMWP_Classes_Tools::isPluginActive('litespeed-cache/litespeed-cache.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'Elementor':
                if(HMWP_Classes_Tools::isPluginActive('elementor/elementor.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'Oxygen':
                if(HMWP_Classes_Tools::isPluginActive('oxygen/functions.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'Beaver Builder':
                if(HMWP_Classes_Tools::isPluginActive('beaver-builder-lite-version/fl-builder.php') 
                    || HMWP_Classes_Tools::isPluginActive('beaver-builder/fl-builder.php')
                ) {
                    $feature['show'] = true;
                }
                break;
            case 'Fusion Builder':
                if(HMWP_Classes_Tools::isPluginActive('fusion-builder/fusion-builder.php')) {
                    $feature['show'] = true;
                }
                break;
            case 'WooCommerce Safe Login':
                if (HMWP_Classes_Tools::isPluginActive('woocommerce/woocommerce.php') ) {
                    $feature['show'] = true;
                }
                break;

            }
        }

        return apply_filters('hmwp_features', $features);
    }

    /**
     * Called when an action is triggered
     *
     * @throws Exception
     */
    public function action()
    {
        parent::action();

        if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings')) {
            return;
        }

        //Save the settings
        if (HMWP_Classes_Tools::getValue('action') == 'hmwp_feature_save') {
            if (!empty($_POST)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Settings')->saveValues($_POST);
            }

            wp_send_json_success(esc_html__('Saved'));

        }
    }
}
