<?php
/* Random Quote Machine server project for FreeCodeCamp
by Marek Jedliński marek.jedlinski@gmail.com; http://q.tranglos.com */
header('Content-type: application/json');
header("Access-Control-Allow-Origin: *");

// error_reporting(E_ALL ^ E_WARNING);

// main response codes (exitcodes)
const QUOTERR_RESP_OK        = 0;  // success
const QUOTERR_RESP_NODATA    = 1;  // db returned no results ( malformed SQL, other db errors)
const QUOTERR_RESP_EXCEPTION = 2;  // exception from php
const QUOTERR_RESP_BADID     = 3;  // caller specified rowid which does not xist


// drop any higher ids in GET request
//(NOT the real count of db records, just a sanity check)
const QUOTERR_MAX_REQUEST_NUM = 99999;

const QUOTERR_MODE_RANDOM  = 1;
const QUOTERR_MODE_TWITTER = 2;
const QUOTERR_MODE_ROWID   = 3;

// prepare response
$response['version'] = '1';
// for error handling: set these dummy values to ensure
// that response always contains these properties.
$response['exitcode'] = QUOTERR_RESP_OK; // 0=success; 1=no result from query 2=exception
$response['quote'] = []; // quote data


// SELECT_DEFAULT selects from ALL quotes.
// SELECT_TWITTER only selects from quotes short enough for twitter
// (in the db, [fullen] is quote len + author len + 3)
const SELECT_DEFAULT = 'SELECT [id], [text], [author], [source], [extra], [link] from [quotes] WHERE [id] IN (SELECT [id] FROM [quotes] ORDER BY RANDOM() LIMIT 1);';
const SELECT_TWITTER = 'SELECT [id], [text], [author], [source], [extra], [link] from [quotes] WHERE [id] IN (SELECT [id] FROM [quotes] WHERE ( [fullen] <= 140 ) ORDER BY RANDOM() LIMIT 1);';
const SELECT_ROWID = 'SELECT [id], [text], [author], [source], [extra], [link] from [quotes] WHERE ( [id] = %d );';
const SELECT_GETCOUNT =  'SELECT MAX([id]) from [quotes];'; // 'SELECT COUNT(1) from [quotes];';


try {
	/* This service may be invoked in 3 ways:
	  	(a) quoterr.com/
	 			- selects any random quote
	 	(b)  quoterr.com/?q=t
	 			- selects a random quote that is short enough for twitter
	 	(c) quoterr.com/?q=123
	 			- requests a specific quote by rowid. If id does not exist, service returns error
	*/
	$mode = QUOTERR_MODE_RANDOM;
	$requested_id = 0;
	if ( isset( $_GET['q'] )) {
		$testValue = filter_input( INPUT_GET, 'q', FILTER_VALIDATE_INT,
			array( "options" => array( "default" => 0, "min_range" => 1, "max_range" => QUOTERR_MAX_REQUEST_NUM )));
		if ( $testValue > 0 ) {
			$mode = QUOTERR_MODE_ROWID;
			$requested_id = $testValue;
		} else {
			if ( $_GET['q'] == 't' ) {
				$mode = QUOTERR_MODE_TWITTER;
			}
		}
	}

	$select = '';
	switch ( $mode ) {
		case QUOTERR_MODE_RANDOM:
			$select = SELECT_DEFAULT;
			break;
		case QUOTERR_MODE_TWITTER:
			$select = SELECT_TWITTER;
			break;
		case QUOTERR_MODE_ROWID;
			$select = sprintf( SELECT_ROWID, $requested_id );
			break;
		default:
			$select = SELECT_DEFAULT; // [x] or throw an exception
	}


	// this will throw exception if db cannot be opened
	$db = new SQLite3( '../db/rqm.sqlite', SQLITE3_OPEN_READONLY );

	// get number of quotes in db
	$response['max'] = $db->querySingle( SELECT_GETCOUNT, false );

	// error here will only issue a warning; result will be empty or false
	$result = $db->querySingle( $select, true );
	if (( $result === NULL ) or ( $result == false ) or empty( $result )) {
		$response['exitcode'] = QUOTERR_RESP_NODATA;
		$response['error'] = 'Query returned no data';
		// give more info if quote id was invalid:
		if (( $mode === QUOTERR_MODE_ROWID ) and ( $requested_id > $response['max'] )) {
			$response['warning'] = 	'Requested quote ID ' . $requested_id . ' is not available';
			$response['exitcode'] = QUOTERR_RESP_BADID;
		}
	} else {
		// put query result in response
		$response['quote'] = $result;
	}

} catch ( Exception $e ) {
	$response['exitcode'] = QUOTERR_RESP_EXCEPTION;
	$response['error'] = $e->getMessage() . ' (line ' . $e->getLine() . ')';

} finally {
	// always send back a response
	echo json_encode( $response );
	// php would echo error message here, which breaks json. As a result,
	// we  get a json PARSER error instead of our own informative err msg.
	// Therefore: we suppress PHP's errors:
	error_reporting(0);

	if ( $db !== NULL ) {
		$db->close();
	}
	$db = null;

}

?>

