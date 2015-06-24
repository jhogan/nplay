<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("Actions.php");
require_once("Users.php");
require_once("GlobalSingleton.php");

class Sessions extends Business_Collection_Base {
    function Logout(){
        foreach($this as $ses){
            $ses->SetLoggedOut();
        }
    }
    function LoggedIn(){
        foreach($this as $ses){
            if ($ses->LoggedIn()) return true;
        }
        return false;
    }
}
class Session extends Business_Base {
    var $_actions;
    var $_user;
    var $_gs;

    function __construct($id=null){
        $this->Business_Base($id);
        $this->_gs =& GlobalSingleton::GetInstance();
	}
    function SessionIPChanged($className, $id, $object){
        $msgID = $this->_gs->ActionMsg2ID('SessionIPChanged');
        $this->AddAction($className, $id, $msgID, $object);
    }

    function SetLoggedOut(){
        $this->LoggedIn(false);
        $this->UserID(0);
        unset($this->_user);
    }
    function InvalidConfirmationCode($code){
        $msgID = $this->_gs->ActionMsg2ID('InvalidConfirmationCode');
        $this->AddAction("Session", 0, $msgID);
    }
    function Loggedout($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('LoggedOut');
        $this->AddAction($className, $id, $msgID);
    }
    function EmailedPassword($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('EmailedPassword');
        $this->AddAction($className, $id, $msgID);
    }
    function ACLViolation($object){
        $msgID = $this->_gs->ActionMsg2ID('ACLViolation');
        $this->AddAction('Session', $this->ID(), $msgID, $object);
    }
    function Violation($object){
        $msgID = $this->_gs->ActionMsg2ID('Violation');
        $this->AddAction('Session', $this->ID(), $msgID, $object);
    }
    function FailedAuthentication($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('FailedAuthentication');
        $this->AddAction($className, $id, $msgID);
    }
    function AuthenticatedButAccountDisabled($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('AuthenticatedButAccountDisabled');
        $this->AddAction($className, $id, $msgID);
    }
    function Authenticated($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('Authenticated');
        $this->AddAction($className, $id, $msgID);
    }
    function ForcedLogout($id){
        $msgID = $this->_gs->ActionMsg2ID('ForcedLogout');
        $this->AddAction("Session", $id, $msgID);
    }
    function Disabled($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('Disabled');
        $this->AddAction($className, $id, $msgID);
    }
    function Enabled($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('Enabled');
        $this->AddAction($className, $id, $msgID);
    }
    function Deleted($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('Deleted');
        $this->AddAction($className, $id, $msgID);
    }
    function Created($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('Created');
        $this->AddAction($className, $id, $msgID);
    }
    function ReusedCaptcha($className, $id, $object){
        $msgID = $this->_gs->ActionMsg2ID('ReusedCaptcha');
        $this->AddAction($className, $id, $msgID, $object);
    }
    function ChangedPlayTimesLocation($className, $id, $object){
        $msgID = $this->_gs->ActionMsg2ID('ChangedPlayTimesLocation');
        $this->AddAction($className, $id, $msgID, $object);
    }
    function UpdateFailed($className, $id, $brokenRuleString){
        $msgID = $this->_gs->ActionMsg2ID('UpdateFailed');
        $this->AddAction($className, $id, $msgID, $brokenRuleString);
    }
    function Snarfed($className, $id, $object){
        $msgID = $this->_gs->ActionMsg2ID('Snarfed');
        $this->AddAction($className, $id, $msgID, $object);
    }
    function Updated($className, $id){
        $msgID = $this->_gs->ActionMsg2ID('Updated');
        $this->AddAction($className, $id, $msgID);
    }
    function Viewed($className, $id=null, $msgID=null){
        if (is_null($id)) return;
        if ($msgID == null){
            $msgID = $this->_gs->ActionMsg2ID('Viewed');
        }
        $this->AddAction($className, $id, $msgID);
    }
    function ViewedNotFound($className, $subject){
        $msgID = $this->_gs->ActionMsg2ID('ViewedNotFound');
        $this->AddAction($className, 0, $msgID, $subject);
    }
    function Searched($className, $object){
        $msgID = $this->_gs->ActionMsg2ID('Searched');
        $this->AddAction($className, 0, $msgId, $object);
    }
    function AddAction($className, $id, $msgId, $object=""){
        $classID = $this->_gs->ClassName2ID($className);
        $acts =& $this->Actions();

        $httpRef = $_SERVER['HTTP_REFERER'];

        $act = new Action();
        $act->SessionID($this->ID());
        $act->ClassID($classID);
        $act->InstanceID($id);
        $act->MsgID($msgId);
        $act->HTTPReferer($httpRef);
        $act->UserID($this->UserID());
        $act->Object($object);

        $acts->Add($act);
    }
    function &Actions(){
        if (! isset($this->_actions)){
            $this->_actions = new Actions();
        }
        return $this->_actions;

    }
    function SessionID($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function PlayTimesLocation($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function UserAgent($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function IPAddress($value=null){
        /* Use (Get|Set)IPAddress to work with normal x.x.x.x ip address formats */
        return $this->SetVar(__FUNCTION__, $value);
    }
    function SetIPAddress($value){
        $this->IPAddress(ip2long($value));
    }
    function GetIPAddress(){
        return long2ip($this->IPAddress());
    }
    function IsTextBased(){
        if (stripos($this->UserAgent(), 'Links (0') !== false) return true;
        if (stripos($this->UserAgent(), 'Links (1') !== false) return true;
        if (    stripos($this->UserAgent(), 'Links (2') !== false && 
                stripos($this->UserAgent(), '; x')      === false) return true;
        if (stripos($this->UserAgent(), 'lynx')  !== false) return true;
        if (stripos($this->UserAgent(), 'w3m')   !== false) return true;
        return false;
    }
    function &User(&$value=null){
        if ($value==null){
            if ($this->UserID() != 0){
                if (!isset($this->_user)){
                    $this->_user = new User($this->UserID());
                }
            }
            return $this->_user;
        }else{
            $this->_user = $value;
        }
    }
    function UserID($value=null){
        if ($value==null){
            if (isset($this->_user)){
                $ret = $this->_user->ID();
                if (is_null($ret) || $ret == ''){
                    return 0;
                }else{
                    return $ret;
                }
            }else{
                return $this->SetVar(__FUNCTION__, $value);
            }
        }else{
            $this->SetVar(__FUNCTION__, $value);
        }
    }
    function LoggedIn($value=null){
        return $this->SetVar(__FUNCTION__, $value);
    }
    function IsAnonymous($value=null){
        return ($this->User() == null);
    }
    function Update(){
        $acts =& $this->Actions();
        $acts->Update();
        parent::Update();
    }
    /*
    function &_BusinessRules(){
    	$brs = new BrokenRules();
	$acts =& $this->Actions();
	$actsbrs =& $acts->BrokenRules();
	$actsbrs->ToString();
	$brs->Append($actsbrs);
	return $brs;
    }
    */
    function ToString(){
    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $this->AddAutoincrementIntegerID();
        $this->AddTextMap('SessionID', 'sessionID');
        $this->AddMap('IPAddress', 'ipAddress', 'integer', false, true, 0, 4, 0); 
        $this->AddIntegerMap('UserID', 'userID');
        $this->AddBooleanMap('LoggedIn', 'loggedIn');
        $this->AddTextMap('PlayTimesLocation', 'playTimesLocation');
        $this->AddTextMap('UserAgent', 'userAgent');

        $this->AddIndex("sessionID", "sessionID, asc, 0");
        $this->AddIndex("loggedIn_sessionID", "loggedIn, asc, 0; sessionID, asc, 0");
    }
    function Table(){
        return "session";
    }
}
?>
