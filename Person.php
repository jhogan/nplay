<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
/* TODO:NICE: Add a "New Person" link closer to the main form to make accessing it easier */
require_once("Business_Objects.php");
require_once("Pictures.php");
class Persons extends Business_Collection_Base{
}
class Person extends Business_Base {
    var $_links, $_i18ns, $_pictures;
	function __construct($id=null){
        $this->_i18ns = new I18Ns();
        $this->_i18ns->ObjectReference($this); // Must happen before SetVar
        $this->Business_Base($id);
        $this->_pictures = new Pictures($this);
	}
    function FirstName($value=null) { 
        if ($value != null) $this->_pictures->MarkDirNameDirty();
        return $this->SetVar(__FUNCTION__, $value); 
    }
    function LastName($value=null) { 
        if ($value != null) $this->_pictures->MarkDirNameDirty(); // Must happen before SetVar
        return $this->SetVar(__FUNCTION__, $value); 
    }
    function Born($value=null) { return $this->SetVar(__FUNCTION__, $value); }
    function Bio($locale=null, $value=null) {
        return $this->_i18ns->SetVar($locale, __FUNCTION__, __CLASS__, $value, $this->ID());
    }

    function Name($value=null){
        if ($value == null){
            return trim($this->FirstName() . ' ' . $this->LastName());
        }else{
            $nameArr = preg_split('/\s+/', trim($value));
            if (count($nameArr) != 2){
                throw new Exception('Two names are supported');
            }
            $this->FirstName($nameArr[0]);
            $this->LastName($nameArr[1]);
            return $this->Name();
        }
    }
    public function &Links(){
        if (!isset($this->_links)){
            $this->_links = new Links($this);
        }
        return $this->_links;
    }

    public function &OtherSiteLinks(){
        $ret = new Links();
        $links =& $this->Links();
        foreach($links as $link){
            if($link->Type() == 'o'){
                $ret->Add( $link);
            }
        }
        return $ret;
    }
    function BirthDate($value=null){
        return $this->SetVar(__FUNCTION__, $value); 
    }
    function PictureDirectory(){
        return $this->_pictures->PictureDirectory();
    }
    function MainPicture(){
        return $this->_pictures->MainPicture();
    }
    function SnarfMainPicture($url){
        return $this->_pictures->SnarfMainPicture($url);
    }

    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('FirstName', 'firstName', 'text', null, true, '', 50, 0); 
        $this->AddMap('LastName', 'lastName', 'text', null, true, '', 50, 0); 
        $this->AddDateMap('BirthDate', 'birthDay');
        $this->AddTextMap('Born', 'born');
    }
    function ToString(){
        return $this->Name();
    }
    function Table(){
        return "person";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        $gs =& GlobalSingleton::GetInstance();
        if ($this->IsNew()){
            $per = new Person("firstName = '" . $this->FirstName() . "' AND " . "lastName = '" . $this->LastName() . "'");
        }else{
            $per = new Person("id != " . $this->ID() . " AND firstName = '" . $this->FirstName() . "' AND " . "lastName = '" . $this->LastName() . "'");
        }
        if (!$per->IsEmpty()){
            $capThisPersonAleadyExistsInTheDatabase = $gs->GetText($locale, 'capThisPersonAleadyExistsInTheDatabase');
            $brs->Add("DUPLICATE_PERSON", $capThisPersonAleadyExistsInTheDatabase);
        }
        return $brs;
    }
    function &DiscoverChildren(){
        $this->AddChild(&$this->_i18ns, 'FK');
    }
    function Update(){
        $this->_pictures->MakePictureDirectory(); 
        parent::Update();
    }
}
?>
