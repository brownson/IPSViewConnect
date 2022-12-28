<?php

class IPSViewUsers extends IPSModule
{
	
	// -------------------------------------------------------------------------
	public function Create() {
		parent::Create();

		$this->RegisterPropertyString  ('PropertyGroups',        '[{"GroupID": 1, "GroupName": "Administrator", "GroupIdent": "Adm",   "GroupDescription": "Gruppe mit Administrator Berechtigungen"},
		                                                           {"GroupID": 2, "GroupName": "Standard",      "GroupIdent": "Std",   "GroupDescription": "Gruppe mit Standard Berechtigungen"},
		                                                           {"GroupID": 3, "GroupName": "Nur Lesen",     "GroupIdent": "Lesen", "GroupDescription": "Gruppe mit Lesezugriff"}]');
		$this->RegisterPropertyString  ('PropertyUsers',         '[]');

		$this->RegisterAttributeInteger  ('GroupID',             3);
	}

	// -------------------------------------------------------------------------
	public function RequestAction($Ident, $Value) {
		switch($Ident) {
			default:
				throw new Exception("Invalid ident");
		}
	}
	
	// -------------------------------------------------------------------------
	public function ApplyChanges() {
		parent::ApplyChanges();

		$this->SendDebug('ApplyChanges', 'ApplyChanges ...', 0);
		
		// Add missing GroupIDs
		$foundMissingGroupID = false;
		$groups = json_decode($this->ReadPropertyString('PropertyGroups'));
		foreach ($groups as &$group) {
			if ($group->GroupID == 0) {
				$group->GroupID = $this->GetNextGroupID();
				$foundMissingGroupID = true;
			}
		}
		if ($foundMissingGroupID) {
			IPS_SetProperty($this->InstanceID, 'PropertyGroups', json_encode($groups));
			IPS_ApplyChanges($this->InstanceID);		
		}

		$this->ValidateSettings();
	}

	// -------------------------------------------------------------------------
	private function ValidateSettings() {		
		$hasDuplicateUserNames = false;
		$users = json_decode($this->ReadPropertyString('PropertyUsers'), true);
		$userMap = Array();
		foreach ($users as $user) {
			if (array_key_exists($user['UserName'], $userMap)) {
				$hasDuplicateUserNames = true;
			}
			$userMap[$user['UserName']] = true;
		}

		if ($hasDuplicateUserNames) {
			$this->SetStatus(200);
		} else {
			$this->SetStatus(102);
		}
	}
	
	// -------------------------------------------------------------------------
	public function GetConfigurationForm() {
		$formContent = file_get_contents(__DIR__ . "/form.json");
		$data = json_decode($formContent, true);

		// Add CheckBoxes for Groups
		$groups = json_decode($this->ReadPropertyString('PropertyGroups'));
		foreach ($groups as $group) {
			$column = Array();
			$column['caption']      = $group->GroupIdent;
			$column['name']         = 'Group'.$group->GroupID;
			$column['width']        = "100px";
			$column['add']          = false;
			$column['edit']         = Array();
			$column['edit']['type'] = "CheckBox";
			$column['save']         = true;
			
			$data['elements'][1]['columns'][] = $column;
		}
		
		// Add Views for Select
		$options  = Array();
		$mediaIDs = IPS_GetMediaList();
		foreach ($mediaIDs as $mediaID) {
			$media = IPS_GetMedia($mediaID);
			if ($media['MediaType'] == 0 /*View*/
			    &&  substr($media['MediaFile'], -8) == '.ipsView') {
				$option = Array();
				$option['caption'] = IPS_GetName($mediaID);
				$option['value']   = $mediaID;
				$options[]         = $option;
			}
		}
		usort($options, function($a, $b) { return $a['caption'] <=> $b['caption']; });
		$data['elements']['1']['columns'][2]['edit']['options'] = $options;

		return json_encode($data);
	}
	
	// Group Administration
	// ==========================================================================================
	
	// -------------------------------------------------------------------------
	private function GetNextGroupID() {
		$result = 1;
		$groups = json_decode($this->ReadPropertyString('PropertyGroups'));
		foreach ($groups as $group) {
			$result = max($group->GroupID, $result);
		}
		$result = max($this->ReadAttributeInteger('GroupID'), $result);
		$result = $result + 1;
		$this->WriteAttributeInteger('GroupID', $result);
		
		return $result;
	}
	
	// -------------------------------------------------------------------------
	private function ExistsGroup($groupID) {
		$groups = json_decode($this->ReadPropertyString('PropertyGroups'));
		foreach ($groups as $group) {
			if ($group->GroupID == $groupID) {
				return true;
			}
		}
		return false;
	}

