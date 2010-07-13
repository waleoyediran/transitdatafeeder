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

echo '<p>&laquo; Go back to the <a href="agency_dashboard.php?agency_id='.$_GET['agency_id'].'">agency dashboard</a></p>';




$calendar_query = "select * from calendar where agency_id = ".$_GET['agency_id'];
$calendar_result = mysql_query($calendar_query);

if ($calendar_result) {
echo '<table style="border:none;background:none;">
<tr>
<th>ID &nbsp;&nbsp;</th>
<th>Service label &nbsp;&nbsp;</th>
<th>Delete &nbsp;&nbsp;</th>
</tr>';

while ($row = mysql_fetch_array($calendar_result, MYSQL_ASSOC)) {
echo "<tr><td>" . $row["calendar_id"] . " &nbsp;&nbsp;</td><td><a href=\"add_calendar.php?agency_id=".$_GET["agency_id"]."&calendar_id=".$row["calendar_id"]."\">".$row["service_label"]. "</a>  &nbsp;&nbsp;</td><td><a onClick=\"return confirm('Are you sure you want to delete service label ".str_replace ( '"', '&rsquo;', $row["service_label"])."?');\" href=\"delete_item.php?agency_id=$agency_id&item=calendar&calendar_id=".$row["calendar_id"]."\"><img src=\"images/drop.png\" border=\"0\"></a></td></tr>";}

echo '</table>';

// end conditional for service_groupings_result
}



else {echo '<p>There are no service day or date designations.</p>';}


echo '<p><a href="add_calendar.php?agency_id='.$_GET['agency_id'].'"><img src="images/new.png" border="0" width="16" height="17"> Add service group</a></p>';




// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>