<?php
/*
	Bluetooth Devices module for MajorDoMo
	Author: Sergey Avdeev <thesoultaker48@gmail.com>
	URL: https://github.com/thesoultaker48/majordomo-bluetoothdevices
*/

class bluetoothdevices extends module {
	
	// Constructor
	function bluetoothdevices() {
		$this->name = 'bluetoothdevices';
		$this->title = 'Устройства Bluetooth';
		$this->module_category = '<#LANG_SECTION_DEVICES#>';
		$this->classname = 'BluetoothDevices';
		$this->checkInstalled();
	}

	// Get product version (for exe files)
	private function get_product_version($file) {
		if($data = @file_get_contents($file)) {
			$key = "V\x00S\x00_\x00V\x00E\x00R\x00S\x00I\x00O\x00N\x00_\x00I\x00N\x00F\x00O\x00\x00\x00";
			$key_pos = strpos($data, $key);
			if($key_pos === FALSE) {
				return '';
			}
			$data = substr($data, $key_pos);
			$key = "P\x00r\x00o\x00d\x00u\x00c\x00t\x00V\x00e\x00r\x00s\x00i\x00o\x00n\x00\x00\x00";
			$key_pos = strpos($data, $key);
			if($key_pos === FALSE) {
				return '';
			}
			$version = '';
			$key_pos = $key_pos + strlen($key);
			for($i=$key_pos; $data[$i]!="\x00"; $i+=2) {
				$version .= $data[$i];
			}
			$version = str_replace(',', '.', $version);
			return trim($version);
		} else {
			return NULL;
		}
	}

	// Compare programs versions
	private function compare_programs_versions($first, $second) {
		$fvc = substr_count($first, '.');
		$svc = substr_count($second, '.');
		if($fvc > $svc) {
			$dvc = $fvc;
		} else {
			$dvc = $svc;
		}
		$fvf= explode('.', $first);
		$svf = explode('.', $second);
		for($i=0;$i<=$dvc;$i++) {
			if(intval($svf[$i]) > intval($fvf[$i])) {
				return TRUE;
			} elseif(intval($svf[$i]) < intval($fvf[$i])) {
				return FALSE;
			}
		}
		return FALSE;
	}
	
	// saveParams
	function saveParams($data=0) {
		$p = array();
		if(isset($this->id)) {
			$p['id'] = $this->id;
		}
		if(isset($this->view_mode)) {
			$p['view_mode'] = $this->view_mode;
		}
		if(isset($this->edit_mode)) {
			$p['edit_mode'] = $this->edit_mode;
		}
		if(isset($this->tab)) {
			$p['tab'] = $this->tab;
		}
		return parent::saveParams($p);
	}

	// getParams
	function getParams() {
		global $id, $mode, $view_mode, $edit_mode, $tab;
		if(isset($id)) {
			$this->id = $id;
		}
		if(isset($mode)) {
			$this->mode = $mode;
		}
		if(isset($view_mode)) {
			$this->view_mode = $view_mode;
		}
		if(isset($edit_mode)) {
			$this->edit_mode = $edit_mode;
		}
		if(isset($tab)) {
			$this->tab = $tab;
		}
	}

	// run
	function run() {
		global $session;
		$out = array();
		if($this->action == 'admin') {
			$this->admin($out);
		} else {
			$this->usual($out);
		}
		if(isset($this->owner->action)) {
			$out['PARENT_ACTION'] = $this->owner->action;
		}
		if(isset($this->owner->name)) {
			$out['PARENT_NAME']=$this->owner->name;
		}
		$out['ID'] = $this->id;
		$out['VIEW_MODE'] = $this->view_mode;
		$out['EDIT_MODE'] = $this->edit_mode;
		$out['MODE'] = $this->mode;
		$out['ACTION'] = $this->action;
		$this->data = $out;
		$p = new parser(DIR_TEMPLATES.$this->name.'/'.$this->name.'.html', $this->data, $this);
		$this->result = $p->result;
	}

