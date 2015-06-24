<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("conf/kvp.php");
require_once("I18N.php");

    $bom =& Business_Objects_Manager::getInstance();
    $bom->addDSN($DEF_DSN, "default");
    $bom->addDSN($MAN_DSN, "manager");
    $l = new locale();
    $l->LocaleId(1033);
    $l->Def(true);
    $l->Sequence(0);
    $l->Update();

    $l = new locale();
    $l->LocaleID(1041);
    $l->Def(false);
    $l->Sequence(1);
    $l->Update();

    $l = new locale();
    $l->LocaleID(3082);
    $l->Def(false);
    $l->Sequence(2);
    $l->Update();

?>

