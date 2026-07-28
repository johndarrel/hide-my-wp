<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_Database {

	const SUPPORTED_MAJOR_VERSION = 2;
	const DELIMITER_METADATA = "\xab\xcd\xefMaxMind.com";

	private $handle;
	private $metadata;
	private $nodeSize;
	private $nodeReader;
	private $dataSectionParser;
	private $startingNodes = array();

	/**
	 * Bind to a MaxMind database using the provided handle
	 *
	 * @param resource $resource a valid stream resource that can be used to read the database
	 * @param bool $closeAutomtically if true, the provided resource will be closed automatically
	 *
	 * @throws Exception
	 */
	public function init( $resource, $closeAutomatically ) {
		/** @var HMWP_Models_Geoip_FileHandle $fileHanle */
		$fileHanle = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_FileHandle' );

		$this->handle = $fileHanle->init( $resource, $closeAutomatically );

		$this->loadMetadata();

		return $this;
	}

	/**
	 * Loads and parses the metadata from the MMDB file.
	 *
	 * @return void
	 * @throws Exception if the metadata cannot be located in the MMDB file.
	 * @throws Exception if the major version of the MMDB file is not supported by this library.
	 */
	private function loadMetadata() {
		$this->handle->seek( 0, SEEK_END );

		/** @var HMWP_Models_Geoip_FileHandle $fileHanle */
		$fileHanle = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_FileHandle' );

		/** @var HMWP_Models_Geoip_DatabaseMetadata $databaseMetadata */
		$databaseMetadata = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_DatabaseMetadata' );

		$position = $this->handle->locateString( self::DELIMITER_METADATA, $fileHanle::DIRECTION_REVERSE, $databaseMetadata::MAX_LENGTH, true );

		if ( $position === null ) {
			throw new \Exception( "Unable to locate metadata in MMDB file" );
		}

		$this->metadata = $databaseMetadata->parse( $this->handle );

		if ( $this->metadata->getMajorVersion() !== self::SUPPORTED_MAJOR_VERSION ) {
			/* translators: 1: Supported MMDB major version, 2: Provided MMDB major version */
			throw new \Exception( sprintf( esc_html( 'This library only supports parsing version %1$d of the MMDB format, a version %2$d database was provided' ), esc_attr(self::SUPPORTED_MAJOR_VERSION), esc_attr($this->metadata->getMajorVersion()) ) );
		}
	}

	/**
	 * Compute the size of a node in bytes based on the metadata record size.
	 *
	 * @return int the computed node size in bytes
	 * @throws Exception if the computed node size is not an even number of bytes
	 */
	private function computeNodeSize() {
		$nodeSize = ( $this->metadata->getRecordSize() * 2 ) / 8;

		if ( ! is_int( $nodeSize ) ) {
			/* translators: 1: Computed node size in bytes */
			throw new \Exception( sprintf( esc_html( 'Node size must be an even number of bytes, computed %1$s' ), esc_html( $this->nodeSize ) ) );
		}

		return $nodeSize;
	}

	/**
	 * Retrieves and initializes the NodeReader instance.
	 *
	 * If the NodeReader instance is not already initialized, it will create and initialize
	 * the instance using the current handle, computed node size, and metadata node count.
	 *
	 * @return HMWP_Models_Geoip_NodeReader the initialized NodeReader instance
	 * @throws Exception
	 */
	private function getNodeReader() {
		if ( $this->nodeReader === null ) {
			/** @var HMWP_Models_Geoip_NodeReader $nodeReader */
			$nodeReader = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_NodeReader' );

			$this->nodeReader = $nodeReader->init( $this->handle, $this->computeNodeSize(), $this->metadata->getNodeCount() );
		}

		return $this->nodeReader;
	}

	private function getDataSectionParser() {

		if ( $this->dataSectionParser === null ) {

			/** @var HMWP_Models_Geoip_DataFieldParser $dataFieldParser */
			$dataFieldParser = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_DataFieldParser' );

			$offset                  = $this->getNodeReader()->getSearchTreeSectionSize() + 16; //16 null bytes separate the two sections
			$this->dataSectionParser = $dataFieldParser->init( $this->handle, $offset );
		}

		return $this->dataSectionParser;
	}

	/**
	 * Retrieve the metadata for this database
	 *
	 * @return HMWP_Models_Geoip_DatabaseMetadata
	 */
	public function getMetadata() {
		return $this->metadata;
	}

	/**
	 * Search the database for the given IP address
	 *
	 * @param HMWP_Models_Geoip_IpAddress|string $ip the IP address for which to search
	 *    A human readable (as accepted by inet_pton) or binary (as accepted by inet_ntop) string may be provided or an instance of IpAddressInterface
	 *
	 * @return array|null the matched record or null if no record was found
	 * @throws Exception if $ip is a string that cannot be parsed as a valid IP address
	 * @throws Exception if the database IP version and the version of the provided IP address are incompatible (specifically, if an IPv6 address is passed and the database only supports IPv4)
	 */
	public function search( $ip ) {

		/** @var HMWP_Models_Geoip_IpAddress $ipAddress */
		$ipAddress = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_IpAddress' );

		if ( is_string( $ip ) ) {
			$ip = $ipAddress->createFromString( $ip );
		} elseif ( ! $ip instanceof HMWP_Models_Geoip_IpAddress ) {
			/* translators: 1: Received IP value */
			throw new \Exception( esc_html( 'IP address must be either a human readable string (presentation format), a binary string (network format), or an instance of Wordfence\MmdbReader\IpAddressInterface, received: %1$s' ) );
		}

		if ( $this->metadata->getIpVersion() === $ipAddress::TYPE_IPV4 && $ip->getType() === $ipAddress::TYPE_IPV6 ) {
			throw new \Exception( 'This database only support IPv4 addresses, but the provided address is an IPv6 address' );
		}

		return $this->searchNodes( $ip );
	}

	/**
	 * Resolves the starting node in the database traversal based on the provided IP address type.
	 *
	 * @param int $type The type of the IP address (e.g., TYPE_IPV4 or TYPE_IPV6). Determines
	 *    how the traversal should adapt to database configurations.
	 *
	 * @return mixed The starting node or record based on the traversal logic.
	 * @throws Exception
	 */
	private function resolveStartingNode( $type ) {

		/** @var HMWP_Models_Geoip_IpAddress $ipAddress */
		$ipAddress = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_IpAddress' );

		$node = $this->getNodeReader()->read( 0 );

		if ( $type === $ipAddress::TYPE_IPV4 && $this->metadata->getIpVersion() === $ipAddress::TYPE_IPV6 ) {
			$skippedBits = ( $ipAddress::LENGTH_IPV6 - $ipAddress::LENGTH_IPV4 ) * 8;
			while ( $skippedBits -- > 0 ) {
				$record = $node->getLeft();
				if ( $record->isNodePointer() ) {
					$node = $record->getNextNode();
				} else {
					return $record;
				}
			}
		}

		return $node;
	}

	/**
	 * Retrieves the starting node for the given type.
	 *
	 * @param mixed $type The type for which to resolve and retrieve the starting node.
	 *
	 * @return mixed The starting node associated with the given type.
	 * @throws Exception
	 */
	private function getStartingNode( $type ) {
		$this->startingNodes[ $type ] = $this->resolveStartingNode( $type );

		return $this->startingNodes[ $type ];
	}

	/**
	 * Searches nodes in the database for the given IP address.
	 *
	 * @param HMWP_Models_Geoip_IpAddress $ip An instance of IP address containing the binary
	 *     representation and type of the address to search for.
	 *
	 * @return array|null The matched record data or null if no matching record is found.
	 * @throws Exception If the starting node cannot be determined based on the IP type.
	 */
	private function searchNodes( $ip ) {
		$key = $ip->getBinary();

		$byteCount  = strlen( $key );
		$node       = $this->getStartingNode( $ip->getType() );
		$record     = null;
		if ( $node instanceof HMWP_Models_Geoip_Node ) {
			for ( $byteIndex = 0; $byteIndex < $byteCount; $byteIndex ++ ) {
				$byte = ord( $key[ $byteIndex ] );
				for ( $bitOffset = 7; $bitOffset >= 0; $bitOffset -- ) {
					$bit    = ( $byte >> $bitOffset ) & 1;
					$record = $node->getRecord( $bit );
					if ( $record->isNodePointer() ) {
						$node = $record->getNextNode();
					} else {
						break 2;
					}
				}
			}
		} else {
			$record = $node;
		}

		if ( $record->isNullPointer() ) {
			return null;
		} elseif ( $record->isDataPointer() ) {

			$this->handle->seek( $record->getDataAddress(), SEEK_SET );

			return $this->getDataSectionParser()->parseField();
		} else {
			return null;
		}
	}

	/**
	 * Open the MMDB file at the given path
	 *
	 * @param string $path the path of an MMDB file
	 *
	 * @throws Exception if unable to open the file at the provided path
	 */
	public function open( $path ) {
		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( $handle === false ) {
			/* translators: 1: MMDB file path */
			throw new \Exception( sprintf( esc_html__( 'Unable to open MMDB file at %1$s', 'hide-my-wp' ), esc_html( $path ) ) );
		}

		return HMWP_Classes_ObjController::newInstance( 'HMWP_Models_Geoip_Database' )->init( $handle, true );
	}

}