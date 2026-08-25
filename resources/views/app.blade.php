<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $bootBranding = \App\Support\BrandingSettings::publicPayload();
        $bootLogoUrl = $bootBranding['logo']['url'] ?? null;
        $bootFaviconUrl = $bootBranding['favicon']['url'] ?? null;
        $bootAppName = config('app.name', 'Pebbles');
        $bootPayload = [
            'appName' => $bootAppName,
            'logoUrl' => $bootLogoUrl,
            'faviconUrl' => $bootFaviconUrl,
        ];
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ ($httpStatus ?? 200) === 404 ? 'Page not found · '.$bootAppName : $bootAppName }}</title>
        @if (($httpStatus ?? 200) === 404)
            <meta name="robots" content="noindex, nofollow">
        @endif
        @if ($bootFaviconUrl)
            <link rel="icon" href="{{ $bootFaviconUrl }}">
        @endif
        @if ($bootLogoUrl)
            <link rel="preload" as="image" href="{{ $bootLogoUrl }}">
        @endif
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/main.jsx'])
        <script>
            window.__PEBBLES_BOOT__ = @json($bootPayload);
        </script>
    </head>
    <body class="antialiased" style="background-color: #fffdf8;">
        <div id="app-boot-loader" class="app-boot-loader" aria-live="polite" aria-busy="true">
            <div class="app-boot-loader-inner">
                @if ($bootLogoUrl)
                    <img
                        src="{{ $bootLogoUrl }}"
                        alt=""
                        class="app-boot-loader-logo"
                        width="160"
                        height="48"
                        decoding="async"
                    >
                @else
                    <div class="app-boot-loader-mark" aria-hidden="true">
                        <span class="app-boot-loader-mark-letter">{{ strtoupper(substr($bootAppName, 0, 1)) }}</span>
                    </div>
                    <p class="app-boot-loader-text">{{ $bootAppName }}</p>
                @endif
            </div>
        </div>
        <div id="root"></div>
    </body>
</html>
