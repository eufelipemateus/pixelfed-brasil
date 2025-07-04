<!DOCTYPE html>
<html>
<head>
    <title>Estamos cheios 😔</title>
    <meta http-equiv="refresh" content="300">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let countdown = 300; // 5 minutes in seconds
            const timerElement = document.getElementById("timer");

            function updateTimer() {
                const minutes = Math.floor(countdown / 60);
                const seconds = countdown % 60;
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                if (countdown > 0) {
                    countdown--;
                    setTimeout(updateTimer, 1000);
                }
            }

            updateTimer();
        });
    </script>
</head>
<body style="text-align:center; padding: 40px;">
    <div style="display: flex; justify-content: center; align-items: center; height: 90vh; flex-direction: column;">
        <img src="{{ asset('/img/pixelfed-icon-color.svg') }}" alt="Logo Pixelfed" >
        <h1>😓 O sistema está com lotação máxima.</h1>
        <p>Por favor, aguarde alguns minutos e tente novamente.</p>
        <p>Tempo para recarregar a página: <span id="timer">5:00</span></p>
    </div>
</body>
</html>
