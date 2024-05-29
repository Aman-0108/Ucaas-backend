<?php

namespace App\Traits;

use DOMDocument;
use Illuminate\Support\Facades\Storage;

trait CreateXml
{
    /**
     * Creates an XML file with specified content.
     *
     * This method generates an XML file with the specified content structure
     * and saves it using Laravel's filesystem under the 'xml' directory.
     *
     * @param string $filename The filename (without extension) for the XML file.
     * @return string Returns 'success' if the XML file is successfully created and saved.
     */
    public function createxml($filename)
    {
        // Create a new DOMDocument object
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        // Create the root <include> element
        $includeElement = $xmlDoc->createElement('include');
        $xmlDoc->appendChild($includeElement);

        // Create the <user> element
        $userElement = $xmlDoc->createElement('user');
        $userElement->setAttribute('id', '1000');
        $includeElement->appendChild($userElement);

        // Create the <params> element
        $paramsElement = $xmlDoc->createElement('params');
        $userElement->appendChild($paramsElement);

        // Create the <param> elements inside <params>
        $paramsData = array(
            array('name' => 'password', 'value' => '${default_password}'),
            array('name' => 'vm-password', 'value' => '1000')
        );

        foreach ($paramsData as $param) {
            $paramElement = $xmlDoc->createElement('param');
            $paramElement->setAttribute('name', $param['name']);
            $paramElement->setAttribute('value', $param['value']);
            $paramsElement->appendChild($paramElement);
        }

        // Create the <variables> element
        $variablesElement = $xmlDoc->createElement('variables');
        $userElement->appendChild($variablesElement);

        // Create the <variable> elements inside <variables>
        $variablesData = array(
            array('name' => 'toll_allow', 'value' => 'domestic,international,local'),
            array('name' => 'accountcode', 'value' => '1000'),
            array('name' => 'user_context', 'value' => 'default'),
            array('name' => 'effective_caller_id_name', 'value' => 'Extension 1000'),
            array('name' => 'effective_caller_id_number', 'value' => '1000'),
            array('name' => 'outbound_caller_id_name', 'value' => '${outbound_caller_name}'),
            array('name' => 'outbound_caller_id_number', 'value' => '${outbound_caller_id}'),
            array('name' => 'callgroup', 'value' => 'techsupport')
        );

        foreach ($variablesData as $variable) {
            $variableElement = $xmlDoc->createElement('variable', $variable['value']);
            $variableElement->setAttribute('name', $variable['name']);
            $variablesElement->appendChild($variableElement);
        }

        // Format the XML document
        $xmlDoc->formatOutput = true;

        // Convert DOMDocument to XML string
        $xmlContent = $xmlDoc->saveXML();

        $folderPath = '/xml';

        // Save XML content to a file using Laravel's filesystem
        Storage::put($folderPath . '/' . $filename . '.xml', $xmlContent);

        return "success";
    }
}
