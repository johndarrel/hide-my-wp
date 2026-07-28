<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_IpAddress {

	const TYPE_IPV4 = 4;
	const TYPE_IPV6 = 6;

	const LENGTH_IPV4 = 4;
	const LENGTH_IPV6 = 16;

	const SEPARATOR_IPV4 = '.';
	const SEPARATOR_IPV6 = ':';

	private static $SEPARATORS = array( self::SEPARATOR_IPV4, self::SEPARATOR_IPV6 );

	private $humanReadable;
	private $binary;
	private $type;

	protected function init( $humanReadable, $binary ) {
		$this->humanReadable = $humanReadable;
		$this->binary        = $binary;
		$this->type          = $this->resolveType( $binary );

		return $this;
	}

	public function getHumanReadable() {
		return $this->humanReadable;
	}

	public function getBinary() {
		return $this->binary;
	}

	public function getType() {
		return $this->type;
	}

	private function resolveType( $binary ) {
		return strlen( $binary ) === self::LENGTH_IPV6 ? self::TYPE_IPV6 : self::TYPE_IPV4;
	}

	/**
	 * Create an IpAddress instance from a human-readable string
	 *
	 * @param string $humanReadable a human-readable IP address
	 *
	 * @return HMWP_Models_Geoip_IpAddress
	 * @throws Exception if $humanReadable is not a valid human-readable IP address
	 */
	public function createFromHumanReadable( $humanReadable ) {
		$binary = inet_pton( $humanReadable );
		if ( $binary === false ) {
			/* translators: 1: Human-readable IP address */
			throw new \Exception( sprintf( esc_html( 'IP address "%1$s" is malformed' ), esc_html( $humanReadable ) ) );
		}

		return HMWP_Classes_ObjController::newInstance( 'HMWP_Models_Geoip_IpAddress' )->init( $humanReadable, $binary );
	}

	/**
	 * Create an IpAddress instance from a binary string
	 *
	 * @param string $binary a binary IP address
	 *
	 * @return HMWP_Models_Geoip_IpAddress
	 * @throws Exception if $binary is not a valid binary IP address
	 */
	public function createFromBinary( $binary ) {
		$humanReadable = inet_ntop( $binary );
		if ( $humanReadable === false ) {
			/* translators: 1: Binary IP address data in hex format */
			throw new \Exception( sprintf( esc_html( 'Binary IP address data is invalid: %1$s' ), esc_html( bin2hex( $binary ) ) ) );
		}

		return HMWP_Classes_ObjController::newInstance( 'HMWP_Models_Geoip_IpAddress' )->init( $humanReadable, $binary );
	}

	/**
	 * Create an IpAddress instance from an unknown string representation
	 *
	 * @param string $string either a human-readable or binary IP address
	 *
	 * @return HMWP_Models_Geoip_IpAddress
	 * @throws Exception if $string cannot be parsed as a valid IP address
	 */
	public function createFromString( $string ) {
		foreach ( self::$SEPARATORS as $separator ) {
			if ( strpos( $string, $separator ) !== false ) {
				try {
					return $this->createFromHumanReadable( $string );
				} catch ( Exception $e ) {
					break;
				}
			}
		}

		return $this->createFromBinary( $string );
	}

	public function __toString() {
		return $this->getHumanReadable();
	}

}