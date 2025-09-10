<?php
include "sdk.php";

pm_Context::init('ddns');

if (isset($_GET["id"]) && isset($_GET["key"]))
{
    $ipv4 = "";
    $ipv6 = "";

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipv4 = $ip;
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ipv6 = $ip;
    }
    
    $passkey = pm_Settings::get('ddnskey.' . $_GET["id"]);

    if (empty($passkey) || $passkey !== $_GET["key"])
        die("Wrong Credentials");

    $record = pm_Dns_Record::getById($_GET["id"]);

    if ($record->getType() == "A")
    {
        if (empty($ipv4))
            die("Can't set A record to IPV6 address");
        if ($ipv4 === $record->getValue())
            die("No update necessary");

        $record->setValue($ipv4);
    }
    else if ($record->getType() == "AAAA")
    {
        if (empty($ipv6))
            die("Can't set AAAA record to IPV4 address");
        if ($ipv6 === $record->getValue())
            die("No update necessary");

        $record->setValue($ipv6);
    }

    $record->save();
    pm_Settings::set('ddnskey.'.$record->getId().'.time', time());
    echo "Updated record to " . $ip;
}
else
{
    die("Parameter(s) missing");
}
?>
