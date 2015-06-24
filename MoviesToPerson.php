<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("Business_Objects.php");
require_once("Person.php");

class MovieToPersons extends Business_Collection_Base {
    var $_excludeOnUpdate;
    function AddPerson(&$movie, &$person, $relationship, $charName){
       @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        $movieToPerson = new MovieToPerson();
        $movieToPerson->Movie(&$movie);
        $movieToPerson->Person(&$person);
        $movieToPerson->Relationship($relationship);
        $movieToPerson->CharacterName($charName);
        $movieToPerson->_collection = &$this;
        $this->_collection[] = $movieToPerson;
    }
    function ExcludeOnUpdate($bo=null){
        if($bo == null){
            return strtolower($this->_excludeOnUpdate);
        }else{
            $this->_excludeOnUpdate = get_class($bo);
        }
    }
}
class MovieToPerson extends Business_Base {
    var $_person;
    var $_movie;
    function &Movie(&$value=null){
       @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        if ($value == null){
            if (!isset($this->_movie)){
               @fprintf(STDOUT, "DBG9 %s/%sLoading Movie id:%s\n", __CLASS__, __FUNCTION__, $this->MovieID());    #SED_DELETE_ON_ROLL
                $this->_movie = new Movie($this->MovieID());
            }
            return $this->_movie;
        }else{
            $this->_movie = $value;
        }
    }
    function Person(&$value=null){
       @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        if ($value == null){
            if (!isset($this->_person)){
               @fprintf(STDOUT, "DBG9 %s/%sLoading Person id:%s\n", __CLASS__, __FUNCTION__, $this->PersonID());    #SED_DELETE_ON_ROLL
                $this->_person = new Person($this->PersonID());
            }
            return $this->_person;
        }else{
            $this->_person = $value;
        }
    }
    function ToString($format='HTML'){
        $per =& $this->Person();
        $mov =& $this->Movie();
        $ret = $mov->ToString() . ' -> ' . $per->ToString();
        if ($this->Relationship() == 's'){
            $charName = $this->CharacterName();
            if ($charName != ""){
                $gs =& GlobalSingleton::GetInstance();
                $capAs = $gs->GetText($locale, 'capAs');
                $ret .= " $capAs $charName";
            }
        }
        return $ret;
    }
    function MovieID($value=null)
    {
        if ($value == null && isset($this->_movie)){
            if(!$this->_movie->IsNew()){
                return $this->_movie->ID();
            }
        }
        return $this->SetVar(__FUNCTION__, $value);
    }
    function PersonID($value=null)
    {
        if ($value == null && isset($this->_person)){
            if(!$this->_person->IsNew()){
                return $this->_person->ID();
            }
        }
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Relationship($value=null)
    {
        return strtolower($this->SetVar(__FUNCTION__, $value));
    }
    function CharacterName($value=null)
    {
        return $this->SetVar(__FUNCTION__, $value);

    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 

        $this->AddIndex('MovieID', 'movieID, asc, 0');
        $this->AddMap('MovieID', 'movieID', 'integer', true, true, 0, 4, 0); 

        $this->AddIndex('PersonID', 'personID, asc, 0');
        $this->AddMap('PersonID', 'personID', 'integer', true, true, 0, 4, 0); 

        $this->AddMap('Relationship', 'relationship', 'text', null, true, '', 50, 0); 
        $this->AddMap('CharacterName', 'characterName', 'text', null, true, '', 50, 0); 
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        $gs =& GlobalSingleton::GetInstance();
        $capThisPersonIsAlreadyAssociatedToThisMovie = $gs->GetText($locale, 'capThisPersonIsAlreadyAssociatedToThisMovie');
        $capActorMustHaveCharacterName = $gs->GetText($locale, 'capActorMustHaveCharacterName');
        $capNoPerson = $gs->GetText($locale, 'capNoPerson');
        $capNoMovie = $gs->GetText($locale, 'capNoMovie');
        if ($this->IsNew()){
            /*TODO:BUG: Single quotes need to be escaped */
            $m2p = new MovieToPerson("movieID = " . $this->MovieID() . " AND " . 
                                        "personID = " . $this->PersonID() . " AND " .
                                        "relationship = '" . $this->Relationship() . "' AND " .
                                        "((relationship <> 's') OR (relationship = 's' AND characterName = '" . $this->CharacterName() . "'))" );
            if (!$m2p->IsEmpty()){
                $brs->Add("DUPLICATE_ASSOCIATION", $capThisPersonIsAlreadyAssociatedToThisMovie);
            }
        }
        $brs->Assert('NO_ACTOR', $this->PersonID() == "");
        $brs->Assert('NO_MOVIE', $this->MovieID() == "");
        $brs->Assert('NO_CHAR_NAME', $capActorMustHaveCharacterName, $this->Relationship() == 's' && $this->CharacterName() == "");
        return $brs;
    }
    function Table(){
        return "movie_to_person";
    }
    function Update($excludeOnUpdate=null){
       @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        if ($excludeOnUpdate==null){
            $col = $this->Collection();
            if ($col != null){
                $excludeOnUpdate = $col->ExcludeOnUpdate();
            }
        }
        if ($excludeOnUpdate != 'movie'){
            if (isset($this->_movie)){
               @fprintf(STDOUT, "DBG9 %s/%s Updating movie\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                $this->_movie->Update();
            }
        }
        if ($excludeOnUpdate != 'person'){
            if (isset($this->_person)){
               @fprintf(STDOUT, "DBG9 %s/%s Updating person\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                $this->_person->Update();
            }
        }
        parent::Update();
    }
}
?>
