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
	
	
	
	
	
	/*
		REQUEST HANDLING
	*/	
	$req_method = $_SERVER['REQUEST_METHOD'];
		
	
	switch ($req_method) {
		
		case 'POST':
		
		
			//	Get the raw post data
			$json_raw = file_get_contents("php://input");
			
			if ($decoded_json = json_decode($json_raw, true)) {
				
								
				//PULL AND CLEAN ALL DATA FROM JSON POST
				
				$req_fname 		 = $decoded_json["firstname"];
				$req_lname 		 = $decoded_json["lastname"];
				$req_mname 		 = $decoded_json["middlename"];
				$req_maiden_name = $decoded_json["maiden_name"];
				$req_birthdate 	 = $decoded_json["birthdate"];
				$req_gender 	 = $decoded_json["gender"];
				$req_password 	 = $decoded_json["password"];
				$req_email		 = $decoded_json["email"];
				$req_username	 = $decoded_json["username"];

				echo "\nThe firstname of the user is " . $decoded_json["firstname"] . "\n";

				//	Clean birthdate data
				
				$clean_birthdate_info = clean_date( $birthdate );
				
				if(!$clean_birthdate_info["isValidDate"]) {
					set_error_response( 205 , "The birthdate you passed was invalid..." );
					break;
				}
				else {
					$birthdate = $clean_birthdate_info["validDateString"];
					echo "birthdate clean works"."\n";
				}
				
				// check to see if the person is already in the person table
				$person_name_check_sql = 'SELECT * FROM person where person.fname= ? AND person.mname= ? AND person.lname= ?';
	
				$person_name_check_stmt = $db_conn->prepare($check_person_name_sql);
	
				$person_name_check_stmt->bind_param("ss", $req_fname, $req_mname, $rea_lname);
	
				if (!($person_name_check_stmt = $db_conn->prepare($person_name_check_sql))) {
					set_error_response( 201, "SQL Error -> " . $person_name_check_stmt->error);
					break;
				}
				
				if (!($person_name_check_stmt->bind_param("ss", $req_fname, $rea_lname))) {
					set_error_response( 201, "SQL Error -> " . $person_name_check_stmt->error);
					break;
				}
	
				$person_name_is_valid = true;
				
				echo "name check worked"."\n";

				if ($person_name_check_stmt->execute()) {
	
					if($person_name_check_result = $person_name_check_stmt->get_result()) {
	
						if($person_name_check_result->num_rows > 0) {
	
							$person_name_is_valid = false;
	
						}
	
					}
	
					else {
							
							set_error_response( 201, "SQL Error -> " . $username_check_stmt->error);
					}	
				}	
	
				else {
						
						set_error_response( 201, "SQL Error -> " . $db_conn->error);
						break;
				}
	
				$person_name_check_stmt->close();
	
				if (!$person_name_is_valid) {
					
					set_error_response( 203 , "The person with the same name already exists in the database");
					break;
				}
*/		
			}
	
			else {
	
				echo "info package decode error";
			
			}


		
		break;
		
		
		
		
		default:
		
			set_error_response( 303, "Wrong request method...");
		
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

/*
	function generate_64_char_random_string() {
		
		$length = 64;
		
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
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
	
	function print_result_values( $result ) {
		
		$num_fields = $result->field_count;
		
		while ($row = $result->fetch_row()) {
			
			echo "\n";
			for ($i = 0; $i < $num_fields; $i++) {
				
				echo $row[$i] . "\t\t";
			}	
		}
		
		echo "\n";
	}
	
	function print_result_headers( $result ) {
		
		echo "\n";
		
		$num_fields = $result->field_count;
		
		$fields = $result->fetch_fields();
		
		for ($i = 0; $i < $num_fields; $i++) {
			echo $fields[$i]->name . "\t\t";
		}	
	}
	function print_result_all( $result ) {
		
		print_result_headers( $result );
		print_result_values( $result );
	}
	//
	//	Random Utility Functions
	//
	function execute_placeholder_query( $db_conn ) {
		
		//	First prepare the SQL query
		$query_string = "SELECT * FROM user";
		
		if ($result = $db_conn->query($query_string)) {
		
			
			print_result_all( $result );
			
			
		}
		else {
			echo "Couldn't prepare the statement";
		}
	}	
	//
	//	Error Handling
	//

	function handle_request_error() {	
		
		
	}
*/
	
?>
