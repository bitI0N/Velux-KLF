<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../libs/KLF200Class.php';  // diverse Klassen
    eval('declare(strict_types=1);namespace KLF200Splitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
    eval('declare(strict_types=1);namespace KLF200Splitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
    eval('declare(strict_types=1);namespace KLF200Splitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
    eval('declare(strict_types=1);namespace KLF200Splitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
    eval('declare(strict_types=1);namespace KLF200Splitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
    $autoloader = new \AutoloaderTLS('PTLS');
    $autoloader->register();

    class AutoloaderTLS
    {
        private $namespace;

        public function __construct($namespace = null)
        {

            $this->namespace = $namespace;
        }

        public function register()
        {
            spl_autoload_register(array($this, 'loadClass'));
        }

        public function loadClass($className)
        {
            $libpath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR;
            $includes[] = $libpath . 'AESGCM' . DIRECTORY_SEPARATOR . 'src';
            $includes[] = $libpath . 'assert' . DIRECTORY_SEPARATOR . 'lib';
            $includes[] = $libpath . 'phpecc' . DIRECTORY_SEPARATOR . 'src';
            $includes[] = $libpath . 'PHP-TLS' . DIRECTORY_SEPARATOR . 'src';
//            $includes[] = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'libs';
            set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $includes));
            $className = str_replace(['Mdanter\\Ecc\\', 'AESGCM\\', 'PTLS\\'], ['', '', ''], $className);
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
//            if (file_exists($file)) {
            require_once $file;
            //          }
            restore_include_path();
        }

    }

}

namespace KLF200Splitter {

    /**
     * Der Status der Verbindung.
     */
    class TLSState
    {
        const unknow = 0;
        const Connected = 3;
        const init = 4;

        /**
         *  Liefert den Klartext zu einem Status.
         * 
         * @param int $Code
         * @return string
         */
        public static function ToString(int $Code)
        {
            switch ($Code) {
                case self::unknow:
                    return 'unknow';
                case self::Connected:
                    return 'Connected';
                case self::init:
                    return 'init';
            }
        }

    }

    //require_once __DIR__ . '/../libs/loadTLS.php';
}

namespace {


    /*
     * @addtogroup klf200
     * @{
     *
     * @package       KLF200
     * @file          module.php
     * @author        Michael Tröger <micha@nall-chan.net>
     * @copyright     2019 Michael Tröger
     * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
     * @version       1.0
     */

    /**
     * KLF200Splitter Klasse implementiert die KLF 200 API
     * Erweitert IPSModule.
     * 
     * @package       KLF200
     * @author        Michael Tröger <micha@nall-chan.net>
     * @copyright     2019 Michael Tröger
     * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
     * @version       1.0
     * @example <b>Ohne</b>
     * @property string $Host
     * @property \KLF200Splitter\TLSState $State
     * @property string $WaitForTLSReceive
     * @property TLS $Multi_TLS
     * @property string $TLSReceiveData
     * @property string $TLSReceiveBuffer
     * @property string $ReceiveBuffer
     * @property APIData $ReceiveAPIData
     * @property array $ReplyAPIData
     * @property array $Nodes
     * @property int $WaitForNodes
     * @property int $SessionId
     */
    class KLF200Splitter extends IPSModule
    {

        use \KLF200Splitter\Semaphore,
            \KLF200Splitter\BufferHelper,
            \KLF200Splitter\DebugHelper,
            \KLF200Splitter\VariableHelper,
            \KLF200Splitter\InstanceStatus {
            \KLF200Splitter\InstanceStatus::MessageSink as IOMessageSink;
            \KLF200Splitter\InstanceStatus::RegisterParent as IORegisterParent;
            \KLF200Splitter\InstanceStatus::RequestAction as IORequestAction;
            \KLF200Splitter\DebugHelper::SendDebug as SendDebug2;
        }
        /**
         * Interne Funktion des SDK.
         *
         * @access public
         */
        public function Create()
        {
            parent::Create();
            $this->RequireParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');
            $this->RegisterPropertyString('Password', '');
            $this->RegisterTimer('KeepAlive', 0, 'KLF200_ReadGatewayState($_IPS[\'TARGET\']);');
            $this->Host = '';
            $this->State = \KLF200Splitter\TLSState::unknow;
            $this->TLSReceiveBuffer = '';
            $this->WaitForTLSReceive = false;
            $this->ReceiveBuffer = '';
            $this->ReplyAPIData = [];
            $this->Nodes = [];
            $this->SessionId = 1;
        }

        /**
         * Interne Funktion des SDK.
         *
         * @access public
         */
        public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
        {
            $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);
            switch ($Message) {
                case IPS_KERNELSTARTED:
                    $this->KernelReady();
                    break;
                case IPS_KERNELSHUTDOWN:
                    //$this->SendDisconnect();
                    // Todo
                    break;
            }
        }

        /**
         * Wird ausgeführt wenn der Kernel hochgefahren wurde.
         */
        protected function KernelReady()
        {
            $this->RegisterParent();
        }

        protected function RegisterParent()
        {
            $IOId = $this->IORegisterParent();
            if ($IOId > 0) {
                $this->Host = IPS_GetProperty($this->ParentID, 'Host');
                $this->SetSummary(IPS_GetProperty($IOId, 'Host'));
            } else {
                $this->Host = '';
                $this->SetSummary(('none'));
            }
            return $IOId;
        }

        /**
         * Wird über den Trait InstanceStatus ausgeführt wenn sich der Status des Parent ändert.
         * Oder wenn sich die Zuordnung zum Parent ändert.
         * @access protected
         * @param int $State Der neue Status des Parent.
         */
        protected function IOChangeState($State)
        {
            if ($State == IS_ACTIVE) {
                if ($this->Connect()) {
                    $this->SetTimerInterval('KeepAlive', 600000);
                    $this->LogMessage('Successfully connected to KLF200.', KL_NOTIFY);
                    $this->SessionId = 1;
                    $this->ReadProtocolVersion();
                    $this->SetGatewayTime();
                    $this->ReadGatewayState();
                    $this->ReadGatewayVersion();
                    $this->SetHouseStatusMonitor();
                } else {
                    $this->SetTimerInterval('KeepAlive', 0);
                }
            } else {
                $this->SetTimerInterval('KeepAlive', 0);
                $this->SetStatus(IS_INACTIVE);
                $this->State = \KLF200Splitter\TLSState::unknow;
            }
        }

        public function RequestAction($Ident, $Value)
        {
            if ($this->IORequestAction($Ident, $Value)) {
                return true;
            }
            return false;
        }

        /**
         * Interne Funktion des SDK.
         * 
         * @access public
         */
        public function GetConfigurationForParent()
        {
            $Config['Port'] = 51200;
            return json_encode($Config);
        }

        public function GetConfigurationForm()
        {
            $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
            if (strlen($this->ReadPropertyString('Password')) > 31) {
                $Form['actions'][] = [
                    "type"  => "PopupAlert",
                    "popup" => [
                        "items" => [
                            [
                                "type"    => "Label",
                                "caption" => "The maximum size for the password are 31 letters."
                            ]
                        ]
                    ]
                ];
            }
            return json_encode($Form);
        }

        /**
         * Interne Funktion des SDK.
         * 
         * @access public
         */
        public function ApplyChanges()
        {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterMessage(0, IPS_KERNELSHUTDOWN);

            $this->RegisterMessage($this->InstanceID, FM_CONNECT);
            $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);

            parent::ApplyChanges();
            $this->RegisterVariableString('SoftwareVersion', $this->Translate('Software Version'), '', 0);
            $this->RegisterVariableInteger('HardwareVersion', $this->Translate('Hardware Version'), '', 0);
            $this->RegisterVariableInteger('CommandVersion', $this->Translate('Command Version'), '', 0);
            $this->RegisterVariableString('ProtocolVersion', $this->Translate('Protocol Version'), '', 0);
            if (IPS_GetKernelRunlevel() != KR_READY) {
                return;
            }

            $OldState = $this->State;

            if ($OldState == \KLF200Splitter\TLSState::init) {
                return;
            }

            $this->TLSReceiveBuffer = '';
            $this->State = \KLF200Splitter\TLSState::unknow;
            $this->WaitForTLSReceive = false;
            $this->ReceiveBuffer = '';
            $this->ReplyAPIData = [];
            $this->Nodes = [];
            $this->RegisterParent();
            if ($this->HasActiveParent()) {
                IPS_ApplyChanges($this->ParentID);
            }
        }

        public function ReadGatewayState()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_STATE_REQ);
            //$APIData = new \KLF200\APIData(\KLF200\APICommand::GET_SCENE_LIST_REQ);
            $ResultAPIData = $this->SendAPIData($APIData);
            //todo 
            // brauchen wir state? Oder substate?
            /*
              Command           Data 1          Data 2      Data 3 – 6
              GW_GET_STATE_CFM  GatewayState    SubState    StateData

              GatewayState value Description
              0 Test mode.
              1 Gateway mode, no actuator nodes in the system table.
              2 Gateway mode, with one or more actuator nodes in the system table.
              3 Beacon mode, not configured by a remote controller.
              4 Beacon mode, has been configured by a remote controller.
              5 - 255 Reserved.

              SubState value, when
              GatewayState is 1 or 2 Description
              0x00 Idle state.
              0x01 Performing task in Configuration Service handler
              0x02 Performing Scene Configuration
              0x03 Performing Information Service Configuration.
              0x04 Performing Contact input Configuration.
              0x?? In Contact input Learn state. ???
              0x80 Performing task in Command Handler
              0x81 Performing task in Activate Group Handler
              0x82 Performing task in Activate Scene Handler
             */
        }

        public function ReadGatewayVersion()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_VERSION_REQ);
            $ResultAPIData = $this->SendAPIData($APIData);
            if ($ResultAPIData->isError()) {
                return false;
            }
            $this->SetValueInteger('CommandVersion', ord($ResultAPIData->Data[0]));
            $this->SetValueString('SoftwareVersion', ord($ResultAPIData->Data[1]) .
                    ord($ResultAPIData->Data[2]) . '.' .
                    ord($ResultAPIData->Data[3]) . '.' .
                    ord($ResultAPIData->Data[4]) .
                    ord($ResultAPIData->Data[5]));
            $this->SetValueInteger('HardwareVersion', ord($ResultAPIData->Data[6]));
            return true;
        }

        public function ReadProtocolVersion()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_PROTOCOL_VERSION_REQ);
            $ResultAPIData = $this->SendAPIData($APIData);
            if ($ResultAPIData->isError()) {
                return false;
            }
            $this->SetValueString(
                    'ProtocolVersion',
                    unpack('n', substr($ResultAPIData->Data, 0, 2))[1] . '.' .
                    unpack('n', substr($ResultAPIData->Data, 2, 2))[1]);
            return true;
        }

        public function SetGatewayTime()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::SET_UTC_REQ, pack('N', time()));
            $ResultAPIData = $this->SendAPIData($APIData);
            return !$ResultAPIData->isError();
        }

        public function GetGatewayTime()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_LOCAL_TIME_REQ);
            $ResultAPIData = $this->SendAPIData($APIData);
            if ($ResultAPIData->isError()) {
                return false;
            }
            $Result = [
                'Timestamp'          => unpack('N', substr($ResultAPIData->Data, 0, 4))[1],
                'Second'             => ord($ResultAPIData->Data[4]),
                'Minute'             => ord($ResultAPIData->Data[5]),
                'Hour'               => ord($ResultAPIData->Data[6]),
                'DayOfMonth'         => ord($ResultAPIData->Data[7]),
                'Month'              => 1 + ord($ResultAPIData->Data[8]),
                'Year'               => 1900 + unpack('n', substr($ResultAPIData->Data, 9, 2))[1],
                'WeekDay'            => ord($ResultAPIData->Data[11]),
                'DayOfYear'          => unpack('n', substr($ResultAPIData->Data, 12, 2))[1],
                'DaylightSavingFlag' => unpack('c', $ResultAPIData->Data[14])[1]
            ];
            return $Result;
        }

        public function RebootGateway()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::REBOOT_REQ);
            $ResultAPIData = $this->SendAPIData($APIData);
            return !$ResultAPIData->isError();
        }

        /*
          public function GetSystemTable()
          {
          $APIData = new \KLF200\APIData(\KLF200\APICommand::CS_GET_SYSTEMTABLE_DATA_REQ);
          $ResultAPIData = $this->SendAPIData($APIData);
          //$this->lock('SendAPIData');
          // wait for finisch
          // 01 00 3A DC 1C 03 C0 1C 01 00 00 00 00
          //$this->unlock('SendAPIData');
          }

          public function GetAllNodesInformation()
          {
          $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_ALL_NODES_INFORMATION_REQ);
          $ResultAPIData = $this->SendAPIData($APIData);
          $State = ord($ResultAPIData->Data);
          if ($State == 1) {
          return [];
          }
          $this->WaitForNodes = ord($ResultAPIData->Data);
          //$this->lock('SendAPIData');
          for ($i = 0; $i < 2000; $i++) {
          if ($this->WaitForNodes < 1) {
          break;
          }
          usleep(1000);
          }
          //$this->unlock('SendAPIData');
          return $this->Nodes;
          }

          public function GetSceneList()
          {
          $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_SCENE_LIST_REQ);
          $ResultAPIData = $this->SendAPIData($APIData);
          }
         */
        public function SetHouseStatusMonitor()
        {
            $APIData = new \KLF200\APIData(\KLF200\APICommand::HOUSE_STATUS_MONITOR_ENABLE_REQ);
            $ResultAPIData = $this->SendAPIData($APIData);
            return !$ResultAPIData->isError();
        }

