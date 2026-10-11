<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#090735">
    <title>@yield('title') · Transolutions</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>

<body class="auth-body">
    <main class="auth-layout">
        <section class="auth-story"><a class="brand" href="{{ route('home') }}"><span class="brand-mark"><x-icon
                        name="wrench" /></span><span>TRANSOLUTIONS<small>AUTO CARE, SIMPLIFIED.</small></span></a>
            <div><span class="eyebrow">YOUR CAR. OUR CARE.</span>
                <h1>A smoother ride<br>starts here.</h1>
                <p>From your first check-up to your next journey.<br>Keep every appointment, repair, and receipt in one
                    place.</p>
                <div class="auth-illustration">
                    <div class="car-disc"><x-icon name="car" /></div><span class="floating-chip"><x-icon
                            name="check" /> In good hands</span>
                </div>
            </div><small>Automatic Transmission Overhaul · Davao City</small>
        </section>
        <section class="auth-card"><a class="back-home" href="{{ route('home') }}">← Back to home</a><x-alerts />@yield('content')</section>
    </main>
</body>

</html>
