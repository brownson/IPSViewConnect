<?

class IPSViewConnect extends IPSModule
{

	// -------------------------------------------------------------------------
	public function Create() {
		parent::Create();

		$this->RegisterPropertyString("Password", "");

		//We need to call the RegisterHook function on Kernel READY
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
	}

	// -------------------------------------------------------------------------
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->RegisterHook("/hook/ipsviewconnect");
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
		if(sizeof($ids) > 0) {
			$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
			$found = false;
			foreach($hooks as $index => $hook) {
				if($hook['Hook'] == $WebHook) {
					if($hook['TargetID'] == $this->InstanceID)
						return;
					$hooks[$index]['TargetID'] = $this->InstanceID;
					$found = true;
				}
			}
			if(!$found) {
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
	protected function GetViewStore() {
		$viewStore      = $this->GetBuffer("ViewStore");
		if ($viewStore != '') {
			$viewStore      = json_decode(gzdecode($viewStore), true);
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
	protected function GetParam($params, $idx) {
		// Idx0   --> ViewID
		// Idx1   --> ViewName
		// Idx2-x --> Param1 - x 
		return $params[$idx + 2];
	}
	
	// -------------------------------------------------------------------------
	protected function GetView($viewID) {
		$content      = IPS_GetMediaContent($viewID);
		if ($content===false) {
			throw new Exception('ViewID '.$this->viewID.' could NOT be found on Server');
		}

		$data         = base64_decode($content);
		$content      = null;

		$obj          = json_decode($data, true);
		$data         = null;
	 	if ($obj===false) {
			throw new Exception('ViewContent for ID '.$this->viewID.' could NOT be decoded');
		}

		return $obj;
	}
	
	// -------------------------------------------------------------------------
	protected function GetViewIDByName($viewName) {
		$viewID      = @IPS_GetObjectIDByName ($viewName.'.ipsView', 0);
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
		$snapshot = json_decode(utf8_encode(IPS_GetSnapshot()), true);
		
		$objects = Array();
		foreach ($snapshot['objects'] as $id => $data) {
			if (array_key_exists($id, $this->viewData)) {
				$objects[$id] = $data;
			}
		}

		// { "options":{"BackupCount":25,"SaveInterval":10, ...},
		//   "objects":{"ID0":{"position":4,"readOnly":false,"ident":"","hidden":false,"type":0,"name":"IP-Symcon", ...},
		//            "ID59994":{"position":10,"readOnly":false,"ident":"","hidden":false,"type":6,"name":"Steuerung",...},
		//            "ID59985":{"position":160,"readOnly":false,"ident":"","hidden":false,"type":6,"name":"Schrankraum",...},},
		//   "profiles":{"Entertainment_Balance36466":{"associations":[],"suffix":"%","minValue":0,...},
		//   "Entertainment_Balance30648":{"associations":[],"suffix":"%","minValue":0,...}},
		//   "timestamp":431275,
		//   "timezone":"Europe/Berlin",
		//   "compatibility":{"version":"5.2","date":1570728486}}

		$result   = Array();
		$result['options']       = $snapshot['options'];
		$result['objects']       = $objects;
		$result['profiles']      = $snapshot['profiles'];
		$result['timestamp']     = $snapshot['timestamp'];
		$result['timezone']      = $snapshot['timezone'];
		$result['compatibility'] = $snapshot['compatibility'];
		
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
		$changes = json_decode(utf8_encode($changes), true);
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
		$this->viewName = $params[1];
		
		if ($this->viewID == 0) {
			$this->viewID = $this->GetViewIDByName($this->viewName);
		}
		
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
			$viewData   = Array('MediaUpdated' => $viewUpdated,
			                    'ViewID'       => $this->viewID,
			                    'ViewName'     => $this->viewName,
			                    'CountIDs'     => count($view['UsedIDs']),
			                    'CountPages'   => count($view['Pages']));
			foreach ($view['UsedIDs'] as $viewID => $writeAccess) {
				$viewData['ID'.$viewID] = $writeAccess;
			}
			$viewData['ID'.$this->viewID] = false;
			$viewData['ID0'] = false;

			$snapshot = json_decode(utf8_encode(IPS_GetSnapshot()), true);
			foreach ($snapshot['objects'] as $id => $data) {
				//$viewData['ID0'] = 0;
				//ToDo: Support NotificationControl
			}

			// Write ViewStore
			$viewStore[$this->viewID] = $viewData;
			$this->SetViewStore($viewStore);

			// Read ViewStore
			$viewStore      = $this->GetViewStore();
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
			$viewRec['ViewName']    = $viewItem['ViewName'];
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
		$this->SetBuffer('ViewStore', gzencode(json_encode('{}')));
		$this->ApplyChanges();
	}


	// -------------------------------------------------------------------------
	protected function API_ValidateReadAccess($objectID) {
		if ($objectID == 0 or $objectID == $this->viewID) {
			return;
		}
		if (array_key_exists('ID'.$objectID, $this->viewData)) {
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
				if ($control['Text1'] == $scriptText) {
					return;
				}
			}
		}
		throw new Exception('ScriptText could NOT be found in View - abort processing!');
	}

	// -------------------------------------------------------------------------
	protected function ProcessHookAPIMethod($method, $params) {
		$this->API_AssignViewData($method, $params);

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
		} else if ($method == 'AC_RenderChart') {
			$this->API_ValidateReadAccess($this->GetParam($params, 1));
			return AC_RenderChart($this->GetParam($params, 0), $this->GetParam($params, 1), $this->GetParam($params, 2), $this->GetParam($params, 3), $this->GetParam($params, 4), $this->GetParam($params, 5),$this->GetParam($params, 6) ,$this->GetParam($params, 7) ,$this->GetParam($params, 8));

		// Events
		} else if ($method == 'IPS_GetEvent') {
			$this->API_ValidateReadAccess($this->GetParam($params, 0));
			return IPS_GetEvent($this->GetParam($params, 0));
		} else if ($method == 'IPS_SetEventActive') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_SetEventActive($this->GetParam($params, 0), $this->GetParam($params, 1));

		// Execute Scripts / SetValue
		} else if ($method == 'IPS_RunScriptWaitEx') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return IPS_RunScriptWaitEx($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'RequestAction') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return RequestAction($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'SetValue') {
			$this->API_ValidateWriteAccess($this->GetParam($params, 0));
			return SetValue($this->GetParam($params, 0), $this->GetParam($params, 1));
		} else if ($method == 'IPS_RunScriptTextWait') {
			$this->API_ValidateScriptText($this->GetParam($params, 0));
			return $this->API_IPS_RunScriptTextWait($params);

		} else {
			throw new Exception('Unknown Method '.$method);
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
			if ($this->ReadPropertyString('Password') == '') {
				// No Authentification
			} else if ($_SERVER['PHP_AUTH_PW'] != $this->ReadPropertyString('Password')) {
				throw new Exception('Password Validation Error!');
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
		
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if($extension == "php") {
			include_once($path);
		} else {
			header("Content-Type: ".$this->GetMimeType($extension));
			readfile($path);
		}
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
