<?php
class Database
{
	//Variables
	private $mysqli;
	private $host = 'localhost';
	private $user = 'root';
	private $pw = '';
	private $db = 'bgdb';
	
	private $selectCount;
	private $updateCount;
	private $insertCount;
	private $deleteCount;
	private $debug = false;
	private $selectEnabled = true;
	private $updateEnabled = true;
	private $insertEnabled = true;
	private $deleteEnabled = true;
	private $truncateEnabled = true;
	
	//Constructor
	function __construct() 
	{
		$this->mysqli = new mysqli($this->host, $this->user, $this->pw, $this->db);
    $this->mysqli->set_charset("utf8");
		if ($this->mysqli->connect_errno) 
		{
			//die("Verbindung fehlgeschlagen: " . $this->mysqli->connect_error);
      include_once '///404.php';
		}
		
		$selectCount = 0;
		$updateCount = 0;
		$insertCount = 0;
		$deleteCount = 0;
	}
	
	//Destructor
	function __destruct() 
	{
	}
	
	public function Debug()
	{
		$this->debug = true;
	}
	
	public function TruncateEnable($value)
	{
		$this->truncateEnabled = $value;
	}
	
	public function SelectEnable($value)
	{
		$this->selectEnabled = $value;
	}
	
	public function UpdateEnable($value)
	{
		$this->updateEnabled = $value;
	}
	
	public function InsertEnable($value)
	{
		$this->insertEnabled = $value;
	}
	
	public function DeleteEnable($value)
	{
		$this->deleteEnabled = $value;
	}
	
	public function IsTruncateEnabled()
	{
		return $this->truncateEnabled;
	}
	
	public function IsSelectEnabled()
	{
		return $this->selectEnabled;
	}
	
	public function IsUpdateEnabled()
	{
		return $this->updateEnabled;
	}
	
	public function IsInsertEnabled()
	{
		return $this->insertEnabled;
	}
	
	public function IsDeleteEnabled()
	{
		return $this->deleteEnabled;
	}
	
	private function FormatSQL($sql, $where, $limit, $order, $orderType, $group='')
	{
		if ($where != '')
		{
			$sql = $sql.' WHERE '.$where;
		}
		
		if($group != '')
		{
		$sql = $sql.' GROUP BY '.$group;
		}
		
		if ($order != '')
		{
			$sql = $sql.' ORDER BY '.$order.' '.$orderType;
		}
		
		if($limit != '')
		{
			$sql = $sql.' LIMIT '.$limit;
		}
		
		return $sql;
	}
	
	public function Error()
	{
		return $this->mysqli->error;
	}
	
	public function ShowColumns($table)
	{
		$sql = 'SHOW COLUMNS from '.$table;
		return $this->mysqli->query($sql);
	}
	public function ShowTables()
	{
		$sql = 'SHOW TABLES';
		return $this->mysqli->query($sql);
	}
	
	public function Select($variables, $table, $where = '', $limit = '', $order = '', $orderType='ASC', $join='', $group='')
	{
		$sql = 'SELECT '.$variables.' FROM '.$table;
		if($join != '')
		{
		$sql = $sql.' '.$join;
		}
		$sql = $this->FormatSQL($sql, $where, $limit, $order, $orderType, $group);
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->selectEnabled)
		{
			return true;
		}
		
		$this->selectCount++;
		return $this->mysqli->query($sql);
	}
	
	public function GetError()
	{
		return $this->mysqli->error;
	}
	
	public function Update($variables, $table, $where, $limit = 0,$order='',$orderType='ASC', $set='')
	{
		$sql = '';
		if($set != '')
		{
			$sql = $set;
		}
		$sql = $sql.' UPDATE '.$table.' SET '.$variables;
		$sql = $this->FormatSQL($sql, $where, $limit,$order,$orderType);
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->updateEnabled)
		{
			return true;
		}
		
		$this->updateCount++;
		if($set == '')
		{
			return $this->mysqli->query($sql);
		}
		$result = $this->mysqli->multi_query($sql);
  	while (mysqli_next_result($this->mysqli));
		return $result;
	}
	
	public function Insert($variables, $values, $table)
	{
		$sql = 'INSERT INTO '.$table.' ('.$variables.') VALUES '.$values.'';
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->insertEnabled)
		{
			return true;
		}
		
		$this->insertCount++;
		return $this->mysqli->query($sql);
	}
	
	public function Truncate($table)
	{
		$sql = 'TRUNCATE TABLE '.$table;
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->truncateEnabled)
		{
			return true;
		}
		
		return $this->mysqli->query($sql);
	}
	
	public function Delete($table, $where)
	{
		$sql = 'DELETE FROM '.$table.' WHERE '.$where;
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->deleteEnabled)
		{
			return true;
		}
		
		$this->deleteCount++;
		return $this->mysqli->query($sql);
	}
	
	public function GetLastID()
	{
		return $this->mysqli->insert_id;
	}
	
	public function EscapeString($string)
	{
		return $this->mysqli->real_escape_string($string);
	}
	
	public function GetSelects()
	{
		return $this->selectCount;
	}
	
	public function GetUpdates()
	{
		return $this->updateCount;
	}
	
	public function GetInserts()
	{
		return $this->insertCount;
	}
	
	public function GetDeletes()
	{
		return $this->deleteCount;
	}
}

$database = new Database();
?>