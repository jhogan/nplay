<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("Business_Objects.php");

class Captions extends Business_Collection_Base {
    var $_hash;
    function &LoadHash(){
        unset($this->_hash); 
        $rows =& $this->GetAllRows();
        foreach($rows as $row){
            $key = $row['locale'] . '|' . $row['symbol'];
            $this->_hash[$key] = $row['text_'];
        }
        return $this->_hash;
    }
    function &Hash(){
        return $this->_hash;
    }
    function GetText($locale, $symbol){
        @fprintf(STDOUT, "DBG9 %s/%s\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        //return $this->_hash[$locale . '|' . $symbol];
        /* CHEATE */
        $symbol = substr($symbol, 3);
        return preg_replace('/([a-z])([A-Z])/',
                                '\1 \2',
                                 $symbol);
    }
    function &AddCaption($local, $symbol, $text){
        printf("DBG9 %s/%s\n",__CLASS__, __FUNCTION__);
        if ($ret = $this->GetText($local, $symbol) == ''){
            printf("DBG9 %s/%s GetText returns ''\n",__CLASS__, __FUNCTION__);
            $cap = new Caption();
            $cap->Locale($local); $cap->Symbol($symbol);
            $cap->Text($text); 
            $this->Add($cap);
            return $cap;
        }else{
            printf("DBG9 %s/%s GetText returns $ret\n",__CLASS__, __FUNCTION__);
            print_r($ret);
            return null;
        }
    }
    function Update(){
        printf("DBG9 %s/%s\n",__CLASS__, __FUNCTION__);
        if ($this->IsValid()){
            printf("DBG9 %s/%s Valid\n",__CLASS__, __FUNCTION__);
            foreach($this as $cap){
                printf("DBG9 %s/%s Testing\n",__CLASS__, __FUNCTION__, $cap->ToString());
                if ($cap->IsNew()){
                    printf("DBG9 %s/%s New\n",__CLASS__, __FUNCTION__);
                    $this->AddCaptionToHash($cap);
                }elseif($cap->IsDirty()){
                    printf("DBG9 %s/%s Dirty\n",__CLASS__, __FUNCTION__);
                    $this->EditCaptionToHash($cap);
                }
                $cap->Update();
                $this->Remove($cap);
            }
        }
    }
    function AddCaptionToHash(&$cap){
        printf("DBG9 %s/%s\n",__CLASS__, __FUNCTION__);
        $key = $this->GetKey($cap);
        $text = $this->_hash[$key];
        if ($text == ''){
            $this->_hash[$key] = $cap->Text();
        }else{
            throw new Exception("Tried to add existing caption: $key");
        }
    }
    function EditCaptionToHash(&$cap){
        printf("DBG9 %s/%s\n",__CLASS__, __FUNCTION__);
        $key = $this->GetKey($cap);
        $this->_hash[$key] = $cap->Text();
    }
    function GetKey(&$cap){
        return $cap->Locale() . '|' . $cap->Symbol();
    }
}
class Caption extends Business_Base {
    function __construct($id=null)
    {
        $this->Business_Base($id);
    }
    function Locale($value=null)
    {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Symbol($value=null)
    {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Text($value=null)
    {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function ToString(){
        return $this->Locale() . ', ' + $this->Symbol() + ', ' + $this->Text();
    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('Locale', 'locale', 'integer', true, true, 0, 4, 0); 
        $this->AddMap('Symbol', 'symbol', 'text', null, true, '', 50, null); 
        $this->AddMap('Text', 'text_', 'text', null, true, '', 255, null); 
    }
    function Table(){
        return "caption";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        $brs->Assert('SYMBOL_START_WITH_PIPE', 
                        'Symbols can\'t start with pipes', 
                           substr($this->Symbol(), 0, 1) == '|');
        return $brs;
    }
}
?>
