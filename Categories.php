<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
/* TODO: Add Rule to force Name and Desc entry on save */
require_once("Business_Objects.php");

class Categories extends Business_Collection_Base {
   
}
class Category extends Business_Base {
    var $_i18ns;
    function __construct($id=null)
    {
       @fprintf(STDOUT, "DBG9 CTOR %s/%s ID: %s\n", __CLASS__, __FUNCTION__, $id);    #SED_DELETE_ON_ROLL
        $this->_i18ns = new I18Ns();
        $this->_i18ns->ObjectReference(&$this);
        $this->Business_Base($id);
    }
    function Name($locale=null, $value=null)
    {
       @fprintf(STDOUT, "DBG9 %s/%s %s-%s\n", __CLASS__, __FUNCTION__, $locale, $value);    #SED_DELETE_ON_ROLL
        return $this->_i18ns->SetVar(
            $locale, __FUNCTION__, __CLASS__, $value, $this->ID());
    }
    function Description($value=null)
    {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Enabled($value=null)
    {
        $ret = $this->SetVar(__FUNCTION__, $value);
        if ($value == null){
            if (!isset($ret)){
                $ret = true; 
            }
         }
         return $ret;
    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('Description', 'description', 'text', null, true, '', 55, null); 
        $this->AddMap('Enabled', 'enabled', 'boolean', null, true, 1, 1, null); 
    }
    function &DiscoverChildren(){
        $this->AddChild(&$this->_i18ns, 'FK');
    }
    function ToString(){
    	return $this->Name();
    }
    function Table(){
        return "category";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        return $brs;
    }
}
?>
