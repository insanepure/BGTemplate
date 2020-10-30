<?php
class Message
{
	static $table = 'messages';
	
	private $db;
	private $data;
	private $valid;
	
	function __construct($db, $id) 
	{
		//Setzte initiale Daten
		$this->db = $db;
		$this->valid = false;
		
		//Lade alle Daten von der Nachricht
		$this->LoadMessage($id);
	}
	
	static function Send($db, $id, $name, $receiver, $topic, $text)
	{
		$variables = 'senderid, sendername, receiver, topic, text, hasread';
		$values = '("'.$id.'","'.$name.'","'.$receiver.'","'.$db->EscapeString($topic).'","'.$db->EscapeString($text).'","0")';
		
		//Wir fügen einfach einen Wert zur Tabbelle "Message" hinzu
		$db->Insert($variables, $values, Message::$table);
	}
	
	function Get($name)
	{
		return $this->data[$name];
	}
	
	function Set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	function Delete()
	{
		$this->db->Delete(Message::$table, 'id="'.$this->Get('id').'"');
	}
	
	function Read()
	{
		$this->Set('hasread', true);
		$this->db->Update('hasread="1"', Message::$table, 'id="'.$this->Get('id').'"', 1);
	}
	
	function IsValid()
	{
		return $this->valid;
	}
		
	function LoadMessage($id)
	{
		//Hole aus der Datenbank alle Daten von der Nachricht
		$result = $this->db->Select('*', Message::$table, 'id="'.$this->db->EscapeString($id).'"', 1);
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
				$this->data = $row;
				$this->valid = true;
			}
			$result->close();
		}
	}
}
?>