<?php
session_start();
session_destroy();
header('Location: /Attachment/index.php');
exit;
