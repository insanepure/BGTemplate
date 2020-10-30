<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';
include_once 'classes/item.php';

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
if($user->Get('challenger') == 0)
{
	?>Du wurdest nicht herausgefordert.<br/>Du wirst gleich weitergeleitet.<?php
	header("Refresh: 3; Url=$actual_link");
}
else if(isset($_GET['a']) && $_GET['a'] == 'accept')
{
	$player = new User($database, $user->Get('challenger'));
	//Kampf erstellen
	//Du kannst diese Werte bearbeiten
	$money = 0;
	$items = array();
	
	$itemID = $user->Get('challengeitem');
	if($itemID != 0)
	{
		$item = $user->GetItemByID($itemID);
		$itemData = new Item($database, $item->item);
		//ID@Amount@Type
		$itemString = $itemID.'@'.$item->amount.'@'.$itemData->Get('type');
		array_push($items, $itemString);
		
		$user->RemoveItem($item, $item->amount);
	}
	
	$user->ResetChallenge();
	
	$keepStats = 0;
	$levelup = 0;
	$mode = '1vs1';
	$name = 'Herausforderung';
	
	$fight = Fight::CreateFight($database, $name, $mode, $money, $items, $keepStats, $levelup);
	$isnpc = 0;
	$team = 0;
	$fighter1 = $fight->AddPlayerFighter($database, $user, $team);
								  
	$attacks = $user->GetAttacks();
	$fighter1->AddAttacks($attacks);
	
	$team = 1;
	$fighter2 = $fight->AddPlayerFighter($database, $player, $team);
								  
	$attacks = $player->GetAttacks();
	$fighter2->AddAttacks($attacks);
	
	
	?>Du hast die Herausforderung angenommen, Der Kampf beginnt.<br/>Du wirst gleich weitergeleitet.<?php
	header("Refresh: 3; Url=$actual_link");
}
else if(isset($_GET['a']) && $_GET['a'] == 'decline' && $user->Get('challengeitem') == 0)
{
	$user->ResetChallenge();
	?>Du hast die Herausforderung abgelehnt.<br/>Du wirst gleich weitergeleitet.<?php
	header("Refresh: 3; Url=$actual_link");
}
?>


<?php
if($user->Get('challenger') != 0)
{
	$player = new User($database, $user->Get('challenger'));
	?>
	Du wurdest von <?php echo $player->Get('name'); ?> herausgefordert.<br/>
	<?php
	$challengeitem = $user->Get('challengeitem');
	$item = $user->GetItemByID($challengeitem);
	$itemData = new Item($database, $item->item);
	?>Es geht um <?php echo $item->amount;?>x <?php echo $itemData->Get('name'); ?>.<br/>
	<br/>
	<a href="challenge.php?a=accept">Annehmen</a> 
	<?php
	if($challengeitem == 0)
	{
		?><a href="challenge.php?a=decline">Ablehnen</a><?php
	}
}

?>


<br/>
<br/>