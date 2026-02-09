<?php
// Clear all sessions and cookies
session_start();
session_destroy();

// Clear cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
    setcookie(session_name(), '', time()-3600, '/', '.osrg.lol');
}

echo "Sessions and cookies cleared. <a href='login'>Try logging in again</a>";
?>