<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_DataFieldParser {

	private $handle;
	private $sectionOffset;

	public function init( $handle, $sectionOffset = null ) {
		$this->handle        = $handle;
		$this->sectionOffset = $sectionOffset === null ? $this->handle->getPosition() : $sectionOffset;

		return $this;
	}

	public function processControlByte() {
		return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_ControlByte' )->consume( $this->handle );
	}

	private function readStandardField( $controlByte ) {

		$size = $controlByte->getSize();
		if ( $size === 0 ) {
			return '';
		}

		return $this->handle->read( $size );
	}

	private function parseUtf8String( $controlByte ) {
		return $this->readStandardField( $controlByte );
	}

	private function parseUnsignedInteger( $controlByte ) {
		return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_IntegerParser' )->parseUnsigned( $this->handle, $controlByte->getSize() );
	}

	/**
	 * Parses a control byte to construct an associative array (map).
	 *
	 * @param object $controlByte The control byte object containing size information.
	 *
	 * @return array Returns an associative array where keys are parsed as strings and values are assigned as parsed fields.
	 * @throws \Exception If the parsed key is not a string, an exception is thrown.
	 */
	private function parseMap( $controlByte ) {

		$map = array();
		for ( $i = 0; $i < $controlByte->getSize(); $i ++ ) {
			$keyByte = $this->processControlByte();

			$key = $this->parseField( $keyByte );
			if ( ! is_string( $key ) ) {
				/* translators: 1: Key byte, 2: Key value, 3: Map value */
				throw new \Exception( sprintf( esc_html( 'Map keys must be strings, received %1$s.' ), esc_html( $keyByte ) ) );
			}

			$value       = $this->parseField();
			$map[ $key ] = $value;

		}

		return $map;
	}

	/**
	 * Parses a control byte to construct an indexed array.
	 *
	 * @param object $controlByte The control byte object containing size information.
	 *
	 * @return array Returns an indexed array where each index is assigned a parsed field value.
	 * @throws Exception
	 */
	private function parseArray( $controlByte ) {
		$array = array();
		for ( $i = 0; $i < $controlByte->getSize(); $i ++ ) {
			$array[ $i ] = $this->parseField();
		}

		return $array;
	}

	private function parseBoolean( $controlByte ) {
		return (bool) $controlByte->getSize();
	}

	/**
	 * Unpacks a single value from the provided binary data using the specified format.
	 *
	 * @param string $format The format code to use for unpacking the binary data.
	 * @param string $data The binary data to unpack.
	 * @param string $controlByte The control byte to include in exception messaging if unpacking fails.
	 *
	 * @return mixed The first value unpacked from the binary data.
	 * @throws \Exception If unpacking the binary data fails.
	 */
	private static function unpackSingleValue( $format, $data, $controlByte ) {
		$values = unpack( $format, $data );
		if ( $values === false ) {
			/* translators: 1: Control byte value */
			throw new \Exception( sprintf( esc_html( 'Unpacking field failed for %1$s' ), esc_html( $controlByte ) ) );
		}

		return reset( $values );
	}

	private static function getPackedLength( $formatCharacter ) {
		switch ( $formatCharacter ) {
			case 'E':
				return 8;
			case 'G':
			case 'l':
				return 4;
		}
		/* translators: 1: Unsupported format character */
		throw new InvalidArgumentException( sprintf( esc_html( 'Unsupported format character: %1$s' ), esc_html( $formatCharacter ) ) );
	}

	private static function usesSystemByteOrder( $formatCharacter ) {
		switch ( $formatCharacter ) {
			case 'l':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Parses data by unpacking it according to the specified format and control byte.
	 *
	 * @param int|string $controlByte The control byte used to derive the standard field data.
	 * @param string $format The format string used for unpacking and determining data alignment.
	 *
	 * @return mixed The unpacked value based on the provided format and control byte.
	 * @throws Exception
	 */
	private function parseByUnpacking( $controlByte, $format ) {

		$data = $this->readStandardField( $controlByte );
		$data = str_pad( $data, self::getPackedLength( $format ), "\0", STR_PAD_LEFT );
		if ( self::usesSystemByteOrder( $format ) ) {

			/** @var HMWP_Models_Geoip_Endianness $Endianness */
			$Endianness = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_Endianness' );

			$data = $Endianness::convert( $data, $Endianness::BIG );
		}

		return $this->unpackSingleValue( $format, $data, $controlByte );
	}

	/**
	 * Parses a pointer from the given control byte and retrieves the corresponding value.
	 *
	 * @param object $controlByte The control byte object containing the size and type data for interpretation.
	 *
	 * @return mixed The value pointed to by the resolved address in the database.
	 * @throws Exception If the resolved pointer points to another pointer, violating the MMDB specification.
	 */
	private function parsePointer( $controlByte ) {
		$data    = $controlByte->getSize();
		$size    = $data >> 3;
		$address = $data & 7;
		if ( $size === 3 ) {
			$address = 0;
		}
		for ( $i = 0; $i < $size + 1; $i ++ ) {
			$address = ( $address << 8 ) + $this->handle->readByte();
		}
		switch ( $size ) {
			case 1:
				$address += 2048;
				break;
			case 2:
				$address += 526336;
				break;
		}
		$previous = $this->handle->getPosition();
		$this->handle->seek( $this->sectionOffset + $address, SEEK_SET );
		$referenceControlByte = $this->processControlByte();

		if ( $referenceControlByte->getType() === $controlByte::TYPE_POINTER ) {
			throw new \Exception( 'Per the MMDB specification, pointers may not point to other pointers. This database does not comply with the specification.' );
		}

		$value = $this->parseField( $referenceControlByte );
		$this->handle->seek( $previous, SEEK_SET );

		return $value;
	}

	private function parseSignedInteger( $controlByte, $format ) {
		if ( $controlByte->getSize() === 0 ) {
			return 0;
		}

		return $this->parseByUnpacking( $controlByte, $format );
	}

	/**
	 * Parses a data field based on the type defined in the control byte and returns the corresponding value.
	 *
	 * @param object|null $cByte An optional reference to the control byte object. If null, a new control byte will be processed.
	 *
	 * @return mixed The parsed value based on the control byte type.
	 * @throws Exception If the control byte type is unrecognized or cannot be processed.
	 */
	public function parseField( &$cByte = null ) {

		/** @var HMWP_Models_Geoip_ControlByte $controlByte */
		$controlByte = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_ControlByte' );

		if ( $cByte === null ) {
			$cByte = $this->processControlByte();
		}

		switch ( $cByte->getType() ) {
			case $controlByte::TYPE_POINTER:
				return $this->parsePointer( $cByte );
			case $controlByte::TYPE_UTF8_STRING:
				return $this->parseUtf8String( $cByte );
			case $controlByte::TYPE_DOUBLE:
				return $this->parseByUnpacking( $cByte, 'E' );
			case $controlByte::TYPE_BYTES:
			case $controlByte::TYPE_CONTAINER:
				return $this->readStandardField( $cByte );
			case $controlByte::TYPE_UINT16:
			case $controlByte::TYPE_UINT32:
			case $controlByte::TYPE_UINT64:
			case $controlByte::TYPE_UINT128:
				return $this->parseUnsignedInteger( $cByte );
			case $controlByte::TYPE_INT32:
				return $this->parseSignedInteger( $cByte, 'l' );
			case $controlByte::TYPE_MAP:
				return $this->parseMap( $cByte );
			case $controlByte::TYPE_ARRAY:
				return $this->parseArray( $cByte );
			case $controlByte::TYPE_END_MARKER:
				return null;
			case $controlByte::TYPE_BOOLEAN:
				return $this->parseBoolean( $cByte );
			case $controlByte::TYPE_FLOAT:
				return $this->parseByUnpacking( $cByte, 'G' );
			default:
				/* translators: 1: Control byte value */
				throw new \Exception( sprintf( esc_html( 'Unable to parse data field for %1$s' ), esc_html( $cByte ) ) );
		}
	}

}