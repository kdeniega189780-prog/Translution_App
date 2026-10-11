<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#090735">
    <meta name="description" content="Transolutions Automatic Transmission Overhaul in Davao City. Request a check-up and follow your vehicle’s care from diagnosis to handover.">
    <title>Transolutions · A smoother ride starts here</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="landing-body">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="landing-nav landing-container">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><x-icon name="wrench" /></span><span>TRANSOLUTIONS<small>AUTO CARE, SIMPLIFIED.</small></span></a>
        <nav aria-label="Main navigation"><a href="#services">Our services</a><a href="#how-it-works">How it works</a>
            @auth <a class="button primary" href="{{ route('dashboard') }}">My workspace <x-icon name="arrow" /></a>
            @else <a class="button primary" href="{{ route('login') }}">Sign in <x-icon name="arrow" /></a> @endauth
        </nav>
    </header>
    <main id="main">
        <section class="landing-hero landing-container">
            <div><span class="landing-kicker"><span class="dot green"></span> YOUR CAR. OUR CARE. · DAVAO CITY</span>
                <h1>A smoother ride<br>starts with <em>better care.</em></h1>
                <p>From a transmission check-up to your everyday maintenance, keep your car’s next chapter running smoothly with Transolutions.</p>
                <div class="landing-actions"><a class="button primary" href="{{ route('bookings.create') }}">Book a check-up <x-icon name="arrow" /></a><a class="button" href="#services">Explore our services</a></div>
                <div class="landing-reassurance"><span><x-icon name="check" /> Clear repair updates</span><span><x-icon name="check" /> Your approval comes first</span></div>
            </div>
            <div class="landing-visual" aria-label="Vehicle care from check-up to handover">
                <div class="landing-orbit"></div><div class="landing-car"><x-icon name="car" /></div>
                <div class="care-chip care-chip-top"><span class="stat-icon green"><x-icon name="wrench" /></span><span><strong>A little care.</strong><small>A smoother journey.</small></span></div>
                <div class="care-chip care-chip-bottom"><span class="stat-icon lavender"><x-icon name="calendar" /></span><span><strong>Your next check-up</strong><small>One simple place to get started.</small></span></div>
                <span class="visual-caption">AUTOMATIC TRANSMISSION & EVERYDAY AUTO CARE</span>
            </div>
        </section>
        <section class="landing-services landing-container" id="services">
            <div class="landing-section-heading"><div><span class="eyebrow">CARE FOR EVERY MILE</span><h2>Good care. From the inside out.</h2></div><p>Transmission expertise and everyday services<br>for the road ahead.</p></div>
            <div class="landing-service-grid">
                <article class="card"><span class="stat-icon green"><x-icon name="wrench" /></span><h3>Transmission care</h3><p>Automatic transmission inspection, overhaul, and rebuild services.</p><span class="eyebrow">SMOOTHER SHIFTS</span></article>
                <article class="card"><span class="stat-icon blue"><x-icon name="car" /></span><h3>Maintenance & repair</h3><p>Engine troubleshooting, oil changes, brake repair, and tire rotation.</p><span class="eyebrow">READY FOR THE EVERYDAY</span></article>
                <article class="card"><span class="stat-icon lavender"><x-icon name="check" /></span><h3>Comfort & check-ups</h3><p>Aircon repair, vehicle inspections, and detailing to care for your drive.</p><span class="eyebrow">FEEL THE DIFFERENCE</span></article>
            </div>
        </section>
        <section class="landing-process landing-container" id="how-it-works">
            <span class="eyebrow">LESS GUESSWORK. MORE PEACE OF MIND.</span><h2>Your car’s care, made simple.</h2>
            <div class="landing-steps"><article><span>01</span><h3>Request a check-up</h3><p>Create an account, add your vehicle, and choose a preferred time. Our team will confirm your schedule.</p></article><article><span>02</span><h3>Review the diagnosis</h3><p>See the mechanic’s findings and approve the proposed repair before work continues.</p></article><article><span>03</span><h3>Follow the progress</h3><p>Track your repair, know when your vehicle is ready, and keep your service history in one place.</p></article></div>
        </section>
        <section class="landing-cta landing-container"><div><span class="eyebrow">LET’S KEEP YOU MOVING</span><h2>A little care goes a long way.</h2><p>Your next journey starts with your next check-up.</p></div><a class="button primary" href="{{ route(auth()->check() ? 'dashboard' : 'register') }}">{{ auth()->check() ? 'Open my workspace' : 'Create your account' }} <x-icon name="arrow" /></a></section>
    </main>
    <footer class="landing-footer landing-container"><span><strong>TRANSOLUTIONS</strong><br>Automatic Transmission Overhaul · Davao City, Philippines</span><span>Already have an account? <a href="{{ route('login') }}">Sign in</a></span></footer>
</body>
</html>
