<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("Business_Objects.php");
class BBSReports extends Business_Collection_Base{
    function TypeCount($type){
        $i=0;
        foreach ($this as $rpt){
            if ($rpt->Type() == $type) $i++;
        }
        return $i;
    }
    function SpamCount(){
        return $this->TypeCount(REPORT_TYPE_SPAM);
    }
    function AbuseCount(){
        return $this->TypeCount(REPORT_TYPE_ABUSE);
    }
}
class BBSReport extends Business_Base {
	function __construct($id=null){
        $this->Business_Base($id);
        $this->DateReported(time());
	}

    function PostID($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function UserID($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function &User(){
        return new User($this->UserID());
    }
    function Comments($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function Type($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function DateReported($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }
    function ResolvedState($value=null) {
        return $this->SetVar(__FUNCTION__, $value);
    }

    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddAutoincrementIntegerID();
        $this->AddIntegerMap('PostID', 'postID');
        $this->AddIntegerMap('UserID', 'userID');
        $this->AddTextMap('Comments', 'comments');
        $this->AddTimestampMap('DateReported', 'dateReported');
        $this->AddIntegerMap('Type', 'type');

        $this->AddIndex('ResolvedState', 'resolvedState, asc, 0'); 
        $this->AddIntegerMap('ResolvedState', 'resolvedState'); 
    }
    function Table(){
        return "BBSReport";
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        return $brs;
    }
}
?>
