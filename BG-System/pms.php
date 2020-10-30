<?php
include_once 'classes/head.php';
include_once 'classes/message.php';
?>

<table>
<tr>
<td>ID</td>
<td>Sender</td>
<td>Betreff</td>
<td>Aktion</td>
</tr>
<?php
$result = $database->Select('*', Message::$table, 'receiver='.$user->Get('id'));
if ($result) 
{
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc()) 
		{
			?>
			<tr>
			<td><?php echo $row['id']; ?></td>
			<td><a href="profile.php?id=<?php echo $row['senderid']; ?>"><?php echo $row['sendername']; ?></a></td>
			<td><?php echo $row['topic']; ?></td>
			<td>
			<?php 
			if(!$row['hasread'])
			{
				?><b><?php
			}
			?>
			<a href="pmread.php?id=<?php echo $row['id']; ?>">lesen</a>
			<?php 
			if(!$row['hasread'])
			{
				?></b><?php
			}
			?>
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
<a href="pmsend.php"> Nachricht schreiben</a>
<br/>
<br/>
<a href="index.php">Zurück </a>