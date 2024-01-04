<?php

class IndexController extends pm_Controller_Action
{
    // Go to domain list on index
    public function indexAction()
    {

        $this->view->pageTitle = 'Dynamic DNS';
        $this->_redirect('/index/domainlist');
    }

    // Unused, go to domain list
    public function unauthorizedAction()
    {
        $this->_redirect('/index/domainlist');
    }

    public function domainlistAction()
    {
        $this->view->pageTitle = 'Dynamic DNS';

        $client = pm_Session::getClient();

        $domains = pm_Domain::getAllDomains();

        $allowedDomains = [];

        foreach($domains as $domain)
        {
            if ($client->hasAccessToDomain($domain->getId()) ) {
                $zone = $domain->getDnsZone();
                if ($zone->isEnabled() &&  $zone->isMaster()) {
                    $allowedDomains[] = $domain;
                }
            }
        }
        
        $list = $this->_getDomainTable($allowedDomains, $client->isAdmin());

        $this->view->list = $list;
    }

    // List all domains user has access to
    public function domainlistDataAction()
    {
        $client = pm_Session::getClient();

        $domains = pm_Domain::getAllDomains();

        $allowedDomains = [];

        foreach($domains as $domain)
        {
            if ($client->hasAccessToDomain($domain->getId())) {
                $zone = $domain->getDnsZone();
                if ($zone->isEnabled()) {
                    $allowedDomains[] = $domain;
                }
            }
        }

        $list = $this->_getDomainTable($allowedDomains, $client->isAdmin());

        $this->_helper->json($list->fetchData());
    }

    // Referer for Website view button
    public function refererAction()
    {
        if (isset($_GET["dom_id"]))
        {
            $this->_redirect("/domain/zone/id/{$_GET["dom_id"]}");
        }
        else
        {
            $this->_redirect('/index/domainlist');
        }
    }

    // Retrieve all domains with enabled master DNS zone into a table
    private function _getDomainTable($domains, $isAdmin = false)
    {
        foreach($domains as $domain)
        {
            $aliases = [];
            foreach(pm_DomainAlias::getByDomain($domain) as $alias)
            {
                $aliases[] = $alias->getName();
            }
            $domainData[] = [
                'name' => "<a href=\"{$this->_helper->url('zone', 'domain')}/id/{$domain->getId()}\">{$domain->getName()}</a>",
                'owner' => $domain->getClient()->getProperty('pname'),
                'aliases' => implode(", ", $aliases)
            ];
        }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($domainData);
        if ($isAdmin)
        {
            $list->setColumns([
                'name' => [
                    'title' => 'Domain',
                    'noEscape' => true,
                    'searchable' => true
                ],
                'aliases' => [
                    'title' => 'Aliases',
                    'searchable' => true
                ],
                'owner' => [
                    'title' => 'Owner'
                ]
                ]);
        }
        else
        {
            
        $list->setColumns([
            'name' => [
                'title' => 'Domain',
                'noEscape' => true,
                'searchable' => true
            ],
            'aliases' => [
                'title' => 'Aliases',
                'searchable' => true
            ]
            ]);
        }
        $list->setDataUrl(array('action' => 'domainlist-data'));

        return $list;
    }
}
