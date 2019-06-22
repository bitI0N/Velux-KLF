<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/KLF200Class.php';  // diverse Klassen
eval('declare(strict_types=1);namespace KLF200Configurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Configurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Configurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');

/**
 * @property array $Zones
 * @property array $Nodes
 * @property int $WaitForNodes
 */
class KLF200Configurator extends IPSModule
{

    use \KLF200Configurator\Semaphore,
        \KLF200Configurator\BufferHelper,
        \KLF200Configurator\DebugHelper {
        \KLF200Configurator\DebugHelper::SendDebug as SendDebug2;
    }
    /**
     * Interne Funktion des SDK.
     */
    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{725D4DF6-C8FC-463C-823A-D3481A3D7003}');
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $APICommands = [
            \KLF200\APICommand::GET_ALL_GROUPS_INFORMATION_NTF,
            \KLF200\APICommand::GET_ALL_GROUPS_INFORMATION_FINISHED_NTF,
            \KLF200\APICommand::GET_ALL_NODES_INFORMATION_NTF,
            \KLF200\APICommand::GET_ALL_NODES_INFORMATION_FINISHED_NTF,
            \KLF200\APICommand::GET_SCENE_INFOAMATION_NTF,
            \KLF200\APICommand::GET_SCENE_LIST_NTF
        ];

