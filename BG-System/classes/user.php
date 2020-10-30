<?php
class UserItem
{
	public $id = 0;
	public $user = 0;
	public $item = 0;
	public $amount = 0;
	public $slot = 0;
	
	function __construct($id, $user, $item, $amount, $slot) 
	{
		$this->id = $id;
		$this->user = $user;
		$this->item = $item;
		$this->amount = $amount;
		$this->slot = $slot;
	}
}

class User
{
	static $table = 'users';
	static $tableAtks = 'userattacks';
	static $tableItems = 'useritems';
	
	private $db;
	private $data;
	private $attacks;
	private $items;
	private $session;
	private $itemSlots;
	
	function __construct($db, $id) 
	{
		//Setzte initiale Daten
		$this->db = $db;
		$this->attacks = array();
		$this->items = array();
		$this->itemSlots = array();
		
		//Lade alle Daten vom Spieler
		$this->LoadPlayer($id);
		//Lade die Attacken des Spielers
		$this->LoadAttacks();
		//Lade die Items des Spielers
		$this->LoadItems();
	}
	
	static function AddMoney($db, $id, $money)
	{
		$db->Update('money = money+"'.$money.'"', User::$table, 'id="'.$id.'"', 1);
	}
	
	static function Encrypt($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	static function GetIDFromName($db, $name)
	{
		//Hole aus der Datenbank alle Daten vom Spieler
		$id = 0;
		$result = $db->Select('id, name', User::$table, 'name="'.$db->EscapeString($name).'"', 1);
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
				$id = $row['id'];
			}
			$result->close();
		}
		
