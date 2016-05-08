<?php
	
	
	//	Include reference to sensitive databse information
	include("../../../db_security/security.php");
	
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
				
				case 'photo-single':
				
				//	Make sure the p_id value is set
				
				if(!(isset($_GET["p_id"]))) {
					set_error_response( 0 , "You didn't set the p_id value...");
					break;
				}
				
				$p_id = $_GET["p_id"];
				
				
				//	Get the photo information for the p_id value
				
				$get_photo_sql = "SELECT * FROM photograph WHERE p_id = ?";
				
				if(!($get_photo_stmt = $db_conn->prepare($get_photo_sql))) {
					set_error_response( 0 , "1 " . $db_conn->error );
					break;
				}
				
				if(!($get_photo_stmt->bind_param("i", $p_id))) {
					set_error_response( 0 , "2" . $db_conn->error );
					break;
				}
				
				if(!($get_photo_stmt->execute())) {
					set_error_response( 0 , "3" . $db_conn->error );
					break;
				}
				
				if(!($result = $get_photo_stmt->get_result())) {
					set_error_response( 0 , "4" . $db_conn->error );
					break;
				}
				
				if($result_row = $result->fetch_array(MYSQLI_ASSOC)) {
					
					//	Echo the results
					http_response_code(200);
					echo json_encode( $result_row );
					break;
				}
				else {
					set_error_response( 0 , "3" , $db_conn->error );
					break;
				}
				
				
				break;
				
				
				case 'repo-photos':
				
					//	Make sure the range type is set
					if(!(isset($_GET["range_type"]))) {
						set_error_response( 0 , "You didn't set the range type");
						break;
					}
					
					$range_type = $_GET["range_type"];
					
					//	Make sure the r_id value is set
					if(!(isset($_GET["r_id"]))) {
						set_error_response( 0 , "You didn't set the r_id value...");
						break;
					}
					
					$r_id = $_GET["r_id"];



					switch($range_type) {
						
						case 'all':
						
							
							//	First make sure the user belongs to the repository
							$user_repo_check_sql = "SELECT * FROM user_repo WHERE ps_id = ? AND r_id = ?";
							
							if(!($user_repo_check_stmt = $db_conn->prepare($user_repo_check_sql))) {
								set_error_response( 0 , $db_conn->error );
								break;
							}
							
							if(!($user_repo_check_stmt->bind_param("ii", $ps_id, $r_id))) {
								set_error_response( 0 , "I couldn't bind the params -> " . $db_conn->error );
								break;
							}
							
							if(!($user_repo_check_stmt->execute())) {
								set_error_response( 0 , "I couldn't execute -> " . $db_conn->error );
								break;
							}
							
							if($result = $user_repo_check_stmt->get_result()) {
								if($result->num_rows != 1) {
									set_error_response( 0 , "User Repo Statement: The number of rows was off -> " . $user_repo_check_stmt->error );
									break;
								}
							}
							
							
							//	Now that we know the user belongs to the repository we can send back some photos
							
							$get_repo_photos_sql = 	"SELECT P.p_id, P.title, P.description, P.large_url, P.thumb_url, P.date_taken, P.date_conf, P.date_uploaded, P.uploaded_by "
													. "FROM photograph P, photo_repo PR "
													. "WHERE P.p_id = PR.p_id AND PR.r_id = ?";
													
							if(!($get_repo_photos_stmt = $db_conn->prepare($get_repo_photos_sql))) {
								set_error_response( 0 , "I couldn't prepare the get photos repo statement -> " . $db_conn->error );
								break;
							}
							
							if(!($get_repo_photos_stmt->bind_param("i", $r_id))) {
								set_error_response( 0, "I couldn't bind the parameters for the SQL statement ($get_repo_photos_sql) -> " . $db_conn->error );
								break;
							}
							
							if(!($get_repo_photos_stmt->execute())) {
								set_error_response( 0 , "I could't execute the statement with the SQL ($get_repo_photos_sql) -> " . $db_conn->error);
								break;
							}
							
							if($result = $get_repo_photos_stmt->get_result()) {
								
								$all_repo_photos = array();
								
								while($result_row = $result->fetch_array(MYSQLI_ASSOC)) {
									
									array_push($all_repo_photos, $result_row);
									
								}
								
								http_response_code(200);
								echo json_encode($all_repo_photos);
							}
							else {
								set_error_response( 0 , "I couldn't get the result...");
								break;
							}

						
						break;
						
						
						
						
						case 'date':
						
							//	Make sure the date range is set
							
							if(!(isset($_GET["date_range"]))) {
								set_error_response( 0 , "The date range wasn't set you idiot...");
								break;
							}
							
							$date_range = $_GET["date_range"];
							
							echo $date_range;
						
						
						break;
						
							
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