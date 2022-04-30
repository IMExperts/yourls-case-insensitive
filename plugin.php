<?php
/*
Plugin Name: Case insensitive YOURLS
Plugin URI: https://github.com/IMExperts/yourls-case-insensitive
Description: Makes YOURLS case insensitive
Version: 1.1
Author: IMExperts
Author URI: http://theseotools.net
YOURLS Version: 1.9+
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Hook our custom function into the 'add_new_link' filter
yourls_add_filter( 'shunt_keyword_is_taken', 'insensitive_keyword_is_taken' );
yourls_add_filter( 'shunt_get_keyword_info', 'insensitive_get_keyword_info' );
yourls_add_filter( 'shunt_update_clicks', 'insensitive_update_clicks' );

// If the keyword exists, display the long URL in the error message
function insensitive_keyword_is_taken( $return, $keyword ) {


	$ydb = yourls_get_db();
	$keyword = yourls_sanitize_keyword( $keyword );
	$taken = false;
	$table = YOURLS_DB_TABLE_URL;
	$already_exists = $ydb->fetchObjects( "SELECT count(*) as cnt FROM `$table` WHERE LOWER(`keyword`) = LOWER('$keyword');" );
	$value = json_decode(json_encode($already_exists), true);
	if ( $value[0]["cnt"] == 0 )
	{
		$taken = false;
	}
	else{
		$taken = true;
	}


	return yourls_apply_filter( 'keyword_is_taken', $taken, $keyword );
}

function insensitive_get_keyword_infos( $keyword, $use_cache = true ) {


    $ydb = yourls_get_db();
    $keyword = yourls_sanitize_keyword( $keyword );

    yourls_do_action( 'pre_get_keyword', $keyword, $use_cache );

    if( $ydb->has_infos($keyword) && $use_cache === true ) {
        return yourls_apply_filter( 'get_keyword_infos', $ydb->get_infos($keyword), $keyword );
    }

    yourls_do_action( 'get_keyword_not_cached', $keyword );

    $table = YOURLS_DB_TABLE_URL;
    $infos = $ydb->fetchObject("SELECT * FROM `$table` WHERE LOWER(`keyword`) = LOWER('$keyword')");

    if( $infos ) {
        $infos = (array)$infos;
        $ydb->set_infos($keyword, $infos);
    } else {
        // is NULL if not found
        $infos = false;
        $ydb->set_infos($keyword, false);
    }

    return yourls_apply_filter( 'get_keyword_infos', $infos, $keyword );
}

function insensitive_get_keyword_info( $return, $keyword, $field, $notfound ) {


	$keyword = yourls_sanitize_keyword( $keyword );
	$infos = insensitive_get_keyword_infos( $keyword );


	$return = $notfound;
	if ( isset( $infos[ $field ] ) && $infos[ $field ] !== false )
		$return = $infos[ $field ];
	
	return yourls_apply_filter( 'get_keyword_info', $return, $keyword, $field, $notfound );
}


 function insensitive_update_clicks( $return, $keyword, $clicks = false ) {

	 
	$ydb = yourls_get_db();
	$keyword = yourls_sanitize_keyword( $keyword );
	$table = YOURLS_DB_TABLE_URL;
    error_log(var_export($keyword, true));
	if ( $clicks !== false && is_int( $clicks ) && $clicks >= 0 )
		$update = $ydb->fetchAffected( "UPDATE `$table` SET `clicks` = :clicks WHERE LOWER(`keyword`) = LOWER(:keyword)", array('clicks' => $clicks, 'keyword' => $keyword) );
	else
		$update = $ydb->fetchAffected( "UPDATE `$table` SET `clicks` = clicks + 1 WHERE LOWER(`keyword`) = LOWER(:keyword)", array('keyword' => $keyword) );

	yourls_do_action( 'update_clicks', $keyword, $update, $clicks );
	return $update;
}
