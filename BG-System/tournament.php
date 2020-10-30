<?php
include_once 'classes/head.php';
include_once 'classes/tournament.php';
include_once 'classes/npcs.php';
include_once 'classes/fight.php';


if(isset($_GET['a']) && $_GET['a'] == 'create' && isset($_POST['name']) 
	&& isset($_POST['start']) && isset($_POST['minstart'])
	&& isset($_POST['itemid']) && isset($_POST['amount']) && isset($_POST['type']))
{
	$name = $_POST['name'];
	$start = $_POST['start'];
	$minstart = $_POST['minstart'];
	$itemid = $_POST['itemid'];
	$amount = $_POST['amount'];
	$type = $_POST['type'];
	Tournament::Create($database, $name, $start, $minstart, $itemid, $amount, $type);
}

$tid = Tournament::GetPlayerTournament($database, $user->Get('id'));
$tournament = null;
if(isset($_GET['id']) && is_numeric($_GET['id']))
{
	$id = $_GET['id'];
	$tournament = new Tournament($database, $id);
}
else if($tid != -1)
{
	$tournament = new Tournament($database, $tid);
}


if($tournament != null && $tournament->IsValid())
{
	$started = strtotime('now') >= strtotime($tournament->Get('start'));
	
	if($started && !$tournament->HasEnoughPlayers())
	{
		$tournament->Cancel();
		?>Das Turnier wurde abgesagt.<br/><?php
	}
	
	if($started && $tournament->CanUserFight($user->Get('id')) && isset($_GET['a']) && $_GET['a'] == 'fight')
	{
		$filter = '';
		$NPCManager = new NPCManager($database, $filter);
	
		$enemyBracket = $tournament->GetEnemyBracket($user->Get('id'), false, $tournament->Get('round'));
		if($enemyBracket != null)
		{
			$fID = Fight::GetPlayerFight($database, $user->Get('id'));
			if($fID != 0)
			{
				header('Location: fight.php');
				exit;
			}
			if($enemyBracket->Get('isnpc') == 0 && Fight::GetPlayerFight($database, $enemyBracket->Get('userid')) != 0)
			{
				?>Dein Gegner ist schon in einem Kampf.<br/><?php
			}
			else
			{
				//Alle Daten sind nun verifiziert, nun öffne den Kampf
				//Definiere den Kampfnamen
				$name = 'Turnierkampf';
				//Definiere den Modus, aktuell ist es 1vs1, aber auch 5vs5 oder 2vs2 ist möglich
				$team = 0;
				$isNPC = false;
				$mode = '1vs1';
				$money = 0;
				//Format ist ID@Amount@Type
				$items = array();
				$keepStats = 0;
				$levelup = 0;
				$tid = $tournament->Get('id');
				$fight = Fight::CreateFight($database, $name, $mode, $money, $items, $keepStats, $levelup, $tid);
				
				//Nun erstelle für den Kampf die teilnehmer
				//Wir nehmen die Werte die wir vorher verifiziert haben, du solltest hier die Werte von dem aktuellen Spieler einfügen

				$fighter = $fight->AddPlayerFighter($database, $user, $team);
												  
				$attacks = $user->GetAttacks();
				$fighter->AddAttacks($attacks);
				
				//Nun zu Spieler 2
				$team = 1;
				$fighter2 = null;
				$attacks = null;
				if($enemyBracket->Get('isnpc'))
				{
					$npc = $NPCManager->Get($enemyBracket->Get('userid'));
					$fighter2 = $fight->AddNPCFighter($database, $npc, $team);
					$attacks = explode(';',$npc->Get('attacks'));
				}
				else
				{
					$enemy = new User($database, $enemyBracket->Get('userid'));
					$fighter2 = $fight->AddPlayerFighter($database, $enemy, $team);
					$attacks = $enemy->GetAttacks();
				}
				
				//ebenfalls müssen hier noch die Attacken hinzugefügt werden
				$fighter2->AddAttacks($attacks);
				
				header('Location: fight.php');
				exit;
			}
		}
	}
	
	
	if(isset($_GET['a']) && $_GET['a'] == 'continue' && $started)
	{
		$tournament->UpdateAllBrackets();
	}
	
	if(isset($_GET['a']) && $_GET['a'] == 'join' && !$started)
	{
		$joined = $tournament->Join($user->Get('id'), $user->Get('name'));
		if($joined)
		{
			?>Du bist dem Turnier beigetreten.<br/><br/><?php
		}
		else
		{
			?>Du bist schon im Turnier.<br/><br/><?php
		}
	}
	else if(isset($_GET['a']) && $_GET['a'] == 'leave' && !$started)
	{
		if($tournament->Leave($user->Get('id')))
		{
			?>Du hast das Turnier verlassen.<br/><br/><?php
		}
		else
		{
			?>Du bist nicht im Turnier.<br/><br/><?php
		}
	}
}

