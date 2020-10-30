<?php
session_start();

include_once 'classes/database.php';
include_once 'classes/user.php';

$user = User::GetLoggedIn($database, session_id());
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
header("Refresh: 3; Url=$actual_link");
if($user != null)
{
	?>
	Du bist bereits eingeloggt.<br/>
	Du wirst gleich weitergeleitet.
	<?php
}
else
{
	$login = $_POST['login'];
	$password = $_POST['password'];
	$name = $_POST['name'];
	$user = User::Register($database, $login, $password, $name);
	if($user == null)
	{
		?>
		Es gibt bereits einen Charakter mit den Namen <?php echo $name; ?><br/>
		Du wirst gleich weitergeleitet.
		<?php
	}
	else
	{
		User::Login($database, $login, $password, session_id());
		?>
		Du wurdest erfolgreich registriert.<br/>
		Du wirst gleich weitergeleitet.
		<?php
	}
}
?>