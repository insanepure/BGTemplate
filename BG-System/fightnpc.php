<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';
include_once 'classes/npcs.php';

$filter = 'levelup=1';
$NPCManager = new NPCManager($database, $filter);

if(isset($_GET['a']) && $_GET['a'] == 'create' && isset($_GET['id']) && is_numeric($_GET['id']))
{
	$fID = Fight::GetPlayerFight($database, $user->Get('id'));
	if($fID == 0)
	{
		$npc = $NPCManager->Get($_GET['id']);
		if($npc == null)
		{
			?>Dieser NPC ist ungültig.<br/><?php
		}
		else
		{
			//Alle Daten sind nun verifiziert, nun öffne den Kampf
			//Definiere den Kampfnamen
			$name = 'NPCKampf';
			//Definiere den Modus, aktuell ist es 1vs1, aber auch 5vs5 oder 2vs2 ist möglich
			$team = 0;
			$isNPC = false;
			$mode = '1vs1';
			$money = $npc->Get('money');
			//Format ist ID@Amount@Type
			$items = explode(';',$npc->Get('items'));
			$keepStats = $npc->Get('keepstats');
			$levelup = $npc->Get('levelup');
			$fight = Fight::CreateFight($database, $name, $mode, $money, $items, $keepStats, $levelup);
			
			//Nun erstelle für den Kampf die teilnehmer
			//Wir nehmen die Werte die wir vorher verifiziert haben, du solltest hier die Werte von dem aktuellen Spieler einfügen

			$fighter = $fight->AddPlayerFighter($database, $user, $team);
											  
			$attacks = $user->GetAttacks();
			$fighter->AddAttacks($attacks);
			
			//Nun zu Spieler 2
			$isNPC = 1;
			$team = 1;
			$fighter2 = $fight->AddFighter($database, 
										   $npc->Get('name'), 
										   $npc->Get('image'), 
										   0, 
										   $team, 
										   $npc->Get('ki'), 
										   $npc->Get('atk'), 
										   $npc->Get('def'), 
										   $npc->Get('lp'), 
										   $npc->Get('lp'), 
										   $npc->Get('kp'), 
										   $npc->Get('kp'), 
										   $isNPC);
			
			//ebenfalls müssen hier noch die Attacken hinzugefügt werden
			$attacks = explode(';',$npc->Get('attacks'));
			$fighter2->AddAttacks($attacks);
			
			header('Location: fight.php');
			exit;
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
<tr><td colspan="2"><h2>Spieler</h2></td></tr>
<tr><td width="100px">Bild:</td> <td width="200px"><img src="<?php echo $user->Get('profileimage'); ?>" width="50px" height="50px"/></td></tr>
<tr><td width="100px">Name:</td> <td width="200px"><?php echo $user->Get('name'); ?></td></tr>
<tr><td width="100px">KI:</td> <td width="200px"><?php echo $user->Get('ki'); ?></td></tr>
<tr><td width="100px">LP:</td> <td width="200px"><?php echo $user->Get('lp').'/'.$user->Get('mlp'); if($user->Get('itemlp') != 0) echo ' +'.$user->Get('itemlp'); ?></td></tr>
<tr><td width="100px">KP:</td> <td width="200px"><?php echo $user->Get('kp').'/'.$user->Get('mkp'); if($user->Get('itemkp') != 0) echo ' +'.$user->Get('itemkp'); ?></td></tr>
<tr><td width="100px">ATK:</td> <td width="200px"><?php echo $user->Get('atk'); if($user->Get('itematk') != 0) echo ' +'.$user->Get('itematk');?></td></tr>
<tr><td width="100px">DEF:</td> <td width="200px"><?php echo $user->Get('def'); if($user->Get('itemdef') != 0) echo ' +'.$user->Get('itemdef');?></td></tr>
</table>
<br/>
<h2>Gegner</h2>
<table><tr>
<?php
$npcs = $NPCManager->GetAll();
foreach($npcs as &$npc)
{
	?>
	<td><table>
	<tr><td width="100px">Bild:</td> <td width="200px"><img src="<?php echo $npc->Get('image'); ?>" width="50px" height="50px"/></td></tr>
	<tr><td width="100px">Name:</td> <td width="200px"><?php echo $npc->Get('name'); ?></td></tr>
	<tr><td width="100px">KI:</td> <td width="200px"><?php echo $npc->Get('ki'); ?></td></tr>
	<tr><td width="100px">LP:</td> <td width="200px"><?php echo $npc->Get('lp'); ?></td></tr>
	<tr><td width="100px">KP:</td> <td width="200px"><?php echo $npc->Get('kp'); ?></td></tr>
	<tr><td width="100px">ATK:</td> <td width="200px"><?php echo $npc->Get('atk'); ?></td></tr>
	<tr><td width="100px">DEF:</td> <td width="200px"><?php echo $npc->Get('def'); ?></td></tr>
	<tr><td colspan="2"><form method="POST" action="fightnpc.php?a=create&id=<?php echo $npc->Get('id'); ?>"><input type="submit" value="Erstellen"></form></td></tr>
	</table></td>
	<?php
}
?>
</tr></table>
<br/>
<br/>
<a href="index.php">Zurück</a>