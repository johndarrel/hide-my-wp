<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Class to manage passkey-based authentication services for users.
 * Extends the abstract provider class to integrate with the `Hide My WP` plugin.
 */
class HMWP_Models_Twofactor_Passkey extends HMWP_Models_Twofactor_Abstract {

    const META_CREDENTIALS = '_hmwp_passkeys';

    const LAST_SUCCESSFUL_LOGIN_META_KEY = '_hmwp_passkey_last_login';
    protected $rpId;
    protected $rpName;

    public function __construct() {
        $host         = wp_parse_url( home_url(), PHP_URL_HOST );
        $this->rpId   = $host ?: $_SERVER['HTTP_HOST'] ?? 'localhost'; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $this->rpName = get_bloginfo( 'name' ) . ' (' . $this->rpId . ')';
    }

    /**
     * Checks if the service is active for a given user by verifying specific options and user credentials.
     *
     * @param object $user The user object for whom the service status is being checked. The user must contain a valid ID property.
     *
     * @return bool True if the service option is enabled and the user has valid credentials, otherwise false.
     * @throws Exception
     */
    public function isServiceActive( $user ) {

        if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor' )->isActiveService( $user, 'hmwp_2fa_passkey' ) ) {
            return false;
        }

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user->ID );

        return is_array( $creds ) && ! empty( $creds );
    }

    /**
     * Retrieves passkey options for a specified user, including credentials and relying party information.
     *
     * @param object $user The user object for whom the passkey options are fetched. The user must contain a valid ID property.
     *
     * @return array An associative array containing the following keys:
     *               - credentials: Array of user credentials with friendly device names, if available.
     *               - rpId: The relying party identifier.
     *               - rpName: The relying party name.
     *               - user: The user object passed to the method.
     */
    public function getPasskeyOption( $user ) {

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user->ID );

        if ( ! is_array( $creds ) ) {
            $creds = [];
        }

        foreach ( $creds as &$c ) {
            if ( ! empty( $c['nickname'] ) ) {
                $c['nickname'] = $this->getFriendlyDeviceName( $c['nickname'] );
            }
        }

        return [
                'credentials' => $creds,
                'rpId'        => $this->rpId,
                'rpName'      => $this->rpName,
                'user'        => $user,
        ];
    }

    /**
     * Renders the authentication page for passkey login, including validation and user interaction elements.
     *
     * @param object $user The user object for whom the authentication page is rendered. The user must contain a valid ID property.
     *
     * @return void Outputs the authentication page directly, including messages, HTML elements, and JavaScript for passkey login. If the user is not provided or no credentials are registered, appropriate messages are displayed and the method exits early.
     */
    public function authenticationPage( $user ) {
        if ( ! $user ) {
            return;
        }

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user->ID );
        if ( ! is_array( $creds ) || empty( $creds ) ) {
            echo '<p class="message">' . esc_html__( 'No passkeys registered for this account.', 'hide-my-wp' ) . '</p>';

            return;
        }

        ?>
        <p class="hmwp-prompt">
            <?php echo esc_html__( 'Use a passkey (Touch ID / Face ID / Windows Hello).', 'hide-my-wp' ); ?>
        </p>

        <input type="hidden" name="passkey_login" value="1"/>
        <input type="hidden" name="passkey_assertion" id="hmwp_passkey_assertion" value=""/>

        <p>
            <button type="button" class="button button-primary" id="hmwp_passkey_button">
                <?php echo esc_html__( 'Login with passkey', 'hide-my-wp' ); ?>
            </button>
            <span class="hmwp-spinner" style="margin-left:8px;"></span>
        </p>
        <style>
            .hmwp-spinner {
                display: inline-block;
                width: 20px;
                height: 30px;
                background: url('<?php echo esc_url( _HMWP_WPLOGIN_URL_ . 'images/loading.gif' ) ?>') no-repeat center center;
                background-size: contain;
                opacity: 0;
                vertical-align: middle;
                margin: 0 8px;
                transition: opacity 0.15s ease;
                flex-shrink: 0;
                float: right;
            }

            .hmwp-spinner.is-active {
                opacity: 0.7;
            }
        </style>
        <script>
            (function () {

                const nonce = document.getElementById('wp-auth-nonce')?.value;
                const btn = document.getElementById('hmwp_passkey_button');
                const out = document.getElementById('hmwp_passkey_assertion');
                const form = document.getElementById('loginform') || document.forms['validate_2fa_form'];

                function showError(message) {
                    let el = document.getElementById('hmwp-error');

                    if (!el) {
                        el = document.createElement('div');
                        el.id = 'hmwp-error';
                        el.style.cssText = 'color:#b32d2e;margin-top:12px;font-size:13px;';
                        form.appendChild(el);
                    }

                    el.textContent = message;
                }

                function clearError() {
                    const el = document.getElementById('hmwp-error');
                    if (el) el.remove();
                }

                function b64uToBuf(b64u) {
                    const b64 = b64u.replace(/-/g, '+').replace(/_/g, '/');
                    const str = atob(b64);
                    const buf = new ArrayBuffer(str.length);
                    const view = new Uint8Array(buf);
                    for (let i = 0; i < str.length; i++) view[i] = str.charCodeAt(i);
                    return buf;
                }

                function bufToB64u(buf) {
                    const bytes = new Uint8Array(buf);
                    let str = '';
                    for (let i = 0; i < bytes.length; i++) str += String.fromCharCode(bytes[i]);
                    return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                }

                async function safeJson(res) {
                    const text = await res.text();

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid server response. Please try again.');
                    }
                }

                async function begin() {
                    const fd = new FormData();
                    fd.append('action', 'hmwp_passkey_begin');
                    fd.append('_ajax_nonce', nonce);
                    fd.append('_wp_http_referer', '<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>');
                    fd.append('user_id', '<?php echo esc_attr( $user->ID ); ?>');

                    const res = await fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    });

                    if (!res.ok) {
                        throw new Error('Connection error. Please try again.');
                    }

                    const json = await safeJson(res);

                    if (!json || !json.success) {
                        throw new Error((json && json.data) ? json.data : 'Authentication failed.');
                    }

                    const pubKey = json.data;
                    pubKey.challenge = b64uToBuf(pubKey.challenge);

                    if (Array.isArray(pubKey.allowCredentials)) {
                        pubKey.allowCredentials = pubKey.allowCredentials.map(c => {
                            c.id = b64uToBuf(c.id);
                            return c;
                        });
                    }

                    const cred = await navigator.credentials.get({ publicKey: pubKey });

                    const assertion = {
                        id: cred.id,
                        rawId: bufToB64u(cred.rawId),
                        type: cred.type,
                        response: {
                            clientDataJSON: bufToB64u(cred.response.clientDataJSON),
                            authenticatorData: bufToB64u(cred.response.authenticatorData),
                            signature: bufToB64u(cred.response.signature),
                            userHandle: cred.response.userHandle ? bufToB64u(cred.response.userHandle) : null
                        }
                    };

                    out.value = JSON.stringify(assertion);
                    form.submit();
                }

                if (btn) {
                    btn.addEventListener('click', async function () {

                        const spinner = document.querySelector('.hmwp-spinner');

                        clearError();

                        btn.disabled = true;
                        btn.classList.add('disabled');
                        if (spinner) spinner.classList.add('is-active');

                        try {
                            await begin();
                        } catch (e) {

                            // User cancelled passkey (normal behavior)
                            if (e.name === 'NotAllowedError') {
                                // silent fail (nu afișezi nimic)
                            } else {
                                showError(e.message || 'Passkey authentication failed.');
                            }

                            btn.disabled = false;
                            btn.classList.remove('disabled');

                            if (spinner) spinner.classList.remove('is-active');
                        }
                    });
                }

            })();
        </script>
        <?php
    }

    /**
     * Validates the authentication of a user based on the provided passkey/login credentials.
     *
     * @param object $user The user object for which authentication is being validated.
     *
     * @return bool Returns true if the authentication is valid, otherwise false.
     */
    public function validateAuthentication( $user ) {

        if ( ! HMWP_Classes_Tools::getValue( 'passkey_login' ) ) {
            return false;
        }

        $json = wp_unslash( $_POST['passkey_assertion'] ?? '' ); //phpcs:ignore
        if ( ! $json ) {
            return false;
        }

        $assert = json_decode( $json, true );
        if ( ! is_array( $assert ) || empty( $assert['id'] ) ) {
            return false;
        }

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user->ID );
        if ( ! is_array( $creds ) ) {
            return false;
        }

        $cred = null;
        foreach ( $creds as $c ) {
            if ( ! empty( $c['id'] ) && hash_equals( $c['id'], $assert['id'] ) ) {
                $cred = $c;
                break;
            }
        }
        if ( ! $cred ) {
            return false;
        }


        try {
            $clientJson = base64url_decode( $assert['response']['clientDataJSON'] );
            $client     = json_decode( $clientJson, true );

            $originHost = wp_parse_url( $client['origin'], PHP_URL_HOST );
            if ( ! hash_equals( $originHost, $this->rpId ) ) {
                return false;
            }

            $authData = base64url_decode( $assert['response']['authenticatorData'] );
            $sig      = base64url_decode( $assert['response']['signature'] );

            $rpHash = substr( $authData, 0, 32 );
            if ( ! hash_equals( $rpHash, hash( 'sha256', $this->rpId, true ) ) ) {
                return false;
            }

            $clientHash = hash( 'sha256', $clientJson, true );
            $signedData = $authData . $clientHash;

            $pubKey = $cred['publicKey'];
            if ( strpos( $pubKey, 'BEGIN PUBLIC KEY' ) === false ) {
                return false;
            }

            if ( openssl_verify( $signedData, $sig, $pubKey, OPENSSL_ALGO_SHA256 ) !== 1 ) {
                return false;
            }

            // update counter
            $counter = unpack( 'N', substr( $authData, 33, 4 ) )[1];
            $prev    = (int) ( $cred['counter'] ?? 0 );
            if ( $counter < $prev ) {
                return false;
            }

            $this->updateCredentialCounter( $user->ID, $assert['id'], $counter );
            $this->saveLastLoginTimestamp( $user->ID );

            return true;

        } catch ( \Throwable $e ) {
            return false;
        }
    }

    /**
     * Registers a passkey for a user by generating a challenge and returning the necessary parameters for the registration process.
     *
     * @param int $user_id The ID of the user for whom the passkey registration is being created.
     *
     * @return array An array containing the challenge, relying party information, user details, public key credential parameters, timeout settings, authenticator selection, and attestation setting.
     */
    public function passkeyRegister( $user_id ) {

        $user      = get_user_by( 'id', $user_id );
        $challenge = $this->randomB64u( 32 );

        return [
                'challenge'              => $challenge,
                'rp'                     => [ 'name' => $this->rpName, 'id' => $this->rpId ],
                'user'                   => [
                        'id'          => $this->userHandle( $user_id ),
                        'name'        => $user->user_login,
                        'displayName' => $user->display_name,
                ],
                'pubKeyCredParams'       => [
                        [ 'type' => 'public-key', 'alg' => - 7 ],
                ],
                'timeout'                => 60000,
                'authenticatorSelection' => [
                        'userVerification'        => 'preferred',
                        'authenticatorAttachment' => 'platform'
                ],
                'attestation'            => 'none',
        ];
    }

    /**
     * Registers a new credential for a user by processing and storing the provided public key and related details.
     *
     * @param int $user_id The ID of the user for whom the credential is being registered.
     * @param array $credential An array containing the credential details, including the public key and credential ID.
     *
     * @return bool Returns true if the credential is successfully registered, otherwise false.
     */
    public function registerCredential( $user_id, $credential ) {

        $attObj   = base64url_decode( $credential['publicKey'] );
        $authData = $this->extractAuthDataFromAttestation( $attObj );
        if ( ! $authData ) {
            return false;
        }

        $cose = $this->extractCoseKeyFromAuthData( $authData );
        if ( ! $cose ) {
            return false;
        }

        $pem = $this->coseToPem( $cose );
        if ( ! $pem ) {
            return false;
        }

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user_id );
        if ( ! is_array( $creds ) ) {
            $creds = [];
        }

        $creds[] = [
                'id'         => sanitize_text_field( $credential['id'] ),
                'publicKey'  => $pem,
                'nickname'   => $this->getFriendlyDeviceName( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), //phpcs:ignore
                'counter'    => 0,
                'created_at' => time(),
        ];

        HMWP_Classes_Tools::saveUserMeta( self::META_CREDENTIALS, $creds, $user_id );

        return true;
    }

    /**
     * Deletes a specified passkey for a given user.
     *
     * @param int $user_id The ID of the user whose passkey is being deleted.
     * @param string $id The unique ID of the passkey to delete.
     *
     * @return bool Returns true if the passkey is successfully deleted, otherwise false.
     */
    public function passkeyDelete( $user_id, $id ) {

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user_id );

        if ( ! is_array( $creds ) || empty( $creds ) ) {
            return false;
        }

        // Build lookup array of all credential IDs
        $ids = wp_list_pluck( $creds, 'id' );

        // Find the index of the passkey to delete
        $index = array_search( $id, $ids, true );

        if ( $index === false ) {
            return false; // passkey not found
        }

        // Remove the entry
        unset( $creds[ $index ] );

        // Reindex array for clean storage
        $creds = array_values( $creds );

        HMWP_Classes_Tools::saveUserMeta( self::META_CREDENTIALS, $creds, $user_id );

        return true;
    }


    /**
     * Saves the last login timestamp for the specified user.
     *
     * @param int $user_id The ID of the user for whom the last login timestamp should be saved.
     *
     * @return bool Returns true if the timestamp was successfully saved, or false if the new timestamp is not valid.
     */
    public function saveLastLoginTimestamp( $user_id ) {

        $valid_timestamp = time();

        $last_totp_login = $this->getLastLoginTimestamp( $user_id );

        if ( $last_totp_login && $last_totp_login >= $valid_timestamp ) {
            return false;
        }

        HMWP_Classes_Tools::saveUserMeta( self::LAST_SUCCESSFUL_LOGIN_META_KEY, $valid_timestamp, $user_id );

        return true;
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
     * Initiates a passkey login process for the specified user by generating a challenge and allowed credentials.
     *
     * @param int $user_id The ID of the user attempting to log in.
     *
     * @return array Returns an array containing the generated challenge, timeout, relying party ID,
     *               user verification preference, and allowed credentials.
     * @throws \Random\RandomException
     */
    public function passkeyLogin( $user_id ) {

        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user_id );

        $challenge = $this->randomB64u( 32 );

        $allow = [];
        foreach ( $creds as $c ) {
            $allow[] = [
                    'type' => 'public-key',
                    'id'   => $c['id']
            ];
        }

        return [
                'challenge'        => $challenge,
                'timeout'          => 60000,
                'rpId'             => $this->rpId,
                'userVerification' => 'preferred',
                'allowCredentials' => $allow,
        ];

    }

    /* ------------------ Compact Helpers ------------------ */

    /**
     * Extracts the authentication data from an attestation object.
     *
     * @param string $attObj The attestation object from which to extract the authentication data.
     *
     * @return string|false The extracted authentication data as a string, or false if the data cannot be found or parsed.
     */
    private function extractAuthDataFromAttestation( $attObj ) {
        $needle = "authData";
        $pos    = strpos( $attObj, $needle );
        if ( $pos === false ) {
            return false;
        }

        $pos  += strlen( $needle );
        $type = ord( $attObj[ $pos ++ ] );

        if ( $type == 0x58 ) {
            $len = ord( $attObj[ $pos ++ ] );
        } elseif ( $type == 0x59 ) {
            $len = unpack( 'n', substr( $attObj, $pos, 2 ) )[1];
            $pos += 2;
        } else {
            return false;
        }

        return substr( $attObj, $pos, $len );
    }


    /**
     * Extracts the COSE key from the provided authentication data.
     *
     * @param string $authData The binary string representing authentication data.
     *
     * @return string The extracted COSE key as a binary string.
     */
    private function extractCoseKeyFromAuthData( $authData ) {
        $offset = 32 + 1 + 4;
        $offset += 16;

        $credLen = unpack( 'n', substr( $authData, $offset, 2 ) )[1];
        $offset  += 2;
        $offset  += $credLen;

        return substr( $authData, $offset );
    }

    /**
     * Converts a COSE key representation into a PEM-encoded public key.
     *
     * @param string $cose The COSE key data, which may be a byte string containing a CBOR map.
     *
     * @return string|false The PEM-encoded public key as a string, or false if the COSE key is invalid or incomplete.
     */
    private function coseToPem( $cose ) {

        // NEW: unwrap if it's a byte string containing a CBOR map
        $cose = $this->unwrap_cbor_if_needed( $cose );

        $offset = 0;
        $map    = $this->cbor_decode_simple_map( $cose, $offset );

        if ( ! isset( $map[ - 2 ], $map[ - 3 ] ) ) {
            return false;
        }

        $x = $map[ - 2 ];
        $y = $map[ - 3 ];

        $raw = "\x04" . $x . $y;

        $der =
                "\x30\x59\x30\x13\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01" .
                "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07\x03\x42\x00" .
                $raw;

        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split( base64_encode( $der ), 64, "\n" ) .
               "-----END PUBLIC KEY-----\n";
    }

    /**
     * Decodes a simple CBOR-encoded map from the provided data.
     *
     * @param string $data The CBOR-encoded binary data.
     * @param int $offset Reference to the current offset pointer, which will be updated during decoding.
     *
     * @return array An associative array representing the decoded CBOR map. If the major type is not a map, returns an empty array.
     */
    private function cbor_decode_simple_map( $data, &$offset ) {
        $first = ord( $data[ $offset ++ ] );
        $major = $first >> 5;
        $count = $first & 31;

        if ( $major != 5 ) {
            return [];
        }

        $map = [];
        for ( $i = 0; $i < $count; $i ++ ) {
            $key         = $this->cbor_decode_simple_value( $data, $offset );
            $val         = $this->cbor_decode_simple_value( $data, $offset );
            $map[ $key ] = $val;
        }

        return $map;
    }

    /**
     * Decodes a CBOR (Concise Binary Object Representation) simple value from the provided binary data.
     *
     * @param string $data The binary string containing CBOR-encoded data.
     * @param int $offset The current offset within the binary data, updated as data is decoded.
     *
     * @return mixed The decoded CBOR value, which can be an integer, string, or null if an unsupported type is encountered.
     */
    private function cbor_decode_simple_value( $data, &$offset ) {
        $b     = ord( $data[ $offset ++ ] );
        $major = $b >> 5;
        $info  = $b & 31;

        // Unsigned int
        if ( $major === 0 ) {
            if ( $info < 24 ) {
                return $info;
            } elseif ( $info === 24 ) {
                return ord( $data[ $offset ++ ] );
            } elseif ( $info === 25 ) {
                $val    = unpack( 'n', substr( $data, $offset, 2 ) )[1];
                $offset += 2;

                return $val;
            } elseif ( $info === 26 ) {
                $val    = unpack( 'N', substr( $data, $offset, 4 ) )[1];
                $offset += 4;

                return $val;
            }
        }

        // Negative int
        if ( $major === 1 ) {
            if ( $info < 24 ) {
                return - 1 - $info;
            } elseif ( $info === 24 ) {
                $val = ord( $data[ $offset ++ ] );

                return - 1 - $val;
            } elseif ( $info === 25 ) {
                $val    = unpack( 'n', substr( $data, $offset, 2 ) )[1];
                $offset += 2;

                return - 1 - $val;
            } elseif ( $info === 26 ) {
                $val    = unpack( 'N', substr( $data, $offset, 4 ) )[1];
                $offset += 4;

                return - 1 - $val;
            }
        }

        // Byte string
        if ( $major === 2 ) {
            if ( $info < 24 ) {
                $len = $info;
            } elseif ( $info === 24 ) {
                $len = ord( $data[ $offset ++ ] );                      // 0x58 <len>
            } elseif ( $info === 25 ) {
                $len    = unpack( 'n', substr( $data, $offset, 2 ) )[1];  // 0x59 <len16>
                $offset += 2;
            } else {
                return null; // not needed for WebAuthn
            }

            $v      = substr( $data, $offset, $len );
            $offset += $len;

            return $v;
        }

        // We don't need to support other types for COSE keys
        return null;
    }

    /**
     * Processes a COSE-encoded byte string and unwraps the CBOR structure if necessary.
     *
     * @param string $cose The COSE-encoded byte string that may contain a CBOR-wrapped payload.
     *
     * @return string The unwrapped CBOR payload if applicable; otherwise, the original input.
     */
    private function unwrap_cbor_if_needed( $cose ) {
        $first = ord( $cose[0] );

        // Major type 2 (byte string)
        if ( ( $first >> 5 ) === 2 ) {
            $len = $first & 31;

            // small lengths (< 24)
            if ( $len < 24 ) {
                return substr( $cose, 1, $len );
            }

            // length in next byte
            if ( $len === 24 ) {
                $llen = ord( $cose[1] );

                return substr( $cose, 2, $llen );
            }

            // length in next 2 bytes
            if ( $len === 25 ) {
                $llen = unpack( 'n', substr( $cose, 1, 2 ) )[1];

                return substr( $cose, 3, $llen );
            }
        }

        return $cose;
    }

    /**
     * Updates the counter value of a specific credential associated with a user.
     *
     * @param string $user_id The unique identifier for the user.
     * @param string $credId The unique identifier of the credential to update.
     * @param int $counter The new counter value to be assigned to the specified credential.
     *
     * @return void
     */
    private function updateCredentialCounter( $user_id, $credId, $counter ) {
        $creds = HMWP_Classes_Tools::getUserMeta( self::META_CREDENTIALS, $user_id );
        if ( ! is_array( $creds ) ) {
            return;
        }

        foreach ( $creds as &$c ) {
            if ( $c['id'] == $credId ) {
                $c['counter'] = $counter;
                break;
            }
        }
        HMWP_Classes_Tools::saveUserMeta( self::META_CREDENTIALS, $creds, $user_id );
    }

    /**
     * Generates a URL-safe Base64-encoded random string.
     *
     * @param int $len The length of the random byte string to generate. Defaults to 32.
     *
     * @return string Returns a URL-safe Base64-encoded string without padding.
     * @throws \Random\RandomException
     */
    private function randomB64u( $len = 32 ) {
        return rtrim( strtr( base64_encode( random_bytes( $len ) ), '+/', '-_' ), '=' );
    }

    /**
     * Generates a secure, unique user handle based on the provided user ID.
     *
     * @param string $user_id The unique identifier for the user.
     *
     * @return string A base64-encoded, URL-safe string representing the user handle.
     */
    private function userHandle( $user_id ) {
        $raw = hash( 'sha256', 'hmwp:' . $user_id . wp_salt( 'auth' ), true );

        return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
    }

    /**
     * Determines a user-friendly name for the device and browser based on the user agent string.
     *
     * @param string $ua The user agent string from which the device and browser information is derived.
     *
     * @return string A string describing the device and browser in a human-readable format.
     */
    private function getFriendlyDeviceName( $ua ) {

        $u = strtolower( $ua );
        $p = 'Device';
        $b = 'Browser';

        if ( strpos( $u, 'mac' ) !== false ) {
            $p = 'Mac';
        } elseif ( strpos( $u, 'windows' ) !== false ) {
            $p = 'Windows PC';
        } elseif ( strpos( $u, 'iphone' ) !== false ) {
            $p = 'iPhone';
        } elseif ( strpos( $u, 'android' ) !== false ) {
            $p = 'Android Phone';
        }

        if ( strpos( $u, 'chrome' ) !== false && strpos( $u, 'edg' ) === false ) {
            $b = 'Chrome';
        } elseif ( strpos( $u, 'edg' ) !== false ) {
            $b = 'Edge';
        } elseif ( strpos( $u, 'safari' ) !== false && strpos( $u, 'chrome' ) === false ) {
            $b = 'Safari';
        } elseif ( strpos( $u, 'firefox' ) !== false ) {
            $b = 'Firefox';
        }

        return $p . ' — ' . $b;
    }

    /**
     * Determines if the resource or feature is available for the given user.
     *
     * @param mixed $user The user object or identifier to check availability for.
     *
     * @return bool True if available for the user, otherwise false.
     */
    public function isAvailableForUser( $user ) {
        return true;
    }
}


if ( ! function_exists( 'base64url_decode' ) ) {
    function base64url_decode( $d ) {
        $b = strtr( $d, '-_', '+/' );

        return base64_decode( str_pad( $b, strlen( $b ) % 4 == 0 ? strlen( $b ) : strlen( $b ) + 4 - strlen( $b ) % 4, '=', STR_PAD_RIGHT ) );
    }
}
