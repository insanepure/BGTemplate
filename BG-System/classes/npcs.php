<?php
class NPC
{
	private $data;
	function __construct($data) 
	{
		//Setzt die Daten einfach
		$this->data = $data;
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

class NPCManager
{
	static $table = 'npcs';
	
	private $db;
	private $data;
	
	function __construct($db, $where='') 
	{
		$this->db = $db;
		$this->data = array();
		
		//Hole alle Attacken aus der Datenbank
		$result = $this->db->Select('*', NPCManager::$table, $where);
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$this->data[$row['id']] = new NPC($row);
				}
			}
			$result->close();

		}
	}
	
	function GetAll()
	{
		return $this->data;
	}
	
	function &Get($id)
	{
		if(!isset($this->data[$id]))
		{
			return null;
		}
		//Gebe eine Referenz anhand der ID zurück
		return $this->data[$id];
	}
}
?>