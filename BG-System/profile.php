<?php
include_once 'classes/head.php';
$player = $user;


if(isset($_GET['id']) && is_numeric($_GET['id']))
{
	$player = new User($database, $_GET['id']);
}

if(isset($_GET['a']) && $_GET['a'] == 'change')
{
	$image = $_POST['image'];
	$description = $_POST['description'];
	$user->ChangeProfile($image, $description);
	?>
	Das Profil wurde bearbeitet.<br/>
	<?php
}

if(isset($_GET['a']) && $_GET['a'] == 'challenge' && $player->Get('id') != $user->Get('id'))
{
	if($player->Get('challenger') != 0)
	{
		?>Der User wurde schon herausgefordert.<br/><?php
	}
	else if($user->Get('lp') == 0)
	{
		?>Du hast nicht genügend LP.<br/><?php
	}
	else if($player->Get('lp') == 0)
	{
		?>Der User hat nicht genügend LP.<br/><?php
	}
	else
	{
		$item = 0;
		if(isset($_GET['item']) && $player->GetItemByID($_GET['item']))
		{
			$item = $_GET['item'];
		}
		$player->Challenge($user->Get('id'), $item);
		?>Du hast <?php echo $player->Get('name'); ?> zum Kampf herausgefordert.<br/><?php
	}
}
?>

<img src="<?php echo $player->Get('profileimage'); ?>"/><br/>
Spieler: <?php echo $player->Get('name'); ?><br/>
<?php 
if($player == $user)
{
?>
	LP: <?php echo $user->Get('lp'); ?><br/>
	KP: <?php echo $user->Get('kp'); ?><br/>
	KI: <?php echo $user->Get('ki'); ?><br/>
	Angriff: <?php echo $user->Get('atk'); ?><br/>
	Verteidigung: <?php echo $user->Get('def'); ?><br/>
<?php
}
?>
<br/>
<?php echo $player->Get('profiledescription'); ?>
<br/>
<?php
if($player == $user)
{
?>
<table>
<form method="POST" action="profile.php?a=change">
<tr><td>Profilbild</td><td><input type="text" name="image" value="<?php echo $player->Get('profileimage'); ?>"/></td></tr>
<tr><td colspan="2"><textarea rows="10" cols="50" name="description"><?php echo $player->Get('profiledescription'); ?></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Ändern"/></td></tr>
</form>
</table>
<?php
}
else
{
?>
<br/>
<a href="profile.php?id=<?php echo $player->Get('id'); ?>&a=challenge&type=1">Herausfordern</a><br/>
<?php
$itemID = 5; // Dragonball ID
if($player->GetItemByID($itemID) != null)
	{
	?>
		<a href="profile.php?id=<?php echo $player->Get('id'); ?>&a=challenge&item=<?php echo $itemID; ?>">Dragonball Herausforderung</a><br/>
	<?php
	}
}
?>
<br/>
<br/>
<a href="index.php">Zurück </a>