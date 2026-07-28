<?php
/**
 * Plugin Backup Codes Service
 * Called when the user is using Backup Codes for 2FA
 *
 * @file  The Backup Codes Model file
 * @package HMWPP/CodesModel
 * @since 1.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Twofactor_Codes extends HMWP_Models_Twofactor_Abstract {

	/** @var string The user meta backup codes key */
	const BACKUP_CODES_META_KEY = '_hmwp_backup_codes';

	/** @var int The number backup codes. */
	const NUMBER_OF_CODES = 5;

	/**
	 * Verify whether the current service is active and operational for the present user.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return bool
	 */
	public function isServiceActive( $user ) {

		if ( $this->isAvailableForUser( $user ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Are there codes accessible for the specified user?
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return boolean
	 */
	public function isAvailableForUser( $user ) {
		// Does this user have available codes?
		if ( 0 < $this->codesRemained( $user ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Provides the count of unused codes associated with the given user.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return int $int The number of unused codes remaining
	 */
	public function codesRemained( $user ) {

		$backup_codes = HMWP_Classes_Tools::getUserMeta( self::BACKUP_CODES_META_KEY, $user->ID );

		if ( is_array( $backup_codes ) && ! empty( $backup_codes ) ) {
			return count( $backup_codes );
		}

		return 0;
	}


	/**
	 * Creates backup codes and updates user metadata.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return array
	 */
	public function generateCodes( $user ) {
		$codes  = array();
		$hashed = array();

		// Check for arguments.
		$num_codes = apply_filters( 'hmwp_codes_number', self::NUMBER_OF_CODES );

		for ( $i = 0; $i < $num_codes; $i ++ ) {
			$code     = $this->getCode();
			$hashed[] = wp_hash_password( $code );
			$codes[]  = $code;
			unset( $code );
		}

		HMWP_Classes_Tools::saveUserMeta( self::BACKUP_CODES_META_KEY, $hashed, $user->ID );

		return $codes;
	}

	/**
	 * The download data provided in a link
	 *
	 * @param array $codes
	 *
	 * @return string
	 */
	public function getDownloadLink( $codes ) {

		/* translators: 1: Website home URL. */
		$title = sprintf( __( '2FA Backup Codes for %1$s', 'hide-my-wp' ), esc_url( home_url( '/' ) ) );

		// Generate download content.
		$download_link = 'data:application/text;charset=utf-8,';
		$download_link .= rawurlencode( "{$title}\r\n\r\n" );

		$i = 1;
		foreach ( $codes as $code ) {
			$download_link .= rawurlencode( "{$i}. {$code}\r\n" );
			$i ++;
		}

		return $download_link;
	}


	/**
	 * Validates the users input token.
	 *
	 * In this class we just return true.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return boolean
	 * @since 1.0.0
	 *
	 */
	public function validateAuthentication( $user ) {

		$backup_code = $this->sanitizeCodeFromRequest( 'authcode' );

		if ( ! $backup_code ) {
			return false;
		}

		return $this->validateCode( $user, $backup_code );
	}

	/**
	 * Validates a backup code.
	 *
	 * Backup Codes are single use and are deleted upon a successful validation.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param int $code The backup code.
	 *
	 * @return boolean
	 */
	public function validateCode( $user, $code ) {

		$backup_codes = HMWP_Classes_Tools::getUserMeta( self::BACKUP_CODES_META_KEY, $user->ID );

		if ( is_array( $backup_codes ) && ! empty( $backup_codes ) ) {
			foreach ( $backup_codes as $hashed ) {
				if ( wp_check_password( $code, $hashed, $user->ID ) ) {
					$this->deleteCode( $user, $hashed );

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Deletes a backup code.
	 *
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param string $hashed The hashed the backup code.
	 */
	public function deleteCode( $user, $hashed ) {

		$backup_codes = HMWP_Classes_Tools::getUserMeta( self::BACKUP_CODES_META_KEY, $user->ID );

		// Delete the current code from the list since it's been used.
		if ( false !== $index = array_search( $hashed, $backup_codes ) ) {
			unset( $backup_codes[ $index ] );
		}

		// Update the backup code master list.
		HMWP_Classes_Tools::saveUserMeta( self::BACKUP_CODES_META_KEY, $backup_codes, $user->ID );

	}
}
