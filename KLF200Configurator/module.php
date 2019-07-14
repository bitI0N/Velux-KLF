<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/KLF200Class.php';  // diverse Klassen
eval('declare(strict_types=1);namespace KLF200Configurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Configurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('declare(strict_types=1);namespace KLF200Configurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');

/**
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
            \KLF200\APICommand::GET_SCENE_INFORMATION_NTF,
            \KLF200\APICommand::GET_SCENE_LIST_NTF,
            \KLF200\APICommand::CS_DISCOVER_NODES_NTF,
            \KLF200\APICommand::CS_SYSTEM_TABLE_UPDATE_NTF
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
            case \KLF200\APICommand::CS_DISCOVER_NODES_NTF:
            case \KLF200\APICommand::CS_SYSTEM_TABLE_UPDATE_NTF:
                // Reload ConfigForm;
                break;
            case \KLF200\APICommand::GET_ALL_NODES_INFORMATION_NTF:
                $this->WaitForNodes--;
                $NodeID = ord($APIData->Data[0]);
                $Name = trim(utf8_decode(substr($APIData->Data, 4, 64)));
                $NodeTypeSubType = unpack('n', substr($APIData->Data, 69, 2))[1];
                $this->SendDebug('NodeID', $NodeID, 0);
                $this->SendDebug('Name', $Name, 0);
                $this->SendDebug('NodeTypeSubType', $NodeTypeSubType, 0);
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
        if ($ResultAPIData->isError()) {
            return [];
        }
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

    public function DiscoveryNodes()
    {
        $APIData = new \KLF200\APIData(\KLF200\APICommand::CS_DISCOVER_NODES_REQ, "\x00");
        $ResultAPIData = $this->SendAPIData($APIData);
        if ($ResultAPIData->isError()) {
            trigger_error($this->Translate($ResultAPIData->ErrorToString()), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function RebootGateway()
    {
        $APIData = new \KLF200\APIData(\KLF200\APICommand::REBOOT_REQ);
        $ResultAPIData = $this->SendAPIData($APIData);
        if ($ResultAPIData->isError()) {
            trigger_error($this->Translate($ResultAPIData->ErrorToString()), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function RemoveNodes(int $Node)
    {
        if (($Node < 0) or ( $Node > 199)) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Node'), E_USER_NOTICE);
            return false;
        }
        $Data = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
        $Data[intdiv($Node, 8)] = chr(1 << ($Node % 8));
        $APIData = new \KLF200\APIData(\KLF200\APICommand::CS_REMOVE_NODES_REQ, $Data);
        $ResultAPIData = $this->SendAPIData($APIData);
        if ($ResultAPIData->isError()) {
            trigger_error($this->Translate($ResultAPIData->ErrorToString()), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    /**
     * Interne Funktion des SDK.
     */
    private function GetNodeConfigFormValues(int $Splitter)
    {
        $FoundNodes = $this->GetAllNodesInformation();
        $this->SendDebug('Found Nodes', $FoundNodes, 0);
        $InstanceIDListNodes = $this->GetInstanceList('{4EBD07B1-2962-4531-AC5F-7944789A9CE5}', $Splitter, 'NodeId');
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
                'configuration' => ['NodeId' => $NodeID]
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

    private function GetDeleteNodeConfigFormValues()
    {
        foreach ($this->Nodes as $NodeID => $Node) {
            $AddValue = [
                'nodeid' => $NodeID,
                'name'   => $Node['Name'],
                'type'   => \KLF200\Node::$SubType[$Node['NodeTypeSubType']]
            ];
            $NodeValues[] = $AddValue;
        }


        return $NodeValues;
    }

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
        $Form['actions'][1]['values'] = $NodeValues;

        if ((float) IPS_GetKernelVersion() < 5.3) {
            array_shift($Form['actions']);
        } else {
            $Form['actions'][0]['items'][0]['items'][0]['onClick'] = <<<'EOT'
                KLF200_DiscoveryNodes($id);
                echo 
                EOT . ' "' . $this->Translate('The view will reload after discovery is finished.') . '";';

            $DeleteNodeValues = $this->GetDeleteNodeConfigFormValues();
            $Form['actions'][0]['items'][0]['items'][1]['popup']['items'][1]['values'] = $DeleteNodeValues;
            $Form['actions'][0]['items'][0]['items'][1]['popup']['items'][0]['onClick'] = <<<'EOT'
                KLF200_RemoveNodes($id,$RemoveNode['nodeid']);
                echo 
                EOT . ' "' . $this->Translate('The view will reload after remove is finished.') . '";';
            $Form['actions'][0]['items'][0]['items'][2]['onClick'] = <<<'EOT'
                KLF200_Reboot($id);
                echo
                EOT . ' "' . $this->Translate('The KLF200 will now reboot.') . '";';
        }
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
            /** @var \KLF200\APIData $ResponseAPIData */
            $ret = @$this->SendDataToParent($APIData->ToJSON('{7B0F87CC-0408-4283-8E0E-2D48141E42E8}'));
            $ResponseAPIData = @unserialize($ret);
            $this->SendDebug('Response', $ResponseAPIData, 1);
            return $ResponseAPIData;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            return new \KLF200\APIData(\KLF200\APICommand::ERROR_NTF, chr(\KLF200\ErrorNTF::TIMEOUT));
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
