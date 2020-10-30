<?php
include_once 'user.php';

class Bracket
{
	private $data;
	
	function __construct($data) 
	{
		//Setzte initiale Daten
		$this->data = $data;
	}
	
	function Get($name)
	{
		return $this->data[$name];
	}
	
	function Set($name, $value)
	{
		$this->data[$name] = $value;
	}
}

class Tournament
{
	static $table = 'tournaments';
	static $tablebrackets = 'tournamentbrackets';
	
	private $db;
	private $data;
	private $valid;
	private $brackets;
	
	static function GetPlayerTournament($db, $userid)
	{
		$tid = -1;
		$result = $db->Select('*', Tournament::$tablebrackets, 'userid="'.$userid.'" AND isnpc="0" AND defeated="0" AND round="0"');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$running = Tournament::IsTournamentRunning($db, $row['tournament']);
					if($running)
						$tid = $row['tournament'];
				}
			}
			$result->close();
		}
		
		return $tid;
	}
	
	static function IsTournamentRunning($db, $tid)
	{
		$running = false;
		$result = $db->Select('*', Tournament::$table, 'id="'.$tid.'" AND end="0" AND start <= CURRENT_TIMESTAMP');
		if ($result) 
		{
			$running = $result->num_rows > 0;
			$result->close();
		}
		
		return $running;
	}
	
	static function IsPlayerInTournament($db, $userid)
	{
		return Tournament::GetPlayerTournament($db, $userid) != -1;
	}
	
	static function Create($db, $name, $start, $minstart, $itemid, $amount, $type)
	{
		$name = $db->EscapeString($name);
		$start = $db->EscapeString($start);
		if($name == '' || $start == '' || !is_numeric($minstart) || $minstart <= 1 
		|| !is_numeric($itemid) || $itemid <= 0 || !is_numeric($amount) || $amount <= 0 || !is_numeric($type) || $type <= 0)
		{
			return;
		}
		
		$round = 0;
		$variables = 'name, start, round, minstart, itemid, itemamount, itemtype';
		$values = '("'.$name.'","'.$start.'","'.$round.'","'.$minstart.'","'.$itemid.'","'.$amount.'","'.$type.'")';
		$db->Insert($variables, $values, Tournament::$table);
	}
	
	function __construct($db, $id) 
	{
		//Setzte initiale Daten
		$this->db = $db;
		$this->valid = false;
		$this->brackets = array();
		
		$this->Load($id);
		$this->LoadBrackets();
	}
	
	function GetEnemyBracket($userid, $isnpc, $round)
	{
		$brackets = $this->GetRoundBrackets($round);
		if($brackets == null)
			return;
		
		foreach($brackets as &$bracket)
		{
			if($bracket->Get('userid') != $userid || $bracket->Get('isnpc') != $isnpc)
			{
				continue;
			}
			
			$col = $bracket->Get('col');
			
			$enemyCol = 0;
			if($col % 2 == 0)
			{
				$enemyCol = $col+1;
			}
			else
			{
				$enemyCol = $col-1;
			}
			
			if(count($brackets) <= $enemyCol)
			{
				return null;
			}
			return $brackets[$enemyCol];
		}
		
		return null;
	}	
	
	function Win($userid, $isnpc)
	{
		$enemyBracket = $this->GetEnemyBracket($userid, $isnpc, $this->Get('round'));
		if($enemyBracket == null)
			return;
		
		$this->Defeat($enemyBracket->Get('id'), true);
	}
	
	function Defeat($bracketid, $update=true)
	{
		foreach($this->brackets as &$roundbrackets)
		{
			foreach($roundbrackets as &$bracket)
			{
				if($bracket->Get('id') == $bracketid)
				{
					$bracket->Set('defeated', 1);
				}
			}
		}
		
		$this->db->Update('defeated="1"', Tournament::$tablebrackets, 'id="'.$bracketid.'"',999999);
		
		if($update)
		{
			$this->UpdateAllBrackets();
		}
	}
	
	function StartNextRound()
	{
		$round = $this->Get('round');
		$nextBrackets = array();
		$col = 0;
		
		$nextRound = $round+1;
		$this->brackets[$nextRound] = array();
		
		foreach($this->brackets[$round] as &$bracket)
		{
			if($bracket->Get('defeated') != 0)
			{
				continue;
			}
			$this->AddBracket($bracket->Get('userid'), $bracket->Get('username'), $nextRound, $col, $bracket->Get('isnpc'));
			++$col;
		}
		
		$this->Set('round', $nextRound);
		$this->db->Update('round="'.$nextRound.'"', Tournament::$table, 'id="'.$this->Get('id').'"',1);
	}
	
	function EndTournament()
	{
		$this->Set('end', true);
		$this->db->Update('end="1"', Tournament::$table, 'id="'.$this->Get('id').'"',1);
		
		$brackets = $this->GetRoundBrackets($this->Get('round'));
		
		$winBracket = $brackets[0];
		
		if($winBracket->Get('isnpc'))
			return;
		
		
		User::AddItemToPlayer($this->db, $winBracket->Get('userid'), $this->Get('itemid'), $this->Get('itemamount'), $this->Get('itemtype'));
	}
	
	function UpdateAllBrackets()
	{
		$round = $this->Get('round');
		
		$nextRound = true;
		
		if(count($this->brackets[$round]) == 1)
		{
			if($this->Get('end') == 0)
			{
				$this->EndTournament();
			}
			return;
			
		}
		
		foreach($this->brackets[$round] as &$bracket)
		{
			$col = $bracket->Get('col');
			
			$enemyCol = 0;
			if($col % 2 == 0)
			{
				$enemyCol = $col+1;
			}
			else
			{
				$enemyCol = $col-1;
			}
			
			if(count($this->brackets[$round]) <= $enemyCol)
			{
				continue;
			}
			$enemyBracket = $this->brackets[$round][$enemyCol];
			
			$isNPC = $bracket->Get('isnpc') == 1;
			$defeated = $bracket->Get('defeated');
			$enemyIsNPC = $enemyBracket->Get('isnpc') == 1;
			$eDefeated = $enemyBracket->Get('defeated');
			
			//if the player or the enemy is not an npc and not defeated yet, nextRound is not possible
			if(!$isNPC && !$defeated && !$eDefeated || !$enemyIsNPC && !$defeated && !$eDefeated)
			{
				$nextRound = false;
				continue;
			}
			
			//skip for non npcs
			if(!$isNPC || $bracket->Get('defeated') != 0)
			{
				continue;
			}
			
			if($enemyIsNPC && $enemyBracket->Get('defeated') == 0)
			{
				$randNum = rand() % 2;
				if($randNum == 0)
					$this->Defeat($bracket->Get('id'), false);
				else
					$this->Defeat($enemyBracket->Get('id'), false);
			}
		}
		
		if($nextRound)
		{
			$this->StartNextRound();
		}
		
		
	}
	
	function Cancel()
	{
		if($this->Get('end') == 1)
			return;
		
		$this->Set('end', true);
		$this->db->Update('end="1"', Tournament::$table, 'id="'.$this->Get('id').'"',1);
		$this->db->Delete(Tournament::$tablebrackets, 'tournament="'.$this->Get('id').'"');
		
	}
	
	function CanUserFight($userid)
	{
		$round = $this->Get('round');
		$userBracket = $this->GetUserBracket($userid, $round);
		if($userBracket == -1)
			return false;
		
		$brackets = $this->brackets[$round];
		$bracket = $brackets[$userBracket];
		$col = $bracket->Get('col');
		
		$enemyCol = 0;
		if($col % 2 == 0)
		{
			$enemyCol = $col+1;
		}
		else
		{
			$enemyCol = $col-1;
		}
		
		if(count($brackets) > $enemyCol)
		{
			$userDefeated = $bracket->Get('defeated');
			$enemyDefeated = $brackets[$enemyCol]->Get('defeated');
			return !$userDefeated && !$enemyDefeated;
		}
		else
		{
			return false;
		}
	}
	
	function HasEnoughPlayers()
	{
		return $this->GetParticipants() >= $this->Get('minstart');
	}
	
	function GetParticipants()
	{
		$startRound = 0;
		if(!isset($this->brackets[$startRound]))
			return 0;
		
		return count($this->brackets[$startRound]);
	}
	
	function Join($userid, $name)
	{
		if($this->IsInRound($userid, 0))
			return false;
		
		$this->AddBracket($userid, $name, 0, $this->GetParticipants(), false);
		return true;
	}

	function AddBracket($id, $name, $round, $column, $isnpc)
	{
		$variables = 'tournament, userid, username, round, col, isnpc';
		
		$row = array();
		$row['tournament'] = $this->Get('id');
		$row['userid'] = $id;
		$row['username'] = $name;
		$row['round'] = $round;
		$row['col'] = $column;
		$row['defeated'] = 0;
		$row['isnpc'] = $isnpc;
		
		
		$values = '("'.$this->Get('id').'","'.$id.'","'.$name.'","'.$round.'","'.$column.'","'.$isnpc.'")';
		$this->db->Insert($variables, $values, Tournament::$tablebrackets);
		
		$bracket = new Bracket($row);
		$this->brackets[$round][$column] = $bracket;
	}
	
	function Leave($userid)
	{
		$itr = $this->GetUserBracket($userid, 0);
		if($itr == -1)
			return false;
		
		$this->db->Delete(Tournament::$tablebrackets, 'userid="'.$userid.'"');
		unset($this->brackets[0][$itr]);
		
		return true;
	}
	
	function GetUserBracket($userid, $round)
	{
		$brackets = $this->GetRoundBrackets($round);
		if($brackets == null)
			return -1;
		
		for($i =0; $i < count($brackets); ++$i)
		{
			$user = $brackets[$i];
			if($user->Get('userid') == $userid && $user->Get('isnpc') == 0)
			{
				return $i;
			}
		}
		
		return -1;
	}
	
	function IsInRound($userid, $round)
	{
		$returnVal = $this->GetUserBracket($userid, $round);
		return $returnVal != -1;
	}
	
	function GetRoundBrackets($round)
	{
		if(!isset($this->brackets[$round]))
			return null;
		
		return $this->brackets[$round];
	}
	
	function Get($name)
	{
		return $this->data[$name];
	}
	
	function Set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	function IsValid()
	{
		return $this->valid;
	}
		
	function Load($id)
	{
		$result = $this->db->Select('*', Tournament::$table, 'id="'.$this->db->EscapeString($id).'"');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$this->data = $row;
					$this->valid = true;
				}
			}
			$result->close();
		}
	}
		
	function LoadBrackets()
	{
		//Hole aus der Datenbank alle Daten von der Nachricht
		$result = $this->db->Select('*', Tournament::$tablebrackets, 'tournament="'.$this->Get('id').'"');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$bracket = new Bracket($row);
					$round = $row['round'];
					$column = $row['col'];
					if(!isset($this->brackets[$round]))
						$this->brackets[$round] = array();
					
					$this->brackets[$round][$column] = $bracket;
				}
			}
			$result->close();
		}
	}
}
?>