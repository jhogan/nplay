<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
class Pictures{
    var $_parent, $_prevDir='';
    var $_dirtyDir=false;
    function __construct($parent){
        $this->_parent =& $parent;
    }
    function ParentType(){
        return strtolower(get_class($this->_parent));
    }
    function MarkDirNameDirty(){
        if (!$this->_dirtyDir && $this->_prevDir == ""){
            if (!$this->_parent->IsNew()){
                $this->_prevDir = $this->PictureDirectory();
                $this->_dirtyDir = true;
            }
        }
    }
    public function BasePictureDirectory(){
        $gs =& GlobalSingleton::GetInstance();
        $dir = $gs->PictureDirectory();
        if (trim($dir) == "")
            throw new Exception("Picture Directory not found");
        return $dir;
    }
    function PictureDirectory(){
        $parType = $this->ParentType();
        if ($parType == 'movie'){
            $title = strtolower($this->_parent->Title());
            $baseSubDir = 'movies';
        }elseif ($parType == 'person'){
            $title = strtolower($this->_parent->Name());
            $baseSubDir = 'persons';
        }else{
            throw new Exception("Parent type '$parType' not supported");
        }

        if ($title != ""){
            $title = str_replace(' ', '-', $title);
            $initial = substr($title, 0, 1);
            $baseDir = $this->BasePictureDirectory();
            return "$baseDir/$baseSubDir/$initial/$title/";
        }else{
            return "";
        }
    }
    function MainPicture(){
        $dir = $this->PictureDirectory(); 
        if (trim($dir) != "")
            return $this->PictureDirectory() . 'main.jpg';
        return "";
    }
    function SnarfMainPicture($url){
        //TODO: Use curl when depedencies are met
        if (trim($url) == ''){
            return;
        }
        $this->MakePictureDirectory();
        if (file_exists($this->MainPicture())){
            if ( ! unlink($this->MainPicture())){
                throw new Exception("Failed to delete existing pic");
            }
        }
        $args = "$url -O " . $this->MainPicture();
        $args = escapeshellcmd($args);
        $cmd = "wget $args";
        system($cmd, $retVal);
        if ($retVal != 0){
            throw new Exception("Snarf failed; errno:$retVal");
        }
        $args = escapeshellcmd($this->MainPicture());
        $cmd = "touch $args";
        system($cmd, $retVal);
        if ($retVal != 0){
            throw new Exception("Touch failed; errno:$retVal");
        }
    }
    function MakePictureDirectory(){
        $picDir = $this->PictureDirectory();
        if ($picDir == "") return false;        
        if ($this->_prevDir == ""){
            if (! file_exists($picDir)){
                $ret = mkdir($picDir, 0755, true);
                if (!$ret){
                    throw new Exception("Couldn't create picture directory: $picDir");
                }
            }
        }else{
            if (! file_exists($picDir)){
                $ret = mkdir($picDir, 0755, true);
                if (!$ret){
                    throw new Exception("Couldn't create picture directory: $picDir");
                }
            }
            if (! rename ($this->_prevDir, $picDir)){
                throw new Exception("failed: rename($this->_prevDir, $picDir)");
            }
        }
        return true;
    }
}
?>
