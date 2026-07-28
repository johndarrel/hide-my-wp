<?php
/**
 * Plugin TOTP Service
 * Called when the user is using TOTP model for 2FA
 *
 * @file  The TOTP Model file
 * @package HMWPP/TOTPModel
 * @since 1.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Twofactor_Tftotp extends HMWP_Models_Twofactor_Abstract {

	/**
	 * The user meta key for the TOTP Secret key.
	 *
	 * @var string
	 */
	const SECRET_META_KEY = '_hmwp_totp_key';

	/**
	 * The user meta key for the last successful TOTP token timestamp logged in with.
	 *
	 * @var string
	 */
	const LAST_SUCCESSFUL_LOGIN_META_KEY = '_hmwp_totp_last_login';

	const DEFAULT_KEY_BIT_SIZE = 160;
	const DEFAULT_CRYPTO = 'sha1';
	const DEFAULT_DIGIT_COUNT = 6;
	const DEFAULT_TIME_STEP_SEC = 30;
	const DEFAULT_TIME_STEP_ALLOWANCE = 4;

	/**
	 * Characters used in base32 encoding.
	 *
	 * @var string
	 */
	private static $base_32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Check if the current service is activated and working for the current user
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return bool
	 */
	public function isServiceActive( $user ) {

        if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor' )->isActiveService( $user, 'hmwp_2fa_totp') ) {
			return false;
		}

		if ( isset( $user->ID ) && self::getUserTotpKey( $user->ID ) ) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * @param WP_Rest_Request $request The Rest Request object.
	 *
	 * @return WP_Error|true true on success, WP_Error on error.
	 */
	public function setupTotp( $user_id, $key, $code ) {

		$code = preg_replace( '/\s+/', '', $code );

		if ( ! $this->isValidKey( $key ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid 2FA Authentication Secret Key.', 'hide-my-wp' ), array( 'status' => 400 ) );
		}

		if ( ! $this->isValidAuthcode( $key, $code ) ) {
			return new WP_Error( 'invalid_key_code', __( 'Invalid 2FA Authentication code.', 'hide-my-wp' ), array( 'status' => 400 ) );
		}

		if ( ! $this->setUserTotpKey( $user_id, $key ) ) {
			return new WP_Error( 'db_error', __( 'Unable to save 2FA Authentication code. Please re-scan the QR code and enter the code provided by your application.', 'hide-my-wp' ), array( 'status' => 500 ) );
		}

		return true;
	}

	/**
	 * Generates a URL that can be used to create a QR code.
	 *
	 * @param WP_User $user The user to generate a URL for.
	 * @param string $secret_key The secret key.
	 *
	 * @return string
	 */
	public function generateQrCodeURL( $user, $secret_key ) {

		$issuer = apply_filters( 'hmwp_totp_issuer', get_bloginfo( 'name', 'display' ) );

		$totp_title = apply_filters( 'hmwp_totp_title', $issuer . ':' . $user->user_login, $user, $issuer );

		$totp_url = add_query_arg( array(
				'secret' => rawurlencode( $secret_key ),
				'issuer' => rawurlencode( $issuer ),
			), 'otpauth://totp/' . rawurlencode( $totp_title ) );

		$totp_url = apply_filters( 'hmwp_totp_url', $totp_url, $user );

        return esc_url( $totp_url, array( 'otpauth' ) );
	}

	/**
	 *
	 * @param WP_User $user The current user being edited.
	 *
	 * @return array|false of options
	 *
	 */
	public function getTwoFactorOption( $user ) {
		if ( ! isset( $user->ID ) ) {
			return false;
		}

		if ( ! $key = $this->getUserTotpKey( $user->ID ) ) {
			$key = $this->generateKey();

			return array(
				'user' => $user,
				'key'  => $key,
				'url'  => $this->generateQrCodeURL( $user, $key )
			);

		}

		return array(
			'user' => $user,
			'key'  => $key,
		);
	}

	/**
	 * Get the TOTP secret key for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	public function getUserTotpKey( $user_id ) {
		return HMWP_Classes_Tools::getUserMeta( self::SECRET_META_KEY, $user_id );
	}

	/**
	 * Set the TOTP secret key for a user.
	 *
	 * @param int $user_id User ID.
	 * @param string $key TOTP secret key.
	 *
	 * @return boolean If the key was stored successfully.
	 */
	public function setUserTotpKey( $user_id, $key ) {
		return HMWP_Classes_Tools::saveUserMeta( self::SECRET_META_KEY, $key, $user_id );
	}

	/**
	 * Delete the TOTP secret key for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean If the key was deleted successfully.
	 */
	public function deleteUserTotpKey( $user_id ) {
		HMWP_Classes_Tools::deleteUserMeta( self::LAST_SUCCESSFUL_LOGIN_META_KEY, $user_id );

		return HMWP_Classes_Tools::deleteUserMeta( self::SECRET_META_KEY, $user_id );
	}

	/**
	 * Check if the TOTP secret key has a proper format.
	 *
	 * @param string $key TOTP secret key.
	 *
	 * @return boolean
	 */
	public function isValidKey( $key ) {
		$check = sprintf( '/^[%s]+$/', self::$base_32_chars );

		if ( 1 === preg_match( $check, $key ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates authentication.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return bool Whether the user gave a valid code
	 */
	public function validateAuthentication( $user ) {

		$code = $this->sanitizeCodeFromRequest( 'authcode', self::DEFAULT_DIGIT_COUNT );

		if ( ! $code ) {
			return false;
		}

		return $this->validateCodeForUser( $user, $code );
	}

	/**
	 * Validates an authentication code for a given user, preventing re-use and older TOTP keys.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 * @param int $code The TOTP token to validate.
	 *
	 * @return bool Whether the code is valid for the user and a newer code has not been used.
	 */
	public function validateCodeForUser( $user, $code ) {

		$valid_timestamp = $this->getAuthcodeValidTicktime( $this->getUserTotpKey( $user->ID ), $code );

		if ( ! $valid_timestamp ) {
			return false;
		}

		$last_totp_login = $this->getLastLoginTimestamp( $user->ID );

		// The TOTP authentication is not valid, if we've seen the same or newer code.
		if ( $last_totp_login && $last_totp_login >= $valid_timestamp ) {
			return false;
		}

		HMWP_Classes_Tools::saveUserMeta( self::LAST_SUCCESSFUL_LOGIN_META_KEY, $valid_timestamp, $user->ID );

		return true;
	}


	/**
	 * Checks if a given code is valid for a given key, allowing for a certain amount of time drift.
	 *
	 * @param string $key The share secret key to use.
	 * @param string $authcode The code to test.
	 *
	 * @return bool Whether the code is valid within the time frame.
	 */
	public function isValidAuthcode( $key, $authcode ) {
		return (bool) $this->getAuthcodeValidTicktime( $key, $authcode );
	}

	/**
	 * Get the last login timestamp of the current user
	 *
	 * @param int $user_id The currently logged-in user ID.
	 *
	 * @return int timestamp of the last login
	 */
	public function getLastLoginTimestamp( $user_id ) {

		$last_totp_login = (int) HMWP_Classes_Tools::getUserMeta( self::LAST_SUCCESSFUL_LOGIN_META_KEY, $user_id );

		if ( $last_totp_login > 0 ) {
			return $last_totp_login;
		}

		return false;
	}

	/**
	 * Verifies the validity of a provided code with respect to a given key, while accounting for a specified level of time variance.
	 *
	 * @param string $key The share secret key to use.
	 * @param string $authcode The code to test.
	 *
	 * @return false|int Returns the timestamp of the auth code on success or false.
	 */
	public function getAuthcodeValidTicktime( $key, $authcode ) {

		$max_ticks = apply_filters( 'hmwp_totp_time_step_allowance', self::DEFAULT_TIME_STEP_ALLOWANCE );

		// Sorted array of ticks, encompassing all permissible values, prioritized by their absolute values for assessing the closest match.
		$ticks = range( - $max_ticks, $max_ticks );
		usort( $ticks, array(
			$this,
			'abssort'
		) );

		$time = floor( time() / self::DEFAULT_TIME_STEP_SEC );

		foreach ( $ticks as $offset ) {
			$log_time = $time + $offset;
			if ( hash_equals( $this->calcTotp( $key, $log_time ), $authcode ) ) {
				// Return the tick timestamp.
				return $log_time * self::DEFAULT_TIME_STEP_SEC;
			}
		}

		return false;
	}

	/**
	 * Generates key and encode it
	 *
	 * @param int $bitsize Nume of bits to use for key.
	 *
	 * @return string
	 */
	public function generateKey( $bitsize = self::DEFAULT_KEY_BIT_SIZE ) {
		$bytes  = ceil( $bitsize / 8 );
		$secret = wp_generate_password( $bytes, true, true );

		return $this->base32Encode( $secret );
	}

	/**
	 * Pack 64
	 *
	 * @param string $value The value that will be packed.
	 *
	 * @return string Binary packed string.
	 */
	public function pack64( $value ) {

		if ( PHP_INT_SIZE >= 8 ) {
			return pack( 'J', $value );
		} else {
			$higher = 0;
		}

		$lowmap = 0xffffffff;
		$lower  = $value & $lowmap;

		return pack( 'NN', $higher, $lower );
	}

    /**
     * Determine a legitimate code based on the shared secret key.
     *
     * @param string $key The shared secret key to use for calculating code.
     * @param mixed $step_count The time step used to calculate the code, which is the floor of time() divided by step size.
     * @param int $digits The number of digits in the returned code.
     * @param string $hash The hash used to calculate the code.
     * @param int $time_step The size of the time step.
     *
     * @return string The totp code
     * @throws Exception
     */
	public function calcTotp( $key, $step_count = false, $digits = self::DEFAULT_DIGIT_COUNT, $hash = self::DEFAULT_CRYPTO, $time_step = self::DEFAULT_TIME_STEP_SEC ) {

		//decode key
		$secret = $this->base32Decode( $key );

		if ( false === $step_count ) {
			$step_count = floor( time() / $time_step );
		}

		$timestamp = self::pack64( $step_count );

		$hash = hash_hmac( $hash, $timestamp, $secret, true );

		$offset = ord( $hash[19] ) & 0xf;

		$code = ( ( ( ord( $hash[ $offset + 0 ] ) & 0x7f ) << 24 ) | ( ( ord( $hash[ $offset + 1 ] ) & 0xff ) << 16 ) | ( ( ord( $hash[ $offset + 2 ] ) & 0xff ) << 8 ) | ( ord( $hash[ $offset + 3 ] ) & 0xff ) ) % pow( 10, $digits );

		return str_pad( $code, $digits, '0', STR_PAD_LEFT );
	}

	/**
	 * Is this Two Factor provider configured and accessible for the specified user?
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @return boolean
	 */
	public function isAvailableForUser( $user ) {
		// Only available if the secret key has been saved for the user.
		$key = $this->getUserTotpKey( $user->ID );

		return ! empty( $key );
	}

	/**
	 * Generates the user authentication prompt form.
	 *
	 * @param WP_User $user The WP_User instance representing the currently logged-in user.
	 *
	 * @codeCoverageIgnore
	 */
	public function authenticationPage( $user ) {
		require_once ABSPATH . '/wp-admin/includes/template.php';
		?>
        <p class="hmwp-prompt">
			<?php echo esc_html__( 'Please enter the code generated by your authenticator app.', 'hide-my-wp' ); ?>
        </p>
        <p class="hmwp-auth-code">
            <label><?php echo esc_html__( 'Authentication Code:', 'hide-my-wp' ); ?>
                <input type="text" inputmode="numeric" autocomplete="one-time-code" name="authcode" id="authcode" class="input authcode" value="" size="20" pattern="[0-9 ]*" placeholder="123 456" data-digits="<?php echo esc_attr( self::DEFAULT_DIGIT_COUNT ); ?>"/>
            </label>
        </p>
        <p class="hmwp-remember-device">
            <label>
                <input type="checkbox" name="remember_device" value="1" <?php checked( HMWP_Classes_Tools::getValue( 'remember_device' ) ); ?> >
                <?php echo esc_html__('Trust this browser', 'hide-my-wp'); ?>
            </label>
        </p>
        <script type="text/javascript">
            setTimeout(function () {
                var d;
                try {
                    d = document.getElementById('authcode');
                    d.focus();
                } catch (e) {
                }
            }, 200);
        </script>

        <br />

		<?php submit_button( esc_attr__( 'Authenticate', 'hide-my-wp' ) ); ?>

        <?php
	}

	/**
	 * Returns a base32 encoded string.
	 *
	 * @param string $string String to be encoded using base32.
	 *
	 * @return string base32 encoded string without padding.
	 */
	public function base32Encode( $string ) {
		if ( empty( $string ) ) {
			return '';
		}

		$binary_string = '';

		foreach ( str_split( $string ) as $character ) {
			$binary_string .= str_pad( base_convert( ord( $character ), 10, 2 ), 8, '0', STR_PAD_LEFT );
		}

		$five_bit_sections = str_split( $binary_string, 5 );
		$base32_string     = '';

		foreach ( $five_bit_sections as $five_bit_section ) {
			$base32_string .= self::$base_32_chars[ base_convert( str_pad( $five_bit_section, 5, '0' ), 2, 10 ) ];
		}

		return $base32_string;
	}

	/**
	 * Translate a base32 string into its binary equivalent and provide the binary representation.
	 *
	 * @param string $base32_string The base 32 string to decode.
	 *
	 * @return string Binary representation of decoded string
	 * @throws Exception If string contains non-base32 characters.
	 *
	 */
	public function base32Decode( $base32_string ) {

		$base32_string = strtoupper( $base32_string );

		if ( ! preg_match( '/^[' . self::$base_32_chars . ']+$/', $base32_string, $match ) ) {
			throw new Exception( 'Invalid characters in the base32 string.' );
		}

		$l      = strlen( $base32_string );
		$n      = 0;
		$j      = 0;
		$binary = '';

		for ( $i = 0; $i < $l; $i ++ ) {

			$n = $n << 5; // Move buffer left by 5 to make room.
			$n = $n + strpos( self::$base_32_chars, $base32_string[ $i ] );    // Add value into buffer.
			$j += 5; // Keep track of number of bits in buffer.

			if ( $j >= 8 ) {
				$j      -= 8;
				$binary .= chr( ( $n & ( 0xFF << $j ) ) >> $j );
			}
		}

		return $binary;
	}

	/**
	 * Utilized in conjunction with usort to arrange an array based on its distance from zero.
	 *
	 * @param int $a First array element.
	 * @param int $b Second array element.
	 *
	 * @return int -1, 0, or 1 as needed by usort
	 */
	private function abssort( $a, $b ) {
		$a = abs( $a );
		$b = abs( $b );
		if ( $a === $b ) {
			return 0;
		}

		return ( $a < $b ) ? - 1 : 1;
	}
}
