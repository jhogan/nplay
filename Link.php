<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("Business_Objects.php");
require_once("GlobalSingleton.php");
/*TODO URLs with ampersands should be replace with &amp; with a special accessor */
class Links extends Business_Collection_Base {
    var $_objRef;
    public function __construct($parent=null){
        if ($parent != null){
            $id = $parent->ID();
            if ($id != ''){
                $this->ObjectReference($parent);
                $gs =& GlobalSingleton::GetInstance();
                $classID = $gs->Object2ID($parent);
                parent::Business_Collection_Base("fk = $id and classID = $classID");
             }
        }
    }
    public function ObjectReference(&$ref=null){
        if($ref != null){
            $this->_objRef =& $ref;
        }else{
            if (isset($this->_objRef)){
                return $this->_objRef;
            }else{
                return null;
            }
        }
    }
}
class Link extends Business_Base {
    var $_objRef;
    var $_validTypes = array('r', 't', 'o', 's');
    function URL($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    function URLPlugged(){
        $cnt = func_num_args();
        $url = $this->URL();

        for ($i=0; $i<$cnt; $i++){
            $plug = urlencode(func_get_arg($i));
            $url = str_replace("%%$i", $plug, $url);
        }
        return $url;
    }
    function Schema(){
        $url = $this->URL();
        if ($url != ''){
            $pos = strpos($url, '://');
            return substr($url, 0, $pos);
        }
        return '';
    }
    function Host(){
        $url = $this->URL();
        if ($url != ''){
            $schema = $this->Schema();
            $start = strlen($schema) + 3;
            $end = strpos($url, '/', $start);
            return substr($url, $start, $end - (strlen($schema) + 3));
        }
        return '';
    }
    function TLD(){
        return $this->PluckHost(0);
    }
    function Domain(){
        $ret = $this->PluckHost(1);  
        if ($ret == 'co'){
            $ret = $this->PluckHost(2);
        }
        return $ret;
    }
    function PluckHost($offset){
        $host = $this->Host();
        if ($host != ''){
            $a = explode('.', $host);
            $a = array_reverse($a);
            return $a[$offset];
        }
        return '';
    }
    function Source($value=null){
        if ($value == null &&
            $this->_Source == "" && 
            $this->URL() != ""){
            $dom = strtolower($this->Domain());
            $dom = strtoupper(substr($dom, 0, 1)) . substr($dom, 1, strlen($dom) - 1); // Capitalize
            return "cap$dom";
        }else{
            return $this->SetVar(__FUNCTION__, $value);
        }
    }
    function Author($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Type($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function TypeString($locale){
        $gs =& GlobalSingleton::GetInstance();
        switch ($this->Type()){
            case 'r':
                $typeString = 'capReview';
                break;
            case 't':
                $typeString = 'capTrailers';
                break;
            case 'o':
                $typeString = 'capOtherSites';
                break;
            case 's':
                $typeString = 'capSystem';
                break;
        }
        return $gs->GetText($locale, $typeString);
    }
    function ToString($locale=1033){
        $gs =& GlobalSingleton::GetInstance();
        $src = $gs->GetText($locale, $this->Source());
        return strtoupper($this->TypeString($locale)) . " (" . $src . '): ' . $this->URL();
    }
    function LinkID($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function LoadLinkID($linkID){
        $this->LoadWhere("fk = 0 and linkID = '$linkID'");
    }
    function &ObjectReference(&$ref=null){
        if ($ref == null){
            $collection =& $this->Collection();
            if($collection == null){
                return $this->_objRef;
            }else{
                return $collection->ObjectReference();
            }
        }else{
            $this->_objRef = $ref;
        }
    }
    function ClassID($value=null)
    {
        if ($value == null){
            if ($this->Type() == 's') return 0;
            $ref =& $this->ObjectReference();
            if ($ref == null){
                return $this->SetVar(__FUNCTION__, $value);
            }else{
                return $ref->ID();
            }
        }else{
            return $this->SetVar(__FUNCTION__, $value);
        }
    }
    function FK($value=null)
    {
        @fprintf(STDOUT, "DBG9 %s/%s\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        if(is_null($value)){
            if ($this->Type() == 's') return 0;
            $obj =& $this->ObjectReference();
            if ($obj != null){
                if (!$obj->IsNew()){
                    return $obj->ID();
                }
            }
        }
        return $this->SetVar(__FUNCTION__, $value);
    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('URL', 'url', 'text', null, true, '', 255, 0); 
        $this->AddMap('Source', 'source', 'text', null, true, '', 50, 0); 

        $this->AddIndex('ClassID', 'classID, asc, 0');
        $this->AddIntegerMap('ClassID', 'classID');

        $this->AddIndex('FK', 'fk, asc, 0');
        $this->AddMap('FK', 'fk', 'integer', true, true, 0, 4, 0); 

        $this->AddMap('Author', 'author', 'text', null, '', '', 50, 0); 
        $this->AddMap('Type', 'type', 'text', null, '', '', 1, 0); 
        $this->AddTextMap('LinkID', 'linkID');
    }
    function Table(){
        return "link";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        $gs =& GlobalSingleton::GetInstance();
        $capMissingURL = $gs->GetText($locale, 'capMissingURL');
        $capInvalidType = $gs->GetText($locale, 'capInvalidType');
        $capSourceMustBeCap = $gs->GetText($locale, 'capSourceMustBeCaption');
        $capAuthorRequired = $gs->GetText($locale, 'capAuthorRequired');
        $capSchemaRequired = $gs->GetText($locale, 'capHTTP(s)SchemaRequired');
        if ($this->Type() == 'r' && $this->Author() == ""){
            $brs->Add("AUTHOR_REQUIRED", $capAuthorRequired);
        }
        $brs->Assert("NO_URL", $capMissingURL, strlen($this->URL()) == 0);
        $brs->Assert("INVALID_TYPE", $capInvalidType, !in_array($this->Type(), $this->_validTypes));
        $brs->Assert("SOURCE_NOT_CAP", $capSourceMustBeCap, strpos($this->Source(), 'cap') !== 0);
        $brs->Assert("HTTP_SCHEMA_REQ", $capSchemaRequired, substr(strtolower($this->Schema()), 0, 4) != "http");
        return $brs;
    }
}
?>
