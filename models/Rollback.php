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

    /**
     * Set the plugin's properties based on provided arguments.
     *
     * @param  array  $args  Associative array of properties to set.
     *
     * @return void
     */
    public function set_plugin( $args = array() ) {
        foreach ( $args as $key => $value ) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Print inline style.
     *
     * This method outputs inline styles for specific HTML elements.
     *
     * @return void
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
     * Initiates the plugin upgrade process by setting up the necessary arguments
     * and invoking the Plugin_Upgrader class to perform the upgrade.
     *
     * @return void
     */
    protected function upgrade()
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	    $logo_url = _HMWP_ASSETS_URL_ . 'img/logo.svg';

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
     * Executes the primary flow for applying a package and subsequently performing an upgrade.
     *
     * @return void
     */
    public function run()
    {
        $this->apply_package();
        $this->upgrade();
    }

    /**
     * Handles the installation process of a plugin by setting up necessary
     * includes and running the Plugin_Upgrader with the provided arguments.
     *
     * @param  mixed  $args  The arguments needed to set up the installation process.
     *
     * @return bool|WP_Error True if installation was successful, WP_Error on failure.
     */
    public function install( $args ) {

        // Includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
        include_once( ABSPATH . 'wp-admin/includes/file.php' );
        include_once( ABSPATH . 'wp-admin/includes/misc.php' );
        include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

        $this->set_plugin( $args )->apply_package();

        $upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

        return $upgrader->install( $this->package_url, array( 'overwrite_package' => true ) );

    }

    /**
     * Activates a specified plugin by updating the list of active plugins
     * and triggering activation hooks.
     *
     * @param  string  $plugin  The plugin to be activated.
     *
     * @return null
     */
    public function activate( $plugin ) {

        $plugin  = trim( $plugin );
        $current = get_option( 'active_plugins' );
        $plugin  = plugin_basename( $plugin );

        if ( $plugin <> '' && ! in_array( $plugin, $current ) ) {

            $current[] = $plugin;
            sort( $current );

            try {
                do_action( 'activate_plugin', $plugin, true );
                update_option( 'active_plugins', $current );
                do_action( 'activate_' . $plugin );
                do_action( 'activated_plugin', $plugin, true );
            } catch ( Exception $e ) {
            }
        }

        return null;
    }

}
