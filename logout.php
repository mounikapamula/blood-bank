<?php
/* logout.php — end the session and return to home */
session_start();

/* 1. Clear all session variables */
$_SESSION = [];

/* 2. If you use session cookies, delete the cookie on the browser */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,          // set cookie expiry in the past
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* 3. Finally, destroy the session on the server */
session_destroy();

/* 4. Redirect back to the public home page */
header("Location: index.php");
exit();
?>
