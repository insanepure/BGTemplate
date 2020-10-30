<?php
class Item
{
	static function Type($id)
	{
		switch($id)
		{
			case 1:
			return 'Heilung';
			case 2:
			return 'Ausrüstung';
		}
		
		return 'Invalid';
	}
	
	static $table = 'items';
	
	private $db;
	private $data;
	private $valid;
	
	function __construct($db, $id) 
	{
		//Setzte initiale Daten
		$this->db = $db;
		$this->valid = false;
		
		//Lade die Attacken des Spielers
		$this->LoadItem($id);
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
		
	function LoadItem($id)
	{
		//Hole aus der Datenbank die Attacken und füge sie zum array hinzu
		$result = $this->db->Select('*', Item::$table, 'id="'.$this->db->EscapeString($id).'"');
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
}
?>