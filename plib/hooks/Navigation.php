<?php
class Modules_ddns_Navigation extends pm_Hook_Navigation
{
    public function getNavigation()
    {
        return [
            [
                        'controller' => 'index',
                        'action' => 'domainlist',
                        'title' => 'Domain List',
                        'pages' => [
                            [
                                'controller' => 'domain',
                                'action' => 'zone',
                                'type' => 'Modules_ddns_DomainsPage',
                                'pages' => [
                                    [
                                        'controller' => 'domain',
                                        'action' => 'record'
                                    ],
                                ],
                            ],
                        ],
                    ]
                    ];
    }
}