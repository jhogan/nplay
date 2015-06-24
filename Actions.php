<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("GlobalSingleton.php");
require_once("Users.php");
require_once("Business_Objects.php");

class Actions extends Business_Collection_Base {
}
class Action extends Business_Base {
    function __construct($id=null){
        $this->Business_Base($id);
        if ($this->IsNew()){
            $this->TimeStamp(time());
        }
}
    function SessionID($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function ClassID($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function InstanceID($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function MsgID($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function Object($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function TimeStamp($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function UserID($value=null){ return $this->SetVar(__FUNCTION__, $value); }

    function HTTPReferer($value=null){
        if($value!=null){
            $HTTPRefArray = explode('/', $value);
            $host = $HTTPRefArray[2];
            if ($host == $_SERVER['SERVER_NAME'] ||
                $host == $_SERVER['SERVER_ADDR']){
                $httpRef = "";
            }else{
                $httpRef = $value;
            }
        }
        return $this->SetVar(__FUNCTION__, $httpRef);
    }

    function DiscoverTableDefinition(){
        $this->AddAutoincrementIntegerID();
        $this->AddIntegerMap('SessionID', 'sessionID');
        $this->AddIntegerMap('ClassID', 'classID');
        $this->AddIntegerMap('InstanceID', 'instanceID');
        $this->AddIntegerMap('MsgID', 'msgID');
        $this->AddTextMap('HTTPReferer', 'HTTPReferer', 2083);
        $this->AddTimestampMap('TimeStamp', 'timestamp');
        $this->AddIntegerMap('UserID', 'userID');
        $this->AddTextMap('Object', 'object');
    }
    function Table(){
        return "action";
    }
    function Msg(){
        $gs =& GlobalSingleton::GetInstance();
        return $gs->ActionMsgID2String($this->MsgID());
    }
    function &User(){
        if ($this->UserID() != 0){
            if (!isset($this->_user)){
                $this->_user = new User($this->UserID());
            }
        }else{
            $this->_user = null;
        }
        return $this->_user;
    }
    function ToString(){
        $gs =& GlobalSingleton::GetInstance();
        $user =& $this->User();
        if ($user != null)
            $username = $user->Username();
        $sesID = $this->SessionID();
        $msg = $this->Msg();
        $id = $this->InstanceID();
        if ($id > 0){
            $directObject =& $gs->NewObject($this->ClassID(), $this->InstanceID());
            $directObject = $directObject->ToString();
        }
        $obj = $this->Object();

        if ($user == null)
            $subject = "[$sesID]";
        else
            $subject = $username . " [$sesID]";

        $verb = $msg;
        $prepPhrase = "for $obj";
        $ret .= str_pad($subject, '15') . ' ';
        $ret .= str_pad($verb, 15) . ' ';
        if (isset($directObject)){
            $ret .= str_pad($directObject, 15) . ' ';
        }
        if ($obj != ''){
            $ret .= $prepPhrase;
        }

        return $ret;
    }
    function Update(){
        $isNew = $this->IsNew();
        parent::Update();
        if ($isNew){
            if (file_exists($fifoPath = 'admin/fifo/act')){
                $fifo = fopen($fifoPath, 'w');
                fwrite($fifo, $this->ID() . ';');
                fclose($fifo);
            }
        }
    }
}
?>
