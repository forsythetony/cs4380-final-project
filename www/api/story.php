<?php
	
	
	//	Include reference to sensitive databse information
	include("../../../../../db_security/security.php");
	
	$db_user = constant("DB_USER");
	$db_host = constant("DB_HOST");
	$db_pass = constant("DB_PASS");
	$db_database = constant("DB_DATABASE");
	
	//	First connect to the database using values from the included file
	$db_conn = new mysqli($db_host, $db_user, $db_pass, $db_database);
	
	if ($db_conn->error_code) {
		
		set_error_response( 400 , "I couldn't connect to the database -> " . $db_conn->connect_error);
		die("The connection to the database failed: " . $db_conn->connect_error);
	}
		
	debug_echo( "database connected" . "\n" );

	
	
	
	
	
	
	
	
	
	
	$req_method = $_SERVER["REQUEST_METHOD"];
	
	switch( $req_method ) {
		
		case 'GET':
			
			/*
				Pull out all the query parameters
			*/
			if(!(isset($_GET["auth_token"]))) {
				$error_str = "The auth token was not set";
				set_error_response( 0 , $error_str );
			}
			
			if(!(isset($_GET["ps_id"]))) {
				$error_str = "The ps_id was not set";
				set_error_response( 0 , $error_str );
			}
			
			if(!(isset($_GET["request_type"]))) {
				$error_str = "You didn't set the request type";
				set_error_response( 0 , $error_str );
			}
			
			
			$auth_token = $_GET["auth_token"];
			$ps_id 		= $_GET["ps_id"];
			$req_type 	= $_GET["request_type"];
			
			
			
			
			
			/*
				Check to make sure the authentication token is valid
			*/
			$auth_token_check_sql = "SELECT * FROM user_auth_token WHERE ps_id = ? LIMIT 1";
			
			if (!($auth_token_check_stmt = $db_conn->prepare($auth_token_check_sql))) {
				
				$error_str = "I couldn't prepare the statement ->" . $db_conn->error;
				
				set_error_response( 0 , $error_str );
			}
	
			if(!($auth_token_check_stmt->bind_param("i", $ps_id))) {
				
				$error_str = "I couldn't bind the parameters -> " . $db_conn->error;
				set_error_response( 0 , $error_str );
			}
			
			if(!($auth_token_check_stmt->execute())) {
				set_error_response( 0, "I couldn't execute the statement -> " . $db_conn->error );
			}
			
			if(!($result = $auth_token_check_stmt->get_result())) {
				set_error_response( 0 , $db_conn->error );
			}
			
			if ($result->num_rows != 1) {
				set_error_response( 0 , "Auth token: The number of rows was off.");
				break;
			}
			
			
			
			/*
				Now we can switch on the request type
			*/
			
			switch( $req_type ) {
				
				case 'photo-stories':
				
					if(!(isset($_GET["p_id"]))) {
						set_error_response( 33 , "You didnt set the photo id" );
						break;
					}
					$p_id = $_GET["p_id"];
					
					
					$sql = "SELECT S.s_id, S.title, S.description, S.recording_url, S.recording_text, S.date_uploaded "
							. "FROM story S, photo_story PS "
							. "WHERE PS.s_id = S.s_id "
							. "AND PS.p_id = ?";
							
					
					if($stmt = $db_conn->prepare($sql)) {
						if($stmt->bind_param("i", $p_id)) {
							
							if($stmt->execute()) {
								
								if($result = $stmt->get_result()) {
									
									$stories = array();
									
									while ($result_row = $result->fetch_array(MYSQLI_ASSOC)) {
										array_push($stories, $result_row);
										
									}
									
									echo json_encode($stories);
								}
							}
							
						}
					}
				break;
				
				
			}
			
			
			
			
			
			
			
			
			
			
		break;
		
		
		
		case 'POST':
		
			debug_echo( "You chose the POST method" );
			
		break;
		
		
		case 'PUT':
		
			debug_echo( "You chose the PUT method" );
			
		break;
		
		
		case 'DELETE':
		
			debug_echo( "You chose the DELETE method" );
			
		break;
		
		default:
		
		break;
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	/*
		UTILITY FUNCTIONS
	*/
	function clean_date( $date_string ) {
		
		$is_valid_date = true;
		
		$clean_date_string;
		
		$pieces = explode("-", $date_string);
		
		
		if (count($pieces) == 3) {
			
			$year = intval($pieces[2]);
			$valid_year = true;
			$month = intval($pieces[0]);
			$valid_month = true;
			$day = intval($pieces[1]);
			$valid_day = true;
			
			$min_age = 15;
			
			
			$max_year = intval(date("Y")) - $min_age;
			
			if (!($year >= 1900 && $year < $max_year)) {
				$valid_year = false;
			}
			
			if (!($month >= 1 && $month <= 12)) {
				$valid_month = false;
			}
			
			if ($valid_year && $valid_month) {
				
				$max_day = cal_days_in_month(CAL_GREGORIAN, $valid_month, $valid_year);
				
				
				if ($day > 0 && $day <= $max_day) {
					$is_valid_date = true;
				}
			}
			else {
				$is_valid_date = false;
			}
			
			if ($is_valid_date) {
				
				$month_string;
				$day_string;
				
				
				if ($day < 10) {
					$day_string = "0" . $day;
				}
				else {
					$day_string = "$day";
				}
				
				if ($month < 10) {
					$month_string = "0" . $month;
				}
				else {
					$month_string = "$month";
				}
				
				$clean_date_string = $year . "/" . $month_string . "/" . $day_string;
			}
		}
		
		$ret_array = array(
			"isValidDate" => $is_valid_date,
			"validDateString" => $clean_date_string
		);

		return $ret_array;
	}

	function pull_date_range( $range_str ) {
		
		$did_pull = true;
		$start_date;
		$end_date;
		
		
		$dates_arr = explode("_", $range_str);
		
		if( count($dates_arr) == 2 ) {
			
			$start_date = $dates_arr[0];
			$end_date 	= $dates_arr[1];
			
		}
		else {
			$did_pull = false;
		}

		
		
		
		$result_dict = array(
			"success"		=> $did_pull,
			"start_date"	=> $start_date,
			"end_date"		=> $end_date
		);
		
		return $result_dict;
	}
	function debug_echo( $str ) {
		
		$echo_debug = false;
		
		if ($echo_debug) {
			echo $str;
		}
	}
	
	function set_error_response( $error_code , $error_message ) {
		
		
		$http_response_code = 500;
		
		$response_array = array(
			"error_code" => $error_code,
			"error_message" => $error_message
		);
				echo json_encode($response_array);
		http_response_code($error_code);
		
	}
	
	
?>
