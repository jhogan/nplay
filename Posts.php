<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("Business_Objects.php");
require_once("Users.php");
class Posts extends Business_Collection_Base{
    function __construct($parent=null){
        $this->Business_Collection_Base();
        if ($parent != null){
            $className = strtolower(get_class($parent));
            if ($className == 'movie'){
                if (!$parent->IsNew()){
                    $id = $parent->ID();
                    $this->LoadWhere("movieID = $id and parentID = 0");
                }
            }
        }
	}
}
class Post extends Business_Base {
    var $_posts;
	function __construct($id=null){
        $this->Business_Base($id);
        if ($id == null){
            $this->DatePosted(time());
        }
	}

    function Text($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }

    function AbuseReportCount($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function DatePosted($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Username($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function ParentID($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function MovieID($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function ViewCount($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function Subject($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function Disabled($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function RecursiveDisabled($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    public function EnableRecursivly(){
        $this->RecursiveDisabled(false);
        $children =& $this->ChildPosts();
        foreach($children as $child){
            $child->EnableRecursivly();
        }
    }
    public function DisableRecursivly(){
        $this->RecursiveDisabled(true);
        $children =& $this->ChildPosts();
        foreach($children as $child){
            $child->DisableRecursivly();
        }
    }
    public function &ChildPosts(){
        $posts = new Posts();
        $children =& $this->Posts();
        foreach($children as $child){
            if ($child->ID() == $this->ID()) continue;
            $posts->Add($child);
        }
        return $posts;
    }
    public function &User(){
        return new User($this->Username());
    }
    public function &Movie(){
        return new Movie($this->MovieID());
    }
    public function &Parent(){
        if ($this->IsParent()){
            return $this;
        }else{
            return new Post($this->ParentID());
        }
    }
    public function IsParent(){
        return ($this->ParentID() == 0);
    }
    public function IsTopic(){
        return $this->IsParent();
    }
    public function &Posts(){
        if (! isset($this->_posts)) {
            $this->_posts = new Posts(); 
            $this->_posts->_collection[] = $this;
            if ($this->HasCollection()){
                $col =& $this->Collection();
                foreach($col as $post){
                    if ($post->ParentID() == $this->ID()){
                        $this->_posts->Add($post);
                    }
                }
            }else{
                $this->_posts->LoadBy('parentID', $this->ID());
            }
        }
        return $this->_posts;
    }
    public function Update(){
        parent::Update();
        $children =& $this->ChildPosts();
        foreach($children as $child){
            $child->Update();
        }
    }

    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 
        $this->AddMap('Text', 'txt', 'text', null, true, '', 3000, 0); 
        $this->AddMap('Subject', 'subject', 'text', null, false, '', 255, 0); 
        $this->AddMap('AbuseReportCount', 'abuseReportCount', 'integer', true, true, 0, 4, 0); 
        $this->AddTimestampMap('DatePosted', 'datePosted');
        $this->AddMap('Username', 'username', 'text', null, true, '', 50, null); 
        
        $this->AddIndex('ParentID', 'parentID, asc, 0');
        $this->AddMap('ParentID', 'parentID', 'integer', true, true, 0, 4, 0); 


        $this->AddIndex('MovieID', 'movieID, asc, 0');
        $this->AddMap('MovieID', 'movieID', 'integer', true, true, 0, 4, 0); 

        $this->AddMap('ViewCount', 'viewCount', 'integer', true, true, 0, 4, 0); 
        $this->AddBooleanMap('Disabled', 'disabled');
        $this->AddBooleanMap('RecursiveDisabled', 'recursiveDisabled');
    }
    function ToString(){
        if ($this->IsTopic()){
            return "Topic: ". $this->Subject();
        }else{
            return "Post: " . substr($this->Text(), 0, 15) . '...';
        }
    }
    function Table(){
        return "post";
    }
}
?>