?>
<?php
if($tournament != null)
{
	if(!$tournament->IsValid())
	{
		?>Das Turnier existiert nicht.<br><?php
	}
	else if(strtotime('now') < strtotime($tournament->Get('start')))
	{
		$participants = $tournament->GetParticipants();
		?>Das Turnier startet am <?php echo $tournament->Get('start'); ?>.<br/>
		  Momentan sind <?php echo $participants; ?> Teilnehmer von <?php echo $tournament->Get('minstart'); ?> registriert.<br/>
		  <br/>
		  <?php
		  if($tournament->IsInRound($user->Get('id'), 0))
		  {
			  ?>
			  <a href="tournament.php?id=<?php echo $id; ?>&a=leave">Verlassen</a><br/>
			  <?php
		  }
		  else
		  {
			  ?>
			  <a href="tournament.php?id=<?php echo $id; ?>&a=join">Beitreten</a><br/>
			  <?php
		  }
	}
	else
	{
		$participants = $tournament->GetParticipants();

		$round = array();
		$i = 0;
		$pCount = $participants;
		while($participants > 1)
		{
			$round[$i] = $pCount;
			if($pCount == 1)
				break;
			$pCount = ceil($pCount/2);
			++$i;
		}

		?>
		<table border="0">
		
		<?php
		for($i = count($round)-1; $i >= 0; --$i)
		{
			$currentRound = $i;
			?><tr><?php
			$brackets = $tournament->GetRoundBrackets($i);
			$roundPlayers = $round[$i];
			for($j = 0; $j < $roundPlayers; ++$j)
			{
				$mCols = ($currentRound * 2);
				?><td width="50px" height="25px" colspan="<?php echo $mCols; ?>" align="center">
				<?php
				//echo $currentRound.' / '.$mCols.' ';
				if(isset($brackets[$j]))
				{
					$defeated = $brackets[$j]->Get('defeated');
					if($defeated)
					{
						?><s><?php
					}
					echo $brackets[$j]->Get('username');
					if($defeated)
					{
						?></s><?php
					}
				}
				?>
				</td><?php
			}
			?></tr><?php
		}
		?>
		</table>
		<br/>
		<?php
		if($tournament->CanUserFight($user->Get('id')))
		{
			?><a href="tournament.php?a=fight">Kampf starten</a><?php
		}
		else if($tournament->Get('end') == 0)
		{
			?><a href="tournament.php?id=<?php echo $tournament->Get('id'); ?>&a=continue">Weiter</a><?php
		}
	}
}
else
{
	?>
	Das Menü ist nur zum testen gedacht.
	<form method="POST" action="tournament.php?a=create">
	<table>
	<tr><td>Name </td><td><input type="text" name="name" placeholder="Name"/></tr>
	<tr><td>Start </td><td><input type="datetime-local" name="start"/></tr>
	<tr><td>Min </td><td><input type="number" name="minstart" placeholder="4"/></tr>
	<tr><td>ItemID </td><td><input type="number" name="itemid" placeholder="1"/></tr>
	<tr><td>Anzahl </td><td><input type="number" name="amount" placeholder="10"/></tr>
	<tr><td>Type </td><td><input type="number" name="type" placeholder="1"/></tr>
	<tr><td colspan="2"><input type="submit" name="Erstellen"/></td></tr>
	</table>
	</form>
	<br/>
	<br/>
	
	<table>
	<tr>
	<td>Name</td>
	<td>Startdatum</td>
	<td>Runde</td>
	<td></td>
	</tr>
	<?php
	$result = $database->Select('id, name, start, round, end', Tournament::$table, '');
	if ($result) 
	{
		$rank = 0;
		if ($result->num_rows > 0)
		{
			while($row = $result->fetch_assoc()) 
			{
				?>
				<tr>
				<td><?php echo $row['name']; ?></td>
				<td><?php echo $row['start']; ?></td>
				<td><?php echo $row['round']+1; ?></td>

				<td><a href="tournament.php?id=<?php echo $row['id']; ?>">
				<?php 
				
				$started = strtotime('now') >= strtotime($row['start']);
				if(!$started) { ?> Anmeldung <?php }
				else if($row['end']){ ?>Beendet<?php } 
				else { ?> Zuschauen <?php } ?></a></td>
				</tr>
				<?php
			}
		}
		$result->close();
	}		
	?>
	</table>
	<?php
}
?>

<br/>
<br/>
<a href="index.php">Zurück</a>