	// BackEnd
	function admin(&$out) {
		// Cycle status
		if(time()-intval(getGlobal('cycle_bluetoothdevicesRun')) < 120) {
			$out['CYCLERUN'] = 1;
		} else {
			$out['CYCLERUN'] = 0;
		}
		// Views
		if($this->data_source == 'bluetoothdevices' || $this->data_source == '') {
			switch($this->view_mode) {
				case 'settings_bluetoothdevices': // Settings
					$this->settings_bluetoothdevices($out);
					break;
				case 'add_bluetoothdevices': // Add
					$this->add_bluetoothdevices($out);
					break;
				case 'edit_bluetoothdevices': // Edit
					$this->edit_bluetoothdevices($out, $this->id);
					break;
				case 'delete_bluetoothdevices': // Delete
					$this->delete_bluetoothdevices($this->id);
					break;
				default: // List
					$this->list_bluetoothdevices($out);
			}
		}
		// BluetoothView
		$out['BV_UNSUPPORTED_VERSION'] = (int)FALSE;
		if(IsWindowsOS()) {
			$out['BV_UNSUPPORTED_VERSION']	= (int)TRUE;
			if($bluetoothview_version = $this->get_product_version(SERVER_ROOT.'/apps/bluetoothview/BluetoothView.exe')) {
				if(!$this->compare_programs_versions($bluetoothview_version, '1.41')) {
					$out['BV_UNSUPPORTED_VERSION'] = (int)FALSE;
				}
			}
		}
	}
	
	// Settings
	function settings_bluetoothdevices(&$out) {
		$this->getConfig();
		$scanMethod = $this->config['scanMethod'];
		$scanInterval = $this->config['scanInterval'];
		$scanTimeout = $this->config['scanTimeout'];
		$resetInterval = $this->config['resetInterval'];
		
		// Save action
		if($this->edit_mode == 'save') {
			global $scanMethod, $scanInterval, $scanTimeout, $resetInterval;

			$this->config['scanMethod'] = strtolower($scanMethod);
			$this->config['scanInterval'] = intval($scanInterval);
			$this->config['scanTimeout'] = intval($scanTimeout);
			$this->config['resetInterval'] = intval($resetInterval);
			$this->saveConfig();
			
			$this->redirect('?');
		}

		$out['SCAN_METHOD'] = $scanMethod;
		$out['SCAN_INTERVAL'] = $scanInterval;
		$out['SCAN_TIMEOUT'] = $scanTimeout;
		$out['RESET_INTERVAL'] = $resetInterval;

		$out['IS_HYBRID_AVAILABLE']		= (int)!IsWindowsOS();
		$out['IS_PING_AVAILABLE']		= (int)!IsWindowsOS();
		$out['IS_SCAN_AVAILABLE']		= (int)TRUE;
		$out['IS_CONNECT_AVAILABLE']	= (int)TRUE;
		// BluetoothView
		if(IsWindowsOS()) {
			$out['IS_CONNECT_AVAILABLE']	= (int)FALSE;
			if($bluetoothview_version = $this->get_product_version(SERVER_ROOT.'/apps/bluetoothview/BluetoothView.exe')) {
				if(!$this->compare_programs_versions($bluetoothview_version, '1.41')) {
					$out['IS_CONNECT_AVAILABLE'] = (int)TRUE;
				}
			}
		}

	}

	// Add bluetooth device
	function add_bluetoothdevices(&$out) {

		// Add action
		if($this->edit_mode == 'add') {
			global $address, $description, $user;
			$address = strtolower($address);

			// Validate address
			if(!preg_match('/^([a-f0-9]{2}:){5}[a-f0-9]{2}$/ims', $address)) {
				$out['ERROR_TEXT'] = 'Необходимо указать корректный адрес Bluetooth устройства!';
			}

			// Generate object name
			$object_name = 'btdev_'.substr(md5($address), 0, 8);
			
			// Check the existence of the object
			if(getObject($this->classname.'.'.$object_name)) {
				$out['ERROR_TEXT'] = 'Данное устройство уже присутствует в списке!';
			}

			// Add new object
			if(empty($out['ERROR_TEXT'])) {
				if($object_id = addClassObject($this->classname, $object_name)) {
					
					// Set description for object
					$object = SQLSelectOne('SELECT * FROM `objects` WHERE `ID` = '.$object_id);
					$object['DESCRIPTION'] = $description;
					SQLUpdate('objects', $object);

					// Set properties
					if($object = getObject($this->classname.'.'.$object_name)) {
						$object->setProperty('address', $address);
						$object->setProperty('online', 0);
						$object->setProperty('lastTimestamp', 0);
						$object->setProperty('user', $user);

						// Redirect
						$this->redirect('?');
					}
				}
			} else {
				$out['ADDRESS'] = $address;
				$out['DESCRIPTION'] = $description;
				$out['USER'] = $user;
			}
		}
		$out['USERS'] = SQLSelect('SELECT `ID`, `NAME` FROM `users` ORDER BY `NAME`');
	}
	
