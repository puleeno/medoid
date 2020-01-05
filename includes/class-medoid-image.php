<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Image {
	public function __construct() {
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	public function init_hooks() {
		add_filter( 'image_downsize', array( $this, 'image_downsize' ), 10, 3 );
		// add_filter( 'wp_get_attachment_image_src', array( $this, 'load_image' ), 99, 3 );
	}

	public function load_image( $image, $attachment_id, $size ) {
		return $image;
	}

	public function image_downsize( $is_downsize, $id, $size ) {
		$is_image         = wp_attachment_is_image( $id );
		$img_url          = wp_get_attachment_url( $id );
		$width            = 0;
		$height           = 0;
		$is_intermediate  = false;
		$img_url_basename = wp_basename( $img_url );

		if ( $size == 'medium' ) {
			$height = 410;
			$width  = 520;
		} elseif ( $size == 'large' ) {
			$height = 540;
			$width  = 620;
		}

		if ( is_array( $size ) ) {
			$width  = $size[0];
			$height = $size[1];
		}

		// try for a new style intermediate size
		$intermediate = image_get_intermediate_size( $id, $size );

		if ( $intermediate ) {
			$img_url         = str_replace( $img_url_basename, $intermediate['file'], $img_url );
			$width           = $intermediate['width'];
			$height          = $intermediate['height'];
			$is_intermediate = true;
		} elseif ( $size === 'thumbnail' ) {
			// fall back to the old thumbnail
			$thumb_file = wp_get_attachment_thumb_file( $id );
			$info       = null;

			if ( $thumb_file ) {
				$info = @getimagesize( $thumb_file );
			}

			if ( $thumb_file && $info ) {
				$img_url         = str_replace( $img_url_basename, wp_basename( $thumb_file ), $img_url );
				$is_intermediate = true;
			}
			$width  = 320;
			$height = 400;
		}

		if ( ! $width && ! $height && isset( $meta['width'], $meta['height'] ) ) {
			// any other type: use the real image
			$width  = $meta['width'];
			$height = $meta['height'];
		}

		$str_size = '?' . http_build_query(
			array(
				'width'  => $width,
				'height' => $height,
			)
		);
		$img_url  = str_replace( 'http://new.loveofboys.io', 'https://loveofboys.com', $img_url );
		$img_url  = sprintf( 'https://imagecdn.app/v2/image/%s%s', urlencode( $img_url ), $str_size );

		if ( $img_url ) {
			return array( $img_url, $width, $height, $is_intermediate );
		}

		return false;
	}
}

new Medoid_Image();
