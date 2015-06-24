<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("Actions.php");
require_once("Sessions.php");
require_once("GlobalSingleton.php");
require_once("lib/rfc2822.php");

class Users extends Business_Collection_Base {
}
class User extends Business_Base {
    var $_currentSessions;
    var $_actions;
    function __construct($id=null){
        $this->Business_Base($id);
	}
    function Username($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function Password($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function Level($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function Email($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function Enabled($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function PlayTimesLocation($value=null){ return $this->SetVar(__FUNCTION__, $value); }
    function Info($value=null){ return $this->SetVar(__FUNCTION__, $value); }

    function &CurrentSessions(){
        if (!isset($this->_currentSessions)){
            $this->_currentSessions = new Sessions('loggedIn = 1 and userID = ' . $this->ID());
        }
        return $this->_currentSessions;
    }
    function LoggedIn(){
        $curSessions =& $this->CurrentSessions();
        return $curSessions->LoggedIn();
    }
    function Logout(){
        $curSessions =& $this->CurrentSessions();
        foreach($curSessions as $ses){
            if ($ses->PlayTimesLocation() == ""){
                $ses->PlayTimesLocation($this->PlayTimesLocation());
            }
        }
        $curSessions->Logout();
        $curSessions->Update();
    }
    function EmailPassword($msg, $sub, $from, $x_mailer){
        $to = $this->Email();
        $headers = "From: $from" . "\r\n" .
                "Reply-To: $from" . "\r\n" .
                "X-Mailer: $x_mailer";

        if ( ! mail($to, $sub, $msg, $headers) ){
            $id=$this->ID();
            throw new Exception("Email error sending to $to ($id)");
        }
    }
    function IsDataAdmin(){
    	return ($this->Level() ==1);
    }
    function IsAdmin(){
    	return ($this->Level() ==2);
    }
    function IsUser(){
    	return ($this->Level() ==0);
    }
    function IsMaintainer(){
        return ($this->IsDataAdmin() || $this->IsAdmin());
    }
    function &Actions(){
        if (! isset($this->_actions)){
            $this->_actions = new Actions('userid = ' . $this->ID());
        }
        return $this->_actions;
    }
    function &_BrokenRules(){
        $brs = new Broken_Rules();
        $gs =& GlobalSingleton::GetInstance();
        $capUsernameMissing = $gs->GetText($locale, 'capUsernameMissing');
        $capPasswordMissing = $gs->GetText($locale, 'capPasswordMissing'); 
        $capInvalidEmail = $gs->GetText($locale, 'capInvalidEmail'); 
        $capUserAlreadyExists = $gs->GetText($locale, 'capUserAlreadyExists'); 
        $capUsernameContainsInvalidChars = $gs->GetText($locale, 'capUsernameContainsInvalidChars'); 

        $brs->Assert("NAME_MISSING", $capUsernameMissing, strlen($this->Username()) == 0);
        /* Todo:BUG: European characters should be allowed (André won't work) */
        if (!eregi('^[a-z0-9 ]+$', $this->Username())){
            $brs->Add("NAME_INVALID", $capUsernameContainsInvalidChars);
        }
        $brs->Assert("PASSWORD_MISSING", $capPasswordMissing, strlen($this->Password()) == 0);
        if ($this->Email() != ''){
            $brs->Assert("INVALID_EMAIL", $capInvalidEmail, !is_valid_email_address($this->Email()));
        }
        
        if ($this->IsNew()){
            $user = new User("username = '" . $this->Username() . "'");
            $brs->Assert("USER_EXISTS", $capUserAlreadyExists, !$user->IsEmpty());
        }
        return $brs;
    }
    function ToString(){
        return $this->Username();
    }
    function DiscoverTableDefinition(){
        /*
        AddMap( $method,   $field,   $type,   $unsigned, 
                $not_null, $default, $length, $autoincrement);
        */
        $gs =& GlobalSingleton::GetInstance();
        $this->AddMap('ID', 'id', 'integer', true, true, 0, 4, 1); 

        $this->AddIndex('Username', 'userName, asc, 0');
        $this->AddMap('Username', 'userName', 'text', null, true, '', 40, null); 

        $this->AddMap('Password', 'password', 'text', null, true, '', 100, null); 
        $this->AddMap('Level', 'level', 'integer', true, true, 0, 4, 0); 
        $this->AddMap('Email', 'email', 'text', null, true, '', 100, null); 
        $this->AddBooleanMap('Enabled', 'enabled', null, true);
        $this->AddTextMap('PlayTimesLocation', 'playTimesLocation', 50);
        /* TODO:BUG: Seems like gs should have current locale as data member */
        $capLocation = $gs->GetText(1033, 'capLocation'); 
        $capOccupation = $gs->GetText(1033, 'capOccupation');
        $capInterests = $gs->GetText(1033, 'capInterests');
        $capBio = $gs->GetText(1033, 'capBio');
        $defalut="URL: \nAOL: \nIM: \nICQ: \nYahoo Messenger: \nMSNMessenger: \n";
        $defalut.="$capLocation: \n$capOccupation: \n$capInterests: \n";
        $defalut.="$capBio: ";
        $this->AddTextMap('Info', 'info', 5000, true, $defalut);
    }
    function Table(){
        return "user";
    }
}
?>
