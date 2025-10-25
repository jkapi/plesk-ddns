<?php
class Modules_ddns_CustomButtons extends pm_Hook_CustomButtons
{

    public function getButtons()
    {
        return [[
            'place' => self::PLACE_DOMAIN_PROPERTIES_DYNAMIC,
            'section' => self::SECTION_DOMAIN_PROPS_DYNAMIC_HOSTING,
            'title' => 'Dynamic DNS',
            'description' => 'Setup DynDNS for this host',
            'icon' => pm_Context::getBaseUrl() . 'images/ddns.png',
            'link' => pm_Context::getBaseUrl() . 'index.php/index/referer',
            'contextParams' => true,
        ], [
            'place' => [
                self::PLACE_HOSTING_PANEL_NAVIGATION,
                self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
                self::PLACE_RESELLER_TOOLS_AND_SETTINGS,
            ],
            'title' => 'Dynamic DNS',
            'description' => 'Dynamic DNS',
            'link' => pm_Context::getBaseUrl()
        ]];
    }

}
