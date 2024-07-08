<!-- resources/views/scraping.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Scraping Log</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{mix('resources/css/app.css')}}">
</head>
<body>
    <h1>Scraping Log</h1>
    <div id="log-container"></div>

    <script type="module" src="{{ mix('resources/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            if (window.Echo) {
                window.Echo.channel('scraping-log')
                    .listen('ScrapingLog', (e) => {
                        let logContainer = document.getElementById('log-container');
                        let logMessage = document.createElement('p');
                        logMessage.textContent = e.message;

                        if(e.message.includes('Brand-URL'))
                            logMessage.classList.add('brand')
                        if(e.message.includes('Brand-Cluster'))
                            logMessage.classList.add('cluster')
                        if(e.message.includes('Collected Card'))
                            logMessage.classList.add('card')

                        logContainer.appendChild(logMessage);

                        window.scrollTo(0, document.body.scrollHeight);
                    });
            } else {
                console.error('Echo is not initialized');
            }
        });
    </script>
</body>
</html>