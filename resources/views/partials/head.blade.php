<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" type="image/png" href="/assets/images/vbat_logo_simple.png">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/assets/images/vbat_logo_simple.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<script>
    (function () {
        // Default is light unless explicitly saved as 'dark'
        const theme = localStorage.getItem('theme') || localStorage.getItem('flux.appearance');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            // If nothing is set yet, explicitly ensure light mode
            if (!theme) {
                try {
                    localStorage.setItem('theme', 'light');
                    localStorage.setItem('flux.appearance', 'light');
                } catch (e) {}
            }
        }
    })();
</script>
