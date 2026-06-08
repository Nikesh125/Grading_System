<?php
session_start();
session_unset();
session_destroy();

// Route cleanly back to the system core portal structure
header("Location: index.html?status=loggedout");
exit();
?>