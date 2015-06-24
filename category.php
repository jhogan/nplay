<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("header.php");
require_once("Categories.php");

main();
function main(){
    try{
        BlockIfViolation();
        global $locale;
        $PHP_SELF = $_SERVER['PHP_SELF'];
        $id = $_POST['lstCategories'];            
        if ($id == ""){
            $id = $_POST['txtID'];            
        }
        $post = $_POST['blnPost'];            
        $cat = new Category($id);
            
        if ($post){
            $desc = $_POST['txtDesc'];      $name = $_POST['txtName'];
            $enabled = $_POST['chkEnabled'];
            $cat->Description($desc);       $cat->Name($locale, $name);
            $enabled = ($enabled == '1') ? 1 : 0;
            $cat->Enabled($enabled);
            /* TODO:NICE: Redirect to previous page */
            UpdateObject($cat);
        }
        ?>
        <form name=frm method=post>
            <table border=1>
                <tr>
                    <td>
                        <?=GetCap('capDescription')?>
                    </td>
                    <td>
                        <input type=text name=txtDesc value='<?=$cat->Description()?>'>
                    </td>
                 </tr>
                 <tr>
                    <td>
                        <?=GetCap('capName')?>
                    </td>
                    <td>
                        <input type=text name=txtName value='<?=$cat->Name($locale)?>'>
                     </td>
                  </tr>
                 <tr>
                    <td>
                        <?=GetCap('capEnable')?>
                    </td>
                    <td>
                        <input type=checkbox name=chkEnabled value=1 <?=($cat->Enabled()) ? 'CHECKED' : '' ?>>
                     </td>
                  </tr>
                  <tr>
                   <td>
                        <a href=categories.php><?=GetCap('catBackToCategoryList')?></a>
                    </td>
                    <td>
                        <input type=submit value=<?=GetCap('catSave')?>>
                    </td>
                     <td>
                        <input type=hidden name=blnPost value=1>
                    </td>
                     <td> <input type=hidden name=txtID value=<?=$id?>>
                    </td>
                </tr>
                </tr>
             </table>
          </form>
        <?
    }
    catch(Exception $ex){
        ProcessException($ex);
    }
}
require_once('tailer.php');
?>

