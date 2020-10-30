<?php
include_once 'classes/head.php';
?>

<table>
<tr>
<td>Platz</td>
<td>User</td>
</tr>
<?php
$result = $database->Select('id, name', User::$table, '');
if ($result) 
{
	$rank = 0;
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc()) 
		{
			$rank++;
			?>
			<tr>
			<td><?php echo $rank; ?></td>
			<td><a href="profile.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
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
<a href="index.php">Zurück </a>