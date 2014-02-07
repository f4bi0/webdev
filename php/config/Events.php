<?php

require_once(dirname(__FILE__).'/../class.DbTracker.php');
Events::$db = DbTracker::get();