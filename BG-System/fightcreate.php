<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';

if(isset($_GET['a']) && $_GET['a'] == 'create' && isset($_POST['name']) 
	&& isset($_POST['team1']) && isset($_POST['team2']) && is_numeric($_POST['team1']) && isset($_POST['team2']))
{
	
	$fID = Fight::GetPlayerFight($database, $user->Get('id'));
	if($fID == 0)
	{
		$name = $_POST['name'];
		$team1 = $_POST['team1'];
		$team2 = $_POST['team2'];
		$mode = $team1.'vs'.$team2;
		$mode = Fight::ValidateMode($mode);
		
		
		if($mode == null)
		{
			echo 'Der Modus ist ungültig.';
		}
		else
		{
			$money = 0;
			$items = array();
			$keepStats = 0;
			$levelup = 0;
			$fight = Fight::CreateFight($database, $name, $mode, $money, $items, $keepStats, $levelup);
			$isnpc = 0;
			$team = 0;
			$fighter = $fight->AddPlayerFighter($database, $user, $team);
										  
			$attacks = $user->GetAttacks();
			$fighter->AddAttacks($attacks);
			
			$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
			header("Refresh: 3; Url=$actual_link");
			?>
			Der Kampf wurde erstellt.<br/>
			Du wirst nun weitergeleitet.
			<?php 
		}
	}
	else
	{
		echo 'Du befindest dich schon in ein Kampf.';
	}
}


?>

<table>
<tr>

<table>
<form method="POST" action="fightcreate.php?a=create">
<tr>
<td>Name</td>
<td><input type="text" placeholder="Name" name="name"/></td>
</tr>
<tr>
<td>Modus</td>
<td>
<select name="team1"><?php for($i =1; $i <= 99; ++$i) { ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php } ?></select>
vs
<select name="team2"><?php for($i =1; $i <= 99; ++$i) { ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php } ?></select>
</td>
</tr>
<tr><td colspan="2"><input type="submit" value="Erstellen"></td></tr>
</table>
<br/>
<a href="index.php">Zurück</a>