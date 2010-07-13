<?php # Script 13.5 - index.php
// This is the main page for the site.

// Include the configuration file for error management and such.
require_once ('./includes/config.inc.php'); 


// Set the page title and include the HTML header.
$page_title = 'Transit Schedule Manager';
include ('./includes/header.html');

echo '';

if (isset($_GET['agency_id'])) {$agency_id=$_GET['agency_id'];} else {$agency_id=$_POST['agency_id'];}

// Connect to the database
require_once ('mysql_connect.php');


if (isset($_SESSION['user_id'])) {
$user_permissions_query = "select * from user_permissions where agency_id = ".$agency_id." and user_id=".$_SESSION['user_id'];
$user_permissions_result = mysql_query($user_permissions_query);
$user_permissions_num_rows = mysql_num_rows($user_permissions_result);
}
else {$user_permissions_num_rows=0;}

// if the user is logged in
if (isset($_SESSION['first_name']) && $user_permissions_num_rows >= 1) {

// Connect to the database
require_once ('mysql_connect.php');


// Query for agencies
$agencies_query = "select * from agency where agency_id = ".$_GET['agency_id'];
$agencies_result = mysql_query($agencies_query);











// Query for route
$routes_query = "select * from routes where route_id = ".$_GET['route_id'];
$routes_result = mysql_query($routes_query);

// set route_id
if (isset($_GET['route_id'])) {
	$route_id=$_GET['route_id'];
}
if (isset($_POST['route_id'])) {
	$route_id=$_POST['route_id'];
}

// Query for trip directions
if (isset($_GET['direction_id'])) {
	if ($_GET['direction_id'] != "0") {
		$direction_conditional = ' and direction_id='.mysql_real_escape_string($_GET['direction_id']);
	} else {
		$direction_conditional = ' and direction_id=0';
	}
} else {
	$direction_conditional = '';
}

$directions_query = "SELECT DISTINCT direction_id from trips where route_id = ".mysql_real_escape_string($_GET['route_id']). $direction_conditional;

$directions_result = mysql_query($directions_query);

$all_directions_query = "SELECT directions.direction_id,directions.direction_label,COUNT(trips.trip_id) as num_trips FROM directions LEFT JOIN trips ON directions.direction_id=trips.direction_id where trips.route_id = ".mysql_real_escape_string($_GET['route_id'])." GROUP BY direction_id;";
$all_directions_result = mysql_query($all_directions_query);

if (mysql_num_rows($all_directions_result)) {
	$only_direction=mysql_result($all_directions_result,0,"direction_label");
	mysql_data_seek ($all_directions_result, 0);
}

echo '<p>&laquo; Go back to the <a href="agency_dashboard.php?agency_id='.$_GET['agency_id'].'">agency dashboard</a></p>';

// Display the route dashboard & name as header

while ($row = mysql_fetch_array($routes_result, MYSQL_ASSOC)) {
	echo '<h2>Route Dashboard - <i>'.$row['route_short_name'].'</i></h2>';

	echo '<table>
<tr>
<th>ID</th>
<td>
';

	echo $row['route_id'];

	echo'</td></tr>
<tr><th align="right">Short Name:</th>
<td>';

	echo $row['route_short_name'];

	echo '</td></tr>
<tr><th>Long Name:</th>
<td>';

	if ($row['route_long_name']=="") {
		echo "<i>[No long name]</i>";
	} else {
		echo $row['route_long_name'];
	}

	echo '</td></tr>
<tr><th>Route description:&nbsp;&nbsp;</th>
<td>';

	if ($row['route_desc']=="") {
		echo "<i>[No description]</i>";
	} else {
		echo $row['route_desc'];
	}

	echo '</td></tr>
<tr><td><a href="add_route.php?agency_id='.$agency_id.'&action=modify&route_id='.$_GET['route_id'].'">Edit route details</a></td></tr>
</table>';

}


if (mysql_num_rows($all_directions_result) > 1) {

	echo '<form name="direction_select" method="get" action="route_dashboard.php"><p>Select a direction: <select name="direction_id" onchange="direction_select.submit()" size="1"><option value="NULL">Click...</option>';

	while ($row = mysql_fetch_array($all_directions_result, MYSQL_ASSOC)) {
		echo '<option value="'.$row['direction_id'].'"';

		if (isset($_GET['direction_id'])) {
			if ($_GET['direction_id'] == $row['direction_id']) {
				echo ' selected="selected"';
			}
		}

		echo '>'.$row['direction_label'].' ('.$row['num_trips'].' trips)</option>';
	}

	echo '</select></p><input type="hidden" name="agency_id" value="'.$agency_id.'"><input type="hidden" name="route_id" value="'.$_GET['route_id'].'"></form>';

} elseif (mysql_num_rows($all_directions_result) == 1 && $only_direction != "")
{
	echo '<h5>Direction &raquo; '.$only_direction.' </h5>';
}


if (mysql_num_rows($directions_result) == 1) {
	echo '<h3>Trips</h3>
<p><a href="add_trip.php?route_id='.$_GET['route_id'].'&agency_id='.$_GET['agency_id'].'&action=add">Add new trip</a></p>

<p><i>Click a trip headsign to view and edit stop times</i></p>';

	$trips_query = "select trips.trip_start_time,trips.based_on, MIN(arrival_time) as first_arrival, MAX(arrival_time) as last_arrival, trips.trip_id, trips.trip_headsign, calendar.service_label,trips.service_id from trips left join stop_times on trips.trip_id=stop_times.trip_id inner join calendar on trips.service_id=calendar.calendar_id where trips.route_id =" . $_GET['route_id'] .$direction_conditional." GROUP BY trips.trip_id ORDER BY service_label,trip_start_time,first_arrival";
	$trips_result = mysql_query($trips_query);

	if ($trips_result) {

		echo '<table>';

		$last_service_label="asdfasdfasdf";

		while ($row = mysql_fetch_array($trips_result, MYSQL_ASSOC)) {
			if ($last_service_label != $row['service_label']) {
				echo '<tr><td colspan="6" style="background-color:#ccc;font-size:10px;padding:0px;font-weight:bold;text-align:center;">'.$row['service_label'].' <a href="copy_service_for_route.php?agency_id='.$agency_id.'&route_id='.$route_id.'&service_id='.$row['service_id'].'"><img src="images/copy.png" width="16" height="16" border="0"></td></tr><tr><th>ID&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Headsign&nbsp;&nbsp;&nbsp;&nbsp;</th><th>First stop&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Last stop&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Delete</th><th>Copy</th></tr>';
			}

			echo '<tr><td>'.$row['trip_id'].'&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href="trip_dashboard.php?trip_id='.$row['trip_id'].'&agency_id='.$_GET['agency_id'].'">';

			if ($row['trip_headsign']== "") {
				echo '<i>No headsign</i>';
			} else {
				echo $row['trip_headsign'];
			}

			if ($row['based_on'] != "") {
				echo "<sup>*</sup>";
				$one_trip_basedon=1;
			}

			echo "</a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>";

			if ($row["trip_start_time"]=="") {
				echo $row["first_arrival"];
			} else {
				echo $row["trip_start_time"];
			}

			echo "&nbsp;&nbsp;&nbsp;&nbsp;</td><td>".$row["last_arrival"]."&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a onClick=\"return confirm('Are you sure you want to delete trip id #".$row['trip_id']." and all stop times associated with it?');\" href=\"delete_item.php?route_id=".$route_id."&agency_id=$agency_id&item=trip&trip_id=".$row["trip_id"]."\"><img src=\"images/drop.png\" width=\"16\" height=\"16\" border=\"0\"></a></td><td><a href=\"copy_trip.php?agency_id=$agency_id&route_id=$route_id&trip_id=".$row['trip_id']."\"><img src=\"images/copy.png\" width=\"16\" height=\"16\" border=\"0\"></td></tr>";

			$last_service_label=$row['service_label'];

		}

		if (isset($one_trip_basedon)) {
			echo '<tr><td colspan="4"><i><b><sup>*</sup> An asterix</b> means that a trip is based on another trip,<br/>borrowing its sequence of stop times and intervals.</i></td></tr>';
		}

		echo '</table>';

	} else {
		echo '<p>There are no trips to display.</p>';
	}
}

echo '<p><a href="add_trip.php?route_id='.$_GET['route_id'].'&agency_id='.$_GET['agency_id'].'&action=add">Add new trip</a></p>';















// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>