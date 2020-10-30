<?php
include_once 'fighter.php';
include_once 'attack.php';
include_once 'user.php';
include_once 'tournament.php';

class Fight
{
	static $table = 'fights';
	
	static function ValidateMode($mode)
	{
		$endMode = null;
		
		$modeExp = explode('vs', $mode);
		
		$valid = false;
		for($i = 0; $i < count($modeExp); ++$i)
		{
			if(!is_numeric($modeExp[$i]) || $modeExp[$i] <= 0)
			{
				continue;
			}
			
			if($endMode == null)
			{
				$endMode = $modeExp[$i];
			}
			else
			{
				$endMode = $endMode.'vs'.$modeExp[$i];
				$valid = true;
			}
		}
		
		if(!$valid)
		{
			return null;
		}
		
		return $endMode;
	}
	
	
	static function GetPlayerFight($db, $user)
	{
		//Hole aus der Datenbank alle Daten vom Spieler
		$join = 'INNER JOIN '.Fight::$table.' ON '.Fighter::$table.'.fight='.Fight::$table.'.id';
		
		$select = Fighter::$table.'.user
				  ,'.Fighter::$table.'.fight
				  ,'.Fight::$table.'.id';
		
		$where = Fighter::$table.'.user="'.$user.'" AND '.Fight::$table.'.state != 3';
		
		$result = $db->Select($select, Fighter::$table, $where, 1, '', 'ASC', $join);
		$id = 0;
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
				$id = $row['fight'];
			}
			$result->close();
		}
		return $id;
	}
	
	static function CreateFight($db, $name, $mode, $money, $items, $keepStats, $levelup, $tournament = 0)
	{
		//Mit dieser Funktion wird der Kampf erstellt
		Fight::DebugLog('Create Fight '.$name.'.');
		
		$variables = 'name, mode, state, round, money, items, keepstats, levelup, tournament';
		$values = '("'.$db->EscapeString($name).'","'.$db->EscapeString($mode).'"
		,"1","1","'.$money.'","'.implode(';',$items).'","'.$keepStats.'","'.$levelup.'","'.$tournament.'")';
		
		//Wir fügen einfach einen Wert zur Tabbelle "fights" hinzu
		$db->Insert($variables, $values, Fight::$table);
		$id = $db->GetLastID();
		
		return new Fight($db, $id);
	}
	
	static $DebugLog = null;
	static function DebugLog($text)
	{
		//Debug Funktion
		if(Fight::$DebugLog == null)
		{
			Fight::$DebugLog = array();
		}
		array_push(Fight::$DebugLog, $text);
	}
	
	static function GetDebugLog()
	{
		return Fight::$DebugLog;
	}
	
	private $db;
	private $data;
	private $valid;
	private $attackManager;
	
	
	private $teams;
	
	function __construct($db, $id) 
	{
		//Konstruktor, wir initializieren die Daten und Laden den Kampf
		$this->db = $db;
		$this->valid = false;
		$this->teams = array();
		$this->attackManager = new AttackManager($db);
		
		//Lade den Kampf
		$this->LoadFight($id);
	}
	
	public function IsValid()
	{
		return $this->valid;
	}
	
	public function Get($name)
	{
		return $this->data[$name];
	}
	
	public function Set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	public function IsOpen()
	{
		return $this->Get('state') == 1;
	}
	
	public function GetSecondsTillKick($fighter)
	{
		$maxSeconds = 60 * 3; // 3 Minutes
		return $maxSeconds - $fighter->GetSecondsSinceLastAction();
	}
	
	public function IsKickable($fighter)
	{
		return $this->GetSecondsTillKick($fighter) <= 0;
	}
	
	public function Kick($fighter)
	{
		if($fighter->Get('attack') != 0 || $fighter->Get('isnpc') || !$this->IsKickable($fighter))
		{
			return;
		}
		$fighter->Set('lp',0);
		$fighter->Set('target',$fighter->Get('id'));
		$this->db->Update('lp="0",target="'.$fighter->Get('id').'"', fighter::$table, 'id="'.$fighter->Get('id').'"', 1);
		
		$this->CheckRound();
		return;
	}
	
	public function DoAttack($fighter, $aid, $tid)
	{
		//Mache die Attacke vom Spieler, erst, überprüfe ob er überhaupt einen Angriff machen kann.
		if($this->Get('state') != 2)
		{
			Fight::DebugLog('Fight is not running.');
			return;
		}
		else if($fighter->Get('attack') != 0)
		{
			Fight::DebugLog('Fighter '.$fighter->Get('id').' already picked an attack.');
			return;
		}
		else if($fighter->Get('lp') == 0)
		{
			Fight::DebugLog('Fighter '.$fighter->Get('id').' is already dead.');
			return;
		}
		else if(!$fighter->HasAttack($aid))
		{
			Fight::DebugLog('Fighter '.$fighter->Get('id').' does not have attack ID '.$aid.'.');
			return;
		}
		
		//Überprüfe das Ziel
		$target = $this->GetFighter($tid);
		if($target == null)
		{
			Fight::DebugLog('Target '.$tid.' of '.$fighter->Get('id').' does not exist.');
			return;
		}
		else if($target->Get('fuseid') != 0)
		{
			Fight::DebugLog('Target '.$tid.' of '.$fighter->Get('id').' is fused.');
			return;
		}
		
		//Nun hole den Angriff
		$attack = $this->attackManager->Get($aid);
		//Setze KP minus
		$atkKP = $attack->GetCost($fighter->Get('mkp'));
		$fighterKP = $fighter->Get('kp');
		if($atkKP > $fighterKP)
		{
			Fight::DebugLog('Fighter '.$fighter->Get('id').' does not have enough kp ('.$atkKP.').');
			return;
		}
		
		$newKP = $fighterKP-$atkKP;
		if($newKP < 0)
		{
			$newKP = 0;
		}
		//Setze Werte
		$fighter->Set('kp', $newKP);
		$fighter->Set('target', $tid);
		$fighter->Set('attack', $aid);
		Fight::DebugLog($fighter->Get('id').' set attack '.$aid.' and target '.$tid.'.');
		
		//Schaue ob Runde vorbei ist
		if(!$this->CheckRound())
		{
			//Runde ist nicht vorbei, update nur die Datenbank
			$this->db->Update('target="'.$tid.'", attack="'.$aid.'", kp="'.$newKP.'"', fighter::$table, 'id="'.$fighter->Get('id').'"', 1);
		}
	}
	
	public function &GetAttack($id)
	{
		return $this->attackManager->Get($id);
	}
	
	public function GetTeam($team)
	{
		//Holt das Team als array
		if(!isset($this->teams[$team]))
		{
			return null;
		}
		return $this->teams[$team];
	}
	
	public function GetAllFighters()
	{
		//Holt die Referenzen aller Spieler
		$fighters = array();
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				array_push($fighters, $fighter);
			}
		}
		return $fighters;
	}
	
	public function &GetUserFighter($user)
	{
		//Holt den Spieler als Referenz basierend auf seiner ID
		$player = null;
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('user') == $user)
				{
				Fight::DebugLog('Player is Fighter '.$fighter->Get('name').' ('.$fighter->Get('id').')');
					return $fighter;
				}
			}
		}
		Fight::DebugLog('Could not find fighter for user '.$user);
		return $player;
	}
	
	public function &GetFighter($id)
	{
		//Holt den Spieler als Referenz basierend auf seiner ID
		$player = null;
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('id') == $id)
				{
					return $fighter;
				}
			}
		}
		Fight::DebugLog('Could not find fighter '.$id);
		return $player;
	}
	
	public function LeaveFight($id)
	{
		if($this->Get('state') != 1)
		{
			Fight::DebugLog('Fight is already in state '.$this->Get('state').', it cannot remove a Fighter.');
			return;
		}
		
		$this->RemoveFighter($id);
		
		//überprüfe ob alle teams leer sind
		Fight::DebugLog('Team num is '.count($this->teams));
		if(count($this->teams) == 0)
		{
			Fight::DebugLog('Deleting the fight '.$this->Get('id'));
			$this->db->Delete(Fight::$table, 'id="'.$this->Get('id').'"');
		}
	}
	
	public function RemoveFighter($id)
	{
		Fight::DebugLog('Removing Fighter.' .$id.' from Fight '.$this->Get('id'));
		//Entfernt den Spieler vom Kampf, wenn der Kampf noch offen ist
		if($this->Get('state') != 1)
		{
			Fight::DebugLog('Fight is already in state '.$this->Get('state').', it cannot remove a Fighter.');
			return;
		}
		
		//Gehe durch das Team und entferne den Spieler
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('id') == $id)
				{
					//ID ist im Kampf, entferne aus Team und aus Datenbank
					Fight::DebugLog('Remove fighter '.$fighter->Get('id').' at index '.$i.' from team '.$fighter->Get('team').'.');
					$this->db->Delete(Fighter::$table, 'id="'.$fighter->Get('id').'"');
					$this->db->Delete(Fighter::$tableAtks, 'fighter="'.$fighter->Get('id').'"');
					unset($team[$i]);
				}
			}
			
			//Wenn Team leer ist, entferne es
			if(count($team) == 0)
			{
				Fight::DebugLog('- Team is zero, so we empty it out.');
				unset( $this->teams[array_search( $team, $this->teams )] );
			}
		}
		
	}
	
	public function &AddPlayerFighter($db, $user, $team)
	{
		$ki = $user->Get('ki');
		$lp = $user->Get('lp') + $user->Get('itemlp');
		$mlp = $user->Get('mlp') + $user->Get('itemlp');
		$kp = $user->Get('kp') + $user->Get('itemkp');
		$mkp = $user->Get('mkp') + $user->Get('itemkp');
		$atk = $user->Get('atk') + $user->Get('itematk');
		$def = $user->Get('def') + $user->Get('itemdef');
		$isnpc = 0;
			return $this->AddFighter($db
									,$user->Get('name')
									,$user->Get('profileimage')
									,$user->Get('id')
									,$team
									,$ki
									,$atk
									,$def
									,$lp
									,$mlp
									,$kp
									,$mkp
									,$isnpc);
	}
	
	public function &AddNPCFighter($db, $npc, $team)
	{
		$isNPC = 1;
		return $this->AddFighter($db, 
									   $npc->Get('name'), 
									   $npc->Get('image'), 
									   $npc->Get('id'), 
									   $team, 
									   $npc->Get('ki'), 
									   $npc->Get('atk'), 
									   $npc->Get('def'), 
									   $npc->Get('lp'), 
									   $npc->Get('lp'), 
									   $npc->Get('kp'), 
									   $npc->Get('kp'), 
									   $isNPC);
	}
	
	public function &AddFighter($db, $name, $image, $user, $team, $ki, $atk, $def, $lp, $mlp, $kp, $mkp, $npc, $isCreated=0)
	{
		//Erstelle die Werte und füge ihn zur Datenbank hinzu
		$variables = 'name, image, user, fight, team, ki, mki, atk, matk, def, mdef, lp, mlp, ilp, kp, mkp, ikp, isnpc, iscreated';
		$values = '("'.$name.'","'.$image.'","'.$user.'","'.$this->Get('id').'","'.$team.'","'.$ki.'","'.$ki.'","'.$atk.'","'.$atk.'"
		           ,"'.$def.'","'.$def.'","'.$lp.'","'.$mlp.'","'.$mlp.'","'.$kp.'","'.$mkp.'","'.$mkp.'","'.$npc.'","'.$isCreated.'")';
		
		$db->Insert($variables, $values, Fighter::$table);
		$id = $db->GetLastID();
		
		
		//Fülle die Werte um den Spieler lokal hinzuzufügen
		$data['id'] = $id;
		$data['image'] = $image;
		$data['name'] = $name;
		$data['user'] = $user;
		$data['team'] = $team;
		$data['fight'] = $this->Get('id');
		$data['ki'] = $ki;
		$data['mki'] = $ki;
		$data['atk'] = $atk;
		$data['matk'] = $atk;
		$data['def'] = $def;
		$data['mdef'] = $def;
		$data['lp'] = $lp;
		$data['mlp'] = $lp;
		$data['ilp'] = $lp;
		$data['kp'] = $kp;
		$data['mkp'] = $kp;
		$data['ikp'] = $kp;
		$data['isnpc'] = $npc;
		$data['attack'] = 0;
		$data['target'] = 0;
		$data['transform'] = '';
		$data['fuseid'] = 0;
		$data['iscreated'] = 0;
		$data['paralyzed'] = 0;
		$data['buffs'] = '';
		$data['heals'] = '';
		
		//Erstelle einen neuen Kämpfer und füge ihn zu seinem Team Array hinzu
		$fighter = new Fighter($db, $data);
		$this->AddFighterToTeam($fighter, $team);
		
		$this->CheckStart();
		
		return $fighter;
	}
	
	private function DoNPCAttacks()
	{
		//Gehe NPCs durch und mache die Angriffe
		Fight::DebugLog('Check if we have NPCs and calculate their attack.');
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('attack') == 0 && $fighter->Get('lp') != 0 && $fighter->Get('isnpc'))
				{
					$this->DoNPCAttack($fighter);
				}
			}
		}
	}
	
	private function DoNPCAttack(&$fighter)
	{
		
		Fight::DebugLog('- Calculate attack for '.$fighter->Get('id').'.');
		$fighterAttacks = $fighter->GetAttacks();
		
		$defenses = array();
		$heals = array();
		$vws = array();
		$attacks = array();
		$creates = array();
		$paralyzes = array();
		$buffs = array();
		$debuffs = array();
		$healbuffs = array();
		$dehealbuffs = array();
		
		//Sortiere Angriffe in arrays
		foreach($fighterAttacks as &$atkID)
		{
			$attack = $this->attackManager->Get($atkID);
			switch($attack->Get('type'))
			{
				case 1: //DMG
				array_push($attacks, $attack);
				break;
				case 2: //HEAL
				array_push($heals, $attack);
				break;
				case 3: //VW
				array_push($vws, $attack);
				break;
				case 4: //DEF
				array_push($defenses, $attack);
				break;
				case 5: //CREATE
				array_push($creates, $attack);
				break;
				case 7: //PARALYZE
				array_push($paralyzes, $attack);
				break;
				case 9: //debuff
				array_push($debuffs, $attack);
				break;
				case 10: //buffs
				array_push($buffs, $attack);
				break;
				case 11: //HEAL BUFF
				array_push($healbuffs, $attack);
				break;
				case 12: //DEHEAL BUFF
				array_push($dehealbuffs, $attack);
				break;
			}
		}
		
		$fighterTeam = $fighter->Get('team');
		
		$aid = 1; //default attack
		$tid = $fighter->Get('id');
		
		//mache eine VW, falls vorhanden und transformation noch nicht gewählt ist
		if(count($vws) > 0 && $fighter->Get('transform') == '' && $fighter->Get('kp') > 0)
		{
			$atkRand = 3;
		}
		else
		{
			$atkRand = rand(1,10);
		}
		
		$findTarget = false;
		
		//Nun mache die verschiedenen Fälle
		Fight::DebugLog('-- '.$fighter->Get('id').' has atkRand '.$atkRand.'.');
		if($atkRand == 2 && count($heals) == 0)
		{
			//Heilung wurde ausgewählt aber er hat keine Heilung, wähle Angriff
			Fight::DebugLog('-- '.$fighter->Get('id').' picked heal but has no heal.');
			$atkRand = 1;
		}
		else if($atkRand == 2)
		{
			//Heilung wurde ausgewählt, finde eine Heilung mit genug KP, wenn nicht, wähle Angriff
			$atkID = rand(0, count($heals)-1);
			if($heals[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for heal.');
				$atkRand = 1;
			}
			else if($fighter->Get('lp') > ($fighter->Get('mlp') * 0.2))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has more than 20% of his max health.');
				$atkRand = 1;
			}
			else
			{
				//Heilung gefunden, finde Ziel
				$aid = $heals[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked heal '.$aid.'.');
				
				$team = $this->teams[$fighterTeam];
				$randID = rand(0, count($team)-1);
				$tid = $team[$randID]->Get('id');
				
				Fight::DebugLog('-- '.$fighter->Get('id').' will heal '.$tid.'.');
			}
		}
		
		if($atkRand == 9 && count($healbuffs) == 0)
		{
			Fight::DebugLog('-- '.$fighter->Get('id').' picked heal buffs but has no heal buffs.');
			$atkRand = 1;
		}
		else if($atkRand == 9)
		{
			//heals wurde ausgewählt, finde eine heal mit genug KP, wenn nicht, wähle Angriff
			$atkID = rand(0, count($healbuffs)-1);
			if($healbuffs[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for heal buff.');
				$atkRand = 1;
			}
			else
			{
				//buff gefunden, finde Ziel
				$aid = $healbuffs[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked heal buff '.$aid.'.');
				
				$team = $this->teams[$fighterTeam];
				$randID = rand(0, count($team)-1);
				if($team[$randID]->HasHeal($aid))
				{
					$atkRand = 1;
					Fight::DebugLog('-- '.$fighter->Get('id').' wants to heal buff '.$tid.', but '.$tid.' already has the heal buff.');
				}
				else
				{
					$tid = $team[$randID]->Get('id');
					Fight::DebugLog('-- '.$fighter->Get('id').' will heal buff '.$tid.'.');
				}
			}
		}
		
		if($atkRand == 7 && count($buffs) == 0)
		{
			Fight::DebugLog('-- '.$fighter->Get('id').' picked buffs but has no buff.');
			$atkRand = 1;
		}
		else if($atkRand == 7)
		{
			//buffs wurde ausgewählt, finde eine buffs mit genug KP, wenn nicht, wähle Angriff
			$atkID = rand(0, count($buffs)-1);
			if($buffs[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for buff.');
				$atkRand = 1;
			}
			else
			{
				//buff gefunden, finde Ziel
				$aid = $buffs[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked buff '.$aid.'.');
				
				$team = $this->teams[$fighterTeam];
				$randID = rand(0, count($team)-1);
				if($team[$randID]->HasBuff($aid))
				{
					$atkRand = 1;
					Fight::DebugLog('-- '.$fighter->Get('id').' wants to buff '.$tid.', but '.$tid.' already has the buff.');
				}
				else
				{
					$tid = $team[$randID]->Get('id');
					Fight::DebugLog('-- '.$fighter->Get('id').' will buff '.$tid.'.');
				}
			}
		}
		
		if($atkRand == 3 && count($vws) == 0)
		{
			//Wenn keine VW, wähle Angriff
			$atkRand = 1;
			Fight::DebugLog('-- '.$fighter->Get('id').' picked vw but has no vws.');
		}
		else if($atkRand == 3)
		{
			//Setze VW, wenn VW schon hat, mache Angriff
			if($fighter->Get('transform') == '')
			{
				$atkID = rand(0, count($vws)-1);
				if($vws[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
				{
					Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for vw.');
					$atkRand = 1;
				}
				else
				{
					$aid = $vws[$atkID]->Get('id');
					Fight::DebugLog('-- '.$fighter->Get('id').' picked vw '.$aid.'.');
				}
			}
			else
			{
				$atkRand = 1;
			}
		}
		if($atkRand == 4 && count($defenses) == 0)
		{
			//Keine Verteidigung, mache Angriff
			$atkRand = 1;
			Fight::DebugLog('-- '.$fighter->Get('id').' picked defense but has no defenses.');
		}
		else if($atkRand == 4)
		{
			//Wähle Verteidigung mit genügend KP
			$atkID = rand(0, count($defenses)-1);
			if($defenses[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for defense.');
				$atkRand = 1;
			}
			else
			{
				$aid = $defenses[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked defense '.$aid.'.');
			}
		}
		if($atkRand == 5 && count($creates) == 0)
		{
			//Keine Verteidigung, mache Angriff
			$atkRand = 1;
			Fight::DebugLog('-- '.$fighter->Get('id').' picked defense but has no defenses.');
		}
		else if($atkRand == 5)
		{
			//Wähle Verteidigung mit genügend KP
			$atkID = rand(0, count($creates)-1);
			if($creates[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for create.');
				$atkRand = 1;
			}
			else
			{
				$aid = $creates[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked create '.$aid.'.');
			}
		}
		
		if($atkRand == 6 && count($paralyzes) == 0)
		{
			//Keine Paralyze, mache Angriff
			$atkRand = 1;
			Fight::DebugLog('-- '.$fighter->Get('id').' picked paralyze but has no paralyzes.');
		}
		else if($atkRand == 6)
		{
			//Wähle Paralyze mit genügend KP
			$atkID = rand(0, count($paralyzes)-1);
			if($paralyzes[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for paralyze.');
				$atkRand = 1;
			}
			else
			{
				$aid = $paralyzes[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked paralyze '.$aid.'.');
			}
			
			$findTarget = true;
		}
		
		if($atkRand == 10 && count($dehealbuffs) == 0)
		{
			//Keine deheal buffs, mache Angriff
			$atkRand = 1;
			Fight::DebugLog('-- '.$fighter->Get('id').' picked dehealbuffs but has no dehealbuff.');
		}
		else if($atkRand == 10)
		{
			//dehealbuff wurde ausgewählt, finde eine dehealbuff mit genug KP, wenn nicht, wähle Angriff
			$atkID = rand(0, count($dehealbuffs)-1);
			if($dehealbuffs[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for dehealbuff.');
				$atkRand = 1;
			}
			else
			{
				//dehealbuff gefunden, finde Ziel
				$aid = $dehealbuffs[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked dehealbuff '.$aid.'.');
				$npcTarget = $this->GetNPCTarget($fighterTeam, $fighter);
				$tid = $npcTarget->Get('id');
				if($npcTarget->HasHeal($aid))
				{
					$atkRand = 1;
					Fight::DebugLog('-- '.$fighter->Get('id').' wants to dehealbuff '.$tid.', but '.$tid.' already has the dehealbuff.');
				}
				else
				{
					Fight::DebugLog('-- '.$fighter->Get('id').' will dehealbuff '.$tid.'.');
				}
			}
		}
		
		if($atkRand == 8 && count($debuffs) == 0)
		{
			//Keine debuff, mache Angriff
			$atkRand = 1;
			Fight::DebugLog('-- '.$fighter->Get('id').' picked debuffs but has no debuff.');
		}
		else if($atkRand == 8)
		{
			//debuffs wurde ausgewählt, finde eine debuffs mit genug KP, wenn nicht, wähle Angriff
			$atkID = rand(0, count($debuffs)-1);
			if($debuffs[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
			{
				Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for debuff.');
				$atkRand = 1;
			}
			else
			{
				//debuff gefunden, finde Ziel
				$aid = $debuffs[$atkID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' picked debuff '.$aid.'.');
				$npcTarget = $this->GetNPCTarget($fighterTeam, $fighter);
				$tid = $npcTarget->Get('id');
				if($npcTarget->HasBuff($aid))
				{
					$atkRand = 1;
					Fight::DebugLog('-- '.$fighter->Get('id').' wants to debuff '.$tid.', but '.$tid.' already has the debuff.');
				}
				else
				{
					Fight::DebugLog('-- '.$fighter->Get('id').' will debuff '.$tid.'.');
				}
			}
		}
		
		if($atkRand == 1 && count($attacks) != 0)
		{
			//Gehe Angriffe durch bis einer gefunden ist
			while(count($attacks) > 0)
			{
				$atkID = rand(0, count($attacks)-1);
				if($attacks[$atkID]->GetCost($fighter->Get('mkp')) > $fighter->Get('kp'))
				{
					//Angriff hat nicht genügend KP, entferne ihn aus der Liste
					Fight::DebugLog('-- '.$fighter->Get('id').' has no kp for attack '.$attacks[$atkID]->Get('id').'.');
					array_splice($attacks, $atkID, 1);
				}
				else
				{
					//Angriff gewählt
					$aid = $attacks[$atkID]->Get('id');
					Fight::DebugLog('-- '.$fighter->Get('id').' picked attack '.$aid.'.');
					break;
				}
			}
			
			//pick a target
			$findTarget = true;
		}
		
		if($findTarget)
		{
			$tid = $this->GetNPCTarget($fighterTeam, $fighter)->Get('id');
		}
		
		//Setze KP minus
		$attack = $this->attackManager->Get($aid);
		
		$atkKP = $attack->GetCost($fighter->Get('mkp'));
		$fighterKP = $fighter->Get('kp');
		
		$newKP = $fighterKP-$atkKP;
		if($newKP < 0)
		{
			$newKP = 0;
		}
		
		$fighter->Set('kp', $newKP);
		$fighter->Set('target', $tid);
		$fighter->Set('attack', $aid);
	}
	
	public function &GetNPCTarget(&$fighterTeam, &$fighter)
	{
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$target = &$team[$i];
				if($target->Get('fuseid') != 0)
				{
					continue;
				}
				if($target->Get('team') == $fighterTeam)
				{
					//Ziel ist eigenes Team, gehe weiter
					break;
				}
				$randID = rand(0, count($team)-1);
				$tid = $team[$randID]->Get('id');
				Fight::DebugLog('-- '.$fighter->Get('id').' will attack '.$tid.'.');
				return $team[$randID];
			}
		}
	}
	
	public function CheckRound()
	{
		//Überprüfe zunächst die NPCs
		$this->DoNPCAttacks();
		
		//Überprüfe ob alle etwas gewählt haben
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('attack') == 0 && $fighter->Get('lp') != 0 && $fighter->Get('fuseid') == 0)
				{
					Fight::DebugLog('- '.$fighter->Get('id').' still needs to pick an attack.');
					return false;
				}
			}
		}
		Fight::DebugLog('Start Round');
		
		Fight::DebugLog(' - Calculating order of fighters.');
		$defenses = array();
		$heals = array();
		$vws = array();
		$attacks = array();
		$creates = array();
		$fuses = array();
		$paralyzes = array();
		$paralyzed = array();
		$debuffs = array();
		$buffs = array();
		$healbuffs = array();
		$dehealbuffs = array();
		//Sortiere die Spieler anhand deren Attacken
		$fighterAttacks = array();
		
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				$aid = $fighter->Get('attack');
				$fighterAttacks[$fighter->Get('id')] = $aid;
				if($aid != 0 && $fighter->Get('lp') != 0)
				{
					$attack = $this->attackManager->Get($aid);
					switch($attack->Get('type'))
					{
						case 1: //DMG
						array_push($attacks, $fighter);
						break;
						case 2: //HEAL
						array_push($heals, $fighter);
						break;
						case 3: //VW
						array_push($vws, $fighter);
						break;
						case 4: //DEF
						array_push($defenses, $fighter);
						break;
						case 5: //CREATE
						array_push($creates, $fighter);
						break;
						case 6: //FUSE
						array_push($fuses, $fighter);
						break;
						case 7: //PARALYZE
						array_push($paralyzes, $fighter);
						break;
						case 8: //PARALYZED
						array_push($paralyzed, $fighter);
						break;
						case 9: //DEBUFFS
						array_push($debuffs, $fighter);
						break;
						case 10: //BUFFS
						array_push($buffs, $fighter);
						break;
						case 11: //HEALS
						array_push($healbuffs, $fighter);
						break;
						case 12: //DEHEALS
						array_push($dehealbuffs, $fighter);
						break;
					}
				}
			}
		}
		Fight::DebugLog(' - Calculating all defenses for all players.');
		$attackTexts = '<table>';
		//Mache erst die fuses
		foreach($fuses as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		//Dann die creates
		foreach($creates as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		//Dann die defs
		foreach($defenses as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		//Dann die healbuffs
		foreach($healbuffs as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		//Dann die buffs
		foreach($buffs as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		Fight::DebugLog(' - Calculating all heals for all players.');
		//Dann die Heilungen
		foreach($heals as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		//Dann die dehealbuffs
		foreach($dehealbuffs as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		//Dann die debuffs
		foreach($debuffs as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		Fight::DebugLog(' - Calculating all vws for all players.');
		//Dann die VWs
		foreach($vws as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		Fight::DebugLog(' - Calculating all paralyze for all players.');
		//Dann die paralyze
		foreach($paralyzes as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		Fight::DebugLog(' - Calculating all paralyzed for all players.');
		//Dann die paralyzed
		foreach($paralyzed as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		
		Fight::DebugLog(' - Calculating all attacks for all players.');
		//Dann die Angriffe
		foreach($attacks as &$fighter)
		{
			$attackText = $this->CalculateAttack($fighter);
			$attackText = '<tr height=50px>'.$attackText.'</tr>';
			$attackTexts = $attackTexts.$attackText;
		}
		$attackTexts = $attackTexts.'</table>';
		
		Fight::DebugLog(' - Check if someone has won.');
		
		$aliveTeam = -1;
		
		Fight::DebugLog(' - UnFusing Fighters');
		foreach($this->teams as &$team)
		{
			foreach($team as &$fighter)
			{
				if(!$fighter->Get('isnpc') && $fighter->Get('user') == 0 && $fighter->Get('lp') == 0)
				{
					Fight::DebugLog(' - - UnFuse '.$fighter->Get('id'));
					$this->UnFuse($fighter);
				}
			}
		}
		
		//revert all heals
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('lp') != 0 && $fighter->Get('heals') != '')
				{
					$this->DoHealCost($fighter);
				}
			}
		}
		//revert all buffs
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				if($fighter->Get('lp') != 0 && $fighter->Get('buffs') != '')
				{
					$this->DoBuffCost($fighter);
				}
			}
		}
		
		//Gucke ob der Kampf schon vorbei ist
		foreach($this->teams as &$team)
		{
			for($i = 0; $i < count($team); ++$i)
			{
				$fighter = &$team[$i];
				$teamID = $fighter->Get('team');
				
				//Spieler lebt noch und hat verwandlungen, ziehe die Kosten ab
				if($fighter->Get('lp') != 0 && $fighter->Get('transform') != '')
				{
					$this->DoTransformCost($fighter);
					
					//Spieler hat keine KP mehr, reverte die VWs
					if($fighter->Get('kp') == 0)
					{
						$this->ReverseAllTransform($fighter);
					}
				}
				//Gucke ob zwei Teams leben, wenn ja, führe fort, wenn einer lebt, gewinnt es
				if($fighter->Get('lp') != 0 && ($aliveTeam == -1 || $aliveTeam == $teamID))
				{
					Fight::DebugLog(' -- player '.$fighter->Get('id').' of team '.$teamID.' is still alive.');
					$aliveTeam = $teamID;
				}
				else if($fighter->Get('lp') != 0 && $aliveTeam != -1)
				{
					Fight::DebugLog(' -- player '.$fighter->Get('id').' of team '.$teamID.' is still alive.');
					$aliveTeam = -2;
				}
				else if($fighter->Get('lp') == 0 && $fighter->Get('attack') != 0)
				{
					//Ein Spieler ist gestorben, reverte seine Verwandlungen und setzte die todesnachricht rein
					Fight::DebugLog(' -- player '.$fighter->Get('id').' of team '.$teamID.' is dead.');
					$attackTexts = $fighter->Get('name').' ist besiegt.<br/>'.$attackTexts;
					$this->ReverseAllTransform($fighter);
				}					
			}
		}
		
		
		//Gehe die Verteidigungen durch und ziehe die Werte ab
		foreach($defenses as &$fighter)
		{
			$this->ReverseCalculateAttack($fighter, $fighterAttacks[$fighter->Get('id')]);
		}
		
		Fight::DebugLog(' - Update Fight Text');
		$text = '<h2>Runde '.$this->Get('round').'</h2>';
		
		$round = $this->Get('round');
		if($aliveTeam >= -1)
		{
			//Spiel zuende, entweder unentschieden oder ein Gewinner
			//Setzte alle Verwandlungen zurück
			foreach($this->teams as &$team)
			{
				for($i = 0; $i < count($team); ++$i)
				{
					$fighter = &$team[$i];
					$this->ReverseAllTransform($fighter);		
				}
			}
			
			if($aliveTeam == -1)
			{
				//DRAW
				Fight::DebugLog('- All teams are dead, there is a draw');
				$text = $text.'Beide Teams wurden besiegt!<br/>';
			}
			else
			{
				//a team has won
				Fight::DebugLog('- Team '.$aliveTeam.' won.');
				$text = $text.'Team '.($aliveTeam+1).' hat gewonnen!<br/>';
			}
			//Beende Kampf
			Fight::DebugLog('End Fight.');
			$this->Set('state', 3);
			$this->db->Update('state="3"', fight::$table, 'id="'.$this->Get('id').'"', 1);
			
			Fight::DebugLog('Delete all Attacks');
			$where = null;
			$tUser = 0;
			$tIsNPC = 0;
			foreach($this->teams as &$team)
			{
				for($i = 0; $i < count($team); ++$i)
				{
					$fighter = &$team[$i];
					if($fighter->Get('team') == $aliveTeam)
					{
						$tUser = $fighter->Get('user');
						$tIsNPC = $fighter->Get('isnpc');
						
						if(!$tIsNPC)
						{
							Fight::DebugLog('add all data like money, items and levelup '.$fighter->Get('id'));
							$this->AddWinAtEnd($fighter);
						}
					}
					Fight::DebugLog('Delete attack of Fighter '.$fighter->Get('id'));
					$fighter = &$team[$i];
					$baseWhere = 'fighter="'.$fighter->Get('id').'"';
					if($where == null)
					{
						$where = $baseWhere;
					}
					else
					{
						$where = $where.' OR '.$baseWhere;
					}
					$this->UpdateFighterRound($fighter);
					if($this->Get('keepstats') && $fighter->Get('user') != 0)
					{
						$this->UpdatePlayerAtEnd($fighter);
					}
				}
			}
			
			if($this->Get('tournament') != 0)
			{
				$tid = $this->Get('tournament');
				$tournament = new Tournament($this->db, $tid);
				$tournament->Win($tUser, $tIsNPC);
			}
			
			$this->db->Delete(Fighter::$tableAtks, $where);
		}
		else
		{
			//Kampf geht weiter, setzte aktionen zurück und setze die Werte aller Spieler in der Datenbank
			Fight::DebugLog(' - Reset target and attack for all players.');
			$createdWhere = null;
			foreach($this->teams as &$team)
			{
				foreach($team as $fighterKey => &$fighter)
				{
					if($fighter->Get('lp') == 0 && $fighter->Get('iscreated')) 
					{
						$baseWhere = 'id="'.$fighter->Get('id').'"';
						if($createdWhere == null)
						{
							$createdWhere = $baseWhere;
						}
						else
						{
							$createdWhere = $createdWhere.' OR '.$baseWhere;
						}
						unset($team[$fighterKey]);
						$this->db->Delete(Fighter::$tableAtks, 'fighter="'.$fighter->Get('id').'"');
					}
					else
					{
						$this->UpdateFighterRound($fighter);
					}
				}
			}
			
			$createdWhere = '('.$createdWhere.') AND fight="'.$this->Get('id').'"';
			$this->db->Delete(Fighter::$table, $createdWhere);
			//Erhöhe Runde
			$round = $round+1;
			$this->Set('round', $round);
			
			Fight::DebugLog('End Round');
		}
		
		//Füge den Kampftext zusammen und setze ihn in der Datenbank
		$text = $text.$attackTexts;
		if($this->Get('text') != '')
		{
			$text = $text.'<br/>'.$this->Get('text');
		}
		$this->Set('text', $text);
		$this->db->Update('round="'.$round.'",text="'.$text.'"', fight::$table, 'id="'.$this->Get('id').'"', 1);
		
		
		return true;
	}
	
	private function AddWinAtEnd(&$fighter)
	{
		$user = $fighter->Get('user');
		$money = $this->Get('money');
		$levelup = $this->Get('levelup');
		
		$update = null;
		if($money != 0)
		{
			$update = 'money=money+"'.$money.'"';
		}
		if($levelup)
		{
			$updateVal = 'level=level+1';
			if($update != null)
			{
				$update = $update.','.$updateVal;
			}
			else
			{
				$update = $updateVal;
			}
		}
		
		if($update != null)
		{
		$this->db->Update($update, User::$table, 'id="'.$user.'"', 1);
		}
		
		if($this->Get('items') != '')
		{
			$items = explode(';',$this->Get('items'));
			foreach($items as &$item)
			{
				$itemData = explode('@',$item);
				User::AddItemToPlayer($this->db, $fighter->Get('user'), $itemData[0], $itemData[1], $itemData[2]);
			}
		}
	}
	
	private function UpdatePlayerAtEnd(&$fighter)
	{
			$this->db->Update('lp="'.$fighter->Get('lp').'",kp="'.$fighter->Get('kp').'"', User::$table, 'id="'.$fighter->Get('user').'"', 1);
	}
	
	private function UpdateFighterRound(&$fighter)
	{
		$fighter->Set('lastactiontime',date('Y-m-d H:i:s',time()));
		$attack = 0;
		$target = 0;
		if($fighter->Get('paralyzed'))
		{
			$attack = $fighter->Get('attack');
			$target = $fighter->Get('id');
		}
		
		$fighter->Set('attack', $attack);
		$fighter->Set('target', $target);
		
					$this->db->Update('
										paralyzed="'.$fighter->Get('paralyzed').'",
										attack="'.$attack.'",
										target="'.$target.'",
										fuseid="'.$fighter->Get('fuseid').'",
										lastactiontime=CURRENT_TIMESTAMP,
										transform="'.$fighter->Get('transform').'", 
										buffs="'.$fighter->Get('buffs').'",
										heals="'.$fighter->Get('heals').'",
										atk="'.$fighter->Get('atk').'", 
										def="'.$fighter->Get('def').'", 
										ki="'.$fighter->Get('ki').'", 
										lp="'.$fighter->Get('lp').'", 
										ilp="'.$fighter->Get('ilp').'", 
										ikp="'.$fighter->Get('ikp').'", 
										kp="'.$fighter->Get('kp').'"', fighter::$table, 'id="'.$fighter->Get('id').'"', 1);
	}
	
	private function UnFuse(&$fusedFighter)
	{
		$team = $fusedFighter->Get('team');
		$team = &$this->teams[$team];
		
		$where = 'id="'.$fusedFighter->Get('id').'" AND fight="'.$this->Get('id').'"';
		$this->db->Delete(Fighter::$table, $where);
		$this->db->Delete(Fighter::$tableAtks, 'fighter="'.$fusedFighter->Get('id').'"');
		
		foreach($team as $fighterKey => &$fighter)
		{
			if($fighter->Get('fuseid') == $fusedFighter->Get('id'))
			{
				$fighter->Set('fuseid',0);
				$fighter->Set('lp',0);
			}
			
			if($fighter->Get('id') == $fusedFighter->Get('id'))
			{
				unset($team[$fighterKey]);
			}
		}
	}
	
	private function ReverseCalculateAttack(&$fighter, $aid)
	{
		//Invertiere die Angriffe, wichtig für Verwandlungen
		Fight::DebugLog(' -- Reverse attack ('.$aid.') for '.$fighter->Get('id').'.');
		
		$attack = $this->attackManager->Get($aid);
		
		switch($attack->Get('type'))
		{
			case 4: //DEF
			return $this->ReverseAttackDefend($fighter, $attack);
			break;
		}
		
		return 'Invalid attack';
	}
	
	private function CalculateAttack(&$fighter)
	{
		//Berechne die Attacken
		Fight::DebugLog(' -- Calculating attack for '.$fighter->Get('id').'.');
		
		//Hole den Angriff und das Ziel
		$attack = $this->attackManager->Get($fighter->Get('attack'));
		$target = $this->GetFighter($fighter->Get('target'));
		
		//Setzte Text und Berechne
		$text = '<td><img src='.$attack->Get('image').' width=50px height=50px></img></td>';
		$atkText = '';
		switch($attack->Get('type'))
		{
			case 1: //DMG
			$atkText = $this->AttackDamage($fighter, $target, $attack);
			break;
			case 2: //HEAL
			$atkText = $this->AttackHeal($fighter, $target, $attack);
			break;
			case 3: //VW
			$atkText = $this->AttackTransform($fighter, $attack);
			break;
			case 4: //DEF
			$atkText = $this->AttackDefend($fighter, $attack);
			break;
			case 5: //CREATE
			$atkText = $this->AttackCreate($fighter, $attack);
			break;
			case 6: //FUSE
			$atkText = $this->AttackFuse($fighter, $target, $attack);
			break;
			case 7: //PARALYZE
			$atkText = $this->AttackParalyze($fighter, $target, $attack);
			break;
			case 8: //PARALYZED
			$atkText = $this->AttackParalyzed($fighter, $attack);
			break;
			case 9: //DEBUFF
			case 10: //BUFF
			$atkText = $this->AttackBuff($fighter, $target, $attack);
			break;
			case 11: //HEAL
			case 12: //DEHEAL
			$atkText = $this->AttackHealBuff($fighter, $target, $attack);
			break;
		}
		
		if($atkText == '')
		{
			$atkText = 'Invalid Attack';
		}
		
		$text = $text.'<td>'.$atkText.'</td>';
		
		return $text;
	}
	
	
	private function AttackDamage(&$fighter, &$target, &$attack)
	{
		//Ein Schadensangriff
		Fight::DebugLog(' --- Calculating damage attack '.$attack->Get('id').' with target '.$target->Get('id').' for '.$fighter->Get('id').'.');
		
		$chance = rand(0, 100);
		if($attack->Get('hitchance') < $chance)
		{
			$text = $attack->Get('misstext');
			$text = str_replace('!user', $fighter->Get('name'), $text);
			$text = str_replace('!target', $target->Get('name'), $text);
			return $text;
		}
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		$text = str_replace('!target', $target->Get('name'), $text);
		
		
		if($target->Get('lp') == 0)
		{
			////Gegner ist tot.
			Fight::DebugLog('--- '.$fighter->Get('id').'s target '.$target->Get('id').' is dead, no damage dealt.');
			$text = $text.' '.$target->Get('name').' ist jedoch bereits besiegt.';
		}
		else
		{
			//DIES IST DIE FORMEL
			//PASSE SIE HIER AN; JENACHDEM; WIE DU SIE BRAUCHST!
			//Aktuell: ((atk / def) * angriff) + ki * (bonusangriff/100)
			$damage = round((($fighter->Get('atk') / $target->Get('def')) * $attack->Get('atk')) + $fighter->Get('ki') * $attack->Get('bonusatk')/100);
			$kpsteal = 0;
			if($attack->Get('stealkp') != 0)
			{
				$kpsteal = round((($fighter->Get('atk') / $target->Get('def')) * $attack->Get('stealkp')) + $fighter->Get('ki') * $attack->Get('bonusatk')/100);
			}
			$crit = rand(1,3);
			//kritische Treffer
			$critText = '';
			switch($crit)
			{
				case 1:
				$damage = $damage * 0.8;
				$critText = ' leichte';
				break;
				case 3:
				$damage = $damage * 1.2;
				$critText = ' schwere';
				break;
			}
			$damage = round($damage);
			//Ziehe Schaden ab
			$targetLP = $target->Get('lp') - $damage;
			$targetKP = $target->Get('kp') - $kpsteal;
			if($targetLP < 0)
			{
				$targetLP = 0;	
			}
			if($targetKP < 0)
			{
				$targetKP = 0;	
			}
			
			$stolenKP = $target->Get('kp') - $targetKP;
			
			$target->Set('lp', $targetLP);
			$target->Set('kp', $targetKP);
			//setzte Text
			Fight::DebugLog('--- '.$fighter->Get('id').' deals '.$damage.' damage and steal '.$stolenKP.' kp to '.$target->Get('id').'.');
			$text = $text.' '.$target->Get('name').' erleidet'.$critText.' '.$damage.' Schaden';
			
			if($stolenKP != 0)
			{
				$newKP = $fighter->Get('kp') + $stolenKP;
				$fighter->Set('kp', $newKP);
				$text = $text.' und verliert '.$stolenKP.' KP';
			}
			
			$text = $text.'.';
		}
		
		
		return $text;
	}
	
	private function AttackHeal(&$fighter, &$target, &$attack)
	{
		//Heile das Ziel
		Fight::DebugLog(' --- Calculating heal attack '.$attack->Get('id').' with target '.$target->Get('id').' for '.$fighter->Get('id').'.');
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		$text = str_replace('!target', $target->Get('name'), $text);
		
		if($target->Get('lp') == 0)
		{
			//Ziel ist tot, nichts mehr zu machen
			Fight::DebugLog('--- '.$fighter->Get('id').'s target '.$target->Get('id').' is dead, no healing done.');
			$text = $text.' '.$target->Get('name').' ist jedoch bereits besiegt.';
		}
		else
		{
			//Heilformel: (Atk * Angriff) + ki * (bonusangriff/100)
			$heal = round(($fighter->Get('atk') * $attack->Get('atk')) + $fighter->Get('ki') * $attack->Get('bonusatk')/100);
			//Füge Leben hinzu
			$targetLP = $target->Get('lp') + $heal;
			if($targetLP > $target->Get('ilp'))
			{
				$targetLP = $target->Get('ilp');	
			}
			$target->Set('lp', $targetLP);
			//setzte Text
			Fight::DebugLog('--- '.$fighter->Get('id').' heals '.$heal.' damage to '.$target->Get('id').'.');
			$text = $text.' '.$target->Get('name').' wird um '.$heal.' Schaden geheilt.';
				
		}
		
		return $text;
	}

	private function ReverseAllTransform(&$fighter)
	{
		//Setzte alle Verwandlungen von einem Spieler zurück
		$transforms = array();
		//hole die VW 
		if($fighter->Get('transform') != '')
		{
			$transforms = explode(';',$fighter->Get('transform'));
		}
		//gehe sie durch und setzte Sie zurück
		foreach($transforms as &$transform)
		{
			$transAttack = $this->attackManager->Get($transform);
			$this->ReverseAttackTransform($fighter, $transAttack);
		}
	}

	private function DoTransformCost(&$fighter)
	{
		//Berechne die Kosten der VWs
		$transforms = array();
		if($fighter->Get('transform') != '')
		{
			$transforms = explode(';',$fighter->Get('transform'));
		}
		
		foreach($transforms as &$transform)
		{
		Fight::DebugLog(' -- Calculating transform cost '.$transform.' for '.$fighter->Get('id').'.');
		
			$transAttack = $this->attackManager->Get($transform);
			//Ziehe die Kosten ab
			
			
			
			$kp = $fighter->Get('kp') - $transAttack->GetCost($fighter->Get('mkp'));
			if($kp < 0)
			{
				$kp = 0;
			}
			$fighter->Set('kp', $kp);
		}
	}

	private function DoHealCost(&$fighter)
	{
		//Berechne die Kosten der VWs
		$heals = explode(';',$fighter->Get('heals'));
		foreach($heals as &$heal)
		{
			$healData = explode('@',$heal);
			$healUser = $healData[0];
			$healID = $healData[1];
			$healRound = $healData[2];
			Fight::DebugLog(' -- Calculating heal buff cost '.$healID.' for '.$fighter->Get('id').'.');
		
			$healAtk = $this->attackManager->Get($healID);
			$healCaster = $this->GetFighter($healUser);
			if($healCaster == null)
			{
				$this->ReverseAttackHealBuff($fighter, $healAtk);
				continue;
			}
			
			
			$kp = $healCaster->Get('kp') - $healAtk->GetCost($healCaster->Get('mkp'));
			if($kp < 0)
			{
				$kp = 0;
			}
			$healCaster->Set('kp', $kp);
			
			if($kp == 0 || $healRound == 0)
			{
				$this->ReverseAttackBuff($fighter, $healAtk);
			}
			else
			{
				$atkVal = $healCaster->Get('atk');
				//Füge die Werte zusammen
				$kiDiff = round($atkVal * $healAtk->Get('ki')/100);
				$ki = $fighter->Get('ki') + $kiDiff;
				if($ki < 1)
				{
					$ki = 1;
				}
				$atkDiff = round($atkVal * $healAtk->Get('atk')/100);
				$atk = $fighter->Get('atk') + $atkDiff;
				if($atk < 1)
				{
					$atk = 1;
				}
				$defDiff = round($atkVal * $healAtk->Get('def')/100);
				$def = $fighter->Get('def') + $defDiff;
				if($def < 1)
				{
					$def = 1;
				}
				$lpDiff = round($atkVal * $healAtk->Get('lp')/100);
				$lp = $fighter->Get('lp') + $lpDiff;
				if($lp < 0)
				{
					$lp = 0;
				}
				$kpDiff = round($atkVal * $healAtk->Get('kp')/100);
				$kp = $fighter->Get('kp') + $kpDiff;
				if($kp < 0)
				{
					$kp = 0;
				}
				$fighter->Set('ki', $ki);
				$fighter->Set('atk', $atk);
				$fighter->Set('def', $def);
				$fighter->Set('lp', $lp);
				$fighter->Set('kp', $kp);
			}
		}
		
		if($fighter->Get('heals') == '')
		{
			return;
		}
		
		$heals = explode(';',$fighter->Get('heals'));
		
		$newHeals = array();
		foreach($heals as &$heal)
		{
			$healData = explode('@',$heal);
			$healData[2]--;
			array_push($newHeals, implode('@',$healData));
		}
		$fighter->Set('heals', implode(';', $newHeals));
	}

	private function DoBuffCost(&$fighter)
	{
		//Berechne die Kosten der VWs
		$buffs = explode(';',$fighter->Get('buffs'));
		foreach($buffs as &$buff)
		{
			$buffData = explode('@',$buff);
			$buffUser = $buffData[0];
			$buffID = $buffData[1];
			$buffRound = $buffData[2];
			Fight::DebugLog(' -- Calculating buff cost '.$buffID.' for '.$fighter->Get('id').'.');
		
			$buffAtk = $this->attackManager->Get($buffID);
			$buffCaster = $this->GetFighter($buffUser);
			if($buffCaster == null)
			{
				$this->ReverseAttackBuff($fighter, $buffAtk);
				continue;
			}
			
			
			$kp = $buffCaster->Get('kp') - $buffAtk->GetCost($buffCaster->Get('mkp'));
			if($kp < 0)
			{
				$kp = 0;
			}
			$buffCaster->Set('kp', $kp);
			
			if($kp == 0 || $buffRound == 0)
			{
				$this->ReverseAttackBuff($fighter, $buffAtk);
			}
		}
		
		if($fighter->Get('buffs') == '')
		{
			return;
		}
		
		$buffs = explode(';',$fighter->Get('buffs'));
		
		$newBuffs = array();
		foreach($buffs as &$buff)
		{
			$buffData = explode('@',$buff);
			$buffData[2]--;
			array_push($newBuffs, implode('@',$buffData));
		}
		$fighter->Set('buffs', implode(';', $newBuffs));
	}
	
	private function AttackTransform(&$fighter, &$attack)
	{
		//Berechne die VW eines Spielers
		Fight::DebugLog(' --- Calculating transform attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		$transforms = array();
		if($fighter->Get('transform') != '')
		{
			$transforms = explode(';',$fighter->Get('transform'));
		}
		$newTransform = array();
		
		//Wenn er die VW schon ausgewählt hat, dann setzte sie zurück
		if(in_array($attack->Get('id'), $transforms))
		{
			$this->ReverseAttackTransform($fighter, $attack);
			$text = $fighter->Get('name').' beendet '.$attack->Get('name').'.';
			return $text;
		}
		
		//Noch nicht ausgewählt, also füge sie hinzu
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		//Gucke ob aktuelle Verwandlungen die selbe transformID haben, wenn ja, tue nichts, wenn nein, entferne sie
		foreach($transforms as &$transform)
		{
			$transAttack = $this->attackManager->Get($transform);
			if($transAttack->Get('transformid') != $attack->Get('transformid'))
			{
				$text = $fighter->Get('name').' beendet '.$transAttack->Get('name').'.</br>'.$text;
				$this->ReverseAttackTransform($fighter, $transAttack);
			}
			else
			{
				array_push($newTransform, $transform);
			}
		}
		array_push($newTransform, $attack->Get('id'));
		
		
		//Füge die Werte zusammen
		$ki = $fighter->Get('ki') + ($fighter->Get('mki') * $attack->Get('atk'));
		$atk = $fighter->Get('atk') + ($fighter->Get('matk') * $attack->Get('atk'));
		$def = $fighter->Get('def') + ($fighter->Get('mdef') * $attack->Get('atk'));
		$lp = $fighter->Get('lp') + ($fighter->Get('mlp') * $attack->Get('atk'));
		$ilp = $fighter->Get('ilp') + ($fighter->Get('mlp') * $attack->Get('atk'));
		$kp = $fighter->Get('kp') + ($fighter->Get('mkp') * $attack->Get('atk'));
		$ikp = $fighter->Get('ikp') + ($fighter->Get('mkp') * $attack->Get('atk'));
		
		//Setzte die Werte
		$fighter->Set('ki', $ki);
		$fighter->Set('atk', $atk);
		$fighter->Set('def', $def);
		$fighter->Set('lp', $lp);
		$fighter->Set('ilp', $ilp);
		$fighter->Set('kp', $kp);
		$fighter->Set('ikp', $ikp);
		$fighter->Set('transform', implode(';', $newTransform));
		
		return $text;
	}
	
	private function ReverseAttackTransform(&$fighter, &$attack)
	{
		Fight::DebugLog(' --- Reverse transform attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		//Wenn eine Verwandlung ruckgängig gemacht wird, ziehe den Bonus ab
		$ki = $fighter->Get('ki') - ($fighter->Get('mki') * $attack->Get('atk'));
		$atk = $fighter->Get('atk') - ($fighter->Get('matk') * $attack->Get('atk'));
		$def = $fighter->Get('def') - ($fighter->Get('mdef') * $attack->Get('atk'));
		$lp = $fighter->Get('lp') - ($fighter->Get('mlp') * $attack->Get('atk'));
		$ilp = $fighter->Get('ilp') - ($fighter->Get('mlp') * $attack->Get('atk'));
		$kp = $fighter->Get('kp') - ($fighter->Get('mkp') * $attack->Get('atk'));
		$ikp = $fighter->Get('ikp') - ($fighter->Get('mkp') * $attack->Get('atk'));
		//Wenn der Spieler dadurch stirbt, setzte Leben auf 0
		if($lp < 0)
		{
			$lp = 0;
		}
		if($kp < 0)
		{
			$kp = 0;
		}
		
		$fighter->Set('ki', $ki);
		$fighter->Set('atk', $atk);
		$fighter->Set('def', $def);
		$fighter->Set('lp', $lp);
		$fighter->Set('ilp', $ilp);
		$fighter->Set('kp', $kp);
		$fighter->Set('ikp', $ikp);
		
		//Speichere die neuen VWs ab, falls der Spieler noch welche hat
		$transforms = array();
		if($fighter->Get('transform') != '')
		{
			$transforms = explode(';',$fighter->Get('transform'));
		}
		$newTransform = array();
		foreach($transforms as &$transform)
		{
			if($transform != $attack->Get('id'))
			{
				array_push($newTransform, $transform);
			}
		}
		$fighter->Set('transform', implode(';', $newTransform));
	}
	
	private function AttackDefend(&$fighter, &$attack)
	{
		Fight::DebugLog(' --- Calculating defend attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		//Füge der Verteidigung des Spielers etwas hinzu
		//Formel: verteidigung = mdef * angriff
		$def = $fighter->Get('def') + ($fighter->Get('mdef') * $attack->Get('atk'));
		$fighter->Set('def', $def);
		
		return $text;
	}
	
	private function ReverseAttackDefend(&$fighter, &$attack)
	{
		Fight::DebugLog(' --- Reverse defend attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		//Ziehe die Verteidigung ab
		$def = $fighter->Get('def') - ($fighter->Get('mdef') * $attack->Get('atk'));
		$fighter->Set('def', $def);
		
		return;
	}
	
	private function AttackCreate(&$fighter, &$attack)
	{
		Fight::DebugLog(' --- Calculating create attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		
		$isNPC = true;
		$atkVal = $attack->Get('atk') / 100;
		
		$name2 = $fighter->Get('name')."'s Klon";
		$team = $fighter->Get('team');
		$image = $fighter->Get('image');
		$ki = round($fighter->Get('ki') * $atkVal);
		$atk = round($fighter->Get('matk') * $atkVal);
		$def = round($fighter->Get('mdef') * $atkVal);
		$lp = round($fighter->Get('mlp') * $atkVal);
		$kp = round($fighter->Get('mkp') * $atkVal);
		
		$isCreated = 1;
		$fighter2 = $this->AddFighter($this->db, $name2, $image, 0, $team, $ki, $atk, $def, $lp, $lp, $kp, $kp, $isNPC, $isCreated);
		$fighter2->AddAttacks($fighter->GetAttacks());
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		
		return $text;
	}
	
	private function AttackFuse(&$fighter, &$target, &$attack)
	{
		Fight::DebugLog(' --- Calculating fuse attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		
		//skip if we already fused
		if($fighter->Get('fuseid') != 0)
		{
			$text = $attack->Get('text');
			$text = str_replace('!user', $fighter->Get('name'), $text);
			$text = str_replace('!target', $target->Get('name'), $text);
			return $text;
		}
		
		if($fighter->Get('id') != $target->Get('id') && $fighter->Get('attack') == $target->Get('attack') 
			&& $fighter->Get('lp') > 0 && $target->Get('lp') > 0 && $fighter->Get('team') == $target->Get('team'))
		{
			$atkVal = $attack->Get('atk') / 100;
			
			$name1 = substr($fighter->Get('name'), 0, strlen($fighter->Get('name'))/2); 
			$name2 = substr($target->Get('name'), strlen($target->Get('name'))/2, strlen($target->Get('name'))); 
			
			$name = $name1.$name2;
			$team = $fighter->Get('team');
			$image = $fighter->Get('image');
			$ki = round($fighter->Get('ki') * $atkVal) + round($target->Get('ki') * $atkVal);
			$atk = round($fighter->Get('matk') * $atkVal) + round($target->Get('matk') * $atkVal);
			$def = round($fighter->Get('mdef') * $atkVal) + round($target->Get('mdef') * $atkVal);
			$lp = round($fighter->Get('lp') * $atkVal) + round($target->Get('lp') * $atkVal);
			$kp = round($fighter->Get('kp') * $atkVal) + round($target->Get('kp') * $atkVal);
			$mlp = round($fighter->Get('mlp') * $atkVal) + round($target->Get('mlp') * $atkVal);
			$mkp = round($fighter->Get('mkp') * $atkVal) + round($target->Get('mkp') * $atkVal);
			
			$fighter2 = $this->AddFighter($this->db, $name, $image, 0, $team, $ki, $atk, $def, $lp, $mlp, $kp, $mkp, 0);
			$attacks = array_unique(array_merge($fighter->GetAttacks(), $target->GetAttacks()));
			$fighter2->AddAttacks($attacks);
			
			$text = $attack->Get('text');
			$text = str_replace('!user', $fighter->Get('name'), $text);
			$text = str_replace('!target', $target->Get('name'), $text);
			$fighter->Set('fuseid', $fighter2->Get('id'));
			$target->Set('fuseid', $fighter2->Get('id'));
		}
		else
		{
			$text = $attack->Get('misstext');
			$text = str_replace('!user', $fighter->Get('name'), $text);
			$text = str_replace('!target', $target->Get('name'), $text);
		}
		
		return $text;
	}
	
	private function AttackParalyze(&$fighter, &$target, &$attack)
	{
		Fight::DebugLog(' --- Calculating paralyze attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		
		$chance = rand(0, 100);
		if($attack->Get('hitchance') < $chance || $target->Get('paralyzed') != 0)
		{
			$text = $attack->Get('misstext');
			$text = str_replace('!user', $fighter->Get('name'), $text);
			$text = str_replace('!target', $target->Get('name'), $text);
			return $text;
		}
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		$text = str_replace('!target', $target->Get('name'), $text);
		
		$target->Set('paralyzed', $attack->Get('rounds'));
		$target->Set('attack', $attack->Get('paralyze'));
		
		return $text;
	}
	
	private function AttackParalyzed(&$fighter, &$attack)
	{
		Fight::DebugLog(' --- Calculating paralyzed attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		
		$fighter->Set('paralyzed', $fighter->Get('paralyzed') - 1);
		
		return $text;
	}
	
	private function AttackBuff(&$fighter, &$target, &$attack)
	{
		Fight::DebugLog(' --- Calculating debuff attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		$buffs = array();
		if($target->Get('buffs') != '')
		{
			$buffs = explode(';',$target->Get('buffs'));
		}
		$newBuffs = array();
		
		foreach($buffs as &$buff)
		{
			$buffData = explode('@',$buff);
			if($buffData[0] == $fighter->Get('id') && $buffData[1] == $attack->Get('id'))
			{
				$this->ReverseAttackBuff($target, $attack);
				$text = $fighter->Get('name').' beendet '.$attack->Get('name').'.';
				return $text;
			}
			else
			{
				array_push($newBuffs, $buff);
			}
		}
		
		$atkVal = $fighter->Get('atk');
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		$text = str_replace('!target', $target->Get('name'), $text);
		
		//Füge die Werte zusammen
		$kiDiff = round($atkVal * $attack->Get('ki')/100);
		$ki = $target->Get('ki') + $kiDiff;
		if($ki < 1)
		{
			$kiDiff -= $ki-1;
			$ki = 1;
		}
		$atkDiff = round($atkVal * $attack->Get('atk')/100);
		$atk = $target->Get('atk') + $atkDiff;
		if($atk < 1)
		{
			$atkDiff -= $atk-1;
			$atk = 1;
		}
		$defDiff = round($atkVal * $attack->Get('def')/100);
		$def = $target->Get('def') + $defDiff;
		if($def < 1)
		{
			$defDiff -= $def-1;
			$def = 1;
		}
		$lpDiff = round($atkVal * $attack->Get('lp')/100);
		$lp = $target->Get('lp') + $lpDiff;
		if($lp < 0)
		{
			$lpDiff -= $lp;
			$lp = 0;
		}
		$ilpDiff = round($atkVal * $attack->Get('lp')/100);
		$ilp = $target->Get('ilp') + $ilpDiff;
		if($ilp < 0)
		{
			$ilpDiff -= $ilp;
			$ilp = 0;
		}
		$kpDiff = round($atkVal * $attack->Get('kp')/100);
		$kp = $target->Get('kp') + $kpDiff;
		if($kp < 0)
		{
			$kpDiff -= $kp;
			$kp = 0;
		}
		$ikpDiff = round($atkVal * $attack->Get('kp')/100);
		$ikp = $target->Get('ikp') + $ikpDiff;
		if($ikp < 0)
		{
			$ikpDiff -= $ikp;
			$ikp = 0;
		}
		$buffData = $fighter->Get('id').'@'.$attack->Get('id').
					'@'.$attack->Get('rounds').
					'@'.$kiDiff.
					'@'.$atkDiff.
					'@'.$defDiff.
					'@'.$lpDiff.
					'@'.$ilpDiff.
					'@'.$kpDiff.
					'@'.$ikpDiff;
		
		//Noch nicht ausgewählt, also füge sie hinzu
		array_push($newBuffs, $buffData);
		
		//Setzte die Werte
		$target->Set('ki', $ki);
		$target->Set('atk', $atk);
		$target->Set('def', $def);
		$target->Set('lp', $lp);
		$target->Set('ilp', $ilp);
		$target->Set('kp', $kp);
		$target->Set('ikp', $ikp);
		$target->Set('buffs', implode(';', $newBuffs));
		
		return $text;
	}
	
	private function ReverseAttackBuff(&$target, &$attack)
	{
		Fight::DebugLog(' --- Reverse buff attack '.$attack->Get('id').' for '.$target->Get('id').'.');
		
		//Speichere die neuen VWs ab, falls der Spieler noch welche hat
		$buffs = array();
		if($target->Get('buffs') != '')
		{
			$buffs = explode(';',$target->Get('buffs'));
		}
		$newBuffs = array();
		foreach($buffs as &$buff)
		{
			$buffData = explode('@',$buff);
			
			if($buffData[0] != $target->Get('id') && $buffData[1] != $attack->Get('id'))
			{
				array_push($newBuffs, $buff);
			}
			else
			{
		//Füge die Werte zusammen
				$ki = $target->Get('ki') - $buffData[3];
				$atk = $target->Get('atk') - $buffData[4];
				$def = $target->Get('def') - $buffData[5];
				$lp = $target->Get('lp') - $buffData[6];
				$ilp = $target->Get('ilp') - $buffData[7];
				$kp = $target->Get('kp') - $buffData[8];
				$ikp = $target->Get('ikp') - $buffData[9];
				//Wenn der Spieler dadurch stirbt, setzte Leben auf 0
				if($lp < 0)
				{
					$lp = 0;
				}
				if($kp < 0)
				{
					$kp = 0;
				}
				$target->Set('ki', $ki);
				$target->Set('atk', $atk);
				$target->Set('def', $def);
				$target->Set('lp', $lp);
				$target->Set('ilp', $ilp);
				$target->Set('kp', $kp);
				$target->Set('ikp', $ikp);
			}
		}
		$target->Set('buffs', implode(';', $newBuffs));
	}
	
	private function AttackHealBuff(&$fighter, &$target, &$attack)
	{
		Fight::DebugLog(' --- Calculating heal buff attack '.$attack->Get('id').' for '.$fighter->Get('id').'.');
		$heals = array();
		if($target->Get('heals') != '')
		{
			$heals = explode(';',$target->Get('heals'));
		}
		$newHeals = array();
		
		foreach($heals as &$heal)
		{
			$healData = explode('@',$heal);
			if($healData[0] == $fighter->Get('id') && $healData[1] == $attack->Get('id'))
			{
				$this->ReverseAttackHealBuff($target, $attack);
				$text = $fighter->Get('name').' beendet '.$attack->Get('name').'.';
				return $text;
			}
			else
			{
				array_push($newHeals, $heal);
			}
		}
		
		$atkVal = $fighter->Get('atk');
		
		$text = $attack->Get('text');
		$text = str_replace('!user', $fighter->Get('name'), $text);
		$text = str_replace('!target', $target->Get('name'), $text);
		
		$healData = $fighter->Get('id').'@'.$attack->Get('id').'@'.$attack->Get('rounds');
		
		//Noch nicht ausgewählt, also füge sie hinzu
		array_push($newHeals, $healData);
		
		$target->Set('heals', implode(';', $newHeals));
		
		return $text;
	}
	
	private function ReverseAttackHealBuff(&$target, &$attack)
	{
		Fight::DebugLog(' --- Reverse heal buff attack '.$attack->Get('id').' for '.$target->Get('id').'.');
		
		//Speichere die neuen VWs ab, falls der Spieler noch welche hat
		$heals = array();
		if($target->Get('heals') != '')
		{
			$heals = explode(';',$target->Get('heals'));
		}
		$newHeals = array();
		foreach($heals as &$heal)
		{
			$healData = explode('@',$heal);
			$healUser = $healData[0];
			$healID = $healData[1];
			
			if($healData[0] != $target->Get('id') && $healData[1] != $attack->Get('id'))
			{
				array_push($newHeals, $heal);
			}
		}
		$target->Set('heals', implode(';', $newHeals));
	}
	
	private function CheckStart()
	{
		if($this->Get('state') != 1)
		{
			return;
		}
		//Überprüfe ob der Modus gültig ist, also ob bei 1vs1 zwei Spieler drin sind und bei 2vs1 drei etc ...
		$mode = $this->Get('mode');
		$teams = explode('vs', $mode);
		Fight::DebugLog('- Count Teams '.count($teams));
		for($i = 0; $i < count($teams); ++$i)
		{
			// gehe die Teams durch und guck ob es stimm, also bei 2vs3 muss team 0 2 haben und team 1 3
			$teamCount = $teams[$i];
			$team = $this->GetTeam($i);
			if(!isset($team))
			{
				Fight::DebugLog('- Team '.$i.' doesnt exist yet.');
				return;
			}
			Fight::DebugLog('- Check for start Team '.($i).' '.count($team).'/'.$teamCount);
			if(!isset($team) || count($team) != $teamCount)
			{
				// Ein Team stimmt nicht, gehe raus
				return;
			}
		}
		//Alles stimmt, starte
		Fight::DebugLog('Start Fight');
		$this->Set('state', 2);
		$this->db->Update('state="2"', fight::$table, 'id="'.$this->Get('id').'"', 1);
		
	}
	
	private function AddFighterToTeam($fighter, $team)
	{
		// Füge den Fighter zu einen Teamarray hinzu
		Fight::DebugLog('Add '.$fighter->Get('id').' to '.$team);
		if(!isset($this->teams[$team]))
		{
			$this->teams[$team] = array();
		Fight::DebugLog('Create Team '.$team);
		}
		array_push($this->teams[$team], $fighter);
	}
	
	private function LoadFight($id)
	{
		//Lade die Kampfdaten aus der Datenbank anhand einer ID
		$result = $this->db->Select('*', Fight::$table, 'id="'.$this->db->EscapeString($id).'"', 1);
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
		
		$this->LoadFighters();
	}
	
	private function LoadFighters()
	{
		//Lade alle Spieler mit dem gleichen fight wert und erstelle Spieler davon
		$result = $this->db->Select('*', Fighter::$table, 'fight="'.$this->Get('id').'"');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
				while($row = $result->fetch_assoc()) 
				{
					$fighter = new Fighter($this->db, $row);
					$this->AddFighterToTeam($fighter, $row['team']);
				}
			}
			$result->close();
		}
	}
}
?>