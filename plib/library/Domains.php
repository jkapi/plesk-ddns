<?php
include "sdk.php";

// This module is used for generating the page titles of domains
class Modules_ddns_Domains
{
    public static function getData()
    {
        // Show all domains current user has access to,
        $client = pm_Session::getClient();

        $domains = pm_Domain::getAllDomains();

        $allowedDomains = [];

        foreach($domains as $domain)
        {
            if ($client->hasAccessToDomain($domain->getId())) {
                $zone = $domain->getDnsZone();
                if ($zone->isEnabled()) {
                    $allowedDomains[] = [
                        'id' => $domain->getId(),
                        'title' => 'Dynamic DNS for ' . $domain->getName()
                    ];
                }
            }
        }
        return $allowedDomains;
    }

    public static function getById($id)
    {
        foreach (self::getData() as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }

        throw new pm_Exception("Picture with id '{$id}' not found.");
    }
}
?>