<?php
/**
 * Rollback Model
 * Called to handle the Rollback of the plugin version
 *
 * @file  The Rollback Model file
 * @package HMWP/RollbackModel
 * @since 6.0.0
 */

class HMWP_Models_Rollback
{

    /**
     * @var string Package URL.
     */
    protected $package_url;

    /**
     * @var string Package URL.
     */
    protected $version;

    /**
     * @var string Plugin name.
     */
    protected $plugin_name;

    /**
     * @var string Plugin slug.
     */
    protected $plugin_slug;

    public function set_plugin($args = array())
    {
        foreach ( $args as $key => $value ) {
            $this->{$key} = $value;
        }
    }

    /**
     * Print inline style.
     *
     * @access private
     */
    private function print_inline_style()
    {
        ?>
        <style>

            h1 {
                background: #0a9b8f;
                text-align: center;
                color: #fff !important;
                padding: 50px !important;
                text-transform: uppercase;
                letter-spacing: 1px;
                line-height: 30px;
            }

            h1 img {
                max-width: 300px;
                display: block;
                margin: auto auto 50px;
            }
        </style>
        <?php
    }

    /**
     * Apply package.
     *
     * Change the plugin data when WordPress checks for updates. This method
     * modifies package data to update the plugin from a specific URL containing
     * the version package.
     */
    protected function apply_package()
    {
        $update_plugins = get_site_transient('update_plugins');
        if (! is_object($update_plugins) ) {
            $update_plugins = new \stdClass();
        }

        $plugin_info = new \stdClass();
        $plugin_info->new_version = $this->version;
        $plugin_info->slug = $this->plugin_slug;
        $plugin_info->package = $this->package_url;
        $plugin_info->url = _HMWP_ACCOUNT_SITE_;

        $update_plugins->response[ $this->plugin_name ] = $plugin_info;

        set_site_transient('update_plugins', $update_plugins);
    }

    /**
     * Upgrade.
     *
     * @access protected
     */
    protected function upgrade()
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	    $logo_url = _HMWP_ASSETS_URL_ . 'img/logo.png';

	    $upgrader_args = [
		    'url' => 'update.php?action=upgrade-plugin&plugin=' . rawurlencode($this->plugin_name),
		    'plugin' => $this->plugin_name,
		    'nonce' => 'upgrade-plugin_' . $this->plugin_name,
		    'title' => '<img src="' . $logo_url . '" alt="">' . esc_html__("Plugin Install Process", 'squirrly-seo'),
	    ];

	    $this->print_inline_style();
	    $upgrader = new \Plugin_Upgrader(new \Plugin_Upgrader_Skin($upgrader_args));
	    $upgrader->upgrade($this->plugin_name);
    }

    /**
     * Run.
     *
     * Rollback to previous versions.
     */
    public function run()
    {
        $this->apply_package();
        $this->upgrade();
    }

}
