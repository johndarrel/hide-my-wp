<?php
/**
 * RoleManager Model
 * Called to handle the RoleManager & Capabilities for the current user
 *
 * @file  The RoleManager Model file
 * @package HMWP/RoleManagerModel
 * @since 5.0.0
 */

class HMWP_Models_RoleManager {

	public $roles;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'addHMWPCaps' ), PHP_INT_MAX );
		add_filter( 'user_has_cap', array( $this, 'setUserHasCap' ), PHP_INT_MAX, 4);
	}

	/**
	 * Make sure the user has the capability if set in any role
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 * @param $user
	 *
	 * @return mixed
	 */
	public function setUserHasCap( $allcaps, $caps, $args, $user ) {

		if ( ! in_array( HMWP_CAPABILITY, $caps ) ) {
			return $allcaps;
		}

		if( isset($allcaps[HMWP_CAPABILITY]) && $allcaps[HMWP_CAPABILITY] ) {
			return $allcaps;
		}elseif( ! isset($allcaps[HMWP_CAPABILITY]) && user_can( $user->ID, 'manage_options' ) ){
			$allcaps[HMWP_CAPABILITY] = 1;
		}

		//If the user has multiple roles
		if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
			foreach ( $user->roles as $role ) {

				/** @var WP_Role $allroles */
				$role_object = get_role( $role );

				foreach ( (array) $caps as $cap ) {
					if ( $role_object->has_cap( $cap ) ) {
						$allcaps[$cap] = 1;
					}
				}

			}
		}

		return $allcaps;
	}

	/**
	 * Register HMWP Caps
	 * in case they don't exist
	 */
	public function addHMWPCaps() {

		if ( function_exists( 'wp_roles' ) ) {

			/** @var WP_Role[] $allroles */
			$allroles = wp_roles()->get_names();

			if ( ! empty( $allroles ) ) {
				$allroles = array_keys( $allroles );
			}

			if ( ! empty( $allroles ) ) {
				foreach ( $allroles as $role ) {
					if ( $role == 'administrator' ) {
						/** @var WP_Role $allroles */
						$wp_role = get_role( $role );
						$this->addCap( $wp_role, HMWP_CAPABILITY );
					}
				}
			}
		}
	}

	public function removeHMWPCaps() {
		if ( function_exists( 'wp_roles' ) ) {
			/** @var WP_Role[] $allroles */
			$allroles = wp_roles()->get_names();

			if ( ! empty( $allroles ) ) {
				$allroles = array_keys( $allroles );
			}

			if ( ! empty( $allroles ) ) {
				foreach ( $allroles as $role ) {
					/** @var WP_Role $allroles */
					$wp_role = get_role( $role );
					$this->removeCap( $wp_role, HMWP_CAPABILITY );
				}
			}
		}

	}

	/**
	 * Add a cap into WP for a role
	 *
	 * @param WP_Role $role
	 * @param string $capability
	 */
	public function addCap( $role, $capability ) {

		if ( ! $role || ! method_exists( $role, 'add_cap' ) ) {
			return;
		}

		if ( ! isset( $role->capabilities[$capability] ) ) {
			$role->add_cap( $capability );
		}
	}

	/**
	 * Remove the caps for a role
	 *
	 * @param WP_Role $role
	 * @param string $capability
	 */
	public function removeCap( $role, $capability ) {

		if ( ! $role || ! method_exists( $role, 'remove_cap' ) ) {
			return;
		}

		if ( isset( $role->capabilities[$capability] ) ) {
			$role->remove_cap( $capability );
		}
	}


}
