<?php
class MarketItem
{
	public $id = 0;
	public $userid = 0;
	public $username = '';
	public $item = 0;
	public $amount = 0;
	public $price = 0;
	public $itemname = '';
	public $itemimage = '';
	
	function __construct($id, $userid, $username, $item, $itemname, $itemimage, $amount, $price) 
	{
		$this->id = $id;
		$this->userid = $userid;
		$this->username = $username;
		$this->item = $item;
		$this->itemname = $itemname;
		$this->itemimage = $itemimage;
		$this->amount = $amount;
		$this->price = $price;
	}
	
}
class Market
{
	static $table = 'market';
	
	private $db;
	private $items;
	
	function __construct($db) 
	{
		//Setzte initiale Daten
		$this->db = $db;
		$this->items = array();
		
		//Lade alle Daten von dem Markt
		$this->LoadItems();
	}
	
	function BuyItem($id, $amount)
	{
		$item = $this->items[$id];
		if($item->amount == $amount)
		{
			$this->db->Delete(Market::$table, 'id="'.$id.'"');
			unset($this->items[$id]);
		}
		else
		{
			$newAmount = $item->amount-$amount;
			$this->db->Update('amount="'.$newAmount.'"', Market::$table, 'id="'.$id.'"',1);
			$this->items[$id]->amount = $newAmount;
		}
	}
	
	function GetItem($id)
	{
		return $this->items[$id];
	}
	
	function GetItems()
	{
		return $this->items;
	}
	
	function AddItem($item, $itemname, $itemimage, $amount, $price, $userid, $username)
	{
		$variables = 'userid, username, item, itemname, itemimage, amount, price';
		$values = '("'.$userid.'","'.$username.'","'.$item.'","'.$itemname.'","'.$itemimage.'","'.$amount.'", "'.$price.'")';
		
		//Wir fügen einfach einen Wert zur Tabelle "market" hinzu
		$this->db->Insert($variables, $values, Market::$table);
		$id = $this->db->GetLastID();
		$item = new MarketItem($id, $userid, $username, $item, $itemname, $itemimage, $amount, $price);
		$this->items[$id] = $item;
	}
		
	function LoadItems()
	{
		//Hole aus der Datenbank alle Daten von der Nachricht
		$result = $this->db->Select('*', Market::$table, '');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$item = new MarketItem($row['id'], $row['userid'], $row['username'], $row['item'], $row['itemname'], $row['itemimage'], $row['amount'], $row['price']);
					$this->items[$row['id']] = $item;
				}
			}
			$result->close();
		}
	}
}
?>