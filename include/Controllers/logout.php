<?php
session_start();
setcookie (session_id(), "", time() - 3600);
session_destroy();
session_write_close();
?>
<html>
<head>
    <style>
        iframe { visibility: hidden; }
    </style>
    <script>
        setTimeout(function(){
            window.location = 'https://s5.studentrnd.org/login/logout';
        }, 2000);
    </script>
</head>
<body>
    <p>Logging you out...</p>
    <iframe src="https://mail.google.com/mail/logout?hl=en"></iframe>
</body>
</html>