		return $id;
	}
	
	static function Register($db, $login, $password, $name)
	{
		//hier werden die Default Values definiert
		$variables = 'login
					  ,password 
					  ,name
					  ,ki
					  ,lp
					  ,kp
					  ,mlp
					  ,mkp
					  ,atk
					  ,def
					  ,money
					  ';
					  
		$values = '("'.$login.'"
					,"'.User::Encrypt($password).'"
					,"'.$name.'"
					,"10"
					,"100"
					,"100"
					,"100"
					,"100"
					,"10"
					,"10"
					,"100"
					)';
		//Füge sie ein
		$db->Insert($variables, $values, User::$table);
		$id = $db->GetLastID();
		//spieler existiert schon, alle returnen wir null
		if($id == 0)
		{
			return null;
		}
		//spieler ist nun angelegt, erzeuge ihn
		$user = new User($db, $id);
		//und füge die default attacken hinz
		$attacks = array(1, 4, 5);
		$user->AddAttacks($attacks);
		return $user;
	}	
	
	static function Login($db, $login, $password, $session)
	{
		$result = $db->Select('id, login, password', User::$table, 'login="'.$login.'"');
		$success = false;
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					//überprüfe ob password übereinstimmt
					if(password_verify($password, $row['password']))
					{
						$db->Update('session="'.$session.'"', User::$table, 'id="'.$row['id'].'"', 1);
						$success = true;
						break;
					}
				}
			}
			$result->close();
		}
		
		return $success;
	}	
	
	static function GetLoggedIn($db, $session)
	{
		$result = $db->Select('id, session', User::$table, 'session="'.$db->EscapeString($session).'"', 1);
		$id = 0;
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
				$id = $row['id'];
			}
			$result->close();
		}
		
		if($id != 0)
		{
			return new User($db, $id);
		}
		return null;
	}	
	
	function Logout()
	{
		$this->db->Update('session=""', User::$table, 'id="'.$this->Get('id').'"', 1);
	}
	
	function Get($name)
	{
		return $this->data[$name];
	}
	
	function Set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	function ResetChallenge()
	{
		$this->Challenge(0, 0);
	}
	
	function Challenge($userid, $item)
	{
		$this->Set('challengeitem', $item);
		$this->Set('challenger', $userid);
		$this->db->Update('challenger="'.$userid.'", challengeitem="'.$item.'"', User::$table, 'id="'.$this->Get('id').'"', 1);
	}
	
	function ChangeProfile($image, $description)
	{
		$image = $this->db->EscapeString($image);
		$description = $this->db->EscapeString($description);
		$this->Set('profileimage', $image);
		$this->Set('profiledescription', $description);
		$this->db->Update('profileimage="'.$image.'", profiledescription="'.$description.'"', User::$table, 'id="'.$this->Get('id').'"', 1);
	}
	
	function UnEquip($itemData, $updatePlayer=true)
	{
		$slot = $itemData->Get('slot');
		if(!isset($this->itemSlots[$slot]))
		{
			return;
		}
		$item = $this->itemSlots[$slot];
		if($item == null)
		{
			return;
		}
		$item->slot = 0;
		$this->db->Update('slot="0"', User::$tableItems, 'id="'.$item->id.'"', 1);
		
		$itemLP = $this->Get('itemlp') - $itemData->Get('lp');
		$this->Set('itemlp', $itemLP);
		$itemKP = $this->Get('itemkp') - $itemData->Get('kp');
		$this->Set('itemkp', $itemKP);
		$itemAtk = $this->Get('itematk') - $itemData->Get('atk');
		$this->Set('itematk', $itemAtk);
		
		$itemDef = $this->Get('itemdef') - $itemData->Get('def');
		$this->Set('itemdef', $itemDef);
		
		if($updatePlayer)
		{
			$this->db->Update('itemlp="'.$itemLP.'"
							  ,itemkp="'.$itemKP.'"
							  ,itematk="'.$itemAtk.'"
							  ,itemdef="'.$itemDef.'"', User::$table, 'id="'.$this->Get('id').'"', 1);
		}
		
		$this->db->Update('slot="0"', User::$tableItems, 'id="'.$item->id.'"', 1);
		unset($this->itemSlots[$slot]);
	}
	
	function Equip(&$item, $itemData)
	{
		$slot = $itemData->Get('slot');
		
		if(isset($this->itemSlots[$slot]))
		{
			$equippedItem = $this->itemSlots[$slot];
			$unequipItemData = new Item($this->db, $equippedItem->item);
			$this->UnEquip($unequipItemData, false);
		}
			
		//equip
		$item->slot = $slot;
		$this->itemSlots[$slot] = $item;
		
		$itemLP = $this->Get('itemlp') + $itemData->Get('lp');
		$this->Set('itemlp', $itemLP);
		$itemKP = $this->Get('itemkp') + $itemData->Get('kp');
		$this->Set('itemkp', $itemKP);
		$itemAtk = $this->Get('itematk') + $itemData->Get('atk');
		$this->Set('itematk', $itemAtk);
		$itemDef = $this->Get('itemdef') + $itemData->Get('def');
		$this->Set('itemdef', $itemDef);
		
			$this->db->Update('itemlp="'.$itemLP.'"
							  ,itemkp="'.$itemKP.'"
							  ,itematk="'.$itemAtk.'"
							  ,itemdef="'.$itemDef.'"', User::$table, 'id="'.$this->Get('id').'"', 1);
		
		$this->db->Update('slot="'.$slot.'"', User::$tableItems, 'id="'.$item->id.'"', 1);
	}
	
	function Heal($lpAdd, $kpAdd)
	{
		$lp = $this->Get('lp');
		$lp = $lp+$lpAdd;
		$kp = $this->Get('kp');
		$kp = $kp+$kpAdd;
		if($lp > $this->Get('mlp'))
		{
			$lp = $this->Get('mlp');
		}
		if($kp > $this->Get('mkp'))
		{
			$kp = $this->Get('mkp');
		}
		
		$this->Set('lp',$lp);
		$this->Set('kp',$kp);
		$this->db->Update('lp="'.$lp.'", kp="'.$kp.'"', User::$table, 'id="'.$this->Get('id').'"', 1);
	}
	
	function SellItem($item, $amount, $itemData)
	{
		$money = $itemData->Get('price') / 2;
		$money = $this->Get('money') + ($money * $amount);
		$this->Set('money', $money);
		$this->db->Update('money="'.$money.'"', User::$table, 'id="'.$this->Get('id').'"',1);
		
		$this->RemoveItem($item, $amount);
	}
	
	function RemoveItem($item, $amount)
	{
		$newAmount = $item->amount - $amount;
		if($newAmount <= 0)
		{
			$this->db->Delete(User::$tableItems, 'id="'.$item->id.'"');
			$this->RemoveItemByID($item->id);
		}
		else
		{
			$this->db->Update('amount="'.$newAmount.'"', User::$tableItems, 'id="'.$item->id.'"', 1);
			$item->amount = $newAmount;
		}
	}
	
	function RemoveItemByID($id)
	{
		for($i = 0; $i < count($this->items); ++$i)
		{
			if($this->items[$i]->id == $id)
			{
				unset($this->items[$i]);
				return;
			}
		}
	}
	
	function GetItems()
	{
		return $this->items;
	}
	
	function GetItem($id)
	{
		foreach($this->items as &$item)
		{
			if($item->id == $id)
			{
				return $item;
			}
		}
		
		return null;
	}
	
	function GetItemByID($id)
	{
		foreach($this->items as &$item)
		{
			if($item->item == $id)
			{
				return $item;
			}
		}
		
		return null;
	}
	
	function HasItem($id)
	{
		//Überprüfe ob der Spieler ein Item besitzt
		return in_array($id, $this->items);
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
		
	function LoadPlayer($id)
	{
		//Hole aus der Datenbank alle Daten vom Spieler
		$result = $this->db->Select('*', User::$table, 'id="'.$this->db->EscapeString($id).'"', 1);
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
				$this->data = $row;
			}
			$result->close();
		}
	}
	
	function BuyItem($price, $id, $amount, $type)
	{
		$money = $this->Get('money');
		$money -= $price;
		if($money < 0)
		{
			return false;
		}
		
		$this->Set('money', $money);
		
		//type == 2 can only be one
		if($type == 2)
		{
			for($i = 0; $i < $amount; ++$i)
			{
				$this->AddItem($id, 1, $type);
			}
		}
		else
		{
			$this->AddItem($id, $amount, $type);
		}
		
		$this->db->Update('money="'.$money.'"', User::$table, 'id="'.$this->Get('id').'"',1);
		
		return true;
	}
		
	function LoadItems()
	{
		//Hole aus der Datenbank die Attacken und füge sie zum array hinzu
		$result = $this->db->Select('*', User::$tableItems, 'user="'.$this->Get('id').'"');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$item = new UserItem($row['id'], $row['user'], $row['item'], $row['amount'], $row['slot']);
					$idx = count($this->items);
					array_push($this->items, $item);
					if($row['slot'] != 0)
					{
						$this->itemSlots[$row['slot']] = &$this->items[$idx];
					}
				}
			}
			$result->close();
		}
	}
	
	static function AddItemToPlayer($db, $userid, $id, $amount, $type)
	{
		$result = $db->Select('*', User::$tableItems, 'user="'.$userid.'"');
		$found = false;
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					if($row['item'] == $id && $type != 2)
					{
						$newAmount = $row['amount']+$amount;
						$db->Update('amount="'.$newAmount.'"', User::$tableItems, 'id="'.$row['id'].'"',1);
						$found = true;
						break;
					}
				}
			}
			$result->close();
		}
		
		if(!$found)
		{
			$variables = 'user, item, amount';
			if($type == 2)
			{
				$values = '('.$userid.', '.$id.', 1)';
				for($i =0; $i < $amount; ++$i)
				{
					$db->Insert($variables, $values, User::$tableItems);
				}
			}
			else
			{
				$values = '('.$userid.', '.$id.', '.$amount.')';
				$db->Insert($variables, $values, User::$tableItems);
			}
		}
	}
	
	function AddItem($id, $amount, $type)
	{
		//type == 2 can only be one
		if($type != 2)
		{
			foreach($this->items as &$item)
			{
				if($item->item == $id)
				{
					$item->amount += $amount;
					$this->db->Update('amount="'.$item->amount.'"', User::$tableItems, 'id="'.$item->id.'"',1);
					return;
				}
			}
		}
		
		//es gibt noch kein item im array, also adden wir es
		$variables = 'user, item, amount';
		$values = '('.$this->Get('id').', '.$this->db->EscapeString($id).', '.$this->db->EscapeString($amount).')';
		$this->db->Insert($variables, $values, User::$tableItems);
		$item = new UserItem($this->db->GetLastID(), $this->Get('id'), $id, $amount, 0);
		array_push($this->items, $item);
	}
		
	function LoadAttacks()
	{
		//Hole aus der Datenbank die Attacken und füge sie zum array hinzu
		$result = $this->db->Select('*', User::$tableAtks, 'user="'.$this->Get('id').'"');
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
		$variables = 'user, attack';
		$values = '';
		//Hier formatieren wir den SQL-Query
		//Format soll werden: (userID, attackenID),(userID, attackenID),(userID, attackenID)
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
		$this->db->Insert($variables, $values, User::$tableAtks);
	}
}
?>