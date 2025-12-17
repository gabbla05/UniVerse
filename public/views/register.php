<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="public/css/style.css">
    <script type="text/javascript" src="./public/js/validation.js" defer></script>
    <title>REJESTRACJA</title>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <form action="register" method="POST">
                <div class="messages">
                    <?php if(isset($messages)){
                        foreach($messages as $message) {
                            echo $message;
                        }
                    }
                    ?>
                </div>
                <input name="email" type="text" placeholder="email@email.com">
                <input name="password" type="password" placeholder="Hasło">
                <input name="confirmedPassword" type="password" placeholder="Powtórz hasło">
                <input name="name" type="text" placeholder="Imię">
                <input name="surname" type="text" placeholder="Nazwisko">
                <button type="submit">ZAREJESTRUJ SIĘ</button>
            </form>
        </div>
    </div>
</body>
</html>