<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("Business_Objects.php");
require_once("GlobalSingleton.php");

class I18Ns extends Business_Collection_Base {
    var $_hash;
    var $_objRef=null;
    function __construct(){
        $this->Business_Collection_Base();
    }
    public function ObjectReference(&$ref=null){
        if($ref != null){
           @fprintf(STDOUT, "DBG9 %s/%s Setting\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
            $this->_objRef =& $ref;
        }else{
           @fprintf(STDOUT, "DBG9 %s/%s Getting\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
            if (isset($this->_objRef)){
                return $this->_objRef;
            }else{
                return null;
            }
        }
    }
    private function &GetI18N($property, $locale){
       @fprintf(STDOUT, "DBG9 %s/%s prop:%s locale:%s\n", __CLASS__, __FUNCTION__, $property, $locale);    #SED_DELETE_ON_ROLL
        foreach($this as $i18n){
           @fprintf(STDOUT, "DBG9 %s - Testing...(%s)\n", __FUNCTION__, $i18n->ToString());    #SED_DELETE_ON_ROLL
            if ($i18n->Property() == $property &&
                $i18n->LocaleID() == $locale){
               @fprintf(STDOUT, "DBG9 %s - I18N Found. Returning: '%s'\n", __FUNCTION__, $i18n->ToString());    #SED_DELETE_ON_ROLL
                return $i18n;
            }
        }
       @fprintf(STDOUT, "DBG9 %s - I18N NOT found. Returning null\n", __FUNCTION__);    #SED_DELETE_ON_ROLL
        return null;
    }
    public function SetVar($locale=null, $property, $object, $text=null, $FK){
        if ($locale == null) throw new Exception("Locale missing in " . __FUNCTION__ . " for property '$property' in class '$object'.");
        if (!is_numeric($locale)) throw new Exception("Locale ($locale) not numeric in " . __FUNCTION__ . " for property '$property' in class '$object'. (Value: '$text')");

        @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        $property = strtolower($property);
        if (is_null($text)){
           @fprintf(STDOUT, "DBG9 %s - Seaching Collection...\n", __FUNCTION__);    #SED_DELETE_ON_ROLL
            $i18n = &$this->GetI18N($property, $locale);
            if($i18n != null){
                return $i18n->Text();
            }else{
               @fprintf(STDOUT, "DBG9 %s - Text NOT found. Falling back...\n", __FUNCTION__);    #SED_DELETE_ON_ROLL
                $gs =& GlobalSingleton::GetInstance();
                $objLocales =& $gs->GetLocales();
                $objLocale =& $objLocales->LowestSequence();
               @fprintf(STDOUT, "DBG9 %s - Got lowest seq: %s.\n", __FUNCTION__, $objLocale->LocaleID());    #SED_DELETE_ON_ROLL
                do{
                    $fallbackLocale = $objLocale->LocaleID();
                    if ($fallbackLocale != $locale){
                       @fprintf(STDOUT, "DBG9 \t%s - Trying locale: %s. \n", __FUNCTION__, $fallbackLocale);    #SED_DELETE_ON_ROLL
                        $i18n = &$this->GetI18N($property, $fallbackLocale); 
                        if($i18n != null){
                            $ret =  $i18n->Text();
                           @fprintf(STDOUT, "DBG9 %s - Text Found. Returning: '%s'\n", __FUNCTION__, $ret);    #SED_DELETE_ON_ROLL
                            return $ret;
                        }
                    }
                }while($objLocale =& $objLocale->NextSequence());
               @fprintf(STDOUT, "DBG9 %s - Match NOT found. Returning empty string\n", __FUNCTION__);    #SED_DELETE_ON_ROLL
                return "";
            }
        }else{
            $gs =& GlobalSingleton::GetInstance();
            $objLocales =& $gs->GetLocales();
            $locales =& $objLocales->GetBy('LocaleID', $locale);
            if ($locales->Count() == 0){
                throw new Exception("Attempting to assign '$text' to $property for invalid locale ($locale)");
            }
           @fprintf(STDOUT, "DBG9 %s - Text given: '%s'\n", __FUNCTION__, $text);    #SED_DELETE_ON_ROLL
            $i18n = &$this->GetI18N($property, $locale);
            if ($i18n == null){
               @fprintf(STDOUT, "DBG9 %s - I18N NOT found. Adding new...\n", __FUNCTION__);    #SED_DELETE_ON_ROLL
                $i18n = new I18N();         $i18n->LocaleID($locale); 
                $i18n->Property($property); $i18n->Text($text);    
               @fprintf(STDOUT, "DBG9 %s - Adding...(%s)\n", __FUNCTION__, $i18n->ToString());    #SED_DELETE_ON_ROLL
                $this->Add(&$i18n);
            }else{
               @fprintf(STDOUT, "DBG9 %s - I18N found. Setting new val ('$text').\n", __FUNCTION__);    #SED_DELETE_ON_ROLL
                $i18n->Text($text);
            }
        }
    }
    function IsValid($bo){
        if ($i18n =& $this->GetI18NFromBO($bo)){
            return $i18n->IsValid();
        }else{
            throw new Exception("No i18n matching bo");
        }
    }
    function &GetI18NFromBO($bo, $locale){
       @fprintf(STDOUT, "DBG9 %s/%s \n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        $className = strtolower(get_class($bo));
        $FK = $bo->ID();
       @fprintf(STDOUT, "DBG9 %s/%s - Class: %s FK: %s\n", __CLASS__, __FUNCTION__, $className, $FK);    #SED_DELETE_ON_ROLL
        foreach($this as $i18n){
           @fprintf(STDOUT, "DBG9 %s/%s - Testing i18n:'(%s)'\n", __CLASS__, __FUNCTION__, $i18n->ToString());    #SED_DELETE_ON_ROLL
            if ($i18n->FK() == $FK &&
                $i18n->LocaleID() == $locale &&
                $i18n->Object() == $className){
                $methods = get_class_methods($bo);
                foreach($methods as $method){
                    $method = strtolower($method);
                   @fprintf(STDOUT, "DBG9 %s/%s - Testing methods:%s against %s'\n", __CLASS__, __FUNCTION__, $method, $i18n->Property());    #SED_DELETE_ON_ROLL
                    if ($method == $i18n->Property()){
                       @fprintf(STDOUT, "DBG9 %s/%s - Returning found i18n\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                        return $i18n;
                    }
                }
            }
        }
       @fprintf(STDOUT, "DBG9 %s/%s - Return null\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        return null;
    }
}
class I18N extends Business_Base {
    function __construct($id=null)
    {
        $this->Business_Base($id);
    }
    function LocaleID($value=null)
    {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Property($value=null)
    {
        return strtolower($this->SetVar(__FUNCTION__, $value));
    }
    function ToString(){
       @fprintf(STDOUT, "DBG9 %s/%s\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        return $this->LocaleID() . '|' . 
                $this->Property() . '|' . $this->Object() . '|' . $this->FK() .
                '||'. $this->Text() . '|' . $this->ID();

    }
    function &ObjectReference(){
       @fprintf(STDOUT, "DBG9 %s/%s\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        $collection =& $this->Collection();
        if($collection == null){
            return null;
        }else{
            return $collection->ObjectReference();
        }
    }
    function Object($value=null) {
       /* TODO:SEC: Object needs to be an integer representing an object */	
       @fprintf(STDOUT, "DBG9 %s/%s\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        if(is_null($value)){
            $obj =& $this->ObjectReference();
            if ($obj != null){
               @fprintf(STDOUT, "DBG9 %s/%s Found from reference\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                return strtolower(get_class($obj));
            }else{
               @fprintf(STDOUT, "DBG9 %s/%s Not found from reference\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                return strtolower($this->SetVar(__FUNCTION__, $value));
            }
        }else{
           @fprintf(STDOUT, "DBG9 %s/%s Setting value\n",__CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
            strtolower($this->SetVar(__FUNCTION__, $value));
        }
    }
    function FK($value=null)
    {
       @fprintf(STDOUT, "DBG9 %s/%s\n",__CLASS__, __FUNCTION__); #SED_DELETE_ON_ROLL
       if(is_null($value)){ $obj =& $this->ObjectReference();    
            if ($obj != null){
                if (!$obj->IsNew()){
                    return $obj->ID();
                }
            }
        }
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Text($value=null)
    {
        if (!is_null($value)){
            $value = str_replace('>?<', '>??<', $value);
        }
        return $this->SetVar(__FUNCTION__, $value);
    }
    function &Locale(){
            $gs =& GlobalSingleton::GetInstance();
            $locales =& $gs->GetLocales();
            $locales =& $locales->GetBy('LocaleID', $this->LocaleID());
            if ($locales->Count() > 0)
                return $locales->current();
            else
                return null;
    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('LocaleID', 'locale', 'integer', true, true, 1031, 4, 0); 
        $this->AddMap('Text', 'txt', 'text', null, true, '', 25000, null); 
        $this->AddMap('Property', 'property', 'text', null, true, '', 255, null); 
        $this->AddMap('Object', 'object', 'text', null, true, '', 255, null); 
        $this->AddMap('FK', 'fk', 'integer', true, true, 0, 4, 0); 
    }
    function Table(){
        return "i18n";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        return $brs;
    }
}
class Locales extends Business_Collection_Base {
    function &LowestSequence(){
        foreach($this as $locale){
            if ($locale->Sequence() == 0){
                return $locale;
            }
        }
        throw new Exception("No locale where sequence==0");
    }
}
class Locale extends Business_Base {
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */

        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('LocaleID', 'locale', 'integer', true, true, 1031, 4, 0); 
        $this->AddBooleanMap('Def', 'def');
        $this->AddMap('Sequence', 'sequence', 'integer', true, true, 0, 4, 0); 
    }
    function __construct($id=null) { $this->Business_Base($id); }

    function LocaleID($value=null) { return $this->SetVar(__FUNCTION__, $value); }

    function Def($value=null) { return $this->SetVar(__FUNCTION__, $value); }
    
    function Sequence($value=null) { return $this->SetVar(__FUNCTION__, $value); }
    
    function Table(){ return "locale"; }
    
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        return $brs;
    }
    function &NextSequence(){
        $collection =& $this->_collection;
        $next = $this->Sequence() + 1;
        foreach($collection as $locale){
            if ($locale->Sequence() == $next){
                return $locale;
            }
        }
        return null;
    }
}

