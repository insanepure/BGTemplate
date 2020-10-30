<?php
include_once 'classes/head.php';
include_once 'classes/message.php';

if(isset($_GET['a']) && $_GET['a'] == 'send' && isset($_POST['name']) && isset($_POST['topic']) && isset($_POST['text']))
{
	$name = $_POST['name'];
	
	$receiver = User::GetIDFromName($database, $name);
	if($receiver == 0)
	{
		?>
		Dieser Spieler existiert nicht.<br/>
		<?php
	}
	else
	{
		Message::Send($database, $user->Get('id'), $user->Get('name'), $receiver, $_POST['topic'], $_POST['text']);
		?>
		Nachricht wurde gesendet.<br/>
		<?php
	}
}
?>

<table>
<tr>

<table>
<form method="POST" action="pmsend.php?a=send">
<tr>
<td>Empfänger</td>
<td><input type="text" placeholder="Name" name="name" <?php if(isset($_GET['to'])) { echo 'value="'.$_GET['to'].'"'; } ?>/></td>
</tr>
<tr>
<td>Betreff</td>
<td><input type="text" placeholder="Betreff" name="topic" <?php if(isset($_GET['topic'])) { echo 'value="RE: '.$_GET['topic'].'"'; } ?>/></td>
</tr>
<tr>
<td colspan="2"><textarea rows="10" cols="50" name="text"></textarea></td>
</tr>
<tr><td colspan="2"><input type="submit" value="Senden"></td></tr>
</table>
<br/>
<a href="pms.php">Zurück</a>