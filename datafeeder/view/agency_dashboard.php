<?php # Script 13.5 - index.php
// This is the main page for the site.

// Include the configuration file for error management and such.
require_once ('./includes/config.inc.php'); 


// Set the page title and include the HTML header.
$page_title = 'Transit Schedule Manager';
include ('./includes/header.html');

echo '';

if (isset($_GET['agency_id'])) {
	$agency_id=$_GET['agency_id'];
} else {
	$agency_id=$_POST['agency_id'];
}

// Connect to the database
require_once ('mysql_connect.php');


if (isset($_SESSION['user_id'])) {
	$user_permissions_query = "select * from user_permissions where agency_id = ".$agency_id." and user_id=".$_SESSION['user_id'];
	$user_permissions_result = mysql_query($user_permissions_query);
	$user_permissions_num_rows = mysql_num_rows($user_permissions_result);
}
else {
	$user_permissions_num_rows=0;
}

// if the user is logged in
if (isset($_SESSION['first_name']) && $user_permissions_num_rows >= 1) {



	// Query for agencies
	$agencies_query = "select * from agency where agency_id = ".$_GET['agency_id'];
	$agencies_result = mysql_query($agencies_query);

	echo '<p>&laquo; Go back to <a href="index.php">choose a different system</a></p>';

	// Display the agency name as a header

	while ($row = mysql_fetch_array($agencies_result, MYSQL_ASSOC)) {
		echo '<h2>'.$row['agency_name']. '</h2>';}

		echo '<div id="Two_Columns">';

	$routes_query = "select * from routes where agency_id = ".$_GET['agency_id'];
	$routes_result = mysql_query($routes_query);

	// If the query returs results
	if  ($routes_result) {



		echo '<div class="sidebox">
			<div class="boxhead"><h2>Routes</h2></div>
			<div class="boxbody">';


		echo '<table style="border:none;background:none;">
			<tr>
				<th>ID &nbsp;&nbsp;</th>
				<th>Name &nbsp;&nbsp;</th>
				<th>Delete</th>
			</tr>';

		while ($row = mysql_fetch_array($routes_result, MYSQL_ASSOC)) {
			echo "<tr><td>" . $row["route_id"] . " &nbsp;&nbsp;</td>";
			echo "<td><a href=\"route_dashboard.php?agency_id=".$_GET["agency_id"]."&route_id=".$row["route_id"]."\">".$row["route_short_name"];

			if ($row["route_short_name"] != '' && $row["route_long_name"] != '') {
				echo '-';
			}

			echo $row["route_long_name"]."</a> &nbsp;&nbsp;</td><td><a onClick=\"return confirm('Are you sure you want to delete route id#". $row["route_id"]." and all trips and stop times associated with it?');\" href=\"delete_item.php?agency_id=$agency_id&item=route&route_id=".$row["route_id"]."\"><img src=\"images/drop.png\" border=\"0\"></a></td></tr>"; 
		}

		echo '</table>
			<p><a href="add_route.php?agency_id='.$_GET['agency_id'].'&action=add"><img src="images/new.png" border="0" width="16" height="17"> Add route</a></p>

			<hr>

			<p><a href="blocks_dashboard.php?agency_id='.$_GET['agency_id'].'">Blocks dashboard</a></p>

		</div>
	</div>';

	// End conditional for results

	}


	echo '<div class="sidebox">
		<div class="boxhead"><h2>Stops</h2></div>
			<div class="boxbody">';

	echo '<p><a style="font-weight:bold;" href="stops_dashboard.php?agency_id='.$_GET['agency_id'].'">Manage stops</a></p>';


	echo '<p><a href="add_stop.php?agency_id='.$_GET['agency_id'].'"><img src="images/new.png" border="0" width="16" height="17"> Add stop</a></p>  

	<hr>

	<p><a style="font-weight:bold;" href="transfers_dashboard.php?agency_id='.$_GET['agency_id'].'">Set prefered transfers</a></p>

	</div>
</div>

</div>
<div id="Two_Columns">


<div class="sidebox">
	<div class="boxhead"><h2>Fares</h2></div>
	<div class="boxbody">

<p><a style="font-weight:bold;" href="fare_dashboard.php?agency_id='.$_GET['agency_id'].'">Set zones, fares & rules</a></p>
</div></div>

<div class="sidebox">
	<div class="boxhead"><h2>Service calendar</h2></div>
	<div class="boxbody">';

	echo '<p><a style="font-weight:bold;" href="calendar_dashboard.php?agency_id='.$_GET['agency_id'].'">Weekly service schedules (Mon-Sun)</a></p>';

	echo '<p><a style="font-weight:bold;" href="service_schedule_groups_dashboard.php?agency_id='.$_GET['agency_id'].'">Service dates (yearly schedule)</a></p>';

	echo '<p><a style="font-weight:bold;" href="exceptions.php?agency_id='.$_GET['agency_id'].'">Holidays / Service date exceptions</a></p>';

	echo '</div></div>';

	echo '<div class="sidebox">
	<div class="boxhead"><h2>News items</h2></div>
	<div class="boxbody">';

	echo '<p><a style="font-weight:bold;" href="news_dashboard.php?agency_id='.$_GET['agency_id'].'">Add, delete, and change news items</a></p></div></div>';

	echo '<div class="sidebox">
	<div class="boxhead"><h2>Google Transit</h2></div>
	<div class="boxbody">';

	echo '<p><a style="font-weight:bold;" href="output_feeds.php?agency_id='.$_GET['agency_id'].'">Re-publish feed files for Google Transit</a></p></div></div>';

// End conditional if logged in

	echo '</div><br clear="all"/>';

}

else {
	echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';
}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>