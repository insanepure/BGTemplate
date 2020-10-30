<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';

if(isset($_GET['id']))
{
	$id = $_GET['id'];
	$fID = Fight::GetPlayerFight($database, $user->Get('id'));
	
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
	header("Refresh: 3; Url=$actual_link");
	
	if($fID == $id)
	{
		$fight = new Fight($database, $fID);
		if(!$fight->IsValid())
		{
			?>
			Der Kampf ist ungültig.<br/>
			Du wirst nun weitergeleitet.
			<?php 
			exit();
		}
		$player = $fight->GetUserFighter($user->Get('id'));
		$fight->LeaveFight($player->Get('id'));
		?>
		Du hast den Kampf verlassen.<br/>
		Du wirst nun weitergeleitet.
		<?php ;
	}
	else
	{
			?>
			Du befindest dich nicht in diesen Kampf.<br/>
			Der Kampf wurde erstellt.<br/>
			Du wirst nun weitergeleitet.
			<?php 
	}

}


?>