<?php

// logout page for dotgne

session_start();
$_SESSION = array();
session_destroy();

header('Location: '.getenv('BASE_URL'));

?>