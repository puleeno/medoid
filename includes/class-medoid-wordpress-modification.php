<?php

class Medoid_Wordpress_Modification {
	protected $wordpress_file;

	public function __construct() {
		$this->wordpress_file = ABSPATH . 'wp-admin/includes/file.php';
	}
	public function modify_wordpress_upload() {
		$handle = fopen( $this->wordpress_file, 'r' );
		if ( false === $handle ) {
			return;
		}
		$contents = '';
		while ( ! feof( $handle ) ) {
			$contents .= fread( $handle, 8192 );
		}
		if ( empty( $contents ) || $this->check_wordpress_file_is_modified( $contents ) ) {
			fclose( $handle );
			return;
		}
		$contents = preg_replace(
			array(
				'/\/\/ Set correct file permissions./',
				'/chmod\( \$new_file, \$perms \);/',
			),
			array(
				"if ( file_exists( \$new_file ) ) { // This line added by Medoid Cloud Storage\n\t// Set correct file permissions.",
				"chmod( \$new_file, \$perms );\n\t} // Medoid end block",
			),
			$contents
		);

		$tmp_file = sprintf( '%s_tmp', $this->wordpress_file );
		copy( $this->wordpress_file, $tmp_file );
		$handle = fopen( $this->wordpress_file, 'w+' );

		if ( fwrite( $handle, $contents ) ) {
			unlink( $tmp_file );
		} else {
			rename( $tmp_file, $this->wordpress_file );
		}
	}

	public function check_wordpress_file_is_modified( $contents ) {
		return preg_match( '/This line added by Medoid Cloud Storage/', $contents );
	}
}
