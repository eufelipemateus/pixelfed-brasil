<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Em manutenção</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }

        .container {
            padding: 20px;
        }

        .logo {
            max-width: 220px;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        p {
            font-size: 18px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('/img/pixelfed-icon-color.svg') }}" alt="Logo Pixelfed" >
        <h1>Voltamos já!</h1>
        <p>Estamos fazendo uma manutenção rápida.</p>
        <p>Tente novamente em alguns instantes.</p>
    </div>
</body>
</html>
