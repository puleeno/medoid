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
	$sizes                        = array();
	$wp_additional_image_sizes    = wp_get_additional_image_sizes();
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

function medoid_create_file_name_unique( $new_file, $image, $medoid_cloud ) {
	if ( gettype( $image ) === 'object' ) {
		$attachment = get_post( $image->post_id );
		if ( ! $attachment ) {
			return false;
		}

		if ( $attachment->post_parent > 0 ) {
			$prefix = medoid_create_parent_prefix_from_post( $attachment );
		} else {
			$prefix = 'untils';
		}
		$ret = sprintf( '%s/%s', $prefix, $new_file );

		if ( ! $medoid_cloud instanceof Medoid_Cloud || $medoid_cloud->is_exists( $ret ) ) {
			$ret = sprintf( '%s/%s-%s', $prefix, date( 'Y-m-d-His' ), $new_file );
		}

		return $ret;
	}
	return sprintf( '%s-%s', date( 'Y-m-d-His' ), $new_file );
}

function medoid_create_parent_prefix_from_post( $post, $current_slug = '' ) {
	if ( $post->post_parent > 0 ) {
		$parent = get_post( $post->post_parent );
		if ( $parent ) {
			$current_slug .= sprintf( '/%s', $parent->post_name );
			$current_slug .= medoid_create_parent_prefix_from_post( $parent, $current_slug );
		}
	}

	return $current_slug;
}
