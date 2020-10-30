<?php
session_start();

include_once 'classes/database.php';
include_once 'classes/user.php';

$user = User::GetLoggedIn($database, session_id());
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
header("Refresh: 3; Url=$actual_link");
if($user == null)
{
	?>
	Du bist bereits ausgeloggt.<br/>
	Du wirst gleich weitergeleitet.
	<?php
}
else
{
	$user->Logout();
	?>
	Du wurdest erfolgreich ausgelogt.<br/>
	Du wirst gleich weitergeleitet.
	<?php
}
?>