	// Edit bluetooth device
	function edit_bluetoothdevices(&$out, $id) {

		// Get object data
		$object_data = SQLSelectOne('SELECT * FROM `objects` WHERE `ID` = '.$id);
		if($object = getObject($this->classname.'.'.$object_data['TITLE'])) {
			$out['ADDRESS'] = strtolower($object->getProperty('address'));
			$out['DESCRIPTION'] = $object->description;
			$out['USER'] = $object->getProperty('user');
		}
		
		// Edit action
		if($this->edit_mode == 'edit') {
			global $address, $description, $user;
			$address = strtolower($address);

			// Check object
			if(!$object->id) {
				$out['ERROR_TEXT'] = 'Невозможно получить информацию о выбранном устройстве!';
			}
			
			// Validate address
			if(!preg_match('/^([a-f0-9]{2}:){5}[a-f0-9]{2}$/ims', $address)) {
				$out['ERROR_TEXT'] = 'Необходимо указать корректный адрес Bluetooth устройства!';
			}

			// Save
			if(empty($out['ERROR_TEXT'])) {
				// Set description for object
				$object_data['DESCRIPTION'] = $description;
				SQLUpdate('objects', $object_data);

				// Set properties
				$object->setProperty('address', $address);
				$object->setProperty('user', $user);

				// Redirect
				$this->redirect('?');
			} else {
				$out['ADDRESS'] = $address;
				$out['DESCRIPTION'] = $description;
				$out['USER'] = $user;
			}
		}
		$out['USERS'] = SQLSelect('SELECT `ID`, `NAME` FROM `users` ORDER BY `NAME`');
	}
	
	// Delete bluetooth device
	function delete_bluetoothdevices($id) {
		SQLExec("DELETE FROM `history` WHERE `OBJECT_ID` = $id");
		SQLExec("DELETE FROM `methods` WHERE `OBJECT_ID` = $id");
		SQLExec("DELETE FROM `pvalues` WHERE `OBJECT_ID` = $id");
		SQLExec("DELETE FROM `properties` WHERE `OBJECT_ID` = $id");
		SQLExec("DELETE FROM `objects` WHERE `ID` = $id");
		$this->redirect('?');
	}

	// List of bluetooth devices
	function list_bluetoothdevices(&$out) {
		if($objects = getObjectsByClass($this->classname)) {
			foreach($objects as $obj) {
				$obj = getObject($this->classname.'.'.$obj['TITLE']);

				// Get username
				$user = SQLSelectOne('SELECT `USERNAME`, `NAME` FROM `users` WHERE `ID` = '.intval($obj->getProperty('user')));
				// Get lastTimestamp
				$lastTimestamp = intval($obj->getProperty('lastTimestamp'));
				
				$out['DEVICES'][] = array(
					'ID'			=> $obj->id,
					'OBJECT'		=> $obj->object_title,
					'DESCRIPTION'	=> $obj->description,
					'ONLINE'		=> $obj->getProperty('online'),
					'ADDRESS'		=> strtolower($obj->getProperty('address')),
					'TIMESTAMP'		=> ($lastTimestamp?date('d.m.Y в H:i', $lastTimestamp):''),
					'USER'			=> ($user?"$user[USERNAME] ($user[NAME])":''),
				);
			}
		}
	}

	// FrontEnd
	function usual(&$out) {
		global $session;
	}
	
