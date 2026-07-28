<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_IntegerParser {

	public function parseUnsigned( $handle, $length ) {
		$value = 0;
		for ( $i = 0; $i < $length; $i ++ ) {
			$byte  = $handle->readByte();
			$value = ( $value << 8 ) + $byte;
		}

		return $value;
	}

}