################## PRIVATE  
        private function ReceiveEvent(\KLF200\APIData $APIData)
        {
            $this->SendDataToChilds($APIData);
        }

        private function Connect()
        {
            if (strlen($this->ReadPropertyString('Password')) > 31) {
                $this->SetStatus(IS_INACTIVE);
                $this->State = \KLF200Splitter\TLSState::unknow;
                return false;
            }
            $Result = $this->CreateConnection();
            if ($Result === false) {
                $this->SetStatus(IS_EBASE + 2);
                $this->State = \KLF200Splitter\TLSState::unknow;
                $this->LogMessage('Error in TLS handshake.', KL_ERROR);
                return false;
            }

            $APIData = new \KLF200\APIData(\KLF200\APICommand::PASSWORD_ENTER_REQ, str_pad($this->ReadPropertyString('Password'), 32, "\x00"));
            $ResultAPIData = $this->SendAPIData($APIData);
            if ($ResultAPIData === false) {
                $this->SetStatus(IS_EBASE + 2);
                $this->State = \KLF200Splitter\TLSState::unknow;
                return false;
            }
            if ($ResultAPIData->Data != "\x00") {
                $this->SendDebug('Login Error', '', 0);
                $this->SetStatus(IS_EBASE + 1);
                $this->LogMessage('Access denied', KL_ERROR);
                return false;
            }
            $this->SendDebug('Login sucessfully', '', 0);
            $this->SetStatus(IS_ACTIVE);
            return true;
        }

        /**
         * Baut eine TLS Verbindung auf.
         * 
         * @access private
         * @return boolean True wenn der TLS Handshake erfolgreich war.
         */
        private function CreateConnection()
        {
            $this->SendDebug('CreateConnection', '', 0);
            try {

                $TLSconfig = \PTLS\TLSContext::getClientConfig([]);
                $TLS = \PTLS\TLSContext::createTLS($TLSconfig);
                if (!$this->TLSHandshake($TLS)) {
                    return false;
                }
                $this->State = \KLF200Splitter\TLSState::Connected;
                $this->SendDebug('TLS ProtocolVersion', $TLS->getDebug()->getProtocolVersion(), 0);
                $UsingCipherSuite = explode("\n", $TLS->getDebug()->getUsingCipherSuite());
                unset($UsingCipherSuite[0]);
                foreach ($UsingCipherSuite as $Line) {
                    $this->SendDebug(trim(substr($Line, 0, 14)), trim(substr($Line, 15)), 0);
                }
                $this->Multi_TLS = $TLS;
            } catch (Exception $exc) {
                $this->SendDebug('Error', $exc->getMessage(), 0);
                trigger_error($exc->getMessage(), E_USER_NOTICE);
                return false;
            }
            return true;
        }

