<?php

class DomainController extends pm_Controller_Action
{
    // List all records in current zone
    public function zoneAction()
    {
        $client = pm_Session::getClient();

        // Check if user has access to domain
        $domainId = $this->getParam('id');
        if (!$client->hasAccessToDomain($domainId)) {
            $this->_redirect('/index/unauthorized');
        }

        // Return if zone isn't enabled or master
        $domain = pm_Domain::getByDomainId($domainId);
        $zone = $domain->getDnsZone();
        if (!$zone->isEnabled() || !$zone->isMaster()) {
            $this->_redirect('/index/domainlist');
        }
        
        $records = $this->_getRecordTable($domainId, $zone->getRecords());

        // Add selectable page title
        $this->view->assign(Modules_ddns_Domains::getById($this->_request->getParam('id')));

        // Naive check for table data request
        if (isset($_GET["controllerName"]))
        {
            $this->_helper->json($records->fetchData());
        }
        else
        {
            $this->view->records = $records;
        }
    }

    // Show DDNS settings for Record
    public function recordAction()
    {
        pm_Context::init('ddns');
        $client = pm_Session::getClient();

        $recordId = $this->getParam('record');
        $record = pm_Dns_Record::getById($recordId);
        $zone = $record->getZone();
        $domain = $zone->getDomain();

        if (!$client->hasAccessToDomain($domain->getId())) {
            $this->_redirect('/index/unauthorized');
        }

        if (!$zone->isEnabled() || !$zone->isMaster()) {
            $this->_redirect('/index/domainlist');
        }
        
        $form = new pm_Form_Simple();

        $enabled = !empty(pm_Settings::get('ddnskey.'.$record->getId()));
        $form->addElement('checkbox', 'ddnsEnabled', [
            'label' => 'DDNS Enabled',
            'value' => $enabled
        ]);
        
        $form->AddElement('text', 'host', [
            'label'   => 'Domain Name',
            'value'   => $record->getHost(),
            'attribs' => array('disabled' => 'disabled')
        ]); 
        $form->AddElement('text', 'type', [
            'label'   => 'Record Type',
            'value'   => $record->getType(),
            'attribs' => array('disabled' => 'disabled')
        ]); 
        $form->AddElement('text', 'ttl', [
            'label'   => 'TTL',
            'value'   => $record->getTtl(),
            'attribs' => array('disabled' => 'disabled')
        ]); 
        $form->AddElement('text', 'address', [
            'label'   => 'IP Address',
            'value'   => $record->getValue(),
            'attribs' => array('disabled' => 'disabled')
        ]); 
        if ($enabled)
        {
            $ddnsUrl =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . pm_Context::getBaseUrl() . 'public/update.php?id=' . $record->getId() . '&key=' . pm_Settings::get('ddnskey.'.$record->getId());
            $form->AddElement('SimpleText', 'key', [
                'label'   => 'DDNS URL',
                'value'   => $ddnsUrl,
            ]); 
            $mtime = pm_Settings::get('ddnskey.'.$record->getId().'.time', 0);
            $mstring = date("Y-m-d H:i:s T", $mtime);
            $modified = $this->_time_since($mtime, true);
            $form->AddElement('SimpleText', 'modified', [
                'label'   => 'DDNS URL',
                'value'   => $modified,
            ]); 
        }
        else
        {
            $form->AddElement('SimpleText', 'key', [
                'label'   => 'DDNS URL',
                'value'   => '-',
            ]); 
            $form->AddElement('SimpleText', 'modified', [
                'label'   => 'DDNS Last Modified',
                'value'   => '-',
            ]); 
        }

        $form->addControlButtons(array(
            'sendTitle' => 'Save',
            'hideLegend' => true,
            'cancelLink' => "{$this->_helper->url('zone', 'domain')}/id/{$domain->getId()}"
        ));
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            if ($form->getValue('ddnsEnabled'))
            {
                if ($enabled == false)
                {
                    pm_Settings::set('ddnskey.'.$record->getId(), md5(uniqid(mt_rand())));
                    pm_Settings::set('ddnskey.'.$record->getId().'.ttl', $record->getTtl());
                }
                $this->_status->addMessage('info', "Dynamic DNS is enabled for host {$record->getHost()}");
            }
            else
            {
                pm_Settings::set('ddnskey.'.$record->getId(), null);
                $ttl = pm_Settings::get('ddnskey.'.$record->getId().'.ttl', $record->getTtl());
                $record->setTtl(60);
                $record->save();
                $this->_status->addMessage('warning', "Dynamic DNS is disabled for host {$record->getHost()}");
            }
            $this->_helper->json(array('redirect' => $_SERVER['REQUEST_URI']));
        }