	// Cycle
	function processCycle() {
		$this->getConfig();
		if($objects = getObjectsByClass($this->classname)) {
			// All objects from $this->classname class
			foreach($objects as $obj) {
				// Current object
				$obj = getObject($this->classname.'.'.$obj['TITLE']);
				$address = strtolower($obj->getProperty('address'));
				// Search device
				$is_found = false;
				$scanMethod = strtolower($this->config['scanMethod']);
				if(IsWindowsOS()) {
					// Windows
					switch($scanMethod) {
						case 'hybrid': // Hybrid method
							die(date('Y/m/d H:i:s').' Method is not supported for Windows OS: '.$this->config['scanMethod'].PHP_EOL);
						case 'ping': // Ping
							// FIXME: BluetoothView (v1.66) does not support ping
							if($scanMethod != 'hybrid') {
								die(date('Y/m/d H:i:s').' Method is not supported for Windows OS: '.$this->config['scanMethod'].PHP_EOL);
								break;
							}
						case 'discovery': // Discovery
							// FIXME: does not find BLE devices
							// FIXME: finds an offline device if it is paired
							if(!$is_found) {
								$devices_file = SERVER_ROOT.'/apps/bluetoothview/devices.txt';
								unlink($devices_file);
								exec(SERVER_ROOT.'/apps/bluetoothview/bluetoothview.exe /stab '.$devices_file);
								if(file_exists($devices_file)) {
									if($data = LoadFile($devices_file)) {
										$data = str_replace(chr(0), '', $data);
										$data = str_replace("\r", '', $data);
										$lines = explode("\n", $data);
										$total = count($lines);
										for($i=0; $i<$total; $i++) {
											$fields = explode("\t", $lines[$i]);
											if(strtolower(trim($fields[2])) == $address) {
												$is_found = true;
												break;
											}
										}
									}
								}
							}
							if($scanMethod != 'hybrid') {
								break;
							}
						case 'connect': // Connect (version >= 1.41)
							if(!$is_found) {
								$method_is_supported = FALSE;
								if($bluetoothview_version = $this->get_product_version(SERVER_ROOT.'/apps/bluetoothview/BluetoothView.exe')) {
									if(!$this->compare_programs_versions($bluetoothview_version, '1.41')) {
										$method_is_supported = TRUE;
									}
								}
								if($method_is_supported) {
									exec(SERVER_ROOT.'/apps/bluetoothview/bluetoothview.exe /try_to_connect '.$address, $data, $code);
									if($code == 0) {
										$is_found = true;
									}
								} else {
									if($scanMethod != 'hybrid') {
										die(date('Y/m/d H:i:s').' The current version of BluetoothView is lower than required (1.41)!');
									}
								}
							}
							if($scanMethod != 'hybrid') {
								break;
							}
						default: // Unknown
							if($scanMethod != 'hybrid') {
								die(date('Y/m/d H:i:s').' Unknown method: '.$this->config['scanMethod'].PHP_EOL);
							}
					}
				} else {
					// Linux
					if((intval($this->config['resetInterval']) >= 0) && (time()-intval(getGlobal('bluetoothdevices_resetTime') > intval($this->config['resetInterval'])))) {
						// Reset bluetooth
						echo date('Y/m/d H:i:s').' Reset bluetooth'.PHP_EOL;
						exec('sudo hciconfig hci0 down; sudo hciconfig hci0 up');
						setGlobal('bluetoothdevices_resetTime', time());
					}
					switch($scanMethod) {
						case 'hybrid': // Hybrid method
						case 'ping': // Ping
							if(!$is_found) {
								$data = exec(str_replace('%ADDRESS%', $address, 'sudo l2ping %ADDRESS% -c1 -f | awk \'/loss/ {print $3}\''));
								if(intval($data) > 0) {
									$is_found = true;
								}
							}
							if($scanMethod != 'hybrid') {
								break;
							}
						case 'discovery': // Discovery
							if(!$is_found) {
								$data = array();
								exec('sudo hcitool scan | grep ":"', $data);
								exec('sudo timeout -s INT 30s hcitool lescan | grep ":"', $data);
								$total = count($data);
								for($i=0; $i<$total; $i++) {
									$data[$i] = trim($data[$i]);
									if(!$data[$i]) {
										continue;
									}
									if(strtolower(substr($data[$i], 0, 17)) == $address) {
										$is_found = true;
										break;
									}
								}
							}
							if($scanMethod != 'hybrid') {
								break;
							}
						case 'connect': // Connect
							if(!$is_found) {
								$data = exec('sudo hcitool cc '.$address.' 2>&1');
								if(empty($data)) {
									$is_found = true;
								}
							}
							if($scanMethod != 'hybrid') {
								break;
							}
						default: // Unknown
							if($scanMethod != 'hybrid') {
								die(date('Y/m/d H:i:s').' Unknown method: '.$this->config['scanMethod'].PHP_EOL);
							}
					}
				}
				// Update object
				if($is_found) {
					$obj->setProperty('lastTimestamp', time());
					if($obj->getProperty('online') == 0) {
						echo date('Y/m/d H:i:s').' Device found: '.$address.PHP_EOL;
						$obj->setProperty('online', 1);
						$obj->callMethod('Found', array('ADDRESS'=>$address));
					}
				} else {
					$lastTimestamp = intval($obj->getProperty('lastTimestamp'));
					if($obj->getProperty('online') == 1) {
						if(time()-$lastTimestamp > intval($this->config['scanTimeout'])) {
							echo date('Y/m/d H:i:s').' Device lost: '.$address.PHP_EOL;
							$obj->setProperty('online', 0);
							$obj->callMethod('Lost', array('ADDRESS'=>$address));
						}
					}
				}
			}
		}
	}

	// Install
	function install($parent_name='') {
		parent::install($parent_name);
	}

