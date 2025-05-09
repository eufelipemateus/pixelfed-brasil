<!DOCTYPE html>
<html>
<head>
    <title>We Are Full 😔</title>
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
        <img src="{{ asset('/img/pixelfed-icon-color.svg') }}" alt="Pixelfed Logo" >
        <h1>😓 The system is at full capacity.</h1>
        <p>Please wait a few minutes and try again.</p>
        <p>Time to reload the page: <span id="timer">5:00</span></p>
    </div>
</body>
</html>
