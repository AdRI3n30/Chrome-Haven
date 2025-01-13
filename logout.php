<?php
    session_start();
    session_destroy();
    header("Location: /chrome-haven/index.php");
    exit();
?>
