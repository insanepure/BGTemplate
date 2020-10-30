<?php
include_once 'classes/head.php';
include_once 'classes/item.php';

if(isset($_GET['a']) && $_GET['a'] == 'buy' && isset($_GET['id']) && is_numeric($_GET['id']))
{
	$id = $_GET['id'];
	$amount = $_POST['amount'];
	
	$item = new Item($database, $id);
	if($item->IsValid())
	{
		$price = $item->Get('price') * $amount;
		if($user->BuyItem($price, $id, $amount, $item->Get('type')))
		{
			?>
			Du hast <?php echo $amount; ?>x <?php echo $item->Get('name'); ?> gekauft.<br/>
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
<tr>
<td><b>Bild</b></td>
<td><b>Name</b></td>
<td><b>Typ</b></td>
<td><b>Beschreibung</b></td>
<td><b>Preis</b></td>
<td></td>
<?php
$result = $database->Select('*', Item::$table);
if ($result) 
{
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc()) 
		{
			?>
			<tr>
			<td><img width="50px" height="50px" src="<?php echo $row['image']; ?>"/></td>
			<td><?php echo $row['name']; ?></td>
			<td>
			<?php 
			echo Item::Type($row['type']);
			?>
			</td>
			<td><?php echo $row['description']; ?></td>
			<td><?php echo $row['price']; ?></td>
			<td>
			<form method="POST" action="shop.php?a=buy&id=<?php echo $row['id']; ?>">
			<select name = "amount" >
			<?php
			$amount = 99;
			for($i = 1; $i <= $amount; ++$i)
			{
				?>
				<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
				<?php
			}
			?>
			</select>
			<input type="submit" value="Kaufen"/>
			</form>
			</td>
			</tr>
			<?php
		}
	}
	$result->close();
}
?>
</table>
<br/>
<br/>
<a href="index.php">Zurück</a>