</body>
</html>        
<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
try{
    global $session;
    ?>
    <br/>
    <b>(C) 2008 Small Talkies</b>
    <br/><b><?=GetCap('capPoweredBy')?></b>:
    <a href="http://en.wikipedia.org/wiki/FOSS"><?=GetCap('capFreeOpenSourceSoftware')?></a>
    (<a href="http://en.wikipedia.org/wiki/Linux">Linux</a> 
    <a href="http://apache.org">Apache</a> 
    <a href="http://mysql.com">MySql</a> 
    <a href="http://php.net">PHP</a>)
    <br/>

    <?

    dump(false);
    UpdateObject($session, false);
}
catch (Exception $ex){
    ProcessException($ex);
}
?>

