<?php
include_once 'classes/head.php';
include_once 'classes/item.php';
include_once 'classes/market.php';

$market = new Market($database);

if(isset($_GET['a']) && $_GET['a'] == 'sell' 
&& isset($_POST['id']) && is_numeric($_POST['id']) 
&& isset($_POST['price']) && is_numeric($_POST['price'])  && $_POST['price'] > 0
&& isset($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0)
{
	$id = $_POST['id'];
	$amount = $_POST['amount'];
	$price = $_POST['price'];

	$item = $user->GetItem($id);
	if($item == null)
	{
		?>Du besitzt das Item nicht mehr.<br/><?php
	}
	else if($item->amount < $amount)
	{
		?>Du hast nicht genügend.<br/><?php
	}
	else if($item->slot != 0)
	{
		?>Das Item ist angelegt und kann daher nicht verkauft werden.<br/><?php
	}
	else
	{
		$itemData = new Item($database, $item->item);
		$market->AddItem($item->item, $itemData->Get('name'), $itemData->Get('image'), $amount, $price, $user->Get('id'), $user->Get('name'));
		$user->RemoveItem($item, $amount);
	}
}
else if(isset($_GET['a']) && $_GET['a'] == 'buy' 
&& isset($_GET['id']) && is_numeric($_GET['id']) 
&& isset($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0)
{
	$id = $_GET['id'];
	$amount = $_POST['amount'];

	$item = $market->GetItem($id);
	if($item == null)
	{
		?>Das Item wird nicht verkauft.<br/><?php
	}
	else if($item->amount < $amount)
	{
		?>Du willst mehr als es im Markt gibt.<br/><?php
	}
	else
	{
		$itemData = new Item($database, $item->item);
		$price = $item->price * $amount;
		if($user->BuyItem($price, $item->item, $amount, $itemData->Get('type')))
		{
			$market->BuyItem($id, $amount);
			User::AddMoney($database, $item->userid, $price);
			?>
			Du hast <?php echo $amount; ?>x <?php echo $itemData->Get('name'); ?> gekauft.<br/>
			<?php
		}
		else
		{
			?>
			Du hast nicht genügend Geld.<br/>
			<?php
		}
	}
}

?>
<table>
<form method="POST" action="market.php?a=sell">
<tr>
<td>Item</td>
<td>
<select name="id">
<?php
$items = $user->GetItems();
foreach($items as &$item)
{
	$itemData = new Item($database, $item->item);
	?>
	<option value="<?php echo $item->id; ?>"><?php echo $itemData->Get('name'); ?> (<?php echo $item->amount; ?>)</option>
	<?php
}
?>
</select>
</td>
</tr>
<tr>
<td>Anzahl</td>
<td>
<select name="amount">
<?php
$amount = 99;
for($i = 1; $i <= $amount; ++$i)
{
	?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php
}
?>
</select>
</td>
</tr>
<tr>
<td>Preis</td>
<td>
<input type="number" name="price" placeholder="100"/>
</td>
</tr>
<tr>
<td colspan="2">
<input type="submit" value = "verkaufen"/>
</td>
</tr>
</table>
</form>
<br/>
<br/>
<table>
<tr>
<td><b>Bild</b></td>
<td><b>Name</b></td>
<td><b>Besitzer</b></td>
<td><b>Anzahl</b></td>
<td><b>Preis</b></td>
<td></td>
</tr>
<?php
$marketItems = $market->GetItems();
foreach($marketItems as &$marketItem)
{
	?>
	<tr>
	<td><img width="50px" height="50px" src="<?php echo $marketItem->itemimage; ?>"></img></td>
	<td><?php echo $marketItem->itemname; ?></td>
	<td><a href="profile.php?id=<?php echo $marketItem->userid; ?>"><?php echo $marketItem->username; ?></a></td>
	<td><?php echo $marketItem->amount; ?></td>
	<td><?php echo $marketItem->price; ?></td>
	<td>
	<form method="POST" action="market.php?a=buy&id=<?php echo $marketItem->id; ?>">
	<select name="amount">
	<?php
	$amount = $marketItem->amount;
	for($i = 1; $i <= $amount; ++$i)
	{
		?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php
	}
	?>
	</select>
	<br/>
	<input type="submit" value="Kaufen"/>
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