<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';
include_once 'classes/tournament.php';

if($user != null)
{
	$fID = Fight::GetPlayerFight($database, $user->Get('id'));
	if($fID != 0)
	{
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
	$actual_link = $actual_link.'/fight.php';

	header("Refresh: 0; Url=$actual_link");
	}
	
	if(Tournament::IsPlayerInTournament($database, $user->Get('id')))
	{
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
		$actual_link = $actual_link.'/tournament.php';

		header("Refresh: 0; Url=$actual_link");
	}
}
?>


<?php
if($user == null)
{
	?>
	<div style = "height:100%; width:100%; position: relative;">
		<div style="margin: 0; position: absolute; top: 50%; left: 50%; -ms-transform: translate(-50%, -50%); transform: translate(-50%, -50%);">
			<table>
			<tr>
			<td width="300px" align="center">
			<form method="POST" action="register.php">
			<input type="text" name="login" placeholder="Login">
			<br/><br/><input type="text" name="name" placeholder="Charaktername">
			<br/><br/><input type="password" name="password" placeholder="Password">
			<br/><br/><input type="submit" value="Registrieren">
			</form>
			<td/>
			<td width="300px" align="center">
			<form method="POST" action="login.php">
			<input type="text" name="login" placeholder="Login">
			<br/><br/><input type="password" name="password" placeholder="Password">
			<br/><br/><input type="submit" value="Einloggen">
			</form>
			</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}
else
{
	
	?>
	Spieler: <?php echo $user->Get('name'); ?><br/>
	Level: <?php echo $user->Get('level'); ?><br/>
	<?php
	$width = 200;
	$height = 16;
	$lpWidth = $width * ($user->Get('lp')/$user->Get('mlp'));
	if($lpWidth > $width) $lpWidth = $width;
	$kpWidth = $width * ($user->Get('kp')/$user->Get('mkp'));
	if($kpWidth > $width)  $kpWidth = $width;
	?>
	<div style="position:absolute; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; border-style: solid; border-width: 1px;">
	<div style="position:relative; top:0px; height:<?php echo $height; ?>px; width:<?php echo $lpWidth; ?>px; background-color:#ff5555">
	<div style="position:relative; top:0px; width:<?php echo $width; ?>px;">
	<?php echo 'LP: '.$user->Get('lp').'/'.$user->Get('mlp'); ?>
	</div>
	</div>
	</div>
	<br/>
	<div style="margin-top:1px; position:absolute; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; border-style: solid; border-width: 1px;">
	<div style="position:relative; top:0px; height:<?php echo $height; ?>px; width:<?php echo $kpWidth; ?>px; background-color:#9999ff">
	<div style="position:relative; top:0px; width:<?php echo $width; ?>px;">
	<?php echo 'KP: '.$user->Get('kp').'/'.$user->Get('mkp'); ?>
	</div>
	</div>
	</div>
	<br/>
	KI: <?php echo $user->Get('ki'); ?><br/>
	Angriff: <?php echo $user->Get('atk'); if($user->Get('itematk') != 0) echo ' +'.$user->Get('itematk'); ?> <br/>
	Verteidigung: <?php echo $user->Get('def'); if($user->Get('itemdef') != 0) echo ' +'.$user->Get('itemdef');?><br/>
	Geld: <?php echo $user->Get('money'); ?><br/>
	<br/>
	<br/>
	<a href="profile.php">Profil anschauen</a><br/>
	<a href="userlist.php">Userliste anschauen</a><br/>
	<br/>
	<a href="pms.php">PMs lesen</a><br/>
	<br/>
	<a href="shop.php">Shop betreten</a><br/>
	<a href="inventory.php">Inventar aufrufen</a><br/>
	<a href="market.php">Marktplatz betreten</a><br/>
	<br/>
	<a href="fightcreate.php">Spieler-Kampf erstellen</a><br/>
	<a href="fightnpc.php">NPC-Kampf erstellen</a><br/>
	<br/>
	<a href="tournament.php">Turnier</a><br/>
	<br/>
	<a href="fightlist.php">Kampf anschauen</a><br/>
	<br/>
	<a href="logout.php">Ausloggen </a>
	<?php
}
?>