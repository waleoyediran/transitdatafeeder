<?php # Script 13.5 - index.php
// This is the main page for the site.

// Include the configuration file for error management and such.
require_once ('./includes/config.inc.php'); 


// Set the page title and include the HTML header.
$page_title = 'Preferred transfers | Trillium WebSchedule';
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

// Query for blocks and the number of trips that are assigned to them
$transfers_query = "SELECT t1.transfer_id, t1.transfer_type, t1.from_stop_id, t1.to_stop_id, t1.transfer_type, t1.min_transfer_time, t2.stop_name AS from_stop_name, t3.stop_name AS to_stop_name, t4.direction_label AS from_direction_label, t5.direction_label AS to_direction_label FROM transfers AS t1 INNER JOIN stops AS t2 ON t1.from_stop_id=t2.stop_id INNER JOIN stops AS t3 ON t1.to_stop_id=t3.stop_id INNER JOIN directions AS t4 ON t2.direction_id=t4.direction_id INNER JOIN directions AS t5 ON t3.direction_id=t5.direction_id where t1.agency_id = ".mysql_real_escape_string($_GET['agency_id']).";";
$transfers_result = mysql_query($transfers_query);

if (mysql_num_rows($transfers_result) > 0) {

echo '<h3>Preferred transfers</h3>

<table>
<tr><th>Transfer ID&nbsp;&nbsp;&nbsp;&nbsp;</th><th>From stop&nbsp;&nbsp;&nbsp;&nbsp;</th><th>To stop&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Transfer type&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Min transfer time&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Modify</th><th>Delete</th></tr>';

while ($row = mysql_fetch_array($transfers_result, MYSQL_ASSOC)) {

echo '<tr><td>'.$row['transfer_id'].'&nbsp;&nbsp;&nbsp;&nbsp;</td></td><td>'.$row['from_stop_name'].' - '.$row['from_direction_label'].' (' .$row['from_stop_id'].')&nbsp;&nbsp;&nbsp;&nbsp;</td><td>'.$row['to_stop_name'].' - '.$row['to_direction_label'].' (' .$row['to_stop_id'].')&nbsp;&nbsp;&nbsp;&nbsp;</td>';

if ($row['transfer_type'] == 0) {$transfer_type_text = 'Recommended transfer point';}
elseif ($row['transfer_type'] == 1) {$transfer_type_text = 'Guaranteed timed transfer';}
elseif ($row['transfer_type'] == 2) {$transfer_type_text = 'Transfer requires minimum time between arrival and departure';}
elseif ($row['transfer_type'] == 3) {$transfer_type_text = 'No tranfers possible';}

echo '<td>'.$transfer_type_text.'&nbsp;&nbsp;&nbsp;&nbsp;</td>';

echo '<td>';

if (is_null($row['min_transfer_time'])) {echo 'n/a';} else {$transfer_minutes=$row['min_transfer_time']/60; echo $transfer_minutes;}

echo '&nbsp;&nbsp;&nbsp;&nbsp;</td>';

echo '<td><a href="add_transfer.php?transfer_id='.$row['transfer_id'].'&agency_id='.$agency_id.'"><img src="images/edit.png" width="16" height="16" border="0"></a></td>

<td>';

echo "<a onClick=\"return confirm('Are you sure you want to delete transfer id #".$row['transfer_id']."?');\" href=\"delete_item.php?transfer_id=".$row['transfer_id']."&agency_id=$agency_id&item=transfer"."\"><img src=\"images/drop.png\" width=\"16\" height=\"16\" border=\"0\"></a>";

echo '</td></tr>';

}

echo '</table>';

}

else {echo '<p>There are no preferred transfers to display for this agency.</p>';}

echo '<p><a href="add_transfer.php?agency_id='.$_GET['agency_id'].'">Add new transfer preference</a></p>';

// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>