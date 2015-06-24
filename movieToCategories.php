<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("header.php");

main();
function main(){
    try{
        BlockIfViolation();
        global $locale;
        $PHP_SELF = $_SERVER['PHP_SELF'];
        $post = $_POST['blnPost'];            
        $id = $_GET['id'];
        $movID = $_GET['movID'];
        $mov = new Movie($movID);
        $movToCats =& $mov->MovieToCategories();
        $cats = new Categories();
        $cats->LoadAll();
	$cats->Sort("Description");
        $movID = $mov->ID();

        print("<h3><a href=movie.php?id=$movID>".$mov->Title()."</a></h3>\n");
            
        if ($post){
            $adds = array(); $deletes = array();
            $proposedCats = $_POST['lstCategories'];
            foreach($proposedCats as $proposedCat){
                $found = false;
                foreach($movToCats as $movToCat){
                    if ($movToCat->CategoryID() == $proposedCat){
                        $found = true;
                    }
                }
                if (!$found){
                    $proposedMovToCat = new MovieToCategory();
                    $proposedMovToCat->MovieID($movID);
                    $proposedMovToCat->CategoryID($proposedCat);
                    $adds[] = $proposedMovToCat;
                }
            }
            foreach($movToCats as $movToCat){
                $found = false;
                foreach($proposedCats as $proposedCat){
                    if ($proposedCat == $movToCat->CategoryID())
                        $found = true;
                }
                if (!$found){
                    $deletes[] = $movToCat;
                }
            }
            foreach($adds as $add) $movToCats->Add($add);
            foreach($deletes as $delete) $delete->MarkForDeletion();
            /* TODO:NICE: This should redirect on save to previous page */
            UpdateObject($movToCats);
        }
        ?>
        <form name=frm method=post action=<?=$PHP_SELF . "?id=$id&movID=$movID"?>>
            <table border=1>
                <tr>
                    <td>
                        <select name="lstCategories[]" multiple=true>
                        <?
                            foreach($cats as $cat){
                                $selectTag = '';
                                foreach($movToCats as $movToCat){
                                    if ($cat->ID() == $movToCat->CategoryID()){
                                        $selectTag = 'SELECTED';
                                        break;
                                    }
                                }
                                ?>
                                <option value=<?=$cat->ID()?> <?=$selectTag?> ><?=$cat->Name($locale)?></option>
                                <?
                            }
                        ?>
                        </select>
                     </td>
                     <td>
                        <input type=hidden name=blnPost value=1>
                     </td>
                </tr>
                <tr>
                     <td>
                        <input type=submit value=Submit>
                     </td>
                </tr>
                <tr>
                     <td>
                        <a href=categories.php> <?=GetCap("capEditCategoryList")?> </a>
                     </td>
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

