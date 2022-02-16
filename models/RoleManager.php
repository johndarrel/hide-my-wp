<?php
/**
 * RoleManager Model
 * Called to handle the RoleManager & Capabilities for the current user
 *
 * @file  The RoleManager Model file
 * @package HMWP/RoleManagerModel
 * @since 5.0.0
 */

class HMWP_Models_RoleManager
{

    public $roles;

    public function __construct()
    {
        add_action('admin_init', array( $this, 'addHMWPCaps' ), PHP_INT_MAX);
    }

    /**
     * Get all the  Caps
     *
     * @param $role
     *
     * @return array
     */
    public function getHMWPCaps( $role = '' )
    {
        $caps = array();

        $caps['hmwp_admin'] = array(
        'hmwp_manage_settings' => true,
        );

        $caps = array_filter($caps);

        if (isset($caps[ $role ]) ) {
            return $caps[ $role ];
        }

        return $caps;
    }

    /**
     * Register HMWP Caps
     * in case they don't exist
     */
    public function addHMWPCaps()
    {

        if (function_exists('wp_roles') ) {
            $allroles = wp_roles()->get_names();
            if (! empty($allroles) ) {
                $allroles = array_keys($allroles);
            }

            if (! empty($allroles) ) {
                foreach ( $allroles as $role ) {
                    if ($role == 'administrator' ) {
                        $this->addHMWPCap('hmwp_admin', $role);
                    }
                }
            }
        }
    }

    public function removeHMWPCaps()
    {
        if (function_exists('wp_roles') ) {
            $allroles = wp_roles()->get_names();
            $caps     = $this->getHMWPCaps('hmwp_admin');

            if (! empty($allroles) ) {
                $allroles = array_keys($allroles);
            }

            if (! empty($allroles) && ! empty($caps) ) {
                foreach ( $allroles as $role ) {
                    $this->removeCap($role, $caps);
                }
            }
        }

    }

    /**
     * Update the HMWP Caps into WP Roles
     *
     * @param $hmwprole
     * @param $wprole
     */
    public function addHMWPCap( $hmwprole, $wprole )
    {
        $hmwpcaps = $this->getHMWPCaps($hmwprole);

        $this->addCap($wprole, $hmwpcaps);
    }

    /**
     * Add a cap into WP for a role
     *
     * @param $name
     * @param $capabilities
     */
    public function addCap( $name, $capabilities )
    {
        $role = get_role($name);

        if (! $role || ! method_exists($role, 'add_cap') ) {
            return;
        }

        foreach ( $capabilities as $capability => $grant ) {
            if (! $role->has_cap($capability) ) {
                $role->add_cap($capability, $grant);
            }
        }
    }

    /**
     * Remove the caps for a role
     *
     * @param $name
     * @param $capabilities
     */
    public function removeCap( $name, $capabilities )
    {
        $role = get_role($name);

        if (! $role || ! method_exists($role, 'remove_cap') ) {
            return;
        }

        foreach ( $capabilities as $capability => $grant ) {
            if ($role->has_cap($capability) ) {
                $role->remove_cap($capability);
            }
        }
    }


}
