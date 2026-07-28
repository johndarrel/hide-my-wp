<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_DatabaseMetadata {

	const MAX_LENGTH = 131072; //128 * 1024;

	const FIELD_MAJOR_VERSION = 'binary_format_major_version';
	const FIELD_NODE_COUNT = 'node_count';
	const FIELD_RECORD_SIZE = 'record_size';
	const FIELD_IP_VERSION = 'ip_version';
	const FIELD_BUILD_EPOCH = 'build_epoch';

	private $data;

	/**
	 * Initialize
	 *
	 * @param $data
	 *
	 * @return HMWP_Models_Geoip_DatabaseMetadata $this
	 */
	public function init( $data ) {
		$this->data = $data;

		return $this;
	}

	private function getField( $key, $default = null, &$exists = null ) {

		if ( ! array_key_exists( $key, $this->data ) ) {
			$exists = false;

			return $default;
		}

		$exists = true;

		return $this->data[ $key ];
	}

	/**
	 * Retrieve a required metadata field value
	 *
	 * @param string $key Metadata field key
	 *
	 * @return mixed Returns the value of the metadata field
	 * @throws \Exception If the metadata field is missing
	 */
	private function requireField( $key ) {

		$value = $this->getField( $key, null, $exists );

		if ( ! $exists ) {
			/* translators: 1: Metadata field key */
			throw new \Exception( sprintf( esc_html( 'Metadata field %1$s is missing' ), esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate that the field value is an integer.
	 *
	 * @param string $key The key of the field to validate.
	 *
	 * @return int The validated integer value of the field.
	 *
	 * @throws \Exception If the field value is not an integer.
	 */
	public function requireInteger( $key ) {

		$value = $this->requireField( $key );

		if ( ! is_int( $value ) ) {
			/* translators: 1: Field key, 2: Received value */
			throw new \Exception( sprintf( esc_html( 'Field %1$s should be an integer.' ), esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Retrieves the major version.
	 *
	 * @return int The major version as an integer.
	 * @throws Exception
	 */
	public function getMajorVersion() {
		return $this->requireInteger( self::FIELD_MAJOR_VERSION );
	}

	/**
	 * Retrieve the node count as an integer.
	 *
	 * @return int The node count.
	 * @throws Exception
	 */
	public function getNodeCount() {
		return $this->requireInteger( self::FIELD_NODE_COUNT );
	}

	/**
	 * Retrieve the record size.
	 *
	 * @return int The record size as an integer value.
	 * @throws Exception
	 */
	public function getRecordSize() {
		return $this->requireInteger( self::FIELD_RECORD_SIZE );
	}

	/**
	 * Retrieve the IP version.
	 *
	 * @return int The IP version as an integer.
	 * @throws Exception
	 */
	public function getIpVersion() {
		return $this->requireInteger( self::FIELD_IP_VERSION );
	}

	/**
	 * Retrieves the build epoch value.
	 *
	 * @return int The build epoch as an integer.
	 * @throws Exception
	 */
	public function getBuildEpoch() {
		return $this->requireInteger( self::FIELD_BUILD_EPOCH );
	}

	/**
	 * @param $handle
	 *
	 * @return HMWP_Models_Geoip_DatabaseMetadata|null
	 * @throws Exception
	 */
	public function parse( $handle ) {

		/** @var HMWP_Models_Geoip_DataFieldParser $dataFieldParser */
		$dataFieldParser = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_DataFieldParser' );

		$offset = $handle->getPosition();
		$parser = $dataFieldParser->init( $handle, $offset );
		$value  = $parser->parseField();

		if ( ! is_array( $value ) ) {
			/* translators: 1: Received value */
			throw new \Exception( esc_html( 'Unexpected field type found when metadata map was expected.' ) );
		}

		return HMWP_Classes_ObjController::newInstance( 'HMWP_Models_Geoip_DatabaseMetadata' )->init( $value );
	}

}