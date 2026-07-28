<?php
/**
 * Firewall Protection
 * Called when the Firewall Protection is activated
 *
 * @file  The Firewall file
 * @package HMWP/Firewall
 * @since 5.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Firewall_Server {

	private $ip;

	private $ips = array();

	/**
	 * Retrieves the client IP address from a list of server variables.
	 * The method prioritizes filtering and validating IPs, excluding localhost addresses.
	 * If a valid IP address is found, it is returned; otherwise, a default value is returned.
	 *
	 * @return string The client's IP address if a valid one is found, or '127.0.0.1' as the default.
	 */
	public function getIp() {

		if ( isset( $this->ip ) ) {
			return $this->ip;
		}

		$this->ip = '127.0.0.1';
		$ips = $this->getServerVariableIPs();

		if ( ! empty( $ips ) ) {
			foreach ( $ips as $ip ) {
				$ip = trim( (string) $ip );

				if ( $ip === '127.0.0.1' || $ip === '::1' || $this->isPrivate( $ip ) ) {
					continue;
				}

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					$this->ip = $ip;
				}
			}
		}

		return $this->ip;
	}


	/**
	 * Get validated IPs from caller server.
	 *
	 * Only REMOTE_ADDR (the real TCP peer) is trusted unconditionally. Forwarded-IP
	 * headers (CF-Connecting-IP, X-Real-IP, ...) are attacker-controllable and are
	 * included ONLY when the request demonstrably came through a trusted proxy, to
	 * prevent IP spoofing that would bypass the firewall/whitelist and brute-force lockout.
	 *
	 * @return array
	 */
	public function getServerVariableIPs() {

		if ( ! empty($this->ips) ) {
			return $this->ips;
		}

		$ips = array();

		// REMOTE_ADDR is the only non-spoofable source: the actual TCP peer.
		$remote = $this->readHeaderIp( 'REMOTE_ADDR' );
		if ( $remote ) {
			$ips['REMOTE_ADDR'] = $remote;
		}

		// Add forwarded-IP headers only when they originate from a trusted proxy.
		foreach ( $this->getTrustedForwardHeaders( $remote ) as $header ) {
			if ( $forward = $this->readHeaderIp( $header ) ) {
				$ips[ $header ] = $forward;
			}
		}

		// set the ips for this call
		$this->ips = $ips;

		return $ips;
	}

	/**
	 * Read and clean an IP from a single server header.
	 *
	 * @param string $header
	 *
	 * @return string|false
	 */
	private function readHeaderIp( $header ) {

		$ip = $_SERVER[ $header ] ?? false; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( $ip === false || $ip === '' ) {
			return false;
		}

		if ( strpos( $ip, ',' ) !== false ) {
			$ip = preg_replace( '/[\s,]/', '', explode( ',', $ip ) );
		}

		return $this->getCleanIp( $ip );
	}

	/**
	 * Decide which forwarded-IP headers can be trusted for the current request.
	 *
	 * @param string|false $remote The cleaned REMOTE_ADDR of the request.
	 *
	 * @return string[]
	 */
	private function getTrustedForwardHeaders( $remote ) {

		$headers = array();

		// Admin explicit opt-in for custom proxies/CDNs (e.g. Sucuri, load balancers).
		$trusted_header = HMWP_Classes_Tools::getOption( 'trusted_ip_header' );
		if ( $trusted_header && isset( $_SERVER[ $trusted_header ] ) ) {
			$headers[] = $trusted_header;
		}

		if ( $remote ) {
			// Cloudflare overwrites CF-Connecting-IP with the real client IP, so it is
			// trustworthy only when the connection actually originates from Cloudflare.
			if ( $this->isCloudflareIp( $remote ) ) {
				$headers[] = 'HTTP_CF_CONNECTING_IP';
			}

			// Same-host reverse proxy (nginx/Apache -> PHP): REMOTE_ADDR is loopback/private.
			if ( $this->isPrivate( $remote ) || $remote === '::1' ) {
				$headers[] = 'HTTP_X_REAL_IP';
				$headers[] = 'HTTP_X_MIDDLETON_IP';
			}
		}

		// Allow site owners to refine the trusted headers for non-standard setups.
		$headers = apply_filters( 'hmwp_trusted_ip_headers', $headers, $remote );

		return array_values( array_unique( array_filter( (array) $headers ) ) );
	}

	/**
	 * Get all the known IP headers (for reference/compatibility).
	 *
	 * NOTE: this is NOT the trust list. Trust is decided in getTrustedForwardHeaders()
	 * based on the verified REMOTE_ADDR of the request.
	 *
	 * @return string[]
	 */
	public function getValidHeaders() {

		// List of the known header Ips
		return array(
			// CloudFlare IP address
			'HTTP_CF_CONNECTING_IP',
			// Real IP address behind proxy
			'HTTP_X_REAL_IP',
			'HTTP_X_MIDDLETON_IP',
			// Remote IP address
			'REMOTE_ADDR',
		);
	}

	/**
	 * Check if an IP belongs to the published Cloudflare proxy ranges.
	 *
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function isCloudflareIp( $ip ) {

		$is_ipv6 = ( strpos( $ip, ':' ) !== false );

		foreach ( $this->getCloudflareRanges( $is_ipv6 ) as $cidr ) {
			if ( $this->ipInCidr( $ip, $cidr ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Published Cloudflare IP ranges (https://www.cloudflare.com/ips/).
	 *
	 * These are intentionally hardcoded so the trust check works offline with no
	 * external dependency. Cloudflare's ranges are very stable, but if they ever
	 * change the list can be extended/overridden via the 'hmwp_cloudflare_ranges'
	 * filter without editing the plugin. A stale list fails safe: an unrecognised
	 * Cloudflare edge simply has its CF-Connecting-IP header ignored (no spoofing).
	 *
	 * @param bool $ipv6 Return the IPv6 list instead of the IPv4 list.
	 *
	 * @return string[]
	 */
	private function getCloudflareRanges( $ipv6 = false ) {

		if ( $ipv6 ) {
			$ranges = array(
				'2400:cb00::/32',
				'2606:4700::/32',
				'2803:f800::/32',
				'2405:b500::/32',
				'2405:8100::/32',
				'2a06:98c0::/29',
				'2c0f:f248::/32',
			);
		} else {
			$ranges = array(
				'173.245.48.0/20',
				'103.21.244.0/22',
				'103.22.200.0/22',
				'103.31.4.0/22',
				'141.101.64.0/18',
				'108.162.192.0/18',
				'190.93.240.0/20',
				'188.114.96.0/20',
				'197.234.240.0/22',
				'198.41.128.0/17',
				'162.158.0.0/15',
				'104.16.0.0/13',
				'104.24.0.0/14',
				'172.64.0.0/13',
				'131.0.72.0/22',
			);
		}

		// Allow the ranges to be patched without a plugin release if Cloudflare changes them.
		$ranges = apply_filters( 'hmwp_cloudflare_ranges', $ranges, $ipv6 );

		return array_filter( (array) $ranges );
	}

	/**
	 * Check whether an IP falls within a CIDR range (IPv4 and IPv6).
	 *
	 * @param string $ip
	 * @param string $cidr
	 *
	 * @return bool
	 */
	private function ipInCidr( $ip, $cidr ) {

		if ( strpos( $cidr, '/' ) === false ) {
			return false;
		}

		list( $subnet, $bits ) = explode( '/', $cidr, 2 );
		$bits = (int) $bits;

		// IPv4
		if ( strpos( $ip, ':' ) === false && strpos( $subnet, ':' ) === false ) {
			$ip_long     = ip2long( $ip );
			$subnet_long = ip2long( $subnet );

			if ( $ip_long === false || $subnet_long === false || $bits < 0 || $bits > 32 ) {
				return false;
			}

			if ( $bits === 0 ) {
				return true;
			}

			$mask = - 1 << ( 32 - $bits );

			return ( ( $ip_long & $mask ) === ( $subnet_long & $mask ) );
		}

		// IPv6
		if ( strpos( $ip, ':' ) !== false && strpos( $subnet, ':' ) !== false && $this->isIPv6Support() ) {
			$ip_bin     = @inet_pton( $ip );
			$subnet_bin = @inet_pton( $subnet );

			if ( $ip_bin === false || $subnet_bin === false || $bits < 0 || $bits > 128 ) {
				return false;
			}

			$whole_bytes = intdiv( $bits, 8 );
			$remainder   = $bits % 8;

			if ( $whole_bytes > 0 && strncmp( $ip_bin, $subnet_bin, $whole_bytes ) !== 0 ) {
				return false;
			}

			if ( $remainder > 0 ) {
				$mask = chr( ( 0xff << ( 8 - $remainder ) ) & 0xff );

				return ( ( $ip_bin[ $whole_bytes ] & $mask ) === ( $subnet_bin[ $whole_bytes ] & $mask ) );
			}

			return true;
		}

		return false;
	}


    /**
     * Return the verified IP
     *
     * @param $ip
     *
     * @return array|bool|mixed|string|string[]|null
     */
	public function getCleanIp( $ip ) {

		if ( ! $this->isValidIP( $ip ) ) {
			$ip = preg_replace( '/:\d+$/', '', $ip );
		}

		if ( $this->isValidIP( $ip ) ) {
			if ( ! $this->isIPv6MappedIPv4( $ip ) ) {
				$ip = $this->inetNtop( $this->inetPton( $ip ) );
			}

			return $ip;
		}

		return false;

	}

	/**
	 * @param $ip
	 *
	 * @return bool
	 */
	private function isIPv6MappedIPv4( $ip ) {
		return preg_match( '/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i', $ip ) > 0;
	}

	private function inetNtop( $ip ) {
		if ( strlen( $ip ) == 16 && substr( $ip, 0, 12 ) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" ) {
			$ip = substr( $ip, 12, 4 );
		}

		return self::isIPv6Support() ? @inet_ntop( $ip ) : $this->_inetNtop( $ip );
	}

	private function _inetNtop( $ip ) {
		// IPv4
		if ( strlen( $ip ) === 4 ) {
			return ord( $ip[0] ) . '.' . ord( $ip[1] ) . '.' . ord( $ip[2] ) . '.' . ord( $ip[3] );
		}

		// IPv6
		if ( strlen( $ip ) === 16 ) {

			// IPv4 mapped IPv6
			if ( substr( $ip, 0, 12 ) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" ) {
				return "::ffff:" . ord( $ip[12] ) . '.' . ord( $ip[13] ) . '.' . ord( $ip[14] ) . '.' . ord( $ip[15] );
			}

			$hex           = bin2hex( $ip );
			$groups        = str_split( $hex, 4 );
			$in_collapse   = false;
			$done_collapse = false;
			foreach ( $groups as $index => $group ) {
				if ( $group == '0000' && ! $done_collapse ) {
					if ( $in_collapse ) {
						$groups[ $index ] = '';
						continue;
					}
					$groups[ $index ] = ':';
					$in_collapse      = true;
					continue;
				}
				if ( $in_collapse ) {
					$done_collapse = true;
				}
				$groups[ $index ] = ltrim( $group, '0' );
				if ( strlen( $groups[ $index ] ) === 0 ) {
					$groups[ $index ] = '0';
				}
			}
			$ip = join( ':', array_filter( $groups, 'strlen' ) );
			$ip = str_replace( ':::', '::', $ip );

			return $ip == ':' ? '::' : $ip;
		}

		return false;
	}

    /**
     * Return the packed binary string of an IPv4 or IPv6 address.
     *
     * @param string $ip
     *
     * @return string
     */
	private function inetPton( $ip ) {
		return str_pad( self::isIPv6Support() ? @inet_pton( $ip ) : $this->_inetPton( $ip ), 16, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00\x00\x00", STR_PAD_LEFT );
	}

	private function _inetPton( $ip ) {
		// IPv4
		if ( preg_match( '/^(?:\d{1,3}(?:\.|$)){4}/', $ip ) ) {
			$octets = explode( '.', $ip );

			return chr( $octets[0] ) . chr( $octets[1] ) . chr( $octets[2] ) . chr( $octets[3] );
		}

		// IPv6
		if ( preg_match( '/^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i', $ip ) ) {
			if ( $ip === '::' ) {
				return "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			}
			$colon_count   = substr_count( $ip, ':' );
			$dbl_colon_pos = strpos( $ip, '::' );
			if ( $dbl_colon_pos !== false ) {
				$ip = str_replace( '::', str_repeat( ':0000', ( ( $dbl_colon_pos === 0 || $dbl_colon_pos === strlen( $ip ) - 2 ) ? 9 : 8 ) - $colon_count ) . ':', $ip );
				$ip = trim( $ip, ':' );
			}

			$ip_groups = explode( ':', $ip );
			$ipv6_bin  = '';
			foreach ( $ip_groups as $ip_group ) {
				$ipv6_bin .= pack( 'H*', str_pad( $ip_group, 4, '0', STR_PAD_LEFT ) );
			}

			return strlen( $ipv6_bin ) === 16 ? $ipv6_bin : false;
		}

		// IPv4 mapped IPv6
		if ( preg_match( '/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $ip, $matches ) ) {
			$octets = explode( '.', $matches[1] );

			return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . chr( $octets[0] ) . chr( $octets[1] ) . chr( $octets[2] ) . chr( $octets[3] );
		}

		return false;
	}

	/**
	 * Verify PHP was compiled with IPv6 support.
	 *
	 * @return bool
	 */
	private function isIPv6Support() {
		return defined( 'AF_INET6' );
	}

	/**
	 * Check and validate IP
	 *
	 * @param $ip
	 *
	 * @return bool
	 */
	private function isValidIP( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Checks an IP to see if it is within a private range
	 *
	 * @param  string  $ip
	 *
	 * @return bool
	 */
	public function isPrivate( $ip ) {

		$private_ips = array(
			'10.0.0.0|10.255.255.255', // single class A network
			'172.16.0.0|172.31.255.255', // 16 contiguous class B network
			'192.168.0.0|192.168.255.255', // 256 contiguous class C network
			'169.254.0.0|169.254.255.255', // Link-local address also referred to as Automatic Private IP Addressing
			'127.0.0.0|127.255.255.255' // localhost
		);

		$long_ip = ip2long( $ip );
		if ( $long_ip != - 1 ) {

			foreach ( $private_ips as $private_ip ) {
				list ( $start, $end ) = explode( '|', $private_ip );

				// If it is a private IP address
				if ( $long_ip >= ip2long( $start ) && $long_ip <= ip2long( $end ) ) {
					return true;
				}
			}
		}

		return false;
	}



}
