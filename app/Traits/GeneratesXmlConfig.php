<?php

namespace App\Traits;

use DOMDocument;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

trait GeneratesXmlConfig
{
    public function generateXmlConfig($data)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><PHONE_CONFIG></PHONE_CONFIG>');

        // Create the config node
        $config = $xml->addChild('config');
        $config->addAttribute('reg.1.server.1.address', $data['server_address_1']);
        $config->addAttribute('reg.1.address', $data['user_id_1']);
        $config->addAttribute('reg.1.auth.userId', $data['user_id_1']);
        $config->addAttribute('reg.1.auth.password', $data['user_password_1']);
        $config->addAttribute('reg.1.server.1.transport', 'UDPOnly');

        // Create the OBiParameterList node
        $obiParameters = $xml->addChild('OBiParameterList');
        $obiParameters->addAttribute('VoiceService.1.VoiceProfile.6.SIP.ProxyServer', 'i3.voip.polycom.com');
        $obiParameters->addAttribute('VoiceService.1.VoiceProfile.6.SIP.ProxyServerPort', '5066');
        $obiParameters->addAttribute('VoiceService.1.VoiceProfile.6.SIP.ProxyServerTransport', 'TLS');
        $obiParameters->addAttribute('VoiceService.1.VoiceProfile.1.Line.6.X_LineName', 'i3');
        $obiParameters->addAttribute('VoiceService.1.VoiceProfile.1.Line.6.X_ServProvProfile', 'F');

        return $xml->asXML();
    }
}