<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("header.php");
require_once("MoviesToPerson.php");
require_once("Movies.php");

main();
function main(){
    try{
        BlockIfViolation();
        global $locale;
        global $returnURI;
        $PHP_SELF = $_SERVER['PHP_SELF'];
        $post = $_POST['blnPost'];            
        $id = $_GET['id'];
        $movID = $_GET['movID'];
        $relationship = $_GET['relationship'];
        $m2p = new MovieToPerson($id);
        if (!$m2p->IsNew()){
            $mov =& $m2p->Movie();
            $person =& $m2p->Person();
        }else{
            $m2p->Relationship($relationship);
            $person = new Person();
            if ($movID == "" ) throw new Exception("Missing movID");
            $mov = new Movie($movID);
        }
        $movID = $mov->ID();
        $persons = new Persons();
        $persons->LoadAll();
        $persons->Sort("FirstName");
        print("<h3><a href=movie.php?id=$movID>".$mov->Title()."</a></h3>\n");

        $characterName = $m2p->CharacterName();
        $relationship = $m2p->Relationship();
            
        if ($post){
            if ($_POST['btnDelete'] != ''){
                $m2p->MarkForDeletion();
                $characterName = $relationship = '';
            }else{
                $person = new Person($_POST['cboPersons']);
                $m2p->Person($person);
                $m2p->PersonID($person->ID());
                $m2p->Movie($mov);
                $m2p->CharacterName($_POST['txtCharacterName']);
                $m2p->Relationship($_POST['txtRelationship']);
                $characterName = $m2p->CharacterName();
                $relationship = $m2p->Relationship();
            }
            if (UpdateObject($m2p)){
                ReturnURI();
            }
        }
        $firstName = $person->FirstName();
        $lastName = $person->LastName();
        ?>
        <form name=frm method=post action=<?=$PHP_SELF . "?id=$id&movID=$movID&returnURI=$returnURI"?>>
            <table border=1>
                <tr>
                    <td>
                        <?
                            print ("<select name=cboPersons>\n");
                            foreach($persons as $person){
                                $name = $person->Name();
                                $id = $person->ID();
                                if ($id == $m2p->PersonID()){
                                    $selectTag = 'SELECTED';
                                }else{
                                    $selectTag = '';
                                }
                            ?>
                                <option value=<?=$id?> <?=$selectTag?>><?=$name?></option>
                            <?
                            }
                            print("</select>\n");
                        ?>
                    </td>
                </tr>
                <?
                if ($relationship == 's'){
                ?>
                    <tr>
                        <td><?=GetCap('capCharacterName')?></td>
                        <td><input type=text name=txtCharacterName value='<?=$characterName?>'></td>
                    </tr>
                <?
                }
                ?>
                <tr>
                    <td><?=GetCap('capRelationship')?></td>
                    <td><input type=text name=txtRelationship value=<?=$relationship?>></td>
                </tr>
                <tr>
                    <td> <input type=submit name=btnSubmit value=<?=GetCap('capSubmit')?>> </td>
                    <td> <a href=<?="$PHP_SELF?movID=$movID"?>><?=GetCap('capNew')?></a>
                </tr>
                <input type=hidden name=blnPost value=1>
            </table>
         </form>
        <?
    }
    catch(Exception $ex){
        ProcessException($ex);
    }
    require_once('tailer.php');
}
?>

