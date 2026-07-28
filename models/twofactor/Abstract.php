<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

abstract class HMWP_Models_Twofactor_Abstract {

	/**
	 * Verify whether the present service is active and operational for the user at this moment.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 */
	abstract public function isServiceActive( $user );

	/**
	 * Enable providers to perform additional processing prior to authentication.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return boolean
	 */
	public function preAuthentication( $user ) {
		return false;
	}

	/**
	 * Verifies the token entered by the user.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return boolean
	 */
	abstract public function validateAuthentication( $user );

	/**
	 * Check if the Two-Factor provider is set up and accessible for the specified user.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return boolean
	 */
	abstract public function isAvailableForUser( $user );

	/**
	 * Create an auth code by generating a random eight-character string.
	 *
	 * @param int $length The code length.
	 * @param string|array $chars Valid auth code characters.
	 *
	 * @return string
	 */
	public function getCode( $length = 8, $chars = '1234567890' ) {
		$code = '';
		if ( is_array( $chars ) ) {
			$chars = implode( '', $chars );
		}
		for ( $i = 0; $i < $length; $i ++ ) {
			$code .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $code;
	}

	/**
	 * Sanitizes a numeric code to be used as an auth code.
	 *
	 * @param string $field The _REQUEST field to check for the code.
	 * @param int $length The valid expected length of the field.
	 *
	 * @return false|string Auth code on success, false if the field is not set or not expected length.
	 */
	public function sanitizeCodeFromRequest( $field, $length = 0 ) {

		if ( ! HMWP_Classes_Tools::getIsset( $field ) ) {
			return false;
		}

		$code = HMWP_Classes_Tools::getValue( $field );
		$code = preg_replace( '/\s+/', '', $code );

		// Maybe validate the length.
		if ( $length && strlen( $code ) !== $length ) {
			return false;
		}

		return (string) $code;
	}
}
