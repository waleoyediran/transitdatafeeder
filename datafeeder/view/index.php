<?php # Script 13.5 - index.php
// This is the main page for the site.

// Include the configuration file for error management and such.
require_once ('./includes/config.inc.php'); 


// Set the page title and include the HTML header.
$page_title = 'Transit Schedule Manager';
include ('./includes/header.html');

// Welcome the user (by name if they are logged in).
echo '<h3>Welcome';
if (isset($_SESSION['first_name'])) {
echo ", {$_SESSION['first_name']}!";}

echo '</h3>';

// if the user is logged in
if (isset($_SESSION['first_name'])) {

// Connect to the database
require_once ('mysql_connect.php');


// Query for agencies
$agencies_query = "select agency.* from user_permissions INNER JOIN agency ON user_permissions.agency_id = agency.agency_id where user_permissions.user_id=".$_SESSION['user_id'].";";
$agencies_result = mysql_query($agencies_query);

// If the query returs results
if  ($agencies_result) {


	// Display the results in a table
	echo '<table border="0">
		<tr><th>ID</th><th>Agency</th></tr>';


	while ($row = mysql_fetch_array($agencies_result, MYSQL_ASSOC)) {
		echo '<tr><td align="left">' . $row['agency_id'] . '</td><td align="left">' . '<a href="agency_dashboard.php?agency_id='. $row['agency_id'].'">' . $row['agency_name'] . '</td></tr>';

// End display loop

	}

	echo '</table>';

// End conditional for results

}

else {
	echo'<p>There are no agencies yet.</p>';
}

echo '<p><a href="add_agency.php">Add new agency</a></p>';


// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>