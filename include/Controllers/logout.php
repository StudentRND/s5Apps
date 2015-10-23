<?php session_start();$_SESSION=[];session_destroy(); ?>
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