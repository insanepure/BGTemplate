<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
	?>
	Der Kampf existiert nicht.<br/>
	Du wirst nun weitergeleitet.
	<?php 
	header("Refresh: 3; Url=$actual_link");
	exit();
}

//hole die Daten vom Kampf abhängig von der fID
$fight = new Fight($database, $_GET['id']);
//Überprüfe ob der Kampf überhaupt existiert
if(!$fight->IsValid())
{
	?>
	Der Kampf ist ungültig.<br/>
	Du wirst nun weitergeleitet.
	<?php 
	header("Refresh: 3; Url=$actual_link");
	exit();
}

if(!$fight->IsOpen())
{
	?>
	Der Kampf ist nicht mehr offen.<br/>
	Du wirst nun weitergeleitet.
	<?php 
	header("Refresh: 3; Url=$actual_link");
	exit();
}


if(isset($_GET['a']) && $_GET['a'] == 'join' && isset($_GET['team']) && is_numeric($_GET['team']))
{
	$modes = explode('vs',$fight->Get('mode'));
	$team = $_GET['team']-1;
	if($team >= count($modes) || $team < 0)
	{
		?>
		Das Team ist ungültig.<br/>
		<?php 
	}
	else
	{
		$teamPlayers = $fight->GetTeam($team);
		$teamMemberAmount = count($teamPlayers);
		if($teamMemberAmount == $modes[$team])
		{
			?>
			Das Team ist schon voll.<br/>
			<?php 
		}
		else
		{
			$isnpc = 0;
			$fighter = $fight->AddFighter($database
										  ,$user->Get('name')
										  ,$user->Get('profileimage')
										  ,$user->Get('id')
										  ,$team
										  ,$user->Get('ki')
										  ,$user->Get('atk')
										  ,$user->Get('def')
										  ,$user->Get('lp')
										  ,$user->Get('mlp')
										  ,$user->Get('kp')
										  ,$user->Get('mkp')
										  ,$isnpc);
										  
			$attacks = $user->GetAttacks();
			$fighter->AddAttacks($attacks);
			?>
			Du bist den Kampf beigetreten.<br/>
			Du wirst nun weitergeleitet.
			<?php 
			header("Refresh: 3; Url=$actual_link");
			exit();
		}
	}
}


?>

<table>
<tr>

<table>
<tr>
<td>Name:</td>
<td><?php echo $fight->Get('name'); ?></td>
</tr>
<tr>
<td>Modus</td>
<td><?php echo $fight->Get('mode'); ?></td>
</tr>
<?php
$modes = explode('vs',$fight->Get('mode'));
$team = 1;
foreach ($modes as &$mode) 
{
	$teamPlayers = $fight->GetTeam($team-1);
	?>
	<tr>
	<td>Team <?php echo $team; ?></td>
	<td>
	<?php 
	$num = 0;
	if($teamPlayers != null)
	{
		foreach($teamPlayers as &$teamMember)
		{
			if($num != 0)
			{
				echo ', ';
			}
			echo $teamMember->Get('name');
			++$num;
		}
	}
	
	if($num < $mode)
	{
		?>
		<form method="POST" action="fightjoin.php?id=<?php echo $_GET['id']; ?>&team=<?php echo $team; ?>&a=join">
		<input type="submit" value="Beitreten"/>
		</form>
		<?php
	}
	?>
	</td>
	</tr>
	<?php
	$team++;
}
?>
</table>
<br/>
<a href="index.php">Zurück</a>