<?php

class IPSViewConnect extends IPSModule
{
	// -------------------------------------------------------------------------
	public function Create() {
		parent::Create();

		//We need to call the RegisterHook function on Kernel READY
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
	}

	// -------------------------------------------------------------------------
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->RegisterHook("/hook/ipsviewconnect");
		} else if ($Message == IM_CHANGESETTINGS) {
			$this->SendDebug('MessageSink', 'Received Change for InstanceID='.$SenderID, 0);
			$this->SetBuffer('WFCStore', gzencode('{}'));
		}
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
		
		//Only call this in READY state. On startup the WebHook instance might not be available yet
		if (IPS_GetKernelRunlevel() == KR_READY) {
			$this->RegisterHook("/hook/ipsviewconnect");
		}
	}
	
	// -------------------------------------------------------------------------
	private function RegisterHook($WebHook) {
		$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
		if (sizeof($ids) > 0) {
			$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
			$found = false;
			foreach($hooks as $index => $hook) {
				if ($hook['Hook'] == $WebHook) {
					if ($hook['TargetID'] == $this->InstanceID) {
						return;
					}
					$hooks[$index]['TargetID'] = $this->InstanceID;
					$found = true;
				}
			}
			if (!$found) {
				$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
			}
			IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
			IPS_ApplyChanges($ids[0]);
		}
	}

	// -------------------------------------------------------------------------
	protected function SendDebugAPI($action, $message) {
		$msgLen = strlen($message);
		$msgOut = $message;
		if ($msgLen > 512) {
			$msgOut = substr($message, 0, 512-3).'...';
		}
		$output = $msgOut;
		$sender = 'API.'.$action.' ('.$msgLen.' Bytes): ';
		$this->SendDebug($sender, $output, 0);
	}

	// -------------------------------------------------------------------------
	protected function SendDebugMemory($sender) {
		$this->SendDebug($sender,' UsedMemory='.(round(memory_get_usage() / 1024 / 1024, 2)). "M, MemoryLimit=".ini_get('memory_limit'), 0);
	}
	
	// -------------------------------------------------------------------------
	protected function GetMemoryLimit() {
		$memory_limit = ini_get('memory_limit');
		if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
			if ($matches[2] == 'M') {
				$memory_limit = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
			} else if ($matches[2] == 'K') {
				$memory_limit = $matches[1] * 1024; // nnnK -> nnn KB
			}
		}
		return $memory_limit;
	}

	// -------------------------------------------------------------------------
	protected function GetViewStore() {
		$viewStore      = $this->GetBuffer("ViewStore");
		if ($viewStore != '') {
			$viewStore      = json_decode(gzdecode($viewStore), true);
			if ($viewStore == null) {
				$viewStore      = json_decode('{}', true);
			}
		} else {
			$viewStore      = json_decode('{}', true);
		}

		return $viewStore;
	}

	// -------------------------------------------------------------------------
	protected function SetViewStore($viewStore) {
		$this->SetBuffer('ViewStore', gzencode(json_encode($viewStore)));
	}

	// -------------------------------------------------------------------------
	protected function GetWFCStore() {
		$wfcStore      = $this->GetBuffer("WFCStore");
		if ($wfcStore != '') {
			$wfcStore      = json_decode(gzdecode($wfcStore), true);
			if ($wfcStore == null) {
				$wfcStore      = json_decode('{}', true);
			}
		} else {
			$wfcStore      = json_decode('{}', true);
		}

		return $wfcStore;
	}

	// -------------------------------------------------------------------------
	protected function SetWFCStore($wfcStore) {
		$this->SetBuffer('WFCStore', gzencode(json_encode($wfcStore)));
	}

	// -------------------------------------------------------------------------
	protected function GetParam($params, $idx) {
		// Idx0   --> ViewID
		// Idx1   --> ViewName
		// Idx2-x --> Param1 - x 
		return $params[$idx + 2];
	}

	// -------------------------------------------------------------------------
	protected function GetView($viewID) {
		$viewSize = IPS_GetMedia($viewID)['MediaSize'];
		$this->SendDebug("GetView", "Load View with ID=$viewID and Size=".(round($viewSize / 1024 / 1024, 2))."M", 0);
		
		if ($viewSize * 1.5 /*Factor for Base64*/ * 2 /*Decoding*/  > $this->GetMemoryLimit()) {
			throw new Exception("ViewContent for ID=$viewID with Size=".(round($viewSize / 1024 / 1024, 2))."M exceeds MemoryLimit ".ini_get('memory_limit')." for decoding!");
		}
		
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
	protected function GetViewIDByName($viewName) {
		$viewID      = @IPS_GetObjectIDByName ($viewName.'.ipsView', 0);
		if ($viewID === false) {
			$mediaID = ctype_digit($viewName) ? intval($viewName) : null;
			$media = @IPS_GetMedia($mediaID);
			if ($mediaID > 0 && $media != null) {
				$viewID = $mediaID;
			}
		}
		if ($viewID === false) {
			$snapshot = json_decode(IPS_GetSnapshot(), true);
			if ($snapshot == null) {
				throw new Exception('Error during json_decode of Snapshot in GetViewIDByName!');
			}
			foreach ($snapshot['objects'] as $id => $data) {
				if ($snapshot['objects'][$id]['name'] == $viewName.'.ipsView') {
					$viewID = intval(str_replace('ID', '', $id));
				} else if ($snapshot['objects'][$id]['name'] == $viewName 
				           && $snapshot['objects'][$id]['type'] == 5
						   && $snapshot['objects'][$id]['data']['type'] == 0
						   && substr($snapshot['objects'][$id]['data']['file'], -8) == '.ipsView') {
					$viewID = intval(str_replace('ID', '', $id));
				}
			}
		}
		if ($viewID === false) {
			throw new Exception("View '$viewName' could NOT be found on Server");
		}
		return $viewID;
	}

	// -------------------------------------------------------------------------
	protected function API_AddDevice($params) {
		$this->API_ValidateReadAccess($this->GetParam($params, 0));

		return NC_AddDevice($this->GetParam($params, 0), /*NotificationControlID*/
		                    $this->GetParam($params, 1), /*Token IPS*/
		                    $this->GetParam($params, 2), /*API Ident (ipsview.gcm)*/
		                    $this->GetParam($params, 3), /*Token API*/
		                    $this->GetParam($params, 4), /*Device Name*/
		                    $this->GetParam($params, 5)  /*ViewID*/
		                   );
	}

	// -------------------------------------------------------------------------
	protected function API_GetSnapshot($params) {
		$snapshot = json_decode(IPS_GetSnapshot(), true);
		if ($snapshot == null) {
			throw new Exception('Error during json_decode of Snapshot!');
		}
		
		$objects = Array();
		foreach ($snapshot['objects'] as $id => $data) {
			if (   array_key_exists($id, $this->viewData)
				or ($snapshot['objects'][$id]['type'] == 1 and $snapshot['objects'][$id]['data']["moduleID"] == "{D4B231D6-8141-4B9E-9B32-82DA3AEEAB78}") /*NC*/
				or ($snapshot['objects'][$id]['type'] == 1 and $snapshot['objects'][$id]['data']["moduleID"] == "{43192F0B-135B-4CE7-A0A7-1475603F3060}") /*AC*/
				) {
				$objects[$id] = $data;
			}
		}

		// { "options":{"BackupCount":25,"SaveInterval":10, ...},
		//   "objects":{"ID0":{"position":4,"readOnly":false,"ident":"","hidden":false,"type":0,"name":"IP-Symcon", ...},
		//            "ID59994":{"position":10,"readOnly":false,"ident":"","hidden":false,"type":6,"name":"Steuerung",...},
		//            "ID59985":{"position":160,"readOnly":false,"ident":"","hidden":false,"type":6,"name":"Schrankraum",...},},
		//   "profiles":{"Entertainment_Balance36466":{"associations":[],"suffix":"%","minValue":0,...},
		//               "Entertainment_Balance30648":{"associations":[],"suffix":"%","minValue":0,...}},
		//   "server":{"architecture":"arm64","date":1657125790,"platform":"SymBox",... "version":"6.3"},
		//   "license":{"expiration":{"demo":0,"subscription":1767222000}, "licensee":"xxxx" .... },
		//   "timestamp":431275,
		//   "timezone":"Europe/Berlin",
		//   "compatibility":{"version":"5.2","date":1570728486},
		// }

		$result   = Array();
		$result['objects']       = $objects;
		$result['profiles']      = $snapshot['profiles'];
		if (array_key_exists('options', $snapshot))
			$result['options']       = $snapshot['options'];
		if (array_key_exists('license', $snapshot))
			$result['license']       = $snapshot['license'];
		if (array_key_exists('server', $snapshot))
			$result['server']        = $snapshot['server'];
		$result['timestamp']     = $snapshot['timestamp'];
		$result['timezone']      = $snapshot['timezone'];
		
		// Backward Compatibility
		$result['compatibility'] = $snapshot['compatibility'];
		$result['licensee']      = IPS_GetLicensee();
		
		return $result;
	}

	// -------------------------------------------------------------------------
	protected function IsObjectMessage($messageID) {
		$messageList = Array(OM_CHANGENAME, OM_CHANGEREADONLY, 
		                     OM_CHANGEHIDDEN, VM_UPDATE, VM_CHANGEPROFILENAME, VM_CHANGEPROFILEACTION,  
		                     EM_UPDATE, EM_CHANGEACTIVE, EM_CHANGELIMIT, EM_CHANGESCRIPT, EM_CHANGETRIGGER, EM_CHANGETRIGGERVALUE,  
		                     EM_CHANGETRIGGEREXECUTION, EM_CHANGECYCLIC, EM_CHANGECYCLICDATEFROM, EM_CHANGECYCLICDATETO, 
		                     EM_CHANGECYCLICTIMEFROM, EM_CHANGECYCLICTIMETO, EM_ADDSCHEDULEACTION, EM_REMOVESCHEDULEACTION,
		                     EM_CHANGESCHEDULEACTION, EM_ADDSCHEDULEGROUP, EM_REMOVESCHEDULEGROUP, EM_CHANGESCHEDULEGROUP,
		                     EM_ADDSCHEDULEGROUPPOINT, EM_REMOVESCHEDULEGROUPPOINT, EM_CHANGESCHEDULEGROUPPOINT,
		                     MM_UPDATE, 
		                     SE_UPDATE);
		return in_array($messageID, $messageList);
	}

	// -------------------------------------------------------------------------
	protected function IsProfileMessage($messageID) {
		$messageList = Array(PM_CREATE, PM_DELETE, PM_CHANGETEXT, PM_CHANGEVALUES, PM_CHANGEDIGITS, PM_CHANGEICON,
		                     PM_ASSOCIATIONADDED, PM_ASSOCIATIONREMOVED, PM_ASSOCIATIONCHANGED);
		return in_array($messageID, $messageList);
	}

	// -------------------------------------------------------------------------
	protected function API_GetSnapshotChanges($params) {
		$changes = @IPS_GetSnapshotChanges($this->GetParam($params, 0));
		if ($changes === false) {
			throw new Exception('Error receiving SnapshotChanges: '.print_r(error_get_last(), true));
		}
		$changes = json_decode($changes, true);
		$result  = Array();
		
		foreach ($changes as $change) {
			if ($this->IsProfileMessage($change['Message'])
				or ($this->IsObjectMessage($change['Message']) && array_key_exists('ID'.$change['SenderID'], $this->viewData) )) {
					$result[] = $change;
				}
		}
		// [ {"TimeStamp":436691,"SenderID":12707,"Message":10905,"Data":["3CA8C9EB",602075,1572819229]},
		//   {"TimeStamp":436692,"SenderID":11567,"Message":10603,"Data":[2677,true,2338,1572819229,1572818328,1572818328]},
		//   {"TimeStamp":436693,"SenderID":0,    "Message":11202,"Data":[1,1127,9,1572819229]},
		//   {"TimeStamp":436694,"SenderID":41483,"Message":10705,"Data":["3A30E1FD",1145,1572819229]},
		//   {"TimeStamp":436695,"SenderID":41483,"Message":11203,"Data":["Execute","scripts/41483.ips.php",11,1572819229]}]
		
		return $result;
	}

	private $viewID    = 0;
	private $viewName  = '';
	private $viewData  = Array();

	// -------------------------------------------------------------------------
	protected function API_AssignViewData($method, $params) {
		$this->viewID   = $params[0];
		if ($this->viewID == 0) {
			$this->viewID = $this->GetViewIDByName($params[1]);
		}
		$this->viewName = str_replace('.ipsView', '', IPS_GetName($this->viewID));

		
		$viewMedia      = IPS_GetMedia($this->viewID);
		if ($viewMedia == null) {
			throw new Exception('ID '.$this->viewID.' is NOT a valid MediaID');
		}
		$viewUpdated    = $viewMedia['MediaUpdated'];

		// Read ViewStore
		$viewStore = $this->GetViewStore();
		if (!array_key_exists($this->viewID, $viewStore) || $viewUpdated > $viewStore[$this->viewID]['MediaUpdated']) {
			$this->SendDebug("API_AssignViewData", 'Reload ViewData for ViewID='.$this->viewID, 0);

			$view       = $this->GetView($this->viewID);
			if (!array_key_exists('AuthPassword', $view)) {
				$view['AuthPassword'] = '';
			}
			if (!array_key_exists('AuthType', $view)) {
				$view['AuthType'] = 0 /*Password*/;
			}
			
			if (!array_key_exists('UsedIDs', $view)) {
				throw new Exception($this->Translate('View has an old Format, please store View with an actual Version of IPSStudio!'));
			}
			
			$viewData   = Array('MediaUpdated'     => $viewUpdated,
			                    'ViewID'           => $this->viewID,
			                    'ViewName'         => $this->viewName,
			                    'AuthPassword'     => base64_decode($view['AuthPassword']),
			                    'AuthType'         => $view['AuthType'],
			                    'RemoteAudioMedia' => $view['RemoteAudioMedia'],
			                    'CountIDs'         => count($view['UsedIDs']),
			                    'CountPages'       => count($view['Pages']));
			foreach ($view['UsedIDs'] as $viewID => $writeAccess) {
				$viewData['ID'.$viewID] = $writeAccess;
			}
			$viewData['ID'.$this->viewID] = false;
			$viewData['ID0'] = false;
			$view=null;

			// Add special IP-Symcon Instance IDs
			$snapshot = json_decode(IPS_GetSnapshot(), true);
			if ($snapshot == null) {
				throw new Exception('Error during json_decode of Snapshot in AssignViewData!');
			}
			$this->SendDebugMemory('API_AssignViewData.IPS_GetSnapshot');
			foreach ($snapshot['objects'] as $id => $data) {
				if (   ($snapshot['objects'][$id]['type'] == 1 and $snapshot['objects'][$id]['data']["moduleID"] == "{D4B231D6-8141-4B9E-9B32-82DA3AEEAB78}") /*NC*/
				    or ($snapshot['objects'][$id]['type'] == 1 and $snapshot['objects'][$id]['data']["moduleID"] == "{43192F0B-135B-4CE7-A0A7-1475603F3060}") /*AC*/
				   ) {
					$viewData[$id] = false;
				}
			}
			
			// Add missing Chart Variables to ViewData
			foreach (IPS_GetMediaListByType(4 /*Chart*/) as $chartIdx => $chartID) {
				if (array_key_exists('ID'.$chartID, $viewData)) {
					$this->SendDebug("API_AssignViewData", 'Found used MediaChart with ID='.$chartID, 0);

					try {
						$chart = json_decode(base64_decode(IPS_GetMediaContent($chartID)), true);
						foreach ($chart['datasets'] as $datasetIdx => $dataset) {
							if (!array_key_exists('ID'.$dataset['variableID'], $viewData)) {
								$this->SendDebug("API_AssignViewData", 'Found missing MediaChart Variable with ID='.$dataset['variableID'], 0);
								$viewData['ID'.$dataset['variableID']] = false;
							}
						}
					} catch (Exception $e) {
						$this->SendDebug("API_AssignViewData", 'Error while processing MediaChart with ID='.$chartID.', Error='.$e->getMessage(), 0);
					}
				}
			}

			// Write ViewStore
			$viewStore[$this->viewID] = $viewData;
			$this->SetViewStore($viewStore);

			// Read ViewStore
			$viewStore      = $this->GetViewStore();
			$this->SendDebug("API_AssignViewData", 'Successfully reloaded ViewData for ViewID='.$this->viewID, 0);
		}
		
		$this->viewData           = $viewStore[$this->viewID];
	}

	// -------------------------------------------------------------------------
	public function GetConfigurationForm() {

		// Read ViewStore
		$viewStore      = $this->GetViewStore();

		// Build ViewCache
		$viewCache      = Array();
		foreach ($viewStore as $id => $viewItem) {
			$viewRec                = Array();
			$viewRec['ViewID']      = $id;
			$viewRec['ViewName']    = str_replace('.ipsView','', @IPS_GetName($id));
			$viewRec['Password']    = $viewItem['AuthType'] == 0 ? $this->Translate("Password required") : $this->Translate("No Password required");
			$viewRec['LastRefresh'] = date('Y-m-d H:i:s', $viewItem['MediaUpdated']);
			$viewRec['Data']        = $viewItem['CountIDs'].' IDs, '.$viewItem['CountPages'].' Pages';
			$viewCache[] = $viewRec;
		}

		$data = json_decode(file_get_contents(__DIR__ . "/form.json"));
		$data->actions[0]->values = $viewCache;

		return json_encode($data);
	}

	// -------------------------------------------------------------------------
	public function ResetCache() {
		$this->SetBuffer('ViewStore', gzencode('{}'));
		$this->SetBuffer('WFCStore', gzencode('{}'));
		$this->ApplyChanges();
		$this->ReloadForm();
	}


	// -------------------------------------------------------------------------
	protected function API_ValidateReadAccess($objectID) {
		if ($objectID == 0 or $objectID == $this->viewID) {
			return;
		}
		if (array_key_exists('ID'.$objectID, $this->viewData)) {
			return;
		}
		if (    array_key_exists('RemoteAudioMedia', $this->viewData)
		    and $this->viewData['RemoteAudioMedia'] > 0
		    and GetValue($this->viewData['RemoteAudioMedia']) == $objectID) {
			return;
		}
		throw new Exception('No Read Access to ID '.$objectID.' - abort processing!');
	}

	// -------------------------------------------------------------------------
	protected function API_ValidateWriteAccess($objectID) {
		if ($objectID == 0 or $objectID == $this->viewID) {
			return;
		}
		if (!array_key_exists('ID'.$objectID, $this->viewData)) {
			throw new Exception('No Read Access to ID '.$objectID.' - abort processing!');
		}
		if (!$this->viewData['ID'.$objectID]) {
			throw new Exception('No Write Access to ID '.$objectID.' - abort processing!');
		}
	}

	// -------------------------------------------------------------------------
	protected function API_ValidateScriptText($scriptText) {
		$view       = $this->GetView($this->viewID);
		foreach ($view['Pages'] as $page) {
			foreach ($page['Controls'] as $control) {
				if (array_key_exists('Text2', $control) and $control['Text2'] == $scriptText) {
					return;
				}
			}
		}
		throw new Exception('ScriptText could NOT be found in View - abort processing!');
	}

	// -------------------------------------------------------------------------
	protected function API_ValidateFunctionResult($result) {
		$error = error_get_last();
		if($error != null && $error['message'] !== ''){
			$result = $result.$error['message'];
		}
		return $result;
	}
	
	// -------------------------------------------------------------------------
	protected function API_RequestActionEx($variableID, $value, $sender) {
		if (!IPS_VariableExists($variableID)) {
			return 'Variable '.$variableID.' NOT found!';
		}

		$targetVariable = IPS_GetVariable($variableID);

		if ($targetVariable['VariableCustomAction'] != 0) {
			$profileAction = $targetVariable['VariableCustomAction'];
		} else {
			$profileAction = $targetVariable['VariableAction'];
		}

		if ($profileAction < 10000) {
			return false;
		}

		if (IPS_InstanceExists($profileAction)) {
			return $this->API_ValidateFunctionResult(@RequestActionEx($variableID, $value, $sender));
		} elseif (IPS_ScriptExists($profileAction)) {
			return $this->API_ValidateFunctionResult(@IPS_RunScriptEx($profileAction, ['VARIABLE' => $variableID, 'VALUE' => $value, 'SENDER' => $sender, 'VIEW_ID' => $this->viewID, 'VIEW_NAME' => $this->viewName]));
		} else {
			return 'Unknown Object '.$profileAction;
		}
	}

	// -------------------------------------------------------------------------
	protected function API_RunScriptWaitEx($scriptID, $params) {
		if (!IPS_ScriptExists($scriptID)) {
			return 'Script '.$scriptID.' NOT found!';
		}

		$params['VIEW_ID'] = $this->viewID; 
		$params['VIEW_NAME'] = $this->viewName;

		return $this->API_ValidateFunctionResult(@IPS_RunScriptWaitEx($scriptID, $params));
	}

	// -------------------------------------------------------------------------
	protected function API_RunScriptEx($scriptID, $params) {
		if (!IPS_ScriptExists($scriptID)) {
			return 'Script '.$scriptID.' NOT found!';
		}

		$params['VIEW_ID'] = $this->viewID; 
		$params['VIEW_NAME'] = $this->viewName;

		return $this->API_ValidateFunctionResult(@IPS_RunScriptEx($scriptID, $params));
	}


	// -------------------------------------------------------------------------
	protected function ProcessHookAPIMethod($method, $params) {
		// Snapshot & Changes
		if ($method == 'IPS_GetSnapshot') {
			return $this->API_GetSnapshot($params);
		} else if ($method == 'IPS_GetSnapshotChanges') {
			return $this->API_GetSnapshotChanges($params);

		//Notifications
		} else if ($method == 'NC_AddDevice') {
			return $this->API_AddDevice($params);

		// Media & Charts
		} else if ($method == 'IPS_GetMediaContent') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return IPS_GetMediaContent($this->GetParam($params, 0));
		} else if ($method == 'IPS_CreateTemporaryMediaStreamToken') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return IPS_CreateTemporaryMediaStreamToken($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'IPS_GetMedia') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return IPS_GetMedia($this->GetParam($params, 0));
		} else if ($method == 'AC_RenderChart') {
			$this->API_ValidateReadAccess($this->GetParam($params, 1));
			return $this->API_ValidateFunctionResult(@AC_RenderChart($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3), $this->GetParam($params, 4), $this->GetParam($params, 5),$this->GetParam($params, 6) ,$this->GetParam($params, 7) ,$this->GetParam($params, 8)));

		// Events
		} else if ($method == 'IPS_GetEvent') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return IPS_GetEvent($this->GetParam($params, 0));
		} else if ($method == 'IPS_SetEventActive') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventActive($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'IPS_SetEventScheduleGroupPoint') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventScheduleGroupPoint($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3), $this->GetParam($params, 4), $this->GetParam($params, 5), $this->GetParam($params, 6));
		} else if ($method == 'IPS_SetEventCyclicDateFrom') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventCyclicDateFrom($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3));
		} else if ($method == 'IPS_SetEventCyclicDateTo') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventCyclicDateTo($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3));
		} else if ($method == 'IPS_SetEventCyclicTimeFrom') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventCyclicTimeFrom($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3));
		} else if ($method == 'IPS_SetEventCyclicTimeTo') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventCyclicTimeTo($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3));
		} else if ($method == 'IPS_SetEventCyclic') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventCyclic($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3), $this->GetParam($params, 4), $this->GetParam($params, 5), $this->GetParam($params, 6));
		} else if ($method == 'IPS_SetEventScheduleGroupPoint') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventScheduleGroupPoint($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3), $this->GetParam($params, 4), $this->GetParam($params, 5), $this->GetParam($params, 6));

		// Read Values
		} else if ($method == 'GetValue') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return GetValue($this->GetParam($params, 0));
		} else if ($method == 'GetValueFormatted') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return GetValueFormatted($this->GetParam($params, 0));

		// Execute Scripts / SetValue
		} else if ($method == 'IPS_RunScriptWaitEx') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return $this->API_RunScriptWaitEx($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'IPS_RunScriptEx') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return $this->API_RunScriptEx($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'RequestAction') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return $this->API_ValidateFunctionResult(@RequestAction($this->GetParam($params, 0), $this->GetParam($params, 1)));
		} else if ($method == 'RequestActionEx') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return $this->API_RequestActionEx($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2));
		} else if ($method == 'SetValue') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return $this->API_ValidateFunctionResult(@SetValue($this->GetParam($params, 0), $this->GetParam($params, 1)));
		} else if ($method == 'IPS_RunScriptTextWait') {
			$this->API_ValidateScriptText($this->GetParam($params, 0));
			return $this->API_ValidateFunctionResult(@IPS_RunScriptTextWait($this->GetParam($params, 0)));

		// Test Connection
		} else if ($method == 'IPS_GetKernelVersion') {
			return IPS_GetKernelVersion();
		} else {
			throw new Exception('Unknown Method '.$method);
		}
	}
	
	// -------------------------------------------------------------------------
	protected function IsLocalIPAddress($IPAddress) {
		if ($IPAddress == '127.0.0.1') {
			return true;
		} 
		return ( !filter_var($IPAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) );
	}

	// -------------------------------------------------------------------------
	protected function ValidateWFCObject($wfcID, $viewID, $objectID, &$processedIDs) {
		if (!IPS_ObjectExists($objectID)) {
			return false;
		}

		if (array_key_exists($objectID, $processedIDs)) {
			return false;
		}
		
		$processedIDs[$objectID] = true;
		
		if ($objectID == $viewID) {
			return true;
		}
		
		if (IPS_LinkExists($objectID)) {
			$link = IPS_GetLink($objectID);
			return $this->ValidateWFCObject($wfcID, $viewID, $link['TargetID'], $processedIDs);
		}
		
		$childIDs = IPS_GetChildrenIDs($objectID);
		foreach($childIDs as $childID) {
			if ($this->ValidateWFCObject($wfcID, $viewID, $childID, $processedIDs)) {
				return true;
			}
		}
		return false;
	}

	// -------------------------------------------------------------------------
	protected function ValidateWFCCategories($wfcID, $viewID) {
		$rootIDs = Array();
		
		// Search all RootIDs of WFC
		if ((bool)IPS_GetProperty($wfcID, 'EnableMobile')) {
			$rootIDs[] = (int)IPS_GetProperty($wfcID, 'MobileID');
		}
		//if ((bool)IPS_GetProperty($wfcID, 'EnableRetro')) {
		//	$rootIDs[] = (int)IPS_GetProperty($wfcID, 'RetroID');
		//}
		//if ((bool)IPS_GetProperty($wfcID, 'EnableRetroMobile')) {
		//	$rootIDs[] = (int)IPS_GetProperty($wfcID, 'RetroMobileID');
		//}

		$items = WFC_GetItems($wfcID);
		foreach($items as $item) {
			if($item["ClassName"] == "Category") {
				$configuration = json_decode($item["Configuration"], true);
				if(isset($configuration["baseID"])) {
					$rootIDs[] = (int)$configuration["baseID"];
				} else {
					$rootIDs[] = 0;
				}
			}
		}

		$processedIDs = Array();
		foreach($rootIDs as $rootID) {
			if ($rootID == 0) {
				return true;
			}
			if ($this->ValidateWFCObject($wfcID, $viewID, $rootID, $processedIDs)) {
				return true;
			}
		}
		return false;
	}
	
	// -------------------------------------------------------------------------
	protected function ValidateWFCPassword() {
		$pwd     = $_SERVER['PHP_AUTH_PW'];
		$user    = $_SERVER['PHP_AUTH_USER'];
		$addr    = $_SERVER['REMOTE_ADDR'];
		$viewID  = $this->viewID;
		
		if ($user != '' && strpos($user, 'wfcID') === 0) {
			$wfcID = intval(str_replace('wfcID', '', $user));
			
			$wfcStore = $this->GetWFCStore();
			$keyPwd = $wfcID.'.'.$viewID.'.'.'Pwd';
			$keyLan = $wfcID.'.'.$viewID.'.'.'Lan';

			if (!array_key_exists($keyPwd, $wfcStore)) {
				$viewValidated = false;
				$items = WFC_GetItems($wfcID);
				foreach($items as $item) {
					if($item["ClassName"] == "IPSView") {
						$configuration = json_decode($item["Configuration"], true);
						if(isset($configuration["viewID"]) && $configuration["viewID"] == $viewID) {
							$viewValidated = true;
						}
					}
				}
				if (!$viewValidated) {
					$viewValidated = $this->ValidateWFCCategories($wfcID, $viewID) ;
				}
				if (!$viewValidated) {
					throw new Exception($this->Translate("View could NOT be validated for WFC (Store WebFront to validate request on View)!"));
				}

				$this->RegisterMessage($wfcID, IM_CHANGESETTINGS);

				$wfcStore[$keyPwd] = IPS_GetProperty($wfcID, "Password");
				$wfcStore[$keyLan] = IPS_GetProperty($wfcID, "IgnorePasswordOnLAN");
				$this->SetWFCStore($wfcStore);
			}

			// Local Address and IgnorePasswordOnLAN
			if ($wfcStore[$keyLan] == 1 && $this->IsLocalIPAddress($addr)) {
			// Valid Password
			} else if ($wfcStore[$keyPwd] == base64_decode($pwd)) {
			// Validation failed
			} else {
				throw new Exception($this->Translate('Password Validation Error!'));
			}
			return true;
		} else {
			return false;
		}
	}

	// -------------------------------------------------------------------------
	protected function ProcessHookAPIRequest() {
		$requestRaw = file_get_contents("php://input");
		$this->SendDebugAPI("Rcv", $requestRaw);

		$request       = json_decode($requestRaw, true);
		$method        = $request['method'];
		$params        = $request['params'];
		$id            = $request['id'];
		$jsonRpc       = $request['jsonrpc'];
		try {
			$this->API_AssignViewData($method, $params);

			if ($this->ValidateWFCPassword()) {
				// Authentification by WFC
			} else if ($this->viewData['AuthPassword'] == '' && $this->viewData['AuthType'] == 10 /*Public*/) {
				// No Authentification
			} else if ($_SERVER['PHP_AUTH_PW'] == $this->viewData['AuthPassword'] && $this->viewData['AuthPassword'] != '') {
				// Authentification by View Password
			} else {
				throw new Exception($this->Translate('Password Validation Error!'));
			}
			
			$methodResult  = $this->ProcessHookAPIMethod($method, $params);
			$response      = Array("jsonrpc" => $jsonRpc, "id" => $id, "result" => $methodResult);
		} catch (Exception $e) {
			$error         = Array("message" => $e->getMessage(), "code" =>  $e->getCode());
			$response      = Array("jsonrpc" => $jsonRpc, "id" => $id, "error" => $error);
		}
		$result = json_encode($response);
		$this->SendDebugAPI("Snd", $result);

		return $result;
	}

	// -------------------------------------------------------------------------
	protected function ProcessHookData() {
		if(!isset($_SERVER['PHP_AUTH_USER']))
			$_SERVER['PHP_AUTH_USER'] = "";
		if(!isset($_SERVER['PHP_AUTH_PW']))
			$_SERVER['PHP_AUTH_PW'] = "";

		ini_set('ips.output_buffer', 20*1024*1024);

		// Process API Request
		if ($_SERVER['SCRIPT_NAME'] == '/hook/ipsviewconnect/api/') {
			echo $this->ProcessHookAPIRequest();
			return;
		}

		// Handle File Requests
		$root = realpath(__DIR__ . "/www");
		//reduce any relative paths. this also checks for file existance
		$path = realpath($root . "/" . substr($_SERVER['SCRIPT_NAME'], strlen("/hook/ipsviewconnect/")));
		if($path === false) {
			http_response_code(404);
			die("File not found!");
		}
		if(substr($path, 0, strlen($root)) != $root) {
			http_response_code(403);
			die("Security issue. Cannot leave root folder!");
		}
		//check dir existance
		if(substr($_SERVER['SCRIPT_NAME'], -1) != "/") {
			if(is_dir($path)) {
				http_response_code(301);
				header("Location: " . $_SERVER['SCRIPT_NAME'] . "/\r\n\r\n");
				return;
			}
		}
		//append index
		if(substr($_SERVER['SCRIPT_NAME'], -1) == "/") {
			if(file_exists($path . "/index.html")) {
				$path .= "/index.html";
			} else if(file_exists($path . "/index.php")) {
				$path .= "/index.php";
			}
		}
		
		// Handle special WebFront startup file
		if (isset($_GET["wfcID"]) 
		    && substr($path, -10) === 'index.html'
		    && file_exists(substr($path, 0, -10).'webfront.html')
		    ) {
			$path = substr($path, 0, -10).'webfront.html';
		}

		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if($extension == "php") {
			include_once($path);
		} else {
			$lastModified = filemtime($path);
			$etagFile     = md5_file($path);
			$etagHeader   = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
			$mimeType     = $this->GetMimeType($extension);
			
			header("Content-Type: ".$mimeType);
			header('Cache-Control: max-age=3600');
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
			header("Etag: $etagFile");
			
			//check if page has changed. If not, send 304 and exit
			if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])==$lastModified || $etagHeader == $etagFile) {
				header("HTTP/1.1 304 Not Modified");
			//check if compression is allowed
			} else if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && $this->IsCompressionAllowed($mimeType)) {
				$compressed = gzencode(file_get_contents($path));
				header("Content-Encoding: gzip");
				header("Content-Length: " . strlen($compressed));
				echo $compressed;
			} else {
				header("Content-Length: " . filesize($path));
				readfile($path);
			}
		}
	}

	// -------------------------------------------------------------------------
	private function IsCompressionAllowed($mimeType) {
		return in_array($mimeType, [
			"text/plain", 
			"text/html", 
			"text/xml", 
			"text/css", 
			"text/javascript", 
			"application/xml", 
			"application/xhtml+xml", 
			"application/rss+xml", 
			"application/json", 
			"application/json; charset=utf-8", 
			"application/javascript", 
			"application/x-javascript", 
			"image/svg+xml"
		]);
	}
		
	// -------------------------------------------------------------------------
	private function GetMimeType($extension) {
		$lines = file(IPS_GetKernelDirEx()."mime.types");
		foreach($lines as $line) {
			$type = explode("\t", $line, 2);
			if(sizeof($type) == 2) {
				$types = explode(" ", trim($type[1]));
				foreach($types as $ext) {
					if($ext == $extension) {
						return $type[0];
					}
				}
			}
		}
		return "text/plain";
	}

}

?>