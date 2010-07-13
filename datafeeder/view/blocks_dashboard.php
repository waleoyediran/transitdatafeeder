<?php # Script 13.5 - index.php
// This is the main page for the site.

// Include the configuration file for error management and such.
require_once ('./includes/config.inc.php'); 


// Set the page title and include the HTML header.
$page_title = 'Blocks | Trillium WebSchedule';
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
$blocks_query = "SELECT blocks.block_id,block_label,COUNT(trips.trip_id) as num_trips FROM blocks LEFT JOIN trips ON blocks.block_id=trips.block_id where blocks.agency_id = ".mysql_real_escape_string($_GET['agency_id'])." GROUP BY blocks.block_id;";
$blocks_result = mysql_query($blocks_query);

if (mysql_num_rows($blocks_result) > 0) {

echo '<h3>Blocks</h3>

<table>
<tr><th>Block ID&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Label&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Number of trips&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Delete</th></tr>';

while ($row = mysql_fetch_array($blocks_result, MYSQL_ASSOC)) {

echo '<tr><td>'.$row['block_id'].'&nbsp;&nbsp;&nbsp;&nbsp;</td></td><td><a href="block_details.php?block_id='.$row['block_id'].'&agency_id='.$agency_id.'">'.$row['block_label'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>'.$row['num_trips'].'&nbsp;&nbsp;&nbsp;&nbsp;</td><td>';

echo "<a onClick=\"return confirm('Are you sure you want to delete block id #".$row['block_id']." and all stop times associated with it?');\" href=\"delete_item.php?block_id=".$row['block_id']."&agency_id=$agency_id&item=block"."\"><img src=\"images/drop.png\" width=\"16\" height=\"16\" border=\"0\"></a>";

echo '</td></tr>';

}

echo '</table>';

}

else {echo '<p>There are no blocks to display for this agency.</p>';}

echo '<p><a href="add_block.php?agency_id='.$_GET['agency_id'].'">Add new block</a></p>';

// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>