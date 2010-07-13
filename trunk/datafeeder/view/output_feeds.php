<?php

set_time_limit(720);

// Include the configuration file for error management and such.
require_once ('./includes/config.inc.php'); 

ini_set('display_errors', 1);

// Set the page title and include the HTML header.
$page_title = 'Transit Schedule Manager';
include ('./includes/header.html');


// Connect to the database
require_once ('mysql_connect.php');

require_once ('shape_functions.inc.php'); 


$agency_id=$_GET['agency_id'];


if (isset($_SESSION['user_id'])) {
	$user_permissions_query = "select * from user_permissions where agency_id = ".$agency_id." and user_id=".$_SESSION['user_id'];
	$user_permissions_result = mysql_query($user_permissions_query);
	$user_permissions_num_rows = mysql_num_rows($user_permissions_result);
} else {$user_permissions_num_rows=0;}

// if the user is logged in
if (isset($_SESSION['first_name']) && $user_permissions_num_rows >= 1) {

	// query for agency_groups
	$agency_groups_query = "SELECT agency_groups.agency_group_id, agency_groups.group_name
	FROM agency_group_assoc
	RIGHT JOIN agency_groups ON agency_groups.agency_group_id = agency_group_assoc.agency_group_id
	WHERE agency_group_assoc.agency_id =$agency_id;";
	$agency_groups_result = mysql_query($agency_groups_query);
	while ($row=mysql_fetch_array($agency_groups_result, MYSQL_ASSOC)) {
		$agency_group_path=$row['group_name'];
		$agency_group_id=$row['agency_group_id'];
	}

	// query for agency_group_assoc
	$agency_group_assoc_query = "select agency_id from agency_group_assoc where agency_group_id=".$agency_group_id;
	$agency_group_assoc_result = mysql_query($agency_group_assoc_query);
	$agency_group_associations_array = array();
	while ($row=mysql_fetch_array($agency_group_assoc_result, MYSQL_ASSOC)) {
		array_push($agency_group_associations_array, $row['agency_id']);
	}
	$agency_group_associations = implode (",",$agency_group_associations_array);

	// agency.txt
	$agency_fields="agency_id,agency_name,agency_url,agency_timezone,agency_phone,agency_fare_url,agency_lang\n";
	$agency_csv=$agency_fields;

	$agencies_query = "select * from agency where agency_id in (".$agency_group_associations.");";
	$agencies_result = mysql_query($agencies_query);

	if ($_SESSION['user_id'] == 5) {

		// echo $agencies_query;
		echo "<br>";

		while ($row = mysql_fetch_array($agencies_result, MYSQL_ASSOC)) {

			$path="/Users/aaron/Sites/sustainablehosting/trilliumtransit.com/transit_feeds/temp/".$agency_group_path."/";

			$agency_csv=$agency_csv.$row["agency_id"].",\"".$row["agency_name"]."\",\"".$row["agency_url"]."\",\"".$row["agency_timezone"]."\",\"".$row["agency_phone"]."\",\"".$row["agency_fare_url"]."\",\"en\"\n";
		}

		$save_file=$path."agency.txt";
		$agency_file=fopen($save_file,'w+');
		$file_write_result=fwrite($agency_file,$agency_csv);
		fclose($agency_file);

		echo "Agency.txt length ".$file_write_result."<br>";

		// stops.txt

		if (isset($_GET['timetable'])) {
			$stop_conditional="";
		} else {
			$stop_conditional=" and stops.stop_lat <> 0 and stops.stop_lon <> 0";
		}

		$stops_fields="stop_id,stop_name,stop_desc,stop_lat,stop_lon,zone_id,stop_url,location_type,parent_station\n";
		$stops_query = "select distinct stops.stop_id, stops.stop_name, stops.stop_desc, stops.stop_lat, stops.stop_lon, stops.zone_id, stops.stop_url, stops.location_type, stops.parent_station
			from stop_times left join stops on stop_times.stop_id=stops.stop_id 
			where stop_times.agency_id in (".$agency_group_associations.")$stop_conditional
			union select stops.stop_id, stops.stop_name, stops.stop_desc, stops.stop_lat, stops.stop_lon, stops.zone_id, stops.stop_url, stops.location_type, stops.parent_station from stops where stops.agency_id in (".$agency_group_associations.") and stops.location_type=1$stop_conditional;";


		// echo $stops_query;
		$stops_result = mysql_query($stops_query);

		$stops_csv=$stops_fields;

		while ($row = mysql_fetch_array($stops_result, MYSQL_ASSOC)) {
			$stop_name = str_replace ( '"', '""', $row['stop_name']);
			$stop_desc = str_replace ( '"', '""', $row['stop_desc']);

			$stops_csv=$stops_csv.$row["stop_id"].",\"".$stop_name."\",\"".$stop_desc."\",".$row["stop_lat"].",".$row["stop_lon"].",".$row["zone_id"].",".$row['stop_url'].",".$row['location_type'].",".$row['parent_station']."\n";
		}

		$save_file=$path."stops.txt";
		$stops_file=fopen($save_file,'w+');
		$file_write_result=fwrite($stops_file,$stops_csv);
		fclose($stops_file);

		echo "Stops.txt length ".$file_write_result."<br>";


		// routes.txt

		$routes_fields="agency_id,route_id,route_short_name,route_long_name,route_desc,route_type,route_url,route_color,route_text_color\n";

		$routes_query = "select routes.*,agency.agency_short_name from routes inner join agency on routes.agency_id=agency.agency_id where agency.agency_id in (".$agency_group_associations.");";
		$routes_result = mysql_query($routes_query);

		$routes_csv=$routes_fields;

		while ($row = mysql_fetch_array($routes_result, MYSQL_ASSOC)) {
			$route_short_name = str_replace ( '"', '""', $row['route_short_name']);
			$route_long_name = str_replace ( '"', '""', $row['route_long_name']);
			$route_desc = str_replace ( '"', '""', $row['route_desc']);

			$routes_csv=$routes_csv.$row["agency_id"].",".$row["route_id"].",\"".$route_short_name."\",\"".$route_long_name."\",\"".$route_desc."\",".$row["route_type"].",".$row["route_url"].",".$row["route_color"].",".$row["route_text_color"]."\n";
		}

		$save_file=$path."routes.txt";
		$routes_file=fopen($save_file,'w+');
		$file_write_result=fwrite($routes_file,$routes_csv);
		fclose($routes_file);

		echo "routes.txt length ".$file_write_result."<br>";

		// trips.txt


		$trips_fields="route_id,service_id,trip_id,trip_headsign,direction_id,block_id,shape_id,trip_bikes_allowed\n";

		$trips_query = "select calendar.calendar_id,trips.trip_id,trips.route_id,trips.based_on,trips.trip_start_time,trips.trip_headsign,trips.block_id,trips.shape_id,trips.trip_bikes_allowed,directions.direction_bool as direction_id,service_schedule_bounds.service_schedule_bounds_id,routes.route_bikes_allowed,TIME_TO_SEC(trips.trip_start_time) AS trip_start_time_sec from trips right join calendar on trips.service_id=calendar.calendar_id right join service_schedule_bounds on calendar.service_schedule_group_id=service_schedule_bounds.service_schedule_group_id left join routes on trips.route_id=routes.route_id left join directions on trips.direction_id=directions.direction_id where trips.agency_id in (".$agency_group_associations.");";


		$trips_result = mysql_query($trips_query);

		// echo '<p>'.$trips_query.'</p>';

		$trips_csv=$trips_fields;

		$trip_patterns = array();
		$pattern_counter = 0;

		while ($row = mysql_fetch_array($trips_result, MYSQL_ASSOC)) {

			// initialize/reset shape_id value for this time around
			$shape_id = '';

			$eval_shape_segment_completeness_result=eval_shape_segment_completeness($row['trip_id']);

			if (!isset($_GET['timetable'])) {

				if ($eval_shape_segment_completeness_result[0]) {

					// echo 'eval_shape_segment_completeness must have returned true because this conditional is running<br/>';

					if (is_null($row['based_on'])) {
						$orig_trip_id = $row['trip_id'];
					} else {
						$orig_trip_id = $row['based_on'];
					}

					// query for the trip pattern
					$stop_order_query = "select stops.stop_id from stop_times inner join stops on stop_times.stop_id = stops.stop_id where trip_id=$orig_trip_id and stop_times.agency_id in ($agency_group_associations)$stop_conditional order by stop_sequence ASC;";
					$stop_order_result = mysql_query($stop_order_query);
	
					// echo '<p>'.$stop_order_query.'</p>';
	
					$temp_pattern_array = array();
					while ($stop_row = mysql_fetch_array($stop_order_result, MYSQL_ASSOC)) {
						array_push($temp_pattern_array,$stop_row['stop_id']);
					}
	
					// create comma-delimited list of all the stop_ids, in order, used in this trip
					$unique_trip_pattern = implode(",", $temp_pattern_array);

					// if it does, then get its ID
					$existing_array_key = get_key_for_trip_pattern( $temp_pattern_array,$trip_patterns);

					// if an id was returned, then go ahead and set the shape_id based on that existing shape
					if ($existing_array_key != 'not exists') {
						// set shape_id according to existing_array_key
						$shape_id=$existing_array_key;	
	
						$trip_patterns[$existing_array_key][1] .= ',' .$row['trip_id'];
					}
					// if it doesn't, then add it
					else {
						$trip_patterns[$pattern_counter] = array();
						$trip_patterns[$pattern_counter][0] = $unique_trip_pattern;
						$trip_patterns[$pattern_counter][1] = $row['trip_id'];
	
						// set shape_id according to the counter
						$shape_id = $pattern_counter;
	
						// advance pattern_counter
						$pattern_counter++;
					}
				} else {	// let us know what happened
					// echo "get_key_for_trip_pattern must have returned NULL for trip_id ".$row['trip_id'].".  the stop_id that it hung on was ".$eval_shape_segment_completeness_result[1].".  the query was ".$eval_shape_segment_completeness_result[2]."<br/>";
					$shape_id = "";
				}
			}

			// now add the row for this trip, and include a shape_id, if it exists
			if ($row['trip_headsign'] == "") {
				$trip_headsign = "(none)";
			} else {
				$trip_headsign = str_replace ( '"', '""', $row['trip_headsign']);
			}

			$trip_id_csv=$row["trip_id"]."A".$row["calendar_id"]."B".$row["service_schedule_bounds_id"];

			$trips_csv=$trips_csv.$row["route_id"].",".$row["calendar_id"]."A".$row["service_schedule_bounds_id"].",".$trip_id_csv.",\"".$trip_headsign."\",".$row["direction_id"].",".$row["block_id"].",".$shape_id.','.$row['route_bikes_allowed']."\n";

		}

		$save_file=$path."trips.txt";
		$trips_file=fopen($save_file,'w+');
		$file_write_result=fwrite($trips_file,$trips_csv);
		fclose($trips_file);

		echo "trips.txt length ".$file_write_result."<br>";


		// stop_times.txt
		$stop_times_csv="trip_id,arrival_time,departure_time,stop_id,stop_sequence,stop_headsign,pickup_type,drop_off_type,shape_dist_traveled\n";

		mysql_data_seek ($trips_result, 0);

		while ($row = mysql_fetch_array($trips_result, MYSQL_ASSOC)) {

			$trip_id_csv=$row["trip_id"]."A".$row["calendar_id"]."B".$row["service_schedule_bounds_id"];

			$trip_id=$row["trip_id"];
			$route_id=$row['route_id'];

			// init previous_segment_id
			$previous_segment_id = NULL;

			if (!isset($_GET['timetable'])) {
				// get the index for the shape, if it exists
				$shape_index = get_key_for_trip_id($row["trip_id"], $trip_patterns);
				// echo '<p>shape_index:'.$shape_index.'</p>';

				// echo '<p>trip_id:'.$row["trip_id"].' shape_index:'.$shape_index.'</p>';
			} else {
				$shape_index='not exists';
			}

			// if the trip is not based on another
			if ($row['based_on']=="") {
				$stop_times_query = "select stop_times.* FROM stop_times left join stops on stop_times.stop_id = stops.stop_id where stop_times.trip_id=".$row['trip_id']." and stop_times.agency_id in (".$agency_group_associations.")$stop_conditional order by stop_sequence asc;";
				$stop_times_result = mysql_query($stop_times_query);
				$loop_count_index=1;

				// reset/initialize previous_stop_time
				$previous_stop_id = 0;
				$shape_dist_traveled = 0;
				$previous_arrival_time = null;
				$previous_departure_time = null;

				while ($row = mysql_fetch_array($stop_times_result, MYSQL_ASSOC)) {
					if ($shape_index != 'not exists') {
						if ($previous_stop_id == 0) {
							$shape_dist_traveled=0;$previous_stop_id=$row['stop_id'];
						} else {
							$current_stop_id=$row['stop_id'];
							$segment_query = "select shape_segments.shape_segment_id from shape_segments inner join shape_segment_triproute_assoc on shape_segments.shape_segment_id = shape_segment_triproute_assoc.shape_segment_id where (shape_segment_triproute_assoc.route_id=$route_id OR shape_segment_triproute_assoc.trip_id=$trip_id) AND shape_segments.start_coordinate_id=$previous_stop_id AND shape_segments.end_coordinate_id=$current_stop_id ORDER BY shape_segment_id DESC LIMIT 1;";
							$segment_result = mysql_query($segment_query);
							$segment_row = mysql_fetch_assoc($segment_result);

							$shape_dist_traveled=$shape_dist_traveled+find_distance_for_segment($segment_row['shape_segment_id']);
		
							if ($previous_segment_id != NULL) {
								$shape_dist_traveled=$shape_dist_traveled+gap_between_segments($previous_segment_id,$segment_row['shape_segment_id']);
							}

							$previous_segment_id = $segment_row['shape_segment_id'];
							$previous_stop_id=$current_stop_id;
		
						}
					} else {
						$shape_dist_traveled='';
					}

					if (!is_null($previous_arrival_time) && !is_null($row['arrival_time'])) {
						if ($previous_arrival_time > $row['arrival_time']) {
							echo '<p><a href="http://trilliumtransit.com/admin/trip_dashboard.php?trip_id='.$row['trip_id'].'&agency_id='.$row['agency_id'].'">trip id #'.$row['trip_id'].'</a> has out of order stop times (stop_time_id #'.$row['stop_time_id'].', arrival time @ '.$row['arrival_time'].')</p>';
						}
					}
					if (!is_null($previous_departure_time) && !is_null($row['departure_time'])) {
						if ($previous_departure_time > $row['departure_time']) {
							echo '<p><a href="http://trilliumtransit.com/admin/trip_dashboard.php?trip_id='.$row['trip_id'].'&agency_id='.$row['agency_id'].'">trip id #'.$row['trip_id'].'</a> has out of order stop times (stop_time_id #'.$row['stop_time_id'].', departure time @ '.$row['departure_time'].')</p>';
						}
					}

					$stop_times_csv=$stop_times_csv.$trip_id_csv.",".$row["arrival_time"].",".$row["departure_time"].",".$row["stop_id"].",".$loop_count_index.",,".$row["pickup_type"].",".$row["drop_off_type"].",".$shape_dist_traveled."\n";

					$loop_count_index=$loop_count_index+1;

					if (!is_null($row['arrival_time'])) {
						$previous_arrival_time = $row['arrival_time'];
					}
					if (!is_null($row['departure_time'])) {
						$previous_departure_time = $row['departure_time'];
					}

					// end while loop for stop_times
				}

			}
			// if the trip is based on another, do this
			else {
				$stop_times_query = "select stop_times.*,TIME_TO_SEC(arrival_time) as arrival_time_sec, TIME_TO_SEC(departure_time) as departure_time_sec from stop_times left join stops on stop_times.stop_id = stops.stop_id where stop_times.trip_id=".$row['based_on']." and stop_times.agency_id in (".$agency_group_associations.")$stop_conditional order by stop_sequence asc;";
				$stop_times_trip_id = $row['based_on'];
				$stop_times_result = mysql_query($stop_times_query);
				$loop_count_index=1;

				// now, find out what the difference between the based_on trip's start time is, and the reset start time for this specific trip
				$first_stop_time_original=mysql_result($stop_times_result,0,"arrival_time_sec");
				$time_difference = $row['trip_start_time_sec'] - $first_stop_time_original;

				mysql_data_seek ($stop_times_result, 0);

				// reset/initialize previous_stop_time
				$previous_stop_id = 0;
				$shape_dist_traveled = 0;

				while ($row = mysql_fetch_array($stop_times_result, MYSQL_ASSOC)) {
					$current_stop_id=$row['stop_id'];

					if ($shape_index !== 'not exists')  {
						// echo "<p>shape_index is not null and trip_id is ".$row['trip_id']."</p>";
						if ($previous_stop_id != 0) {
							$segment_query = "select shape_segments.shape_segment_id from shape_segments inner join shape_segment_triproute_assoc on shape_segments.shape_segment_id = shape_segment_triproute_assoc.shape_segment_id where (shape_segment_triproute_assoc.route_id=$route_id OR shape_segment_triproute_assoc.trip_id=$stop_times_trip_id) AND shape_segments.start_coordinate_id=$previous_stop_id AND shape_segments.end_coordinate_id=$current_stop_id ORDER BY shape_segment_id DESC;";
							// echo $segment_query;
							$segment_result = mysql_query($segment_query);
							$segment_row = mysql_fetch_assoc($segment_result);

							if (mysql_num_rows($segment_result) == 0) {
								$shape_dist_traveled='';
								$previous_segment_id = NULL;
							} else {
								$shape_segment_id=mysql_result($segment_result,0,"shape_segment_id");

								if ($previous_segment_id != NULL) {
									$shape_dist_traveled=$shape_dist_traveled+gap_between_segments($previous_segment_id,$segment_row['shape_segment_id']);
								}

								$shape_dist_traveled=$shape_dist_traveled+find_distance_for_segment($shape_segment_id);

								$previous_segment_id = $shape_segment_id;
							}

						}
						$previous_stop_id=$current_stop_id;
					}

					if (is_null($row['arrival_time'])) {
						$adjusted_arrival_time="";
					} else {
						// set minutes & seconds separately
						$adjusted_arrival_minsec=date("i:s",$time_difference+$row['arrival_time_sec']);

						// set hours separately
						$adjusted_arrival_hours=floor(($time_difference+$row['arrival_time_sec'])/3600);

						// set adjusted arrival/departure time to see if it is more than 24 hours
						$adjusted_arrival_time=$time_difference+$row['arrival_time_sec'];

						// if new time is more than 24 hours, and then adjust hours accordingly
						if ($adjusted_arrival_time > 86400) {
							// subtract 1 day (86400 seconds) from the hours difference, divide by hours (3600 sec) and then round down
							$more_hours_difference=floor(($adjusted_arrival_time-86400)/3600);
							// now add that to the adjusted arrival & departure hours
							$adjusted_arrival_hours=24+$more_hours_difference;
						}

						// now stitch together the adjusted_arrival_time and adjusted_departure_time variables
						$adjusted_arrival_time=$adjusted_arrival_hours.':'.$adjusted_arrival_minsec;
					}

					if (is_null($row['departure_time'])) {
						$adjusted_departure_time="";
					} else {
						// set minutes & seconds separately
						$adjusted_departure_minsec=date("i:s",$time_difference+$row['departure_time_sec']);

						// set hours separately
						$adjusted_departure_hours=floor(($time_difference+$row['departure_time_sec'])/3600);

						// set adjusted arrival/departure time to see if it is more than 24 hours
						$adjusted_departure_time=$time_difference+$row['departure_time_sec'];

						// if new time is more than 24 hours, and then adjust hours accordingly
						if ($adjusted_departure_time > 86400) {
							// subtract 1 day (86400 seconds) from the hours difference, divide by hours (3600 sec) and then round down
							$more_hours_difference=floor(($adjusted_departure_time-86400)/3600);
							// now add that to the adjusted departure & departure hours
							$adjusted_departure_hours=24+$more_hours_difference;
						}

						// now stitch together the adjusted_arrival_time and adjusted_departure_time variables
						$adjusted_departure_time=$adjusted_departure_hours.':'.$adjusted_departure_minsec;
					}

					$stop_times_csv=$stop_times_csv.$trip_id_csv.",".$adjusted_arrival_time.",".$adjusted_departure_time.",".$row["stop_id"].",".$loop_count_index.",,".$row["pickup_type"].",".$row["drop_off_type"].",".$shape_dist_traveled."\n";

					$loop_count_index=$loop_count_index+1;
				}
			}

			// end while loop for trips
		}

		$save_file=$path."stop_times.txt";
		$stop_times_file=fopen($save_file,'w+');
		$file_write_result=fwrite($stop_times_file,$stop_times_csv);
		fclose($stop_times_file);

		echo "stop_times.txt length ".$file_write_result."<br>";

		// calendar.txt
		$calendar_fields="service_id,monday,tuesday,wednesday,thursday,friday,saturday,sunday,start_date,end_date\n";

		$calendar_query = "select calendar.calendar_id, service_schedule_bounds.service_schedule_bounds_id,monday,tuesday,wednesday,thursday,friday,saturday,sunday,date_format(service_schedule_bounds.start_date,'%Y%m%d') as start_date,date_format(service_schedule_bounds.end_date,'%Y%m%d') as end_date from service_schedule_bounds inner join calendar on service_schedule_bounds.service_schedule_group_id=calendar.service_schedule_group_id where service_schedule_bounds.agency_id in (".$agency_group_associations.");";
		$calendar_result = mysql_query($calendar_query);

		$calendar_csv=$calendar_fields;

		while ($row = mysql_fetch_array($calendar_result, MYSQL_ASSOC)) {
			$calendar_csv=$calendar_csv.$row["calendar_id"]."A".$row["service_schedule_bounds_id"].",".$row["monday"].",".$row["tuesday"].",".$row["wednesday"].",".$row["thursday"].",".$row["friday"].",".$row["saturday"].",".$row["sunday"].",".$row["start_date"].",".$row["end_date"]."\n";
		}

		$save_file=$path."calendar.txt";
		$calendar_file=fopen($save_file,'w+');
		$file_write_result=fwrite($calendar_file,$calendar_csv);
		fclose($calendar_file);

		echo "calendar.txt length ".$file_write_result."<br>";

		// calendar_dates.txt
		$calendar_dates_fields="service_id,date,exception_type\n";

		$calendar_dates_query = "SELECT DISTINCT DATE_FORMAT( DATE,  '%Y%m%d' ) AS date, calendar_date_service_exceptions.exception_type, service_exception,service_schedule_bounds.service_schedule_bounds_id
			FROM calendar_date_service_exceptions
			RIGHT JOIN calendar_dates ON calendar_date_service_exceptions.calendar_date_id = calendar_dates.calendar_date_id RIGHT JOIN calendar ON calendar_date_service_exceptions.service_exception=calendar.calendar_id RIGHT join service_schedule_bounds on calendar.service_schedule_group_id=service_schedule_bounds.service_schedule_group_id
			WHERE calendar_date_service_exceptions.agency_id in (".$agency_group_associations.");";
		$calendar_dates_result = mysql_query($calendar_dates_query);

		$calendar_dates_csv=$calendar_dates_fields;

		while ($row = mysql_fetch_array($calendar_dates_result, MYSQL_ASSOC)) {
			if ($row['exception_type']==0) {
				$exception_type=2;
			}
			if ($row['exception_type']==1) {
				$exception_type=1;
			}

			$calendar_dates_csv=$calendar_dates_csv.$row["service_exception"]."A".$row['service_schedule_bounds_id'].",".$row["date"].",$exception_type\n";
		}

		$save_file=$path."calendar_dates.txt";
		$calendar_dates_file=fopen($save_file,'w+');
		$file_write_result=fwrite($calendar_dates_file,$calendar_dates_csv);
		fclose($calendar_dates_file);

		echo "calendar_dates.txt length ".$file_write_result."<br>";

		// fare_attributes.txt
		$fare_attributes_fields="agency_id,fare_id,price,currency_type,payment_method,transfers,transfer_duration\n";

		$fare_attributes_query = "select * from fare_attributes where agency_id in (".$agency_group_associations.");";
		$fare_attributes_result = mysql_query($fare_attributes_query);

		$fare_attributes_csv=$fare_attributes_fields;

		while ($row = mysql_fetch_array($fare_attributes_result, MYSQL_ASSOC)) {
			$fare_attributes_csv=$fare_attributes_csv.$row["agency_id"].",".$row["fare_id"].",".$row["price"].",".$row["currency_type"].",".$row["payment_method"].",".$row["transfers"].",".$row["transfer_duration"]."\n";
		}

		$save_file=$path."fare_attributes.txt";
		$fare_attributes_file=fopen($save_file,'w+');
		$file_write_result=fwrite($fare_attributes_file,$fare_attributes_csv);
		fclose($fare_attributes_file);

		echo "fare_attributes.txt length ".$file_write_result."<br>";

		// fare_rules.txt
		$fare_rules_fields="fare_id,route_id,origin_id,destination_id,contains_id\n";

		$fare_rules_query = "select * from fare_rules where agency_id in (".$agency_group_associations.");";
		$fare_rules_result = mysql_query($fare_rules_query);

		$fare_rules_csv=$fare_rules_fields;

		while ($row = mysql_fetch_array($fare_rules_result, MYSQL_ASSOC)) {
			$fare_rules_csv=$fare_rules_csv.$row["fare_id"].",".$row["route_id"].",".$row["origin_id"].",".$row["destination_id"].",".$row["contains_id"]."\n";
		}

		$save_file=$path."fare_rules.txt";
		$fare_rules_file=fopen($save_file,'w+');
		$file_write_result=fwrite($fare_rules_file,$fare_rules_csv);
		fclose($fare_rules_file);

		echo "fare_rules.txt length ".$file_write_result."<br>";

		// frequencies.txt
		$frequencies_fields="trip_id,start_time,end_time,headway_secs,exact_times\n";

		$frequencies_query = "SELECT calendar.calendar_id, frequencies . * , service_schedule_bounds.service_schedule_bounds_id
			FROM frequencies
			RIGHT JOIN trips ON frequencies.trip_id = trips.trip_id
			RIGHT JOIN calendar ON trips.service_id = calendar.calendar_id
			RIGHT JOIN service_schedule_bounds ON calendar.service_schedule_group_id = service_schedule_bounds.service_schedule_group_id
			WHERE frequencies.agency_id in (".$agency_group_associations.");";
		$frequencies_result = mysql_query($frequencies_query);

		$frequencies_csv=$frequencies_fields;

		while ($row = mysql_fetch_array($frequencies_result, MYSQL_ASSOC)) {
			$trip_id_csv=$row["trip_id"]."A".$row["calendar_id"]."B".$row["service_schedule_bounds_id"];

			$frequencies_csv=$frequencies_csv.$trip_id_csv.",".$row["start_time"].",".$row["end_time"].",".$row["headway_secs"].",".$row["exact_times"]."\n";
		}

		$save_file=$path."frequencies.txt";
		$frequencies_file=fopen($save_file,'w+');
		$file_write_result=fwrite($frequencies_file,$frequencies_csv);
		fclose($frequencies_file);

		echo "frequencies.txt length ".$file_write_result."<br>";

		// transfers.txt
		$transfers_fields="from_stop_id,to_stop_id,transfer_type,min_transfer_time\n";

		$transfers_query = "SELECT * from transfers WHERE agency_id IN (".$agency_group_associations.");";
		$transfers_result = mysql_query($transfers_query);

		$transfers_csv=$transfers_fields;

		while ($row = mysql_fetch_array($transfers_result, MYSQL_ASSOC)) {
			$transfers_csv=$transfers_csv.$row["from_stop_id"].",".$row["to_stop_id"].",".$row["transfer_type"].",".$row["min_transfer_time"]."\n";
		}

		$save_file=$path."transfers.txt";
		$transfers_file=fopen($save_file,'w+');
		$file_write_result=fwrite($transfers_file,$transfers_csv);
		fclose($transfers_file);

		echo "transfers.txt length ".$file_write_result."<br>";

		if (!isset($_GET['timetable'])) {
			// shapes.txt
			$shapes_fields="shape_id,shape_pt_lat,shape_pt_lon,shape_pt_sequence,shape_dist_traveled\n";
			$shapes_csv=$shapes_fields;

			// echo '<p>count($trip_patterns):'.count($trip_patterns).'</p>';
			for ($i=0;$i < count($trip_patterns);$i++) {
				//	echo '<p>the for loop for trip_patterns loops right now</p>';
				// reset or initialize $shp_pt_sequence
				$shp_pt_sequence = 0;

				// get an array of stops, out of the trip_patterns row, that will be looped through later
				$stops_array=explode(",",$trip_patterns[$i][0]);

				//	echo '<p>trip pattern:'.$trip_patterns[$i][0].'</p>';
				// create a list of trips from the trip_patterns row
				$trips_list=str_replace(" ",",",trim($trip_patterns[$i][1]));

				//	echo '<p>trips list:'.$trips_list.'</p>';
				// set the shape_id according to the for loop iterator
				$shape_id=$i;
	
				// reset/init last_stop_id
				$last_stop_id = 0;

				$total_shape_dist_traveled=0;

				for ($stops_i=0;$stops_i<count($stops_array);$stops_i++) {
					// only do this if its the second iteration or greater
					if ($last_stop_id != 0) {
						//set the current stop id according to the iteration in this array loop
						$current_stop_id = $stops_array[$stops_i];
							
						// query for a shape_segment that has last_stop_id and current_stop_id, and whose trip_assoc is contained in the list of trips from trip_patterns
						$shape_segment_query = "select shape_segments.shape_segment_id from shape_segments inner join shape_segment_triproute_assoc on shape_segments.shape_segment_id = shape_segment_triproute_assoc.shape_segment_id where (shape_segment_triproute_assoc.trip_id IN ($trips_list) OR shape_segment_triproute_assoc.route_id IN (SELECT DISTINCT route_id from trips where trip_id IN ($trips_list))) AND shape_segments.start_coordinate_id=$last_stop_id AND shape_segments.end_coordinate_id=$current_stop_id ORDER BY shape_segment_id DESC LIMIT 1";
							
						// echo '<p>shape_segment_query:'.$shape_segment_query.'</p>';
							
						$shape_segment_result = mysql_query($shape_segment_query);
							
						// take the results from last query and extract a single variable
						$shape_segment_row = mysql_fetch_assoc($shape_segment_result);
						$shape_segment_id = $shape_segment_row['shape_segment_id'];
							
						// only execute this code if we're looping over the second segment
							
						if ($stops_i > 1) {
							// include the distance for the gaps between segments
							$total_shape_dist_traveled = $total_shape_dist_traveled + gap_between_segments($last_segment_id,$shape_segment_id);}
								
							// query for shape_points
							$shape_points_query = "select * from shape_points where shape_segment_id = $shape_segment_id order by shape_pt_sequence ASC;";
								
							// echo '<p>'.$shape_points_query.'</p>';
								
							$shape_points_result = mysql_query($shape_points_query);
							$shape_pt_i=0;
								
							// now, loop over shape_points
							while ($shape_points_row = mysql_fetch_array($shape_points_result, MYSQL_ASSOC)) {

								// only do this on the second time round and after
								if ($shape_pt_i != 0) {
									$total_shape_dist_traveled = $total_shape_dist_traveled+distance($shape_points_row['shape_pt_lat'], $shape_points_row['shape_pt_lon'], $last_lat, $last_lon,"m");
								}

								$last_lat = $shape_points_row["shape_pt_lat"];
								$last_lon = $shape_points_row["shape_pt_lon"];

								// add row to shapes_csv
								$shapes_csv=$shapes_csv.$shape_id.",".$shape_points_row["shape_pt_lat"].",".$shape_points_row["shape_pt_lon"].",".$shp_pt_sequence.",".$total_shape_dist_traveled."\n";

								$shp_pt_sequence++;
								$shape_pt_i++;
							}
								
							$last_segment_id = $shape_segment_id;
					}
						
					$last_stop_id=$stops_array[$stops_i];
				}
			}

			// write down shapes.txt
			$save_file=$path."shapes.txt";
			$shapes_file=fopen($save_file,'w+');
			$file_write_result=fwrite($shapes_file,$shapes_csv);
			fclose($shapes_file);

			echo "shapes.txt length ".$file_write_result."<br>";
		}

		// notify me
		if ($_SESSION['user_id'] != 5) {
			mail ("aaron@trilliumtransit.com", 'A new Google Transit feed file for HumCo has just been requested.', "", 'From: automailer@trilliumtransit.com');
		}

		// create the zip archive
		$zip_archive_path="/hsphere/local/home/aaronant/trilliumtransit.com/transit_feeds/".$agency_group_path."/".date("Ymd")."/";
		$zip_relative_archive_path="../transit_feeds/".$agency_group_path."/".date("Ymd")."/";

		// mkdir($zip_archive_path, 0777);

		// $createZip = new createZip;

		// $fileContents = file_get_contents("../transit_feeds/temp/humboldt/stops.txt");
		// $createZip -> addFile($fileContents, "stops.txt");

		$fileName = $zip_relative_archive_path."google_transit.zip";
		// $fd = fopen ($fileName, "wb");
		// $out = fwrite ($fd, $createZip -> getZippedfile());
		// fclose ($fd);

		// $createZip -> forceDownload($fileName);
		// @unlink($fileName);


		// End conditional if logged in
	} else {
		echo '<p>Your request to publish an updated Google Transit feed has been logged.  Trillium staff will process this request by outputting, validating, and re-publishing your feed soon.  You will be contacted.</p>';

		mail ("aaron@trilliumtransit.com", "A new Google Transit feed file for $agency_group_path has just been requested by ".$_SESSION['first_name'], "", 'From: automailer@trilliumtransit.com');
	}
} else {
	echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';
}


// var_dump(get_defined_vars());

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>