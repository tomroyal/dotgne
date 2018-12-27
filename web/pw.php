<?php

// temp get pass hash for testing
// TODO remove this when done

$pass = $_GET['p'];

$newpw = password_hash($pass,PASSWORD_DEFAULT);

echo($newpw);

?>