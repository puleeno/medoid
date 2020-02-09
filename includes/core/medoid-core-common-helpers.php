<?php

if ( ! function_exists( 'array_get' ) ) {
	function array_get( $arr, $arrayIndex, $defaultValue = null ) {
		if ( is_string( $arrayIndex ) ) {
			$arrayIndex = explode( '.', $arrayIndex );
		} else {
			$arrayIndex = (array) $arrayIndex;
		}
		foreach ( $arrayIndex as $index ) {
			if ( ! isset( $arr[ $index ] ) ) {
				return $defaultValue;
			}
			$arr = $arr[ $index ];
		}
		return $arr;
	}
}

function medoid_get_wp_image_sizes( $size ) {
	$wp_additional_image_sizes    = wp_get_additional_image_sizes();
	$sizes                        = array();
	$get_intermediate_image_sizes = get_intermediate_image_sizes();

	// Create the full array with sizes and crop info
	foreach ( $get_intermediate_image_sizes as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {
			$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
			$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
			$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
		} elseif ( isset( $wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $wp_additional_image_sizes[ $_size ]['width'],
				'height' => $wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	if ( $size ) {
		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		} else {
			return false;
		}
	}
	return $sizes;
}


function medoid_get_image_sizes( $size ) {
	if ( empty( $size ) ) {
		return false;
	}

	$height = 0;
	$width  = 0;
	if ( is_array( $size ) ) {
		$width  = array_get( $size, 0 );
		$height = array_get( $size, 1 );

		return array(
			'width'  => $width,
			'height' => $height,
		);
	}

	return medoid_get_wp_image_sizes( $size );
}
