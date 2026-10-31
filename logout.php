<?php
session_start();
session_unset();
session_destroy();
header("Location: login.html?logged_out=1");
exit();
?>