        $this->view->pageTitle = "Dynamic DNS for Record {$record->getHost()}";
        $this->view->record = $record;
        $this->view->form = $form;
    }
    
    // Return table with records for a certain domain
    private function _getRecordTable($domainId, $records)
    {
        pm_Context::init('ddns');
        // String for a green checkmark, pulled from plesk homepage
        $ddnsString = '<span class="pul-action pul-action--size-16"><span class="pul-status pul-status--success pul-action__content"><span class="pul-icon pul-icon--size-16 pul-action__icon"><svg focusable="false"><use href="/ui-library/images/symbols.svg#check-mark-circle-filled:16"></use></svg></span></span></span>';
        
        $allowedRecords = [];
        foreach($records as $record)
        {
            $ddnsEnabled = pm_Settings::get('ddnskey.'.$record->getId());
            
            $type = $record->getType();
            $modified = "";
            if ($ddnsEnabled) {
                $mtime = pm_Settings::get('ddnskey.'.$record->getId().'.time', 0);
                $mstring = date("Y-m-d H:i:s T", $mtime);
                $modified = $this->_time_since($mtime);
            }
            if ($type == "A" || $type == "AAAA") {
                $allowedRecords[] = [
                    'ddns' => $ddnsEnabled ? $ddnsString : "",
                    'host' => "<a href=\"{$this->_helper->url('record', 'domain')}/id/{$domainId}/record/{$record->getId()}\">{$record->getHost()}</a>",
                    'ttl' => $record->getTtl(),
                    'type' => $type,
                    'value' => $record->getValue(),
                    'modified' =>  $modified
                ];
            }
        }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($allowedRecords);
        $list->setColumns([
            'ddns' => [
                'title' => 'DDNS',
                'noEscape' => true,
                'searchable' => false
            ],
            'host' => [
                'title' => 'Host',
                'noEscape' => true,
                'searchable' => true
            ],
            'ttl' => [
                'title' => 'TTL'
            ],
            'type' => [
                'title' => 'Record Type'
            ],
            'value' => [
                'title' => 'Value'
            ],
            'modified' => [
                'title' => 'Last Modified',
                'noEscape' => true
            ]
            ]);
        $list->setDataUrl(array('action' => 'zone-data', 'link' => $domainId));

        return $list;
    }

    private function _time_since($since, $raw = false) {
        if ($since == 0)
            return "never";
        $chunks = array(
            array(60 * 60 * 24 , 'day'),
            array(60 * 60 , 'hour'),
            array(60 , 'minute'),
            array(1 , 'second')
        );

        $now = time();
        $interval =  $now - $since;

        for ($i = 0, $j = count($chunks); $i < $j; $i++) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];
            if (($count = floor($interval / $seconds)) != 0) {
                break;
            }
        }
        $sinceDate = date('Y-m-d H:i:s', $since);
        if ($name == 'day')
            $print = $sinceDate;
        else
            if ($raw)
                $print = ($count == 1) ? '1 '.$name.' ago' : "$count {$name}s ago ({$sinceDate})";
            else
                $print = "<abbr title=\"{$sinceDate}\">" . (($count == 1) ? '1 '.$name.' ago' : "$count {$name}s ago") . "</abbr>";
        return $print;
    }
}