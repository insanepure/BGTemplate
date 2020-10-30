<?php
class Attack
{
	private $data;
	function __construct($data) 
	{
		//Setzt die Daten einfach
		$this->data = $data;
	}
	
	function GetCost($userKP)
	{
		if($this->Get('isprocentualkp'))
		{
			return $userKP * ($this->Get('kpcost'))/100;
		}
		return $this->Get('kpcost');
	}
	
	function Get($name)
	{
		//Hiermit kann man die Daten holen
		return $this->data[$name];
	}
	
	function Set($name, $value)
	{
		//Hiermit kann man die Daten setzen
		$this->data[$name] = $value;
	}
}

class AttackManager
{
	static $table = 'attacks';
	
	private $db;
	private $data;
	
	function __construct($db) 
	{
		$this->db = $db;
		$this->data = array();
		
		//Hole alle Attacken aus der Datenbank
		$result = $this->db->Select('*', AttackManager::$table, '');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					//Setze anhand der ID der Attacke ein neues Objekt der klasse Attack
					$this->data[$row['id']] = new Attack($row);
				}
			}
			$result->close();

		}
	}
	
	function &Get($id)
	{
		//Gebe eine Referenz anhand der ID zurück
		return $this->data[$id];
	}
}
?>