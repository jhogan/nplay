<?php
    // vim: set et ts=4 sw=4 fdm=marker:
    declare(ticks = 1);
    pcntl_signal(SIGTERM, 'exit_handler');
    pcntl_signal(SIGINT, 'exit_handler');
    define("FIFO_NAME", 'fifo/act');

    $dir = opendir("../");
    while (($file = readdir($dir)) !== false) {
        if (preg_match("/^[A-Z].*\.php/", $file)){
            $file = "../$file";
            require_once($file);
        }
    }
    require_once("../Actions.php");
    require_once("../../phpbo/Business_Objects.php");

    $bom =& Business_Objects_Manager::getInstance();
    $bom->addDSN("mysql://xxxx:xxxx@localhost/test", "default");
    /* Manager dsn can't have a db */
    $bom->addDSN("mysql://xxxx:xxxx@localhost", "manager");
    $actFifo = new ActionFifo();
    while(true){
        $id = $actFifo->Read();
        $act = new Action($id);
        // echo "\x07"; //BEEP
        echo $act->ToString() . "\n";
    }

    function exit_handler($sig){
        echo "Caught signal: $sig\n";
        exit(0);
    }
    
    class ActionFifo{
        var $_fifo, $_fifoPath;
        function __construct(){
            $fifoPath = FIFO_NAME;
            if (!file_exists($fifoPath)){
                throw new Exception("Fifo '$fifoPath' doesn't exist");
            }
        }
        function Fifo($value=null){
            if ($value==null){
                return $this->_fifo;
            }else{
                $this->_fifo = $value;
            }
        }
        function Open(){
            $fifoPath = FIFO_NAME;
            echo "Listening on fifo $fifoPath\n";
            $fifo = fopen($fifoPath, 'r');
            $this->Fifo($fifo);
        }
        function Read(){
            $fifo = $this->Fifo();
            if (!isset($fifo)){
                $this->Open();
                $fifo = $this->Fifo();
            }
            while (1){
                $ch = fgetc($fifo);
                if ($ch == ";"){
                    return $data;
                }elseif ($ch == ""){
                    sleep(1);
                }else{
                    $data .= $ch;
                }
            }
        }
    }
?>
