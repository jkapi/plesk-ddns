<?php
class Modules_ddns_DomainsPage extends pm_Navigation_OverviewPage
{
    public function getData() {
        return Modules_ddns_Domains::getData();
    }

}