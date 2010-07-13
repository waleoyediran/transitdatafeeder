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

echo '<h3>News and service updates</h3>';

echo '<p><a href="add_news.php?agency_id='.$_GET['agency_id'].'">Add news item</a></p>';

echo '<p><i>Click a news item title to make changes</i></p>';

$news_query = "select news.*, date_format(start_date, '%e %b %Y') as formated_start_date, date_format(end_date, '%e %b %Y') as formated_end_date, news_categories.news_category_name from news left join news_categories on news.news_category_id=news_categories.news_category_id where news.agency_id = ".$_GET['agency_id']." order by news_category_id,start_date asc";
$news_result = mysql_query($news_query);

if ($news_result) {

echo '<table border="0" cellspacing="0" cellpadding="2">
<tr>
<th>ID &nbsp;&nbsp;</th>
<th>Title &nbsp;&nbsp;</th>
<th>Start date &nbsp;&nbsp;</th>
<th>End date &nbsp;&nbsp;</th>
<th>Type of news item &nbsp;&nbsp;</th>
<th>Delete &nbsp;&nbsp;</th>
</tr>
';

while ($row = mysql_fetch_array($news_result, MYSQL_ASSOC)) {
echo '<tr><td>' . $row['news_id'] . ' &nbsp;&nbsp;</td><td><a href="add_news.php?news_id='.$row['news_id'].'&agency_id='.$_GET['agency_id'].'">' . $row['title'] . '</a>  &nbsp;&nbsp;</td><td>' . $row['formated_start_date'] . ' &nbsp;&nbsp;'. '</td><td>'.$row['formated_end_date'] . ' &nbsp;&nbsp;'. '</td><td>'.$row['news_category_name'] . ' &nbsp;&nbsp;';

echo '</td><td><a onClick="return confirm(\'Are you sure you want to delete the news item "'.str_replace ( "'", "&rsquo;", $row['title']).'"?\');" href="delete_item.php?agency_id='.$agency_id.'&item=news&news_id='.$row['news_id'].'"><img src="images/drop.png" border="0"></a></td>';

// end while loop

}

echo '</table>';

// end conditional for stops in the database

}

// if there are no stops in the database

else {echo '<p>There are no news items in the database.</p>';}


echo '<p><a href="add_news.php?agency_id='.$_GET['agency_id'].'">Add news item</a></p>';

// End conditional if logged in

}
else {echo 'You are not logged in to use this page.  <a href="login.php">Log in here.</a>';}

?>

<?php // Include the HTML footer file.
include ('./includes/footer.html');
?>