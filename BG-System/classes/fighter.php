<?php

class Fighter
{
	static $table = 'fighters';
	static $tableAtks = 'fighterattacks';
	
	private $db;
	private $data;
	private $attacks;
	
	function __construct($db, $data) 
	{
		//Setzte initiale Daten
		$this->db = $db;
		$this->attacks = array();
		
		$this->data = $data;
		//Lade die Attacken des Spielers
		$this->LoadAttacks();
	}
	
	function Get($name)
	{
		return $this->data[$name];
	}
	
	function Set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	function HasBuff($id)
	{
		$buffs = explode(';',$this->Get('buffs'));
		foreach($buffs as &$buff)
		{
			$buffData = explode('@',$buff);
			$buffID = $buffData[1];
			if($buffID == $id)
				return true;
		}
		
		return false;
	}
	
	function HasHeal($id)
	{
		$heals = explode(';',$fighter->Get('heals'));
		foreach($heals as &$heal)
		{
			$healData = explode('@',$heal);
			$healID = $healData[1];
			if($healID == $id)
				return true;
		}
		
		return false;
	}
	
	function GetSecondsSinceLastAction()
	{
		return time() - strtotime($this->Get('lastactiontime'));
	}
	
	function GetAttacks()
	{
		return $this->attacks;
	}
	
	function HasAttack($id)
	{
		//Überprüfe ob der Spieler eine Attacke besitzt
		return in_array($id, $this->attacks);
	}
		
	function LoadAttacks()
	{
		//Hole aus der Datenbank die Attacken und füge sie zum array hinzu
		$result = $this->db->Select('*', Fighter::$tableAtks, 'fighter="'.$this->Get('id').'"');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					array_push($this->attacks, $row['attack']);
				}
			}
			$result->close();
		}
	}
	
	function AddAttacks($ids)
	{
		//Füge mehrere Attacken zum Spieler hinzu
		$variables = 'fighter, attack';
		$values = '';
		//Hier formatieren wir den SQL-Query
		//Format soll werden: (fighterID, attackenID),(fighterID, attackenID),(fighterID, attackenID)
		foreach($ids as &$id)
		{
			array_push($this->attacks, $id);
			$val = '("'.$this->Get('id').'","'.$id.'")';
			if($values != '')
			{
				$values = $values.','.$val;
			}
			else
			{
				$values = $val;
			}
		}
		$values = $values.';';
		//Füge zur Datenbank hinzu
		$this->db->Insert($variables, $values, Fighter::$tableAtks);
	}
}
?>