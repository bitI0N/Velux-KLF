<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/KLF200Class.php';  // diverse Klassen
eval('declare(strict_types=1);namespace KLF200Node {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Node {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Node {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Node {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Node {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Node {?>' . file_get_contents(__DIR__ . '/../libs/helper/AttributeArrayHelper.php') . '}');

/**
 * @property char $NodeId
 * @property int $SessionId
 */
class KLF200Node extends IPSModule
{

    use \KLF200Node\Semaphore,
        \KLF200Node\BufferHelper,
        \KLF200Node\VariableHelper,
        \KLF200Node\VariableProfileHelper,
        \KLF200Node\AttributeArrayHelper,
        \KLF200Node\DebugHelper {
        \KLF200Node\DebugHelper::SendDebug as SendDebug2;
    }
    /**
     * Interne Funktion des SDK.
     */
    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{725D4DF6-C8FC-463C-823A-D3481A3D7003}');
        $this->RegisterPropertyInteger('NodeId', -1);
        $this->SessionId = 1;
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $APICommands = [
            \KLF200\APICommand::GET_NODE_INFORMATION_NTF,
            \KLF200\APICommand::NODE_INFORMATION_CHANGED_NTF,
            \KLF200\APICommand::NODE_STATE_POSITION_CHANGED_NTF,
            \KLF200\APICommand::COMMAND_RUN_STATUS_NTF,
            \KLF200\APICommand::COMMAND_REMAINING_TIME_NTF,
            \KLF200\APICommand::SESSION_FINISHED_NTF,
            \KLF200\APICommand::STATUS_REQUEST_NTF,
            \KLF200\APICommand::WINK_SEND_NTF,
            \KLF200\APICommand::MODE_SEND_NTF
        ];
        $this->SessionId = 1;
        $NodeId = $this->ReadPropertyInteger('NodeId');
        $this->NodeId = chr($NodeId);
        if (($NodeId < 0) or ( $NodeId > 255)) {
            $Line = "NOTHING";
        } else {
            $NodeId = substr(json_encode(utf8_encode(chr($this->ReadPropertyInteger('NodeId'))), JSON_UNESCAPED_UNICODE), 1, -1);
            if (strlen($NodeId) == 6) {
                $NodeId = preg_quote('\\u' . substr(strtoupper($NodeId), 2));
            }
            foreach ($APICommands as $APICommand) {
                $Lines[] = '.*"Command":' . $APICommand . ',"Data":"' . $NodeId . '.*';
            }
            $Line = implode('|', $Lines);
        }
        $this->SetReceiveDataFilter('(' . $Line . ')');
        $this->SendDebug('FILTER', $Line, 0);
        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RequestNodeInformation();
        }
    }

    private function ReceiveEvent(\KLF200\APIData $APIData)
    {
        switch ($APIData->Command) {
            case \KLF200\APICommand::GET_NODE_INFORMATION_NTF:
                $NodeID = ord($APIData->Data[0]);
                /* Data 1 Data 2 - 3 Data 4    Data 5 - 68 Data 69
                  NodeID  Order      Placement Name        Velocity
                  Data 70 - 71    Data 72      Data 73     Data 74       Data 75   Data 76
                  NodeTypeSubType ProductGroup ProductType NodeVariation PowerMode BuildNumber
                  Data 77 - 84 Data 85 Data 86 - 87    Data 88 - 89 Data 90 - 91       Data 92 - 93
                  SerialNumber State   CurrentPosition Target       FP1CurrentPosition FP2CurrentPosition
                  Data 94 - 95       Data 96 - 97       Data 98 - 99  Data 100 - 103 Data 104   Data 105 - 125
                  FP3CurrentPosition FP4CurrentPosition RemainingTime TimeStamp      NbrOfAlias AliasArray
                 */

                $Name = trim(utf8_decode(substr($APIData->Data, 4, 64)));
                $NodeTypeSubType = unpack('n', substr($APIData->Data, 69, 2))[1];
                $this->SendDebug('NodeID', $NodeID, 0);
                $this->SendDebug('Name', $Name, 0);
                $this->SendDebug('NodeTypeSubType', sprintf('%04X', $NodeTypeSubType), 0);
                $this->SendDebug('NodeType', ($NodeTypeSubType >> 6), 0);
                $this->SendDebug('SubType', ($NodeTypeSubType & 0x003F), 0);
                $this->SendDebug('ProductGroup', ord($APIData->Data[71]), 0);
                $this->SendDebug('ProductType', ord($APIData->Data[72]), 0);
                $this->SendDebug('NodeVariation', ord($APIData->Data[73]), 0);
                $this->SendDebug('PowerMode', ord($APIData->Data[74]), 0);
                $this->SendDebug('BuildNumber', ord($APIData->Data[75]), 0);
                $this->SendDebug('SerialNumber', substr($APIData->Data, 76, 8), 1);
                $this->SendDebug('State', ord($APIData->Data[84]), 0);
                $CurrentPosition = unpack('n', substr($APIData->Data, 85, 2))[1];
                $this->SendDebug('CurrentPosition', sprintf('%04X', $CurrentPosition), 0);
                $Target = unpack('n', substr($APIData->Data, 87, 2))[1];
                $this->SendDebug('Target', sprintf('%04X', $Target), 0);
                $FP1CurrentPosition = unpack('n', substr($APIData->Data, 89, 2))[1];
                $this->SendDebug('FP1CurrentPosition', sprintf('%04X', $FP1CurrentPosition), 0);
                $FP2CurrentPosition = unpack('n', substr($APIData->Data, 91, 2))[1];
                $this->SendDebug('FP2CurrentPosition', sprintf('%04X', $FP2CurrentPosition), 0);
                $FP3CurrentPosition = unpack('n', substr($APIData->Data, 93, 2))[1];
                $this->SendDebug('FP3CurrentPosition', sprintf('%04X', $FP3CurrentPosition), 0);
                $FP4CurrentPosition = unpack('n', substr($APIData->Data, 95, 2))[1];
                $this->SendDebug('FP4CurrentPosition', sprintf('%04X', $FP4CurrentPosition), 0);
                $RemainingTime = unpack('n', substr($APIData->Data, 97, 2))[1];
                $this->SendDebug('RemainingTime', $RemainingTime, 0);
                $TimeStamp = unpack('N', substr($APIData->Data, 99, 4))[1];
                $this->SendDebug('TimeStamp', $TimeStamp, 0);
                $this->SendDebug('TimeStamp', strftime('%H:%M:%S %d.%m.%Y', $TimeStamp), 0);
                break;
            /* case \KLF200\APICommand::NODE_INFORMATION_CHANGED_NTF:
              break; */
            case \KLF200\APICommand::NODE_STATE_POSITION_CHANGED_NTF:
                /*
                  Data 1 Data 2 Data 3 - 4      Data 5 - 6
                  NodeID State  CurrentPosition Target
                  Data 7 - 8         Data 9 - 10        Data 11 -12        Data 13 - 14       Data 15 - 16
                  FP1CurrentPosition FP2CurrentPosition FP3CurrentPosition FP4CurrentPosition RemainingTime
                  Data 17 - 20
                  TimeStamp
                 */
                $this->SendDebug('State', ord($APIData->Data[1]), 0);
                $CurrentPosition = unpack('n', substr($APIData->Data, 2, 2))[1];
                $this->SendDebug('CurrentPosition', sprintf('%04X', $CurrentPosition), 0);
                $Target = unpack('n', substr($APIData->Data, 4, 2))[1];
                $this->SendDebug('Target', sprintf('%04X', $Target), 0);
                $FP1CurrentPosition = unpack('n', substr($APIData->Data, 6, 2))[1];
                $this->SendDebug('FP1CurrentPosition', sprintf('%04X', $FP1CurrentPosition), 0);
                $FP2CurrentPosition = unpack('n', substr($APIData->Data, 8, 2))[1];
                $this->SendDebug('FP2CurrentPosition', sprintf('%04X', $FP2CurrentPosition), 0);
                $FP3CurrentPosition = unpack('n', substr($APIData->Data, 10, 2))[1];
                $this->SendDebug('FP3CurrentPosition', sprintf('%04X', $FP3CurrentPosition), 0);
                $FP4CurrentPosition = unpack('n', substr($APIData->Data, 12, 2))[1];
                $this->SendDebug('FP4CurrentPosition', sprintf('%04X', $FP4CurrentPosition), 0);
                $RemainingTime = unpack('n', substr($APIData->Data, 14, 2))[1];
                $this->SendDebug('RemainingTime', $RemainingTime, 0);
                $TimeStamp = unpack('N', substr($APIData->Data, 16, 4))[1];
                $this->SendDebug('TimeStamp', $TimeStamp, 0);
                $this->SendDebug('TimeStamp', strftime('%H:%M:%S %d.%m.%Y', $TimeStamp), 0);
                break;
            case \KLF200\APICommand::COMMAND_RUN_STATUS_NTF:
                // 00 06 01 00 00 FF FF 01 02 0E 00 00 00 
                /*
                  Command                   Data 1 - 2  Data 3      Data 4
                  GW_COMMAND_RUN_STATUS_NTF SessionID   StatusID    Index
                  Data 5        Data 6 – 7
                  NodeParameter ParameterValue
                  Data 8    Data 9      Data 10 - 13
                  RunStatus StatusReply InformationCode
                 */
                $NodeParameter = ord($APIData->Data[4]);
                $this->SendDebug('NodeParameter', $NodeParameter, 0);
                $ParameterValue = unpack('n', substr($APIData->Data, 5, 2))[1];
                $this->SendDebug('ParameterValue', sprintf('%04X', $ParameterValue), 0);
                $RunStatus = ord($APIData->Data[7]);
                $this->SendDebug('RunStatus', $RunStatus, 0);
                $this->SendDebug('RunStatus', \KLF200\RunStatus::ToString($RunStatus), 0);
                $StatusReply = ord($APIData->Data[8]);
                $this->SendDebug('StatusReply', $StatusReply, 0);
                $this->SendDebug('StatusReply', \KLF200\StatusReply::ToString($StatusReply), 0);
                if ($RunStatus == \KLF200\RunStatus::EXECUTION_FAILED) {
                    trigger_error($this->Translate(\KLF200\RunStatus::ToString($RunStatus)), E_USER_NOTICE);
                    return;
                }

                break;
            case \KLF200\APICommand::COMMAND_REMAINING_TIME_NTF:
                break;
            case \KLF200\APICommand::SESSION_FINISHED_NTF:
                break;
            case \KLF200\APICommand::STATUS_REQUEST_NTF:
                //00 00 01 00 01 02 FF 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 
                /*
                  Command                Data 1 – 2 Data 3   Data 4    Data 5    Data 6
                  GW_STATUS_REQUEST_NTF  SessionID  StatusID NodeIndex RunStatus StatusReply
                  Data 7
                  StatusType
                 *      0 = “Target Position” or
                 *      1 = “Current Position” or
                 *      2 = “Remaining Time”
                 *      Data 8      Data 9 - 59
                 *      StatusCount ParameterData
                 * 
                  Data 7
                  StatusType
                 *      3 = “Main Info”
                 *      Data 8 - 9      Data 10 - 11    Data 12 - 13
                 *      TargetPosition  CurrentPosition RemainingTime
                 *      Data 14 - 17                Data 18
                 *      LastMasterExecutionAddress  LastCommandOriginator
                 */
                $StatusID = ord($APIData->Data[2]);
                $this->SendDebug('StatusID', $StatusID, 0);
                $NodeIndex = ord($APIData->Data[3]);
                $this->SendDebug('NodeIndex', $NodeIndex, 0);
                $RunStatus = ord($APIData->Data[4]);
                $this->SendDebug('RunStatus', $RunStatus, 0);
                $StatusReply = ord($APIData->Data[5]);
                $this->SendDebug('StatusReply', $StatusReply, 0);
                $StatusType = ord($APIData->Data[6]);
                $this->SendDebug('StatusType', $StatusType, 0);
                if ($StatusType == 0xFF) {
                    $this->SendDebug('Error', \KLF200\StatusReply::ToString($StatusReply), 0);
                    trigger_error($this->Translate(\KLF200\StatusReply::ToString($StatusReply)), E_USER_NOTICE);
                    return;
                }
                break;
            case \KLF200\APICommand::WINK_SEND_NTF:
                break;
            case \KLF200\APICommand::MODE_SEND_NTF:
        }
    }

    public function RequestNodeInformation()
    {
        $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_NODE_INFORMATION_REQ, $this->NodeId);
        $ResultAPIData = $this->SendAPIData($APIData);
        if ($ResultAPIData === null) {
            return false;
        }
        $State = ord($ResultAPIData->Data[0]);
        switch ($State) {
            case 0:
                return true;
            case 1:
                trigger_error($this->Translate('Request rejected'), E_USER_NOTICE);
                return false;
            case 2:
                trigger_error($this->Translate('Invalid node index'), E_USER_NOTICE);
                return false;
        }
    }

    private function GetSessionId()
    {
        $SessionId = ($this->SessionId + 1) & 0xff;
        $this->SessionId = $SessionId;
        return chr($SessionId);
    }

    public function RequestStatus()
    {
        /*
          Command               Data 1 – 2 Data 3          Data 4 – 23   Data 24
          GW_STATUS_REQUEST_REQ SessionID  IndexArrayCount IndexArray    StatusType
          Data 25    Data 26
          FPI1       FPI2
          StatusType
          value     |Description
          0       |Request Target position
          1       |Request Current position
          2       |Request Remaining time
          3       |Request Main info.
         */
        $Data = $this->NodeId . $this->GetSessionId() . chr(1) . $this->NodeId . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
        $Data .= chr(0) . chr(0b11100000) . chr(0);
        $APIData = new \KLF200\APIData(\KLF200\APICommand::STATUS_REQUEST_REQ, $Data);
        $ResultAPIData = $this->SendAPIData($APIData);
        if ($ResultAPIData === null) {
            return false;
        }
        return ord($ResultAPIData->Data[2]) == 1;
    }

    public function SetMainParameter(int $Value)
    {
        /*
          Command               Data 1 – 2  Data 3              Data 4          Data 5
          GW_COMMAND_SEND_REQ   SessionID   CommandOriginator   PriorityLevel   ParameterActive
          Data 6    Data 7  Data 8 - 41                     Data 42         Data 43 – 62    Data 63
          FPI1      FPI2    FunctionalParameterValueArray   IndexArrayCount IndexArray      PriorityLevelLock
          Data 64   Data 65 Data 66
          PL_0_3    PL_4_7  LockTime
         */
        $Data = $this->NodeId . $this->GetSessionId(); //Data 1-2
        $Data .= chr(1) . chr(3) . chr(0); // Data 3-5
        $Data .= chr(0) . chr(0); // Data 6-7
        $Data .= pack('n', $Value); // Data 8-9
        $Data .= "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"; // Data 10-25
        $Data .= "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"; // Data 26-41
        $Data .= chr(1) . $this->NodeId . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"; //Data 42-62
        $Data .= chr(0); // Data 63
        $Data .= chr(0) . chr(0) . chr(0); // Data 64-66
        $APIData = new \KLF200\APIData(\KLF200\APICommand::COMMAND_SEND_REQ, $Data);
        $ResultAPIData = $this->SendAPIData($APIData);
        if ($ResultAPIData === null) {
            return false;
        }
        return ord($ResultAPIData->Data[2]) == 1;
    }

    public function ReceiveData($JSONString)
    {
        $APIData = new \KLF200\APIData($JSONString);
        $this->SendDebug('Event', $APIData, 1);
        $this->ReceiveEvent($APIData);
    }

    private function SendAPIData(\KLF200\APIData $APIData)
    {
        if ($this->NodeId == chr(-1)) {
            return NULL;
        }
        $this->SendDebug('ForwardData', $APIData, 1);
        try {
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            /** @var \KLF200\APIData $ResponseAPIData */
            $ret = $this->SendDataToParent($APIData->ToJSON('{7B0F87CC-0408-4283-8E0E-2D48141E42E8}'));
            $ResponseAPIData = @unserialize($ret);
            $this->SendDebug('Response', $ResponseAPIData, 1);
            if ($ResponseAPIData->Command == \KLF200\APICommand::ERROR_NTF) {
                trigger_error($this->Translate($ResponseAPIData->ErrorToString()), E_USER_NOTICE);
                return null;
            }
            return $ResponseAPIData;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            return null;
        }
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

}