	// Uninstall
	function uninstall() {
		setGlobal('cycle_bluetoothdevicesControl', 'stop');
		SQLExec("DELETE FROM `pvalues` WHERE `PROPERTY_ID` IN (SELECT `ID` FROM `properties` WHERE `OBJECT_ID` IN (SELECT `ID` FROM `objects` WHERE `CLASS_ID` = (SELECT `ID` FROM `classes` WHERE `TITLE` = '".$this->classname."')))");
		SQLExec("DELETE FROM `history` WHERE `OBJECT_ID` IN (SELECT `ID` FROM `objects` WHERE `CLASS_ID` = (SELECT `ID` FROM `classes` WHERE `TITLE` = '".$this->classname."'))");
		SQLExec("DELETE FROM `properties` WHERE `OBJECT_ID` IN (SELECT `ID` FROM `objects` WHERE `CLASS_ID` = (SELECT `ID` FROM `classes` WHERE `TITLE` = '".$this->classname."'))");
		SQLExec("DELETE FROM `objects` WHERE `CLASS_ID` = (SELECT `ID` FROM `classes` WHERE `TITLE` = '".$this->classname."')");
		SQLExec("DELETE FROM `methods` WHERE `CLASS_ID` = (SELECT `ID` FROM `classes` WHERE `TITLE` = '".$this->classname."')");	 
		SQLExec("DELETE FROM `classes` WHERE `TITLE` = '".$this->classname."'");
		parent::uninstall();
	}

	// dbInstall
	function dbInstall($data) {
		// Class
		addClass($this->classname);

		// Method: Found
		$meth_id = addClassMethod($this->classname, 'Found', '');
		if($meth_id) {
			$property = SQLSelectOne('SELECT * FROM `methods` WHERE `ID` = '.$meth_id);
			$property['DESCRIPTION'] = 'Устройство появилось в зоне доступа';
			SQLUpdate('methods', $property);
		}
		
		// Method: Lost
		$meth_id = addClassMethod($this->classname, 'Lost', '');
		if($meth_id) {
			$property = SQLSelectOne('SELECT * FROM `methods` WHERE `ID` = '.$meth_id);
			$property['DESCRIPTION'] = 'Устройство пропало из зоны доступа';
			SQLUpdate('methods', $property);
		}

		// Property: Online
		$prop_id = addClassProperty($this->classname, 'online', 0);
		if($prop_id) {
			$property = SQLSelectOne('SELECT * FROM `properties` WHERE `ID` = '.$prop_id);
			$property['DESCRIPTION'] = 'Состояние доступности';
			SQLUpdate('properties', $property);
		}
		
		// Property: Address
		$prop_id = addClassProperty($this->classname, 'address', 0);
		if($prop_id) {
			$property = SQLSelectOne('SELECT * FROM `properties` WHERE `ID` = '.$prop_id);
			$property['DESCRIPTION'] = 'Адрес устройства';
			SQLUpdate('properties', $property);
		}
	
		// Property: lastTimestamp
		$prop_id = addClassProperty($this->classname, 'lastTimestamp', 0);
		if($prop_id) {
			$property = SQLSelectOne('SELECT * FROM `properties` WHERE `ID` = '.$prop_id);
			$property['DESCRIPTION'] = 'Время последнего онлайна';
			SQLUpdate('properties', $property);
		}
		
		// Property: User
		$prop_id = addClassProperty($this->classname, 'user', 0);
		if($prop_id) {
			$property = SQLSelectOne('SELECT * FROM `properties` WHERE `ID` = '.$prop_id);
			$property['DESCRIPTION'] = 'Пользователь устройства';
			SQLUpdate('properties', $property);
		}
		
		// Config
		$this->getConfig();
		if(IsWindowsOS()) {
			// Windows
			$this->config['scanMethod'] = 'discovery';
			if($bluetoothview_version = $this->get_product_version(SERVER_ROOT.'/apps/bluetoothview/BluetoothView.exe')) {
				if(!$this->compare_programs_versions($bluetoothview_version, '1.41')) {
					$this->config['scanMethod'] = 'connect';
				}
			}
		} else {
			// Linux
			$this->config['scanMethod'] = 'hybrid';
		}
		$this->config['scanInterval'] = 60; // 1 min
		$this->config['scanTimeout'] = 5*60; // 5 min
		$this->config['resetInterval'] = 2*60*60; // 2 hrs
		$this->saveConfig();
		
		// Global property
		setGlobal('cycle_bluetoothdevicesDisabled', 0);
		setGlobal('cycle_bluetoothdevicesAutoRestart', 1);
		setGlobal('cycle_bluetoothdevicesRun', 0);
		setGlobal('cycle_bluetoothdevicesControl', 'start');
		setGlobal('bluetoothdevices_resetTime', 0);

		parent::dbInstall($data);
	}

}

?>
