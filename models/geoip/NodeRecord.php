<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_NodeRecord {

	private $reader;
	private $value;

	public function init( $reader, $value ) {
		$this->reader = $reader;
		$this->value  = $value;

		return $this;
	}

	public function getValue() {
		return $this->value;
	}

	public function isNodePointer() {
		return $this->value < $this->reader->getNodeCount();
	}

	/**
	 * Retrieves the next node associated with the current record.
	 *
	 * This method checks if the current record is a valid node pointer before attempting
	 * to read and return the next node. If the record is not a node pointer, or if an error occurs
	 * while reading the node, an exception is thrown.
	 *
	 * @return mixed The next node retrieved by the reader.
	 * @throws \Exception If an invalid node pointer is found in the database.
	 *
	 * @throws \Exception If the record is not a node pointer.
	 */
	public function getNextNode() {
		if ( ! $this->isNodePointer() ) {
			throw new \Exception( esc_html( 'The next node was requested for a record that is not a node pointer' ) );
		}

		try {
			return $this->reader->read( $this->getValue() );
		} catch ( \Exception $e ) {
			throw new \Exception( esc_html( 'Invalid node pointer found in database'), esc_attr($e->getCode()) );
		}

	}

	public function isNullPointer() {
		return $this->value === $this->reader->getNodeCount();
	}

	public function isDataPointer() {
		return $this->value > $this->reader->getNodeCount();
	}

	/**
	 * Calculates and returns the address of the data associated with the current record.
	 *
	 * This method verifies that the current record is a valid data pointer before attempting
	 * to compute the data address. If the record is not a data pointer, an exception is thrown.
	 * The data address is calculated based on the record's value, the number of nodes, and the size
	 * of the search tree section as determined by the reader.
	 *
	 * @return int The computed data address.
	 * @throws \Exception If the record is not a data pointer.
	 */
	public function getDataAddress() {

		if ( ! $this->isDataPointer() ) {
			throw new \Exception( esc_html( 'The data address was requested for a record that is not a data pointer' ) );
		}

		return $this->value - $this->reader->getNodeCount() + $this->reader->getSearchTreeSectionSize();
	}

}