	// -------------------------------------------------------------------------
	public function AddGroup($groupID, $groupName, $groupIdent, $groupDescription) {
		if ($this->ExistsGroup($groupID)) {
			throw new Exception("Group $groupID already exists!");
		}
		$groups = json_decode($this->ReadPropertyString('PropertyGroups'));
		$group = Array();
		$group['GroupID']          = $groupID;
		$group['GroupName']        = $groupName;
		$group['GroupIdent']       = $groupIdent;
		$group['GroupDescription'] = $groupDescription;
		$groups[] = $group;
		
		IPS_SetProperty($this->InstanceID, 'PropertyGroups', json_encode($groups));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// -------------------------------------------------------------------------
	public function ChangeGroup($groupID, $groupName, $groupIdent, $groupDescription) {
		if (!$this->ExistsGroup($groupID)) {
			throw new Exception("Group $groupID NOT found!");
		}
		$groups = json_decode($this->ReadPropertyString('PropertyGroups'), true);
		foreach ($groups as &$group) {
			if ($group['GroupID'] == $groupID) {
				$group['GroupName']        = $groupName;
				$group['GroupIdent']       = $groupIdent;
				$group['GroupDescription'] = $groupDescription;
			}
		}
		
		IPS_SetProperty($this->InstanceID, 'PropertyGroups', json_encode($groups));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// -------------------------------------------------------------------------
	public function DeleteGroup($groupID) {
		if (!$this->ExistsGroup($groupID)) {
			throw new Exception("Group $groupID NOT found!");
		}
		$groupsOld = json_decode($this->ReadPropertyString('PropertyGroups'));
		$groupsNew = Array();
		foreach ($groupsOld as $group) {
			if ($group->GroupID != $groupID) {
				$groupsNew[] = $group;
			}
		}
			
		IPS_SetProperty($this->InstanceID, 'PropertyGroups', json_encode($groupsNew));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// User Administration
	// ==========================================================================================

	// -------------------------------------------------------------------------
	private function ExistsUser($userName) {
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		foreach ($users as $user) {
			if ($user->UserName == $userName) {
				return true;
			}
		}
		return false;
	}

	// -------------------------------------------------------------------------
	public function AddUser($userName, $userPwd, $viewID, $groupID) {
		if ($this->ExistsUser($userName)) {
			throw new Exception("User $userName already exists!");
		}
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		$user = Array();
		$user['UserName']      = $userName;
		$user['UserPwd']       = $userPwd;
		$user['ViewID']        = $viewID;
		$user['Group'.$groupID] = true;
		$users[] = $user;
		
		IPS_SetProperty($this->InstanceID, 'PropertyUsers', json_encode($users));
		IPS_ApplyChanges($this->InstanceID);		
	}


	// -------------------------------------------------------------------------
	public function DeleteUser($userName) {
		if (!$this->ExistsUser($userName)) {
			throw new Exception("User $userName NOT found!");
		}
		$usersOld = json_decode($this->ReadPropertyString('PropertyUsers'));
		$usersNew = Array();
		foreach ($usersOld as $user) {
			if ($user->UserName != $userName) {
				$usersNew[] = $user;
			}
		}
			
		IPS_SetProperty($this->InstanceID, 'PropertyUsers', json_encode($usersNew));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// -------------------------------------------------------------------------
	public function SetUserView($userName, $viewID) {
		if (!$this->ExistsUser($userName)) {
			throw new Exception("User $userName NOT found!");
		}
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		foreach ($users as &$user) {
			if ($user->UserName == $userName) {
				$user->ViewID = $viewID;
			}
		}
		
		IPS_SetProperty($this->InstanceID, 'PropertyUsers', json_encode($users));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// -------------------------------------------------------------------------
	public function SetUserPwd($userName, $userPwd) {
		if (!$this->ExistsUser($userName)) {
			throw new Exception("User $userName NOT found!");
		}
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		foreach ($users as &$user) {
			if ($user->UserName == $userName) {
				$user->UserPwd = $userPwd;
			}
		}
		
		IPS_SetProperty($this->InstanceID, 'PropertyUsers', json_encode($users));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// -------------------------------------------------------------------------
	public function SetUserGroup($userName, $groupID, $value) {
		if (!$this->ExistsUser($userName)) {
			throw new Exception("User $userName NOT found!");
		}
		if (!$this->ExistsGroup($groupID)) {
			throw new Exception("Group $groupID NOT found!");
		}
		$users = json_decode($this->ReadPropertyString('PropertyUsers'), true);
		foreach ($users as &$user) {
			if ($user['UserName'] == $userName) {
				$user['Group'.$groupID] = $value;
			}
		}
		
		IPS_SetProperty($this->InstanceID, 'PropertyUsers', json_encode($users));
		IPS_ApplyChanges($this->InstanceID);		
	}

	// -------------------------------------------------------------------------
	public function ChangeUserPwd($userName, $oldUserPwd, $newUserPwd) {
		if (!$this->ExistsUser($userName)) {
			throw new Exception("User $userName NOT found!");
		}
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		foreach ($users as &$user) {
			if ($user->UserName == $userName) {
				if ($user->UserPwd != $oldUserPwd) {
					throw new Exception("Password for $userName does NTO match!");
				}
				$this->SetUserPwd($userName, $newUserPwd);
			}
		}	
		return true;
	}

	
	// Build User Views
	// ==========================================================================================

	// -------------------------------------------------------------------------
	public function GetUserExists($username) {
		$this->ExistsUser($username);
	}

	// -------------------------------------------------------------------------
	public function GetUserViewID($userName) {
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		foreach ($users as &$user) {
			if ($user->UserName == $userName) {
				return $user->ViewID;
			}
		}	
		throw new Exception("Invalid Username ".$userName);
	}
	
	// -------------------------------------------------------------------------
	public function GetUserPwd($userName) {
		$users = json_decode($this->ReadPropertyString('PropertyUsers'));
		foreach ($users as &$user) {
			if ($user->UserName == $userName) {
				return $user->UserPwd;
			}
		}	
		throw new Exception("Invalid Username ".$userName);
	}

	// -------------------------------------------------------------------------
	protected function SendDebugMemory($sender) {
		$this->SendDebug($sender,' UsedMemory='.(round(memory_get_usage() / 1024 / 1024, 2)). "M, MemoryLimit=".ini_get('memory_limit'), 0);
	}
	
	// -------------------------------------------------------------------------
	protected function GetView($viewID) {		
		$this->SendDebugMemory('GetView');
		$content      = @IPS_GetMediaContent($viewID);
		$this->SendDebugMemory('GetView.IPS_GetMediaContent');
		if ($content===false) {
			throw new Exception('ViewID '.$this->viewID.' could NOT be found on Server');
		}

		$data         = base64_decode($content);
		$this->SendDebugMemory('GetView.base64_decode');
		$content      = null;

		$obj          = json_decode($data, true);
		$this->SendDebugMemory('GetView.json_decode');
		$data         = null;
	 	if ($obj==null) {
			throw new Exception('ViewContent for ID '.$this->viewID.' could NOT be decoded');
		}

		return $obj;
	}

	// -------------------------------------------------------------------------
	protected function GetUserGroupIDs($userName) {
		$groupIDs  = Array();
		$groups    = json_decode($this->ReadPropertyString('PropertyGroups'), true);
		$users     = json_decode($this->ReadPropertyString('PropertyUsers'), true);
		foreach ($users as &$user) {
			if ($user['UserName'] == $userName) {
				foreach ($groups as $group) {
					$groupKey = 'Group'.$group['GroupID'];
					if (array_key_exists($groupKey, $user) && $user[$groupKey]) {
						$groupIDs[] = $group['GroupID'];
					}
				}
			}
		}
		return $groupIDs;
	}



	// -------------------------------------------------------------------------
	public function GetUserView($userName) {
		$groupIDs  = $this->GetUserGroupIDs($userName);
		$viewID    = $this->GetUserViewID($userName);
		$view      = $this->GetView($viewID);
		$viewPwd   = $this->GetUserPwd($userName);
		
		if (!array_key_exists('UsedIDs', $view)) {
			throw new Exception($this->Translate('View has an old Format, please store View with an actual Version of IPSView!'));
		}

		$view['AuthPassword'] = base64_encode($viewPwd);
		$view['AuthType']     = 0 /*Password*/;

		// No Group Assignments available for View --> keep all usedIDs
		if (!array_key_exists('GroupIDs', $view)) {
			return $view;
		}
		
		$usedIDs   = Array();
		foreach ($view['UsedIDs'] as $usedID->$isWritable) {
			$isUserWritable = false;
			foreach ($groupIDs as $groupID) {
				if (!$isWritable) {
				} else if (array_key_exists($groupID, $view['GroupIDs']) 
					       && array_key_exists($usedID, $view['GroupIDs'][$groupID])) {
					$isUserWritable = $isUserWritable || $view['GroupIDs'][$groupID][$usedID];
				} else if (array_key_exists($groupID, $view['GroupIDs'] ) 
					       && array_key_exists(0, $view['GroupIDs'][$groupID])) {   
					$isUserWritable = $isUserWritable || $view['GroupIDs'][$groupID][0];
				} else {}				   
			}
			$usedIDs[usedID] = $isUserWritable;
		}
		
		$view['UsedIDs']      = $usedIDs;
		
		return $view;
	}


}

?>