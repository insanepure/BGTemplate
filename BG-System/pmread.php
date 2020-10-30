<?php
include_once 'classes/head.php';
include_once 'classes/message.php';

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/pms.php";
if(isset($_GET['id']) && is_numeric($_GET['id']))
{
	$message = new Message($database, $_GET['id']);
	if(!$message->IsValid() || ($message->Get('receiver') != $user->Get('id') && $message->Get('senderid') != $user->Get('id')))
	{
		header("Refresh: 0; Url=$actual_link");
		exit();
	}
	else if($message->Get('receiver') == $user->Get('id'))
	{
		$message->Read();
		if(isset($_GET['a']) && $_GET['a'] == 'delete')
		{
			$message->Delete();
			?>Die Nachricht wurde gelöscht.<br/>
			Du wirst gleich weitergeleitet.
			<?php
			header("Refresh: 3; Url=$actual_link");
			exit();
			
		}			
	}
}
else
{
	header("Refresh: 0; Url=$actual_link");
	exit();
}
?>

<table>
<tr><td>Absender: </td><td><a href="profile.php?id=<?php echo $message->Get('senderid'); ?>"><?php echo $message->Get('sendername'); ?></a></td></tr>
<tr><td>Betreff: </td><td><?php echo $message->Get('topic'); ?></td></tr>
<tr><td colspan="2"><?php echo $message->Get('text'); ?></td></tr>
</table>
<a href="pmsend.php?to=<?php echo $message->Get('sendername'); ?>&topic=<?php echo $message->Get('topic'); ?>">Antworten</a><br/>
<?php 
if($message->Get('receiver') == $user->Get('id'))
{
	?>
	<a href="pmread.php?a=delete&id=<?php echo $message->Get('id'); ?>">Löschen</a><br/>
	<?php
}
?>
<br/>
<br/>
<a href="pms.php">Zurück </a>