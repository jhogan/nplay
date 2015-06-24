<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
define('DELIMITER', '||');
class ExceptionLogs implements Iterator{
    var $_fileName;
    var $_collection=array();
    function __construct($fileName){
        $this->_fileName = $fileName;
    }
    public function rewind() {
        reset($this->_collection);
    }

    public function &current() {
        return current($this->_collection);
    }

    public function key() {
        return key($this->_collection);
    }

    public function &next() {
        return next($this->_collection);
    }

    public function valid() {
        return $this->current() !== false;
    }
    function Add(&$exLog){
        $this->_collection[] = $bo;    
    }
    function Load(){
        $fp = fopen($this->_fileName, 'r');
        if ($fp == false) throw new Exception("Can't open exception log");
        while (($ch = fgetc($fp)) !== false){
            if ($ch == "\n"){
                print("$row\n\n");

                $ex = new ExceptionLog($row);
                $this->_collection[] = $ex;
                $row='';
            }else{
                print($ch);
                $row .= $ch;
            }
        }
        fclose($fp);
    }
}
class ExceptionLog{
    var $_ex, $_fileName, $_id;
    var $_session, $_date, $_msg;
    var $_code, $_file, $_line, $_trace;

    function __construct($ex, $fileName=null, $session=null){
        if (is_string($ex)){
            $exArr = explode(DELIMITER, $ex);
            $this->_id = $exArr[0];
            $this->_session = $exArr[1];
            $this->_date = $exArr[2];
            $this->_msg = $exArr[3];
            $this->_code = $exArr[4];
            $this->_file = $exArr[5];
            $this->_line = $exArr[6];
            $this->_trace = $exArr[7];
        }else{
            $this->_ex = $ex; $this->_fileName = $fileName;
            $this->_date = date("Y-m-d H:i:s", time());
            $this->_session = $session;
        }
    }
    function FileName(){
        return $this->_fileName;
    }

    function ID(){
        if (!isset($this->_id))
            $this->_id = md5(uniqid(rand(), true));
        return $this->_id;
    }
    function Session(){
        return $this->_session;
    }
    function Date(){
        return $this->_date;
    }
    function Message(){
        if (!isset($this->_msg)){
            if (is_object($this->_ex)){
                $this->_msg = $this->_ex->getMessage();
            }
        }
        return $this->_msg;
    }
    function Code(){
        if (!isset($this->_code)){
            if (is_object($this->_ex)){
                $this->_code = $this->_ex->getCode();
            }
        }
        return $this->_code;
    }
    function File(){
        if (!isset($this->_file)){
            if (is_object($this->_ex)){
                $this->_file = $this->_ex->getFile();
            }
        }
        return $this->_file;
    }
    function Line(){
        if (!isset($this->_line)){
            if (is_object($this->_ex)){
                $this->_line = $this->_ex->getLine();
            }
        }
        return $this->_line;
    }
    function Trace(){
        if (!isset($this->_trace)){
            if (is_object($this->_ex)){
                $this->_trace = $this->_ex->getTraceAsString();
            }
        }
        return $this->_trace;
    }

    function ToString($formated=0){
        if ($formated == 0 ){ 
            $ret = $this->ID() . DELIMITER .
                    $this->Session() . DELIMITER .
                    $this->Date() . DELIMITER .
                    $this->Message() . DELIMITER .
                    $this->Code() . DELIMITER .
                    $this->File() . DELIMITER .
                    $this->Line() . DELIMITER .
                    $this->Trace(); 
            $ret = str_replace("\n", '\n', $ret);
        }elseif ($formated==1){
            $ret .= 'ID: ' . $this->ID() . "<br>" ;
            $ret .= 'Session: ' . $this->Session() . "<br>" ;
            $ret .= 'Date: ' . $this->Date() . "<br>" ;
            $ret .= 'Message: ' . htmlspecialchars($this->Message()) . "<br>" ;
            $ret .= 'Code: ' . $this->Code() . "<br>" ;
            $ret .= 'File: ' . $this->File() . "<br>" ;
            $ret .= 'Line: ' . $this->Line() . "<br>" ;
            $ret .= 'Trace: ' . '<br>';
            $traces = explode("\n", $this->Trace());
            foreach($traces as $trace){
                $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;$trace<br>";
            }
        }elseif ($formated==2){
            $ret .= 'ID: ' . $this->ID() . "\n" ;
            $ret .= 'Session: ' . $this->Session() . "\n" ;
            $ret .= 'Date: ' . $this->Date() . "\n" ;
            $ret .= 'Message: ' . $this->Message() . "\n" ;
            $ret .= 'Code: ' . $this->Code() . "\n" ;
            $ret .= 'File: ' . $this->File() . "\n" ;
            $ret .= 'Line: ' . $this->Line() . "\n" ;
            $ret .= 'Trace: ' . "\n";
            $traces = explode('\n', $this->Trace());
            foreach($traces as $trace){
                $ret .= "\t\t\t\t$trace\n";
            }
         }
        return $ret;
    }
    function Row(){
        return $this->ToString();
    }
    function Save(){
        /* This file may need to be created manually and given the proper permissions or
            this call will fail*/
        $ret = file_put_contents($this->FileName(), $this->Row() . "\n", FILE_APPEND | LOCK_EX);
        if (!$ret)
            throw new Exception("Couldn't write exception to error log. This may be a permission problem.");
    }


}
?>
