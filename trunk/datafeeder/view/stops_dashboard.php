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

echo '<p>&laquo; Go back to <a href="index.php">agency dashboard</a></p>';

// Display the agency name as a header

while ($row = mysql_fetch_array($agencies_result, MYSQL_ASSOC)) {
echo '<h2>'.$row['agency_name']. '</h2>';}

echo '<p><a href="add_stop.php?agency_id='.$_GET['agency_id'].'">Add stop</a></p>';

echo '<h3>Stops</h3>';

echo '<p><i>Click a stop name to make changes</i></p>';

$stops_query = "select stops.*, zones.zone_name,directions.direction_label,stop_code from stops left join zones on stops.zone_id=zones.zone_id left join directions on stops.direction_id=directions.direction_id where stops.agency_id = ".$_GET['agency_id']." order by stop_list_order asc";
$stops_result = mysql_query($stops_query);

if ($stops_result) {

echo '<table border="0" cellspacing="0" cellpadding="2">
<tr>
<th>List order &nbsp;&nbsp;</th>
<th>Stop code &nbsp;&nbsp;</th>
<th>Name &nbsp;&nbsp;</th>
<th>Direction &nbsp;&nbsp;</th>
<th>Zone &nbsp;&nbsp;</th>
<th>Delete &nbsp;&nbsp;</th>
</tr>
';

while ($row = mysql_fetch_array($stops_result, MYSQL_ASSOC)) {
echo '<tr><td>' . $row['stop_list_order'] . ' &nbsp;&nbsp;</td><td>' . $row['stop_code'] . ' &nbsp;&nbsp;</td><td><a href="add_stop.php?stop_id='.$row['stop_id'].'&agency_id='.$_GET['agency_id'].'">' . $row['stop_name'] . '</a> ';
if ($row['location_type']==1) {echo '(Station)';}
echo '&nbsp;&nbsp;</td><td>'.$row['direction_label'].'&nbsp;&nbsp;</td><td>' . $row['zone_name'] . ' &nbsp;&nbsp;'. '</td><td><a onClick="return confirm(\'Are you sure you want to delete '.str_replace ( "'", "&rsquo;", $row['stop_name']).' and all stop times associated with it?\');" href="delete_item.php?agency_id='.$agency_id.'&item=stop&stop_id='.$row['stop_id'].'"><img src="images/drop.png" border="0"></a></td>';

// end while loop

}

echo '</table>';

// end conditional for stops in the database

}

// if there are no stops in the database

else {echo '<p>There are no stops in the database.</p>';}


echo '<p><a href="add_stop.php?agency_id='.$_GET['agency_id'].'">Add stop</a></p>';

// End conditional if logged in


echo '<h3>Directions</h3>';

$directions_query = "select * from directions where agency_id=$agency_id;";
$directions_result = mysql_query($directions_query);

if (mysql_num_rows($directions_result) != 0) {

echo '<table border="0" cellspacing="0" cellpadding="2">
<tr>
<th>Direction label</th>
<th>Delete &nbsp;&nbsp;</th>
</tr>
';

while ($row = mysql_fetch_array($directions_result, MYSQL_ASSOC)) {
echo '<tr><td>'.$row['direction_label'].'</td><td><a onClick="return confirm(\'Are you sure you want to delete '.str_replace ( "'", "&rsquo;", $row['direction_label']).'?\');" href="delete_item.php?agency_id='.$agency_id.'&item=direction&direction_id='.$row['direction_id'].'"><img src="images/drop.png" border="0"></a></td></tr>
';
}

echo '</table>';

}

else {echo '<p>There are no directions defined.</p>';}

echo '<p><a href="add_direction.php?agency_id='.$_GET['agency_id'].'">Add direction</a></p>';

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>