        if (count($APICommands) > 0) {
            foreach ($APICommands as $APICommand) {
                $Lines[] = '.*"Command":' . $APICommand . '.*';
            }
            $Line = implode('|', $Lines);
            $this->SetReceiveDataFilter('(' . $Line . ')');
            $this->SendDebug('FILTER', $Line, 0);
        }
    }

    private function ReceiveEvent(\KLF200\APIData $APIData)
    {
        switch ($APIData->Command) {
            case \KLF200\APICommand::GET_ALL_NODES_INFORMATION_NTF:
                $this->WaitForNodes--;
                //00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 01 03 C0 0A 01 00 00 47 56 23 4B 26 11 20 00 03 FF F7 FF F7 FF F7 FF F7 FF F7 FF F7 FF 00 00 5D 0C FA 4B 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 
                /* Data 1 Data 2 - 3 Data 4    Data 5 - 68 Data 69
                  NodeID  Order      Placement Name        Velocity
                  Data 70 - 71    Data 72      Data 73     Data 74       Data 75   Data 76
                  NodeTypeSubType ProductGroup ProductType NodeVariation PowerMode BuildNumber
                  Data 77 - 84 Data 85 Data 86 - 87    Data 88 - 89 Data 90 - 91       Data 92 - 93
                  SerialNumber State   CurrentPosition Target       FP1CurrentPosition FP2CurrentPosition
                  Data 94 - 95       Data 96 - 97       Data 98 - 99  Data 100 - 103 Data 104   Data 105 - 125
                  FP3CurrentPosition FP4CurrentPosition RemainingTime TimeStamp      NbrOfAlias AliasArray
                 */
                $NodeID = ord($APIData->Data[0]);
                $Name = trim(utf8_decode(substr($APIData->Data, 4, 64)));
                $NodeTypeSubType = unpack('n', substr($APIData->Data, 69, 2))[1];
                $this->SendDebug('NodeID', $NodeID, 0);
                $this->SendDebug('Name', $Name, 0);
                $this->SendDebug('NodeTypeSubType', $NodeTypeSubType, 0);
                //$this->SendDebug('ProductGroup', ord($APIData->Data[71]), 0);
                //$this->SendDebug('ProductType', ord($APIData->Data[72]), 0);
                //$this->SendDebug('NodeVariation', ord($APIData->Data[73]), 0);
                $this->SendDebug('SerialNumber', substr($APIData->Data, 76, 8), 1);
                $this->SendDebug('BuildNumber', ord($APIData->Data[75]), 0);
                $Nodes = $this->Nodes;
                $Nodes[$NodeID] = [
                    'Name'            => $Name,
                    'NodeTypeSubType' => $NodeTypeSubType
                ];
                $this->Nodes = $Nodes;
                break;
            case \KLF200\APICommand::GET_ALL_NODES_INFORMATION_FINISHED_NTF:
                $this->WaitForNodes = -1;
                break;
        }
    }

    private function GetInstanceList(string $GUID, int $Parent, string $ConfigParam)
    {
        $InstanceIDList = [];
        foreach (IPS_GetInstanceListByModuleID($GUID) as $InstanceID) {
            // Fremde Geräte überspringen
            if (IPS_GetInstance($InstanceID)['ConnectionID'] == $Parent) {
                $InstanceIDList[] = $InstanceID;
            }
        }
        if ($ConfigParam != '') {
            $InstanceIDList = array_flip(array_values($InstanceIDList));
            array_walk($InstanceIDList, [$this, 'GetConfigParam'], $ConfigParam);
        }
        return $InstanceIDList;
    }

    private function GetConfigParam(&$item1, $InstanceID, $ConfigParam)
    {
        $item1 = IPS_GetProperty($InstanceID, $ConfigParam);
    }

    public function GetAllNodesInformation()
    {
        $this->Nodes = [];
        $APIData = new \KLF200\APIData(\KLF200\APICommand::GET_ALL_NODES_INFORMATION_REQ);
        $ResultAPIData = $this->SendAPIData($APIData);
        $State = ord($ResultAPIData->Data[0]);
        if ($State == 1) {
            return [];
        }
        $this->WaitForNodes = ord($ResultAPIData->Data[1]);
        $this->SendDebug('WaitForNodes:', ord($ResultAPIData->Data[1]), 0);
        for ($i = 0; $i < 10000; $i++) {
            if ($this->WaitForNodes < 1) {
                break;
            }
            usleep(1000);
        }
        return $this->Nodes;
    }

    /**
     * Interne Funktion des SDK.
     */
    private function GetNodeConfigFormValues(int $Splitter)
    {
        $FoundNodes = $this->GetAllNodesInformation();
        $this->SendDebug('Found Nodes', $FoundNodes, 0);
        $InstanceIDListNodes = []; //$this->GetInstanceList('{DEDC12F1-4CF7-4DD1-AE21-B03D7A7FADD7}', $Splitter, 'NodeID');
        $this->SendDebug('IPS Nodes', $InstanceIDListNodes, 0);
        $NodeValues = [];
        foreach ($FoundNodes as $NodeID => $Node) {
            $InstanceIDNode = array_search($NodeID, $InstanceIDListNodes);
            if ($InstanceIDNode !== false) {
                $AddValue = [
                    'instanceID' => $InstanceIDNode,
                    'nodeid'     => $NodeID,
                    'name'       => IPS_GetName($InstanceIDNode),
                    'type'       => \KLF200\Node::$SubType[$Node['NodeTypeSubType']],
                    'location'   => stristr(IPS_GetLocation($InstanceIDNode), IPS_GetName($InstanceIDNode), true)
                ];
                unset($InstanceIDListNodes[$InstanceIDNode]);
            } else {
                $AddValue = [
                    'instanceID' => 0,
                    'nodeid'     => $NodeID,
                    'name'       => $Node['Name'],
                    'type'       => \KLF200\Node::$SubType[$Node['NodeTypeSubType']],
                    'location'   => ''
                ];
            }
            $AddValue['create'] = [
                'moduleID'      => '{4EBD07B1-2962-4531-AC5F-7944789A9CE5}',
                'configuration' => ['NodeID' => $NodeID]
            ];

            $NodeValues[] = $AddValue;
        }

        foreach ($InstanceIDListNodes as $InstanceIDNode => $Node) {
            $NodeValues[] = [
                'instanceID' => $InstanceIDNode,
                'nodeid'     => $Node,
                'name'       => IPS_GetName($InstanceIDNode),
                'type'       => 'unknown',
                'location'   => stristr(IPS_GetLocation($InstanceIDNode), IPS_GetName($InstanceIDNode), true)
            ];
        }
        return $NodeValues;
    }

    /*
      private function GetRemoteConfigFormValues(int $Splitter)
      {
      $APIDataRemoteList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ControlList);
      $FoundRemotes = $this->Send($APIDataRemoteList);
      $this->SendDebug('Found Remotes', $FoundRemotes, 0);
      $InstanceIDListRemotes = $this->GetInstanceList('{C7EA583D-2BAC-41B7-A85A-AD0DF648E514}', $Splitter, 'Type');
      $this->SendDebug('IPS Remotes', $InstanceIDListRemotes, 0);
      $RemoteValues = [];
      $HasTuner = false;
      foreach ($FoundRemotes as $RemoteName) {
      $RemoteID = \OnkyoAVR\Remotes::ToRemoteID($RemoteName);
      if ($RemoteID < 0) {
      continue;
      }
      if ($RemoteID == \OnkyoAVR\Remotes::TUN) {
      $HasTuner = true;
      continue;
      }
      $InstanceIDRemote = array_search($RemoteID, $InstanceIDListRemotes);
      if ($InstanceIDRemote !== false) {
      $AddValue = [
      'instanceID' => $InstanceIDRemote,
      'name'       => IPS_GetName($InstanceIDRemote),
      'type'       => 'Remote',
      'zone'       => $RemoteName,
      'location'   => stristr(IPS_GetLocation($InstanceIDRemote), IPS_GetName($InstanceIDRemote), true)
      ];
      unset($InstanceIDListRemotes[$InstanceIDRemote]);
      } else {
      $AddValue = [
      'instanceID' => 0,
      'name'       => $RemoteName,
      'type'       => 'Remote',
      'zone'       => $RemoteName,
      'location'   => ''
      ];
      }
      $AddValue['create'] = [
      'moduleID'      => '{C7EA583D-2BAC-41B7-A85A-AD0DF648E514}',
      'configuration' => ['Type' => $RemoteID]
      ];
      $RemoteValues[] = $AddValue;
      }
      foreach ($InstanceIDListRemotes as $InstanceIDRemote => $RemoteID) {
      $RemoteName = \OnkyoAVR\Remotes::ToRemoteName($RemoteID);
      $RemoteValues[] = [
      'instanceID' => $InstanceIDRemote,
      'name'       => IPS_GetName($InstanceIDRemote),
      'type'       => 'Remote',
      'zone'       => $RemoteName,
      'location'   => stristr(IPS_GetLocation($InstanceIDRemote), IPS_GetName($InstanceIDRemote), true)
      ];
      }
      $TunerValues = $this->GetTunerConfigFormValues($Splitter, $HasTuner);

      return array_merge($RemoteValues, $TunerValues);
      }

      private function GetTunerConfigFormValues(int $Splitter, bool $HasTuner)
      {
      $InstanceIDListTuner = $this->GetInstanceList('{47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F}', $Splitter, 'Zone');
      $this->SendDebug('IPS Tuner', $InstanceIDListTuner, 0);
      $TunerValues = [];
      foreach ($InstanceIDListTuner as $InstanceIDTuner => $ZoneID) {
      $AddValue = [
      'instanceID' => $InstanceIDTuner,
      'name'       => IPS_GetName($InstanceIDTuner),
      'type'       => 'Tuner',
      'zone'       => '',
      'location'   => stristr(IPS_GetLocation($InstanceIDTuner), IPS_GetName($InstanceIDTuner), true)
      ];
      if ($HasTuner) {
      $AddValue['create'] = [
      'moduleID'      => '{47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F}',
      'configuration' => ['Zone' => $ZoneID]
      ];
      }
      $TunerValues[] = $AddValue;
      }
      if ($HasTuner and ( count($TunerValues) == 0)) {
      foreach ($this->Zones as $ZoneID => $Zone) {
      $Create['Tuner ' . $Zone['Name']] = [
      'moduleID'      => '{47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F}',
      'configuration' => ['Zone' => $ZoneID]
      ];
      }
      $TunerValues[] = [
      'instanceID' => 0,
      'name'       => 'Tuner',
      'type'       => 'Tuner',
      'zone'       => '',
      'location'   => '',
      'create'     => $Create
      ];
      }
      return $TunerValues;
      }

      private function GetNetworkConfigFormValues(int $Splitter)
      {
      $APIDataNetServiceList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetserviceList);
      $FoundNetServiceList = $this->Send($APIDataNetServiceList);
      $HasNetPlayer = false;
      if (count($FoundNetServiceList) > 0) {
      $HasNetPlayer = true;
      }
      $InstanceIDListNetPlayer = $this->GetInstanceList('{3E71DC11-1A93-46B1-9EA0-F0EC0C1B3476}', $Splitter, 'Zone');
      $this->SendDebug('IPS NetPlayer', $InstanceIDListNetPlayer, 0);
      $NetPlayerValues = [];
      foreach ($InstanceIDListNetPlayer as $InstanceIDNetPlayer => $ZoneID) {
      $AddValue = [
      'instanceID' => $InstanceIDNetPlayer,
      'name'       => IPS_GetName($InstanceIDNetPlayer),
      'type'       => 'Netplayer',
      'zone'       => '',
      'location'   => stristr(IPS_GetLocation($InstanceIDNetPlayer), IPS_GetName($InstanceIDNetPlayer), true)
      ];
      if ($HasNetPlayer) {
      $AddValue['create'] = [
      'moduleID'      => '{3E71DC11-1A93-46B1-9EA0-F0EC0C1B3476}',
      'configuration' => ['Zone' => $ZoneID]
      ];
      }
      $NetPlayerValues[] = $AddValue;
      }
      if ($HasNetPlayer and ( count($NetPlayerValues) == 0)) {
      foreach ($this->Zones as $ZoneID => $Zone) {
      $Create['Netplayer ' . $Zone['Name']] = [
      'moduleID'      => '{3E71DC11-1A93-46B1-9EA0-F0EC0C1B3476}',
      'configuration' => ['Zone' => $ZoneID]
      ];
      }
      $NetPlayerValues[] = [
      'instanceID' => 0,
      'name'       => 'Netplayer',
      'type'       => 'Netplayer',
      'zone'       => '',
      'location'   => '',
      'create'     => $Create
      ];
      }
      return $NetPlayerValues;
      }
     */
    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        if (!$this->HasActiveParent()) {
            $Form['actions'][] = [
                'type'  => 'PopupAlert',
                'popup' => [
                    'items' => [[
                    'type'    => 'Label',
                    'caption' => 'Instance has no active parent.'
                        ]]
                ]
            ];
            $this->SendDebug('FORM', json_encode($Form), 0);
            $this->SendDebug('FORM', json_last_error_msg(), 0);

            return json_encode($Form);
        }
        $Splitter = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $IO = IPS_GetInstance($Splitter)['ConnectionID'];
        if ($IO == 0) {
            $Form['actions'][] = [
                'type'  => 'PopupAlert',
                'popup' => [
                    'items' => [[
                    'type'    => 'Label',
                    'caption' => 'Splitter has no IO instance.'
                        ]]
                ]
            ];
            $this->SendDebug('FORM', json_encode($Form), 0);
            $this->SendDebug('FORM', json_last_error_msg(), 0);

            return json_encode($Form);
        }
        $NodeValues = $this->GetNodeConfigFormValues($Splitter);
//        $RemoteValues = $this->GetRemoteConfigFormValues($Splitter);
        //      $NetworkValues = $this->GetNetworkConfigFormValues($Splitter);
        $Form['actions'][0]['values'] = $NodeValues; //array_merge($ZoneValues, $RemoteValues, $NetworkValues);

        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($Form);
    }

    public function ReceiveData($JSONString)
    {
        $APIData = new \KLF200\APIData($JSONString);
        $this->SendDebug('Event', $APIData, 0);
        $this->ReceiveEvent($APIData);
    }

    private function SendAPIData(\KLF200\APIData $APIData)
    {
        $this->SendDebug('ForwardData', $APIData, 0);
        try {
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            /** @var \KLF200\APIData $result */
            $ret = $this->SendDataToParent($APIData->ToJSON('{7B0F87CC-0408-4283-8E0E-2D48141E42E8}'));
            $result = unserialize($ret);
            $this->SendDebug('Response', $result, 0);
            if ($result->Command == \KLF200\APICommand::ERROR_NTF) {
                return null;
            }
            return $result;
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
