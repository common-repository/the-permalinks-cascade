<?php
namespace ThePermalinksCascade\utilities;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 * ------------------------------------------------------------- */


/**
 * @uses get_option()
 * @uses date_i18n()
 *
 * @version 1.0.1
 *
 * @param int $gm_time
 * @return string
 */
function gmt_to_local_date( $gm_time ) {
    static $format          = '';
    static $timezone_offset = '';
    
    if ( $gm_time <= 0 )
        return '-';
        
    if (! $format ) {
        $format = get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' );
    }

    if (! $timezone_offset ) {
        $timezone_offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
    }

    return date_i18n( $format, $gm_time + $timezone_offset );
}

/**
 * @version 1.0
 *
 * @param int $time
 * @return string
 */
function time_since( $time ) {
    static $units = array();

    if ( $time <= 0 ) {
        return '-';
    }

    if (! $units ) {
        $units = array( 
            86400 => array(
                __( '1 day ago', 'the-permalinks-cascade' ),
                __( '%d days ago', 'the-permalinks-cascade' )
            ),
            3600  => array(
                __( '1 hour ago', 'the-permalinks-cascade' ),
                __( '%d hours ago', 'the-permalinks-cascade' )
            ),
            60    => array(
                __( '1 minute ago', 'the-permalinks-cascade' ),
                __( '%d minutes ago', 'the-permalinks-cascade' )
            ),
            1     => array(
                __( '1 second ago', 'the-permalinks-cascade' ),
                __( '%d seconds ago', 'the-permalinks-cascade' )
            )
        );   
    }

    $now   = time();
    $delta = $now - $time;
    
    foreach ( $units as $unit => $time_since_formats ) {
        if ( $delta >= $unit ) {
            $num = round( $delta / $unit );
            
            return sprintf( $time_since_formats[(int)( $num > 1 )], $num );
        }
    }
    
    // Returned if $delta < 1
    return $units[1][0];
}

/**
 * @version 1.0
 *
 * @param string $sentence
 * @param int $length
 * @return string
 */
function truncate_sentence( $sentence, $length ) {
	$sentence = trim( $sentence );

	if ( strlen( $sentence ) > $length ) {
        $sentence = substr( $sentence, 0, $length );
        
        // If the string doesn't end with a space, 
        // removes the last, probably truncated, word.
        if ( ' ' != $sentence[$length - 1] ) {
        	$sentence = substr( $sentence, 0, strrpos( $sentence, ' ' ) );
        }
        
        $sentence  = rtrim( $sentence, ' .,;:?!' );
        $sentence .= '...';
    }

    return $sentence;
}