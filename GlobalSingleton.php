<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("Business_Objects.php");
require_once("Captions.php");
class GlobalSingleton{
    var $_locales, $_i18ns, $_captions, $_classNames, $_actionMessages;
    var $_picDir;
    function &GetInstance(){
        static $instance=null;
        if (!$instance){
            $instance = new GlobalSingleton();
        }
        return $instance;
    }
    function __construct(){
        $this->_classNames = array( 'Movie', 
                                    'Person', 'Link',      'MovieToPerson', 'Movies', 
                                    'Post',   'BBSReport', 'User',          'Session',
                                    'Category');
        $this->_actionMessages = array( 'Viewed', 
                                        'Searched',             'ViewedNowPlaying',                 'Updated',       'UpdateFailed', 
                                        'Snarfed',              'ChangedPlayTimesLocation',         'Created',       'Deleted', 
                                        'Enabled',              'Disabled',                         'ForcedLogout',  'EmailedPassword',
                                        'FailedAuthentication', 'AuthenticatedButAccountDisabled',  'Authenticated', 'Loggedout',
                                        'ViewedNowPlayingRSS', 'ViewedNotFound', 'InvalidConfirmationCode', 'ReusedCaptcha',
                                        'ViewedNowPlayingRSS', 'Violation', 'SessionIPChanged', 'ACLViolation');
    }
    public function PictureDirectory($value=null){
        if ($value == null){
            return $this->_picDir;
        }else{
            $this->_picDir = $value;
        }
    }

    function NewObject($classID, $instanceID){
        global $gs;
        $className = $this->ClassID2Name($classID);
        $obj = new $className($instanceID);
        return $obj;
    }
    public function &GetLocales(){
        if (!isset($this->_locales)){
            $locales = new Locales();
            $locales->LoadAll();
            $this->_locales = $locales;
        }
        return $this->_locales;
    }
    public function GetText($locale, $symbol){
        if (!isset($this->_captions)){
            $this->_captions = new Captions();
            /* Cheat
            $this->_captions->LoadHash()
            */
        }
        return $this->_captions->GetText($locale, $symbol);
    }
    public function ClassID2Name($id){
        return $this->_classNames[$id];
    }
    public function Object2ID(&$obj){
        $className = get_class($obj);
        return $this->ClassName2ID($className);
    }
    function ClassName2ID($name){
        $name = strtolower($name);
        foreach($this->_classNames as $id=>$name0){
            if ($name == strtolower($name0)){
                return $id;
            }
        }
        throw new Exception("Couldn't find class ID for $name");
    }
    function ActionMsgID2String($id){
        return $this->_actionMessages[$id];
    }
    function ActionMsg2ID($msg){
        $msg = strtolower($msg);
        foreach($this->_actionMessages as $id=>$msg0){
            if ($msg == strtolower($msg0)){
                return $id;
            }
        }
        throw new Exception("Couldn't find message ID for '$msg'");
    }
}
?>
