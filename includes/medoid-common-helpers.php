<?php

if ( ! function_exists( 'array_get' ) ) {
	function array_get( $array, $key, $defaultValue = false ) {
		$keys = explode( '.', $key );
		foreach ( $keys as $key ) {
			if ( ! isset( $array[ $key ] ) ) {
				return $defaultValue;
			}
			$value = $array = $array[ $key ];
		}
		return $value;
	}
}

function is_medoid_debug() {
	return defined( 'MEDOID_DEBUG' ) && MEDOID_DEBUG;
}

function medoid_get_wp_image_sizes( $size ) {
	if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
		return array(
			'width'  => get_option( $size . '_size_w' ),
			'height' => get_option( $size . '_size_h' ),
			'crop'   => (bool) get_option( $size . '_crop' ),
		);
	}

	$get_intermediate_image_sizes = get_intermediate_image_sizes();
	if ( ! $size || ! in_array( $size, $get_intermediate_image_sizes ) ) {
		return false;
	}
	// Get additional image sizes;
	$wp_additional_image_sizes = wp_get_additional_image_sizes();

	return $wp_additional_image_sizes[ $size ];
}


function medoid_get_image_sizes( $size ) {
	if ( empty( $size ) ) {
		return false;
	}
	if ( is_string( $size ) && preg_match( '/\d{1,}x{1,}/', $size ) ) {
		$size = explode( 'x', $size );
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
			$prefix = 'untils/';
		}
		$ret = sprintf( '%s%s', $prefix, $new_file );

		try {
			if ( ! $medoid_cloud instanceof Medoid_Cloud || $medoid_cloud->is_exists( $ret ) ) {
				$ret = sprintf( '%s/%s-%s', $prefix, date( 'Y-m-d-His' ), $new_file );
			}
		} catch ( Exception $e ) {
			Logger::get( 'medoid' )->error( $e->getMessage() );
			return false;
		}

		return $ret;
	}
	return sprintf( '%s-%s', date( 'Y-m-d-His' ), $new_file );
}

function medoid_create_parent_prefix_from_post( $post ) {
	$current_slug = '';
	if ( $post->post_parent > 0 ) {
		$parent = get_post( $post->post_parent );
		if ( $parent ) {
			$current_slug .= sprintf( '%s/', $parent->post_name );
			$current_slug  = sprintf( '%s%s', medoid_create_parent_prefix_from_post( $parent ), $current_slug );
		}
	}

	return $current_slug;
}

function update_image_guid_after_upload_success( $image, $response, $cloud ) {
	global $wpdb;
	if ( $wpdb->update( $wpdb->posts, array( 'guid' => $response->get_url() ), array( 'ID' => $image->post_id ) ) ) {
		delete_post_meta( $image->post_id, '_wp_attached_file' );
		Logger::get( 'medoid' )->debug(
			sprintf( 'Update attachment #%d with value "%s" is successful', $image->post_id, $response->get_url() )
		);
	} else {
		Logger::get( 'medoid' )->debug(
			sprintf( 'Update attachment #%d with value "%s" is failed', $image->post_id, $response->get_url() )
		);
	}
}
add_action( 'medoid_upload_cloud_image', 'update_image_guid_after_upload_success', 10, 3 );

function medoid_check_can_delete_image_files_on_local( $attachment_id, $current_medoid_id ) {
	$sql = DB::prepare(
		'SELECT COUNT(ID)
		FROM ' . DB::get_table( 'medoid_images' ) . '
		WHERE (is_uploaded=0 OR cloud_id=0) AND ID <> %d AND post_id=%d',
		$current_medoid_id,
		$attachment_id
	);
	return (int) DB::get_var( $sql ) <= 0;
}

function medoid_update_attachment_metadata( $image, $response, $cloud ) {
	if ( $image->post_id <= 0 ) {
		Logger::get( 'medoid' )->warning(
			sprintf( 'The medoid image is not contains post_id: %s', var_export( $image, true ) ),
			(array) $image
		);
		return;
	}
	$attachment_id = $image->post_id;
	$meta          = wp_get_attachment_metadata( $attachment_id );

	if ( ! metadata_exists( 'post', $attachment_id, 'medoid_backup_metadata' ) ) {
		update_post_meta( $attachment_id, 'medoid_backup_metadata', $meta );
	}

	// Delete attachment sizes meta
	if ( isset( $meta['sizes'] ) ) {
		unset( $meta['sizes'] );
	}
	wp_update_attachment_metadata( $attachment_id, $meta );
	Logger::get( 'medoid' )->debug(
		sprintf(
			'The attachment metadata of #%d is deleted',
			$attachment_id
		)
	);
}
add_action( 'medoid_upload_cloud_image', 'medoid_update_attachment_metadata', 10, 3 );

function delete_image_files_after_upload( $image, $response, $cloud ) {
	if ( empty( $image->delete_local_file ) ) {
		return;
	}
	if ( $image->post_id <= 0 ) {
		Logger::get( 'medoid' )->warning(
			sprintf( 'The medoid image is not contains post_id: %s', var_export( $image, true ) ),
			(array) $image
		);
		return;
	}

	$attachment_id = $image->post_id;
	if ( ! medoid_check_can_delete_image_files_on_local( $attachment_id, $image->ID ) ) {
		Logger::get( 'medoid' )->debug(
			sprintf(
				'The attachment #%d is used on other cloud so it can not delete',
				$attachment_id
			)
		);
		return;
	}
	$meta = get_post_meta( $attachment_id, 'medoid_backup_metadata', true );
	if ( ! $meta ) {
		$meta = wp_get_attachment_metadata( $attachment_id );
	}
	$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
	$file         = get_attached_file( $attachment_id );

	wp_delete_attachment_files( $attachment_id, $meta, $backup_sizes, $file );
	Logger::get( 'medoid' )->debug( sprintf( 'The attachment files of #%s are deleted', $attachment_id ) );

	// Delete medoid_backup_metadata post meta when files are deleted
	delete_post_meta( $attachment_id, 'medoid_backup_metadata' );
	Logger::get( 'medoid' )->debug( sprintf( 'The backup metadata of #%d attachment is deleted', $attachment_id ) );
}
add_action( 'medoid_upload_cloud_image', 'delete_image_files_after_upload', 10, 3 );

// Convert file name to ASCII characters
function medoid_remove_accents_file_name( $filename ) {
	$extension = pathinfo( $filename, PATHINFO_EXTENSION );
	if ( $extension ) {
		$filename = str_replace( '.' . $extension, '', $filename );
		return sprintf( '%s.%s', remove_accents( $filename ), $extension );
	}
	return remove_accents( $filename );
}
