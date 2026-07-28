<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_Node {

	const SIDE_LEFT = 0;
	const SIDE_RIGHT = 1;

	private $reader;
	private $data;
	private $left, $right;

	public function init( $reader, $data ) {
		$this->reader = $reader;
		$this->data   = $data;

		return $this;
	}

	public function getRecord( $side ) {

		/** @var HMWP_Models_Geoip_NodeRecord $nodeRecord */
		$nodeRecord = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_NodeRecord' );

		$value = $this->reader->extractRecord( $this->data, $side );

		return $nodeRecord->init( $this->reader, $value );
	}

	public function getLeft() {
		return $this->getRecord( self::SIDE_LEFT );
	}

	public function getRight() {
		return $this->getRecord( self::SIDE_RIGHT );
	}

}