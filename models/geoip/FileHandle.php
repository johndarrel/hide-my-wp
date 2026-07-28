<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_FileHandle {

	const POSITION_START = 0;

	const DIRECTION_REVERSE = - 1;

	const CHUNK_SIZE_DEFAULT = 1024;

	private $resource;
	private $close;

	public function init( $resource, $close = true ) {
		$this->resource = $resource;
		$this->close    = $close;

		return $this;
	}

	public function __destruct() {
		if ( $this->close && is_resource( $this->resource ) ) {
			fclose( $this->resource ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
	}

	/**
	 * Moves the file pointer to a specified location.
	 *
	 * @param int $offset The offset to seek to.
	 * @param int $whence The position from where the offset is applied. Defaults to SEEK_SET.
	 *                    SEEK_SET - Set position equal to the offset.
	 *                    SEEK_CUR - Set position to current location plus the offset.
	 *                    SEEK_END - Set position to end-of-file plus the offset.
	 *
	 * @return void
	 *
	 * @throws \Exception If seeking to the specified offset fails.
	 */
	public function seek( $offset, $whence = SEEK_SET ) {
		if ( fseek( $this->resource, $offset, $whence ) !== 0 ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
			/* translators: 1: File offset */
			throw new \Exception( sprintf( esc_html__( 'Seeking file to offset %1$s failed', 'hide-my-wp' ), esc_html( $offset ) ) );
		}
	}

	/**
	 * Retrieves the current position of the file pointer within the resource.
	 *
	 * @return int The current position in the file.
	 * @throws \Exception If retrieving the file position fails.
	 */
	public function getPosition() {
		$position = ftell( $this->resource ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftell
		if ( $position === false ) {
			throw new \Exception( esc_html__( 'Retrieving current position in file failed', 'hide-my-wp' ) );
		}

		return $position;
	}


	public function isAtStart() {
		return $this->getPosition() === self::POSITION_START;
	}

	public function isAtEnd() {
		return feof( $this->resource ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_feof
	}

	/**
	 * Reads a specified number of bytes from the file resource.
	 *
	 * @param int $length The number of bytes to read from the file.
	 *
	 * @return string The data read from the file.
	 * @throws \Exception If the read operation fails.
	 */
	public function read( $length ) {
		$read = fread( $this->resource, $length ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		if ( $read === false ) {
			/* translators: 1: Number of bytes attempted to read */
			throw new \Exception( sprintf( esc_html__( 'Reading %1$s byte(s) from file failed', 'hide-my-wp' ), esc_html( $length ) ) );
		}

		return $read;
	}

	/**
	 * Reads and returns a single byte from the source as its ASCII value.
	 *
	 * @return int The ASCII value of the read byte.
	 * @throws Exception
	 */
	public function readByte() {
		return ord( $this->read( 1 ) );
	}

	/**
	 * Reads data in chunks of a specified size until all available data is read.
	 *
	 * @param int $chunkSize The size of each chunk to read. Defaults to the class-defined constant CHUNK_SIZE_DEFAULT.
	 *
	 * @return string The accumulated data read from the source.
	 * @throws Exception
	 */
	public function readAll( $chunkSize = self::CHUNK_SIZE_DEFAULT ) {
		$data = '';
		do {
			$chunk = $this->read( $chunkSize );
			if ( empty( $chunk ) ) {
				break;
			}
			$data .= $chunk;
		} while ( true );

		return $data;
	}

	/**
	 * Locates a specified string within a stream, searching in the given direction and optionally up to a specified limit.
	 *
	 * @param string $string The string to locate within the stream.
	 * @param int $direction The direction to search in, either forward or reverse. Should match pre-defined direction constants.
	 * @param int|null $limit Optional. The maximum distance to search from the starting position. Defaults to null for no limit.
	 * @param bool $after Optional. If true, returns the position after the string; otherwise, returns the position of the string's start. Defaults to false.
	 *
	 * @return int|null The position of the located string or null if the string is not found within the specified criteria.
	 * @throws Exception If an error occurs during the seek or read operations.
	 */
	public function locateString( $string, $direction, $limit = null, $after = false ) {
		$searchStart = $limit === null ? null : $this->getPosition();
		$length      = strlen( $string );
		$position    = $searchStart;
		if ( $direction === self::DIRECTION_REVERSE ) {
			$position -= $length;
		}
		do {
			try {
				$this->seek( $position, SEEK_SET );
			} catch ( \Exception $e ) {
				//This assumes that a seek failure means that the target position is out of range (and hence the search just needs to stop rather than throwing an exception)
				break;
			}
			$test = $this->read( $length );
			if ( $test === $string ) {
				return $position + ( $after ? $length : 0 );
			}
			$position += $direction;
		} while ( $limit === null || abs( $position - $searchStart ) < $limit );

		return null;
	}

}