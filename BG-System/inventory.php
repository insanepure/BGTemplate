<?php
include_once 'classes/head.php';
include_once 'classes/item.php';


if(isset($_GET['a']) && isset($_GET['id']) && is_numeric($_GET['id']))
{
	$id = $_GET['id'];
	$item = $user->GetItem($id);
	if($item == null)
	{
		?>
		Das Item ist ungültig.<br/>
		<?php
	}
	else
	{
		$itemData = new Item($database, $item->item);
		$amount = 1;
		if($_GET['a'] == 'use' && $itemData->Get('type') == 1 && $item->amount >= $amount)
		{
			$user->Heal($itemData->Get('lp'), $itemData->Get('kp'));
			$user->RemoveItem($item, $amount);
			?>
			Du hast dich geheilt.<br/>
			<?php
		}
		else if($_GET['a'] == 'sell')
		{
			if($item->slot == 0)
			{
				$amount = 1;
				$user->SellItem($item, $amount, $itemData);
				?>
				Du hast das Item verkauft.<br/>
				<?php
			}
			else
			{
				?>
				Du musst das item zunächst ablegen.<br/>
				<?php
			}
		}
		else if($_GET['a'] == 'equip' && $itemData->Get('type') == 2)
		{
			$user->Equip($item, $itemData);
			?>
			Du hast das Item angelegt.<br/>
			<?php
		}
		else if($_GET['a'] == 'unequip' && $itemData->Get('type') == 2)
		{
			$user->UnEquip($itemData);
			?>
			Du hast das Item abgelegt.<br/>
			<?php
		}
	}
}

?>
<table>
<tr>
<td><b>Bild</b></td>
<td><b>Name</b></td>
<td><b>Anzahl</b></td>
<td><b>Slot</b></td>
<td></td>
<td></td>
<?php
$items = $user->GetItems();
foreach($items as &$item)
{
	$itemData = new Item($database, $item->item);
	?>
	<tr>
	<td><img width="50px" height="50px" src="<?php echo $itemData->Get('image'); ?>"/></td>
	<td><?php echo $itemData->Get('name'); ?></td>
	<td><?php echo $item->amount; ?></td>
	<td><?php if($item->slot != 0) echo $item->slot; ?></td>
	<td>
	<?php
	if($itemData->Get('type') == 1)
	{
		//Heilung
		?>
		<form method="POST" action="inventory.php?a=use&id=<?php echo $item->id; ?>">
		<input type="submit" value="Benutzen"/>
		</form>
		<?php
	}
	else if($itemData->Get('type') == 2)
	{
		//Ausrüstung
		if($item->slot == 0)
		{
			?>
			<form method="POST" action="inventory.php?a=equip&id=<?php echo $item->id; ?>">
			<input type="submit" value="Ausrüsten"/>
			</form>
			<?php
		}
		else
		{
			?>
			<form method="POST" action="inventory.php?a=unequip&id=<?php echo $item->id; ?>">
			<input type="submit" value="Ablegen"/>
			</form>
			<?php
		}
	}
	?>
	</td>
	<td>
	<form method="POST" action="inventory.php?a=sell&id=<?php echo $item->id; ?>">
	<input type="submit" value="Verkaufen"/>
	</form>
	</td>
	</tr>
	<?php
}
?>
</table>
<br/>
<br/>
<a href="index.php">Zurück</a>