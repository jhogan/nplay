<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("Business_Objects.php");
require_once("Categories.php");

class MovieToCategories extends Business_Collection_Base {
    var $_excludeOnUpdate;
    function AddCategory(&$movie, &$category){
        @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        $movieToCategory = new MovieToCategory();
        $movieToCategory->Movie($movie);
        $movieToCategory->Category($category);
        $movieToCategory->_collection = &$this;
        $this->_collection[] = $movieToCategory;
    }
    function ExcludeOnUpdate($bo=null){
        if($bo == null){
            return strtolower($this->_excludeOnUpdate);
        }else{
            $this->_excludeOnUpdate = get_class($bo);
        }
    }
}
class MovieToCategory extends Business_Base {
    var $_category;
    var $_movie;
    function BO($id=null)
    {
        $this->Business_Base($id);
    }
    function &Movie(&$value=null){
        if ($value == null){
            return $this->_movie;
        }else{
            $this->_movie = &$value;
        }
    }
    function Category(&$value=null){
       @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        if ($value == null){
            if (!isset($this->_category)){
               @fprintf(STDOUT, "DBG9 %s/%sLoading Category id:%s\n", __CLASS__, __FUNCTION__, $this->CategoryID());    #SED_DELETE_ON_ROLL
                $this->_category = 
                    new Category($this->CategoryID());
            }
            return $this->_category;
        }else{
            $this->_category = &$value;
        }
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
    function CategoryID($value=null)
    {
        if ($value == null && isset($this->_category)){
            if(!$this->_category->IsNew()){
                return $this->_category->ID();
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

        $this->AddIndex('MovieID', 'movieID, asc, 0');
        $this->AddMap('MovieID', 'movieID', 'integer', true, true, 0, 4, 0); 

        $this->AddMap('CategoryID', 'categoryID', 'integer', true, true, 0, 4, 0); 
    }
    function Table(){
        return "movie_to_category";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        return $brs;
    }
    function Update(){
       @fprintf(STDOUT, "DBG9 %s/%s\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
        $col = $this->Collection();
        if ($col->ExcludeOnUpdate() != 'movie'){
            if (isset($this->_movie)){
               @fprintf(STDOUT, "DBG9 %s/%s Updating movie\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                $this->_movie->Update();
            }
        }
        if ($col->ExcludeOnUpdate() != 'category'){
            if (isset($this->_category)){
               @fprintf(STDOUT, "DBG9 %s/%s Updating category\n", __CLASS__, __FUNCTION__);    #SED_DELETE_ON_ROLL
                $this->_category->Update();
            }
        }
        parent::Update();
    }
}
?>
