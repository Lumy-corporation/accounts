<?php
// accounts/logout.php
session_start();
session_unset();
session_destroy();

header('Location: /accounts/index.php');
exit;