################## DATAPOINTS CHILDS
        /**
         * Interne Funktion des SDK. Nimmt Daten von Childs entgegen und sendet Diese weiter.
         * 
         * @access public
         * @param string $JSONString
         * @result bool true wenn Daten gesendet werden konnten, sonst false.
         */
        public function ForwardData($JSONString)
        {
            if ($this->State <> \KLF200Splitter\TLSState::Connected) {
                return serialize(new \KLF200\APIData(\KLF200\APICommand::ERROR_NTF, chr(\KLF200\ErrorNTF::TIMEOUT)));
            }
            $APIData = new \KLF200\APIData($JSONString);
            $result = @$this->SendAPIData($APIData);
            return serialize($result);
        }

        /**
         * Sendet die Events an die Childs.
         * 
         * @access private
         * @param \KLF200\APIData $APIData
         */
        private function SendDataToChilds(\KLF200\APIData $APIData)
        {
            $this->SendDataToChildren($APIData->ToJSON('{5242DAEF-EEBD-441F-AB0B-E83C01475B65}'));
        }

################## DATAPOINTS PARENT    
        /**
         * Empfängt Daten vom Parent.
         * 
         * @access public
         * @param string $JSONString Das empfangene JSON-kodierte Objekt vom Parent.
         * @result bool True wenn Daten verarbeitet wurden, sonst false.
         */
        public function ReceiveData($JSONString)
        {
            $data = json_decode($JSONString);
            $Data = $this->TLSReceiveBuffer . utf8_decode($data->Buffer);
            if ((ord($Data[0]) >= 0x14) && (ord($Data[0]) <= 0x18) && (substr($Data, 1, 2) == "\x03\x03")) {
                $TLSData = $Data;
                $Data = '';
                while (strlen($TLSData) > 0) {
                    $len = unpack('n', substr($TLSData, 3, 2))[1] + 5;
                    if (strlen($TLSData) >= $len) {
                        $Part = substr($TLSData, 0, $len);
                        $TLSData = substr($TLSData, $len);
                        if ($this->State == \KLF200Splitter\TLSState::init) {
                            if (!$this->WriteTLSReceiveData($Part)) {
                                break;
                            }
                        } else if ($this->State == \KLF200Splitter\TLSState::Connected) {
                            try {
                                $TLS = $this->GetTLSContext();
                                $TLS->encode($Part);
                                $SLIPData = $TLS->input();
                                $this->SetTLSContext($TLS);
                                $this->DecodeSLIPData($SLIPData);
                            } catch (\PTLS\Exceptions\TLSAlertException $e) {
                                $this->SendDebug('Error', $e->getMessage(), 0);
                                if (strlen($out = $e->decode())) {
                                    $JSON['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
                                    $JSON['Buffer'] = utf8_encode($out);
                                    $JsonString = json_encode($JSON);
                                    parent::SendDataToParent($JsonString);
                                }
                                trigger_error($e->getMessage(), E_USER_NOTICE);
                                $this->State = \KLF200Splitter\TLSState::unknow;
                                $this->TLSReceiveBuffer = '';
                                return;
                            }
                        }
                    } else {
                        break;
                    }
                }
                if (strlen($TLSData) == 0) {
                    $this->TLSReceiveBuffer = '';
                } else {
                    //$this->SendDebug('Receive TLS Part', $TLSData, 0);
                    $this->TLSReceiveBuffer = $TLSData;
                }
            } else { // Anfang (inkl. Buffer) paßt nicht
                $this->TLSReceiveBuffer = '';
                return;
            }
        }

        private function DecodeSLIPData($SLIPData)
        {
            $SLIPData = $this->ReceiveBuffer . $SLIPData;
            $this->SendDebug('Input SLIP Data', $SLIPData, 1);
            $Start = strpos($SLIPData, chr(0xc0));
            if ($Start === false) {
                $this->SendDebug('ERROR', 'SLIP Start Marker not found', 0);
                $this->ReceiveBuffer = '';
                return false;
            }
            if ($Start != 0) {
                $this->SendDebug('WARNING', 'SLIP start is ' . $Start . ' and not 0', 0);
            }
            $End = strpos($SLIPData, chr(0xc0), 1);
            if ($End === false) {
                $this->SendDebug('WAITING', 'SLIP End Marker not found', 0);
                $this->ReceiveBuffer = $SLIPData;
                return false;
            }
            $TransportData = str_replace(["\xDB\xDC", "\xDB\xDD"], ["\xC0", "\xDB"],
                                         substr($SLIPData, $Start + 1, $End - $Start - 1));
            $Tail = substr($SLIPData, $End + 1);
            $this->ReceiveBuffer = $Tail;
            if (ord($TransportData[0]) != 0) {
                $this->SendDebug('ERROR', 'Wrong ProtocolID', 0);
                return false;
            }
            $len = ord($TransportData[1]) + 2;
            if (strlen($TransportData) != $len) {
                $this->SendDebug('ERROR', 'Wrong frame length', 0);
                return false;
            }
            $Checksum = substr($TransportData, -1);
            $ChecksumData = substr($TransportData, 0, -1);
            //todo Checksum
            $Command = unpack('n', substr($TransportData, 2, 2))[1];
            $Data = substr($TransportData, 4, $len - 5);
            $APIData = new \KLF200\APIData($Command, $Data);
            if ($APIData->isEvent()) {
                $this->SendDebug('Event', $APIData, 1);
                $this->ReceiveEvent($APIData);
            } else {
                $this->ReplyAPIData = $APIData;
            }
            if (strpos($Tail, chr(0xc0)) !== false) {
                $this->SendDebug('Tail hast Start Marker', '', 0);
                $this->DecodeSLIPData('');
            }
        }

        /**
         * Wartet auf eine Antwort einer Anfrage an den LMS.
         *
         * @param string $APICommand
         * @result mixed
         */
        private function ReadReplyAPIData()
        {
            for ($i = 0; $i < 2000; $i++) {
                $Buffer = $this->ReplyAPIData;
                if (!is_null($Buffer)) {
                    $this->ReplyAPIData = null;
                    return $Buffer;
                }
                usleep(1000);
            }
            return null;
        }

        //################# SENDQUEUE


        private function TLSHandshake(&$TLS)
        {
            $this->State = \KLF200Splitter\TLSState::init;
            $this->SendDebug('TLS start', '', 0);
            $loop = 1;
            $SendData = $TLS->decode();
            $this->SendDebug('Send TLS Handshake ' . $loop, $SendData, 0);
            $JSON['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
            $JSON['Buffer'] = utf8_encode($SendData);
            $JsonString = json_encode($JSON);
            $this->TLSReceiveData = '';
            $this->WaitForTLSReceive = true;
            parent::SendDataToParent($JsonString);
            while (!$TLS->isHandshaked() && ($loop < 10)) {
                $loop++;
                $Result = $this->ReadTLSReceiveData();
                if ($Result === false) {
                    $this->SendDebug('TLS no answer', '', 0);
                    trigger_error('TLS no answer', E_USER_NOTICE);
                    break;
                }
                $this->SendDebug('Get TLS Handshake', $Result, 0);
                try {
                    $TLS->encode($Result);
                    if ($TLS->isHandshaked()) {
                        break;
                    }
                } catch (\PTLS\Exceptions\TLSAlertException $e) {
                    $this->SendDebug('Error', $e->getMessage(), 1);
                    if (strlen($out = $e->decode())) {
                        $JSON['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
                        $JSON['Buffer'] = utf8_encode($out);
                        $JsonString = json_encode($JSON);
                        $this->TLSReceiveData = '';
                        parent::SendDataToParent($JsonString);
                    }
                    trigger_error($e->getMessage(), E_USER_NOTICE);
                    $this->WaitForTLSReceive = false;
                    return false;
                }

                $SendData = $TLS->decode();
                if (strlen($SendData) > 0) {
                    $this->SendDebug('TLS loop ' . $loop, $SendData, 0);
                    $JSON['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
                    $JSON['Buffer'] = utf8_encode($SendData);
                    $JsonString = json_encode($JSON);
                    $this->TLSReceiveData = '';
                    $this->WaitForTLSReceive = true;
                    parent::SendDataToParent($JsonString);
                } else {
                    $this->SendDebug('TLS waiting loop ' . $loop, $SendData, 0);
                }
            }
            $this->WaitForTLSReceive = false;
            if (!$TLS->isHandshaked()) {
                return false;
            }
            return true;
        }

        private function GetSessionId()
        {
            $SessionId = ($this->SessionId + 1) & 0xffff;
            $this->SessionId = $SessionId;
            return pack('n', $SessionId);
        }

        private function SendAPIData(\KLF200\APIData $APIData)
        {
            //Statt SessionId benutzen wir einfach NodeID.
            /* if (in_array($APIData->Command, [
              \KLF200\APICommand::COMMAND_SEND_REQ,
              \KLF200\APICommand::STATUS_REQUEST_REQ,
              \KLF200\APICommand::WINK_SEND_REQ,
              \KLF200\APICommand::SET_LIMITATION_REQ,
              \KLF200\APICommand::GET_LIMITATION_STATUS_REQ,
              \KLF200\APICommand::MODE_SEND_REQ,
              \KLF200\APICommand::ACTIVATE_SCENE_REQ,
              \KLF200\APICommand::STOP_SCENE_REQ,
              \KLF200\APICommand::ACTIVATE_PRODUCTGROUP_REQ
              ])) {
              $APIData->Data = $this->GetSessionId() . $APIData->Data;
              } */
            try {

                $this->SendDebug('Wait to send', $APIData, 1);
                $time = microtime(true);
                while (true) {
                    if ($this->lock('SendAPIData')) {
                        break;
                    }
                    if (microtime(true) - $time > 5) {
                        throw new Exception($this->Translate('Send is blocked for ') . \KLF200\APICommand::ToString($APIData->Command), E_USER_ERROR);
                    }
                }
                if ($this->State != \KLF200Splitter\TLSState::Connected) {
                    throw new Exception($this->Translate('Socket not connected'), E_USER_NOTICE);
                }
                $Data = $APIData->GetSLIPData();
                $this->SendDebug('Send', $APIData, 1);
                $this->SendDebug('Send SLIP Data', $Data, 1);
                $TLS = $this->GetTLSContext();
                $TLS->output($Data);
                $SendData = $TLS->decode();
                $this->SetTLSContext($TLS);
                $JSON['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
                $JSON['Buffer'] = utf8_encode($SendData);
                $JsonString = json_encode($JSON);
                $this->ReplyAPIData = null;
                parent::SendDataToParent($JsonString);
                $ResponseAPIData = $this->ReadReplyAPIData();

                if ($ResponseAPIData === null) {
                    throw new Exception($this->Translate('Timeout'), E_USER_NOTICE);
                }
                $this->SendDebug('Response', $ResponseAPIData, 1);
                $this->unlock('SendAPIData');
                if ($ResponseAPIData->isError()) {
                    trigger_error($this->Translate($ResponseAPIData->ErrorToString()), E_USER_NOTICE);
                }
                return $ResponseAPIData;
            } catch (Exception $exc) {
                $this->SendDebug('Error', $exc->getMessage(), 0);
                if ($exc->getCode() != E_USER_ERROR) {
                    $this->unlock('SendAPIData');
                }
                trigger_error($this->Translate($exc->getMessage()), E_USER_NOTICE);
                return new \KLF200\APIData(\KLF200\APICommand::ERROR_NTF, chr(\KLF200\ErrorNTF::TIMEOUT));
            }
        }

        private function ReadTLSReceiveData()
        {
            for ($i = 0; $i < 2000; $i++) {
                $Input = $this->TLSReceiveData;
                if ($Input != '') {
                    $this->TLSReceiveData = '';
                    return $Input;
                }
                usleep(1000);
            }
            return false;
        }

        private function WriteTLSReceiveData(string $Data)
        {
            if ($this->TLSReceiveData == '') {
                $this->TLSReceiveData = $Data;
                while ($this->TLSReceiveData != '') {
                    usleep(1000);
                }
                return true;
            }
            return false;
        }

        protected function SendDebug($Message, $Data, $Format)
        {
            if (is_a($Data, '\\KLF200\\APIData')) {
                /* @var $Data \KLF200\APIData */
                $this->SendDebug2($Message . ':Command', \KLF200\APICommand::ToString($Data->Command), 0);
                if ($Data->isError()) {
                    $this->SendDebug2('Error', $Data->ErrorToString(), 0);
                } else if ($Data->Data != '') {
                    $this->SendDebug2($Message . ':Data', $Data->Data, $Format);
                }
            } else {
                $this->SendDebug2($Message, $Data, $Format);
            }
        }

        /**
         * 
         * @return \PTLS\TLSContext
         */
        private function GetTLSContext()
        {
            $this->lock('TLS');
            return $this->Multi_TLS;
        }

        /**
         * 
         * @param \PTLS\TLSContext $TLS
         */
        private function SetTLSContext($TLS)
        {
            $this->Multi_TLS = $TLS;
            $this->unlock('TLS');
        }

    }

}
/** @} */
    