<?php
include_once 'classes/head.php';
include_once 'classes/fight.php';

$fID = Fight::GetPlayerFight($database, $user->Get('id'));
?>
<table>
<?php
$result = $database->Select('*', Fight::$table);
if ($result) 
{
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc()) 
		{
			?>
			<tr>
			<td><?php echo $row['name']; ?></td>
			<td>
			<?php 
			if($row['state'] == 1)
			{
				?>Offen<?php
			}
			else if($row['state'] == 2)
			{
				?>Läuft<?php
			}
			else
			{
				?>Beendet<?php
			}
			?>
			</td>
			<td>
			<?php 
			if($row['state'] == 1 && $fID == $row['id'])
			{
				?><a href="fightleave.php?id=<?php echo $row['id']; ?>">Verlassen</a><?php
			}
			else if($row['state'] == 1)
			{
				?><a href="fightjoin.php?id=<?php echo $row['id']; ?>">Beitreten</a><?php
			}
			else
			{
				?><a href="fight.php?id=<?php echo $row['id']; ?>">Ansehen</a><?php
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
<br/>
<a href="index.php">Zurück</a>