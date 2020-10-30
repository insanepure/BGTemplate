<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';

if($user == null)
{
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
	header("Location: $actual_link");
}

$fID = 0;
if(isset($_GET['id']))
{
	$fID = $_GET['id'];
}
else
{
	$fID = Fight::GetPlayerFight($database, $user->Get('id'));
}


////Kommentiere dies aus um die Debug-Informationen zu den Datenbank-Abfragen zu erhalten
//$database->Debug();

//hole die Daten vom Kampf abhängig von der fID
$fight = new Fight($database, $fID);
//Überprüfe ob der Kampf überhaupt existiert
if(!$fight->IsValid())
{
	echo 'Der Kampf existiert nicht!<br/>';
	echo '<a href="fightcreate.php">Zurück</a>';
	exit();
}

if($fight->IsOpen())
{
	echo 'Der Kampf wurde noch nicht gestartet!<br/>';
	echo '<a href="fightlist.php">Zurück</a>';
	exit();
}


//Hole den Spielers
$player = $fight->GetUserFighter($user->Get('id'));
//Überprüfe ob der Spieler einen Angriff gewählt hat.
if($player != null && $fight->Get('state') == 2)
{
	if($player->Get('fuseid') != 0)
	{
		$player = $fight->Getfighter($player->Get('fuseid'));
	}
	
	
	if($player->Get('lp') == 0 || $player->Get('attack') != 0)
	{
		$fight->CheckRound();
	}
	else if(isset($_POST['atk']) && is_numeric($_POST['atk']) && isset($_POST['target']) && is_numeric($_POST['target']))
	{
		//Der Angriff wurde gewählt
		$atk = $_POST['atk'];
		$target = $_POST['target'];
		//Nun sage dem Spieler, er soll angreifen
		$fight->DoAttack($player, $atk, $target);
	}
	else if(isset($_GET['a']) && $_GET['a'] == 'kick' && isset($_GET['fid']) && is_numeric($_GET['fid']))
	{
		$fid = $_GET['fid'];
		$fighter = $fight->GetFighter($fid);
		if($fighter != null)
		{
			$fight->Kick($fighter);
		}
	}
	
	if($player->Get('fuseid') != 0)
	{
		$player = $fight->Getfighter($player->Get('fuseid'));
	}
	
}

////Kommentiere das hier aus um Debug Informationen zum Kampf zu erhalten
//$debugLog = implode('<br/>',Fight::GetDebugLog());
//echo $debugLog;
//echo '<br/><br/>';



//Nun stelle den Kampf dar
?>

<table width="100%">
<tr>
<td width="25%" align="center" >
<table>
<h2>Team 1</h2>
<?php
$team1 = $fight->GetTeam(0);
foreach($team1 as &$fighter)
{
	if($fighter->Get('fuseid') != 0)
	{
		continue;
	}
	echo '<tr><td>';
	echo '<img src="'.$fighter->Get('image').'" width="50px" height="50px"/><br/>';
	echo $fighter->Get('name').'<br/>';
	echo 'KI: '.$fighter->Get('ki').'<br/>';
	$width = 200;
	$height = 16;
	$lpWidth = $width * ($fighter->Get('lp')/$fighter->Get('mlp'));
	if($lpWidth > $width) $lpWidth = $width;
	$kpWidth = $width * ($fighter->Get('kp')/$fighter->Get('mkp'));
	if($kpWidth > $width)  $kpWidth = $width;
	?>
	<div style="position:absolute; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; border-style: solid; border-width: 1px;">
	<div style="position:relative; top:0px; height:<?php echo $height; ?>px; width:<?php echo $lpWidth; ?>px; background-color:#ff5555">
	<div style="position:relative; top:0px; width:<?php echo $width; ?>px;">
	<?php echo 'LP: '.$fighter->Get('lp').'/'.$fighter->Get('mlp'); ?>
	</div>
	</div>
	</div>
	<br/>
	<div style="margin-top:1px; position:absolute; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; border-style: solid; border-width: 1px;">
	<div style="position:relative; top:0px; height:<?php echo $height; ?>px; width:<?php echo $kpWidth; ?>px; background-color:#9999ff">
	<div style="position:relative; top:0px; width:<?php echo $width; ?>px;">
	<?php echo 'KP: '.$fighter->Get('kp').'/'.$fighter->Get('mkp'); ?>
	</div>
	</div>
	</div>
	<br/>
	<?php
	echo 'ATK: '.$fighter->Get('atk').'/'.$fighter->Get('matk').'<br/>';
	echo 'DEF: '.$fighter->Get('def').'/'.$fighter->Get('mdef').'<br/>';
	//Zeige Wählt oder Wartet an, jenachdem, ob er eine Attacke ausgewählt hat oder nicht
	if($fight->Get('state') == 2)
	{
		if($fighter->GET('attack') == 0 && $fighter->Get('lp') != 0)
		{
			?>
			Wählt.<br/>
			<?php
			if($fight->IsKickable($fighter))
			{
				?><a href="fight.php?a=kick&fid=<?php echo $fighter->Get('id'); ?>">Kicken</a><?php
			}
			else
			{
				?>Noch <?php echo $fight->GetSecondsTillKick($fighter); ?> Sekunden verbleiben.<br/><?php
			}
		}
		else
		{
			echo 'Wartet<br/>';
		}
	}
	echo '</td></tr>';
}
?>
</table>
</td>
<td width="50%" align="center" valign="top">
<?php 
if($player == null || $fight->Get('state') != 2)
{
?>
<a href="fight.php?id=<?php echo $_GET['id']; ?>">Aktualisieren</a><br/><br/>
<a href="fightlist.php">Zurück</a>
<?php
}
else if($player->Get('attack') == 0 && $player->Get('lp') != 0)
{
?>
<h2>Attacken</h2>

<a href="fight.php">Aktualisieren</a>
<br/>

<form method="POST" action="fight.php">
Angriff: <select name="atk">

<?php
//Hier gehen wir durch die Attacken von dem Spieler und zeigen sie an
$attacks = $player->GetAttacks();
foreach($attacks as &$atkID)
{
	//$attack ist ein Objekt der Klasse "attack", siehe classes/attack.php
	$attack = $fight->GetAttack($atkID);
	//Zeige eine Verlinkung mit der AttackenID an.
	echo '<option value="'.$attack->Get('id').'">'.$attack->Get('name').'</option>';
}
?>

</select>
<br/>
Ziel: <select name="target">

<?php
//Nun holen wir uns die Liste aller Spieler
$fighters = $fight->GetAllFighters();
//wir erstellen einen neuen Array um die Fighters zu sortieren
$sortedFighters = array();
foreach($fighters as &$fighter)
{
	if($fighter == null)
	{
		continue;
	}
	if($fighter->Get('fuseid') != 0)
	{
		continue;
	}
	//Schaue ob sie zum Team gehören, wenn ja, tue sie am Ende, wenn nein, am Anfang
	if($fighter->Get('team') == $player->Get('team'))
	{
		array_push($sortedFighters, $fighter);
		
	}
	else
	{
		array_unshift($sortedFighters, $fighter);
	}
}

//Gehe nun die sortierte Liste durch
foreach($sortedFighters as &$fighter)
{
	//$fighter ist ein Objekt der Klasse "fighter", siehe classes/fighter.php
	//füge den Fighter zur Liste hinzu
	echo '<option value="'.$fighter->Get('id').'">'.$fighter->Get('name').'</option>';
}
?>

</select>
<br/>
<input type="submit" value="Angreifen">
</form>
<?php
}
else
{
	?>
	<a href="fight.php">Aktualisieren</a><br/><br/>
	<?php
}
?>
</td>
<td width="25%" align="center" >
<table>
<h2>Team 2</h2>

<?php
$team1 = $fight->GetTeam(1);
foreach($team1 as &$fighter)
{
	if($fighter->Get('fuseid') != 0)
	{
		continue;
	}
	echo '<tr><td>';
	echo '<img src="'.$fighter->Get('image').'" width="50px" height="50px"/><br/>';
	echo $fighter->Get('name').'<br/>';
	echo 'KI: '.$fighter->Get('ki').'<br/>';
	$width = 200;
	$height = 16;
	$lpWidth = $width * ($fighter->Get('lp')/$fighter->Get('mlp'));
	if($lpWidth > $width) $lpWidth = $width;
	$kpWidth = $width * ($fighter->Get('kp')/$fighter->Get('mkp'));
	if($kpWidth > $width)  $kpWidth = $width;
	?>
	<div style="position:absolute; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; border-style: solid; border-width: 1px;">
	<div style="position:relative; top:0px; height:<?php echo $height; ?>px; width:<?php echo $lpWidth; ?>px; background-color:#ff5555">
	<div style="position:relative; top:0px; width:<?php echo $width; ?>px;">
	<?php echo 'LP: '.$fighter->Get('lp').'/'.$fighter->Get('mlp'); ?>
	</div>
	</div>
	</div>
	<br/>
	<div style="margin-top:1px; position:absolute; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; border-style: solid; border-width: 1px;">
	<div style="position:relative; top:0px; height:<?php echo $height; ?>px; width:<?php echo $kpWidth; ?>px; background-color:#9999ff">
	<div style="position:relative; top:0px; width:<?php echo $width; ?>px;">
	<?php echo 'KP: '.$fighter->Get('kp').'/'.$fighter->Get('mkp'); ?>
	</div>
	</div>
	</div>
	<br/>
	<?php
	echo 'ATK: '.$fighter->Get('atk').'/'.$fighter->Get('matk').'<br/>';
	echo 'DEF: '.$fighter->Get('def').'/'.$fighter->Get('mdef').'<br/>';
	//Zeige Wählt oder Wartet an, jenachdem, ob er eine Attacke ausgewählt hat oder nicht
	if($fight->Get('state') == 2)
	{
		if($fighter->GET('attack') == 0 && $fighter->Get('lp') != 0)
		{
			?>
			Wählt.<br/>
			<?php
			if($fight->IsKickable($fighter))
			{
				?><a href="fight.php?a=kick&fid=<?php echo $fighter->Get('id'); ?>">Kicken</a><?php
			}
			else
			{
				?>Noch <?php echo $fight->GetSecondsTillKick($fighter); ?> Sekunden verbleiben.<br/><?php
			}
		}
		else
		{
			echo 'Wartet<br/>';
		}
	}
	echo '</td></tr>';
}
?>

</table>
</td>
</tr>
<tr height="50px">
</tr>
<tr>

<td width="25%">
</td>

<td width="50%" align="center">
<h2>LOG</h2>

<?php echo $fight->Get('text'); ?>

</td>

<td width="25%">
</td>

</tr>
</table>