<?php
/**
 * XML2Array: A class to convert XML to array in PHP
 * It returns the array which can be converted back to XML using the Array2XML script
 * It takes an XML string or a DOMDocument object as an input.
 *
 * See Array2XML: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 *
 * @category    apps
 * @package     remote-boot
 * @subpackage  Libraries
 * @author      Lalit Patel
 * @author      DigiTickets
 * @license     Apache License 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @link        http://www.lalit.org/lab/convert-xml-to-array-in-php-xml2array
 * @link        https://github.com/digitickets/lalit
 *
 */

 ///////////////////////////////////////////////////////////////////////////////
 //
 // Licensed under the Apache License, Version 2.0 (the "License");
 // you may not use this file except in compliance with the License.
 // You may obtain a copy of the License at
 //
 //     http://www.apache.org/licenses/LICENSE-2.0
 //
 // Unless required by applicable law or agreed to in writing, software
 // distributed under the License is distributed on an "AS IS" BASIS,
 // WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 // See the License for the specific language governing permissions and
 // limitations under the License.
 ///////////////////////////////////////////////////////////////////////////////

 namespace clearos\apps\remote_boot;

 use DOMDocument;
 use DOMNode;
 use Exception;

class XML2Array
{
    /**
     * @var string
     */
    private static $encoding = 'UTF-8';

    /**
     * @var DOMDocument
     */
    private static $xml = null;

    /**
     * Convert an XML to Array.
     *
     * @param string|DOMDocument $input_xml
     *
     * @return array
     *
     * @throws Exception
     */
    public static function createArray($input_xml)
    {
        $xml = self::getXMLRoot();
        if (is_string($input_xml)) {
            try {
                $xml->loadXML($input_xml);
                if (!is_object($xml) || empty($xml->documentElement)) {
                    throw new Exception();
                }
            } catch (Exception $ex) {
                throw new Exception('[XML2Array] Error parsing the XML string.'.PHP_EOL.$ex->getMessage());
            }
        } elseif (is_object($input_xml)) {
            if (get_class($input_xml) != 'DOMDocument') {
                throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
            }
            $xml = self::$xml = $input_xml;
        } else {
            throw new Exception('[XML2Array] Invalid input');
        }
        $array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }

    /**
     * Initialize the root XML node [optional].
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $standalone
     * @param bool   $format_output
     */
    public static function init($version = '1.0', $encoding = 'utf-8', $standalone = FALSE, $format_output = TRUE)
    {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->xmlStandalone = $standalone;
        self::$xml->formatOutput = $format_output;
        self::$encoding = $encoding;
    }

    /**
     * Convert an Array to XML.
     *
     * @param DOMNode $node - XML as a string or as an object of DOMDocument
     *
     * @return array
     */
    private static function convert(DOMNode $node)
    {
        $output = [];

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output['@cdata'] = trim($node->textContent);
                break;

            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:

                // for each child node, call the convert function recursively
                for ($i = 0, $m = $node->childNodes->length; $i < $m; ++$i) {
                    $child = $node->childNodes->item($i);
                    $v = self::convert($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;

                        // assume more nodes of same kind are coming
                        if (!array_key_exists($t, $output)) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } else {
                        //check if it is not an empty node
                        if (!empty($v)) {
                          $output = $v;
                        }
                        // recover zeroes from the XML file, as they also cause
                        // empty() to return TRUE
                        elseif ($v === '0' || $v === 0) {
                          $output = $v;
                        }
                    }
                }

                if (is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) == 1) {
                            $output[$t] = $v[0];
                        }
                    }
                    if (empty($output)) {
                        //for empty nodes
                        $output = '';
                    }
                }

                // loop through the attributes and collect them
                if ($node->attributes->length) {
                    $a = [];
                    foreach ($node->attributes as $attrName => $attrNode) {
                        $a[$attrName] = $attrNode->value;
                    }
                    // if its an leaf node, store the value in @value instead of directly storing it.
                    if (!is_array($output)) {
                        $output = ['@value' => $output];
                    }
                    $output['@attributes'] = $a;
                }
                break;
        }

        return $output;
    }

    /**
     * Get the root XML node, if there isn't one, create it.
     *
     * @return DOMDocument
     */
    private static function getXMLRoot()
    {
        if (empty(self::$xml)) {
            self::init();
        }

        return self::$xml;
    }
}
