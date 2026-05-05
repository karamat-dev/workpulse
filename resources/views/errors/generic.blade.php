<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $status }} - {{ config('app.name', 'muSharp') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('uploads/logo/favicon.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background: #f6f8fb;
                color: #152033;
            }

            .error-shell {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 32px 18px;
                box-sizing: border-box;
            }

            .error-panel {
                width: min(100%, 520px);
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                box-shadow: 0 18px 48px rgba(15, 23, 42, .08);
                padding: 30px;
            }

            .error-code {
                color: #dc2626;
                font-size: 13px;
                font-weight: 800;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            h1 {
                margin: 10px 0 8px;
                font-size: 28px;
                line-height: 1.15;
            }

            p {
                margin: 0;
                color: #64748b;
                line-height: 1.6;
            }

            .error-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-top: 24px;
            }

            .error-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 40px;
                padding: 0 15px;
                border-radius: 6px;
                border: 1px solid #cbd5e1;
                color: #152033;
                font-size: 14px;
                font-weight: 700;
                text-decoration: none;
                background: #ffffff;
            }

            .error-button.primary {
                border-color: #dc2626;
                background: #dc2626;
                color: #ffffff;
            }
        </style>
    </head>
    <body>
        <main class="error-shell">
            <section class="error-panel" role="alert" aria-labelledby="error-title">
                <div class="error-code">Error {{ $status }}</div>
                <h1 id="error-title">We could not complete that request.</h1>
                <p>{{ $message }}</p>
                <div class="error-actions">
                    <a class="error-button primary" href="{{ url('/musharp') }}">Go to Workpulse</a>
                    <a class="error-button" href="{{ url()->previous() }}">Go back</a>
                </div>
            </section>
        </main>
    </body>
</html>
