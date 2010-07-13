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







echo '<h3><i>Service exceptions</h3>';

// Query for calendar_dates
// Here are the resouces I used for the double-join:
// http://www.thescripts.com/forum/thread572649.html
// http://dev.mysql.com/doc/refman/5.0/en/join.html

$calendar_dates_query = "select calendar_dates.description, calendar_dates.calendar_date_id, date_format(calendar_dates.date, '%e %b %Y') as formated_date, service_add.service_label as service_name_added, service_remove.service_label as service_name_removed from calendar_dates
left join calendar as service_add on service_add.calendar_id=calendar_dates.service_added  
left join calendar as service_remove on service_remove.calendar_id=calendar_dates.service_removed
where calendar_dates.agency_id = ".$_GET['agency_id']." order by calendar_dates.date asc";
$calendar_dates_result = mysql_query($calendar_dates_query);

echo '<h5>Exception dates</h5>';

if ($calendar_dates_result) {

	echo '<table>
<tr><th style="padding-right:20px">Date</th><th style="padding-right:20px">Holiday / exception description</th><th>Delete</th>';


	// begin while loop
	while ($row = mysql_fetch_array($calendar_dates_result, MYSQL_ASSOC)) {
		echo '<tr><td>';
		echo $row['formated_date'];
		echo ' &nbsp;&nbsp;</td><td><a href="add_calendar_date.php?agency_id='.$agency_id.'&calendar_date_id='.$row['calendar_date_id'].'"><img src="images/edit.png" border="0">';
		echo $row['description'];
		echo '</a> &nbsp;&nbsp;</td>';
		echo '<td><a onClick="return confirm(\'Are you sure you want to delete service exception dates id #'.$row['calendar_date_id'].'?\');"  href="delete_item.php?agency_id='.$agency_id.'&item=calendar_date&calendar_date_id='.$row['calendar_date_id'].'"><img src="images/drop.png" border="0"></a></td>

</tr>';

		// end while loop
	}

	echo '</table>';

} else {
	echo '<p>There are no service date exceptions.</p>';
}





echo '<p><a href="add_calendar_date.php?&agency_id='.$_GET['agency_id'].'">Add new exception date</a></p>';

// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>