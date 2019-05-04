<?php
/**
 * Mecloud helper functions
 *
 * @package Medoud
 * @category Helpers
 */

/**
 * Get the medoud singleton instance.
 */
function medoud() {
	if ( empty( $GLOBALS['mecloud'] ) ) {
		$GLOBALS['mecloud'] = Medoud::instance();
	}
	return $GLOBALS['mecloud'];
}
