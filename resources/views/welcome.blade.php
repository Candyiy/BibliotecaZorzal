<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#F4EFE5">

        <title>{{ config('app.name', 'ZORZEL') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <div class="z-public-shell">
            <header class="z-public-nav">
                <a href="{{ route('home') }}" class="flex items-center gap-3 no-underline" wire:navigate>
                    <span class="z-brand-mark">
                        <x-z.icon name="book-open" class="size-4" />
                    </span>
                    <span class="z-wordmark">
                        ZORZEL
                        <span class="z-wordmark-caption">Biblioteca digital</span>
                    </span>
                </a>

                <nav class="flex items-center gap-2" aria-label="Navegación pública">
                    @auth
                        <a href="{{ route('libros.index') }}" wire:navigate class="btn btn-secondary btn-sm">Biblioteca</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" wire:navigate class="btn btn-ghost btn-sm">Iniciar sesión</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" wire:navigate class="btn btn-primary btn-sm">Crear cuenta</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="z-public-main">
                <div class="z-public-hero">
                    <div class="z-public-copy">
                        <h1 class="z-public-title">Biblioteca<br>con alma Zorzal</h1>
                        <!--
                        <p class="z-public-lede">
                            Un catálogo para encontrar títulos, consultar licencias y leer en PDF.
                            ZORZEL junta la biblioteca y la suscripción en un mismo lugar.
                        </p>
                        -->

                        <div class="z-public-actions">
                            @auth
                                <a href="{{ route('libros.index') }}" wire:navigate class="btn btn-primary">
                                    Entrar a la biblioteca
                                    <x-z.icon name="arrow-right" class="size-4" />
                                </a>
                            @else
                                <a href="{{ route('login') }}" wire:navigate class="btn btn-primary">
                                    Entrar a la biblioteca
                                    <x-z.icon name="arrow-right" class="size-4" />
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" wire:navigate class="btn btn-secondary">Crear cuenta</a>
                                @endif
                            @endauth
                        </div>

                        <!--
                        <p class="z-public-note">
                            <x-z.icon name="library" class="size-4" />
                            Catálogo · Licencias · Lectura PDF
                        </p> -->
                    </div>

                    <aside class="z-showcase" aria-label="Una vista del catálogo">
                        <div class="z-showcase-head">
                            <div>
                                <h2 class="z-card-title">El estante</h2>
                                <p class="z-card-note">Una fila del catálogo, lista para abrir.</p>
                            </div>
                        </div>

                        <div class="z-shelf" aria-hidden="true">
                            @foreach ([62, 84, 48, 92, 70, 100, 56, 78] as $altura)
                                <span class="z-spine" style="--z-altura: {{ $altura }}%"></span>
                            @endforeach
                        </div>
                    </aside>
                </div>
            </main>

            <section class="z-public-rates" aria-labelledby="planes-publicos">
                <div class="z-rates-inner">
                    <div class="z-rates-head">
                        <div>
                            <h2 id="planes-publicos" class="z-page-title">Tarifas de la biblioteca</h2>
                            <!--
                            <p class="z-page-subtitle">Elige el plazo de acceso y vuelve cuando quieras.</p>
                            -->
                        </div>
                    </div>

                    <div class="z-rates-grid">
                        @forelse ($planes as $plan)
                            @php $promocion = $plan->promocionActiva(); @endphp
                            <article class="z-rate-card">
                                <header class="z-rate-card-head">
                                    <span class="z-rate-mark" aria-hidden="true">
                                        <x-z.icon name="calendar" class="size-4" />
                                    </span>
                                    <div>
                                        <h3 class="z-rate-name">{{ ucfirst($plan->tipo) }}</h3>
                                        <p class="z-rate-term">{{ $plan->duracionEtiqueta() }} · {{ $plan->modalidadEtiqueta() }} · {{ $plan->coberturaEtiqueta() }}</p>
                                    </div>
                                </header>

                                <div class="z-rate-card-body">
                                    @if ($promocion)
                                        <p class="z-rate-promo">
                                            <x-z.icon name="spark" class="size-3.5" />
                                            <span>{{ $promocion->motivo }} · − {{ $promocion->etiquetaDescuento() }}</span>
                                        </p>
                                    @endif

                                    <p class="z-rate-price">
                                        @if ($promocion)
                                            <span class="z-rate-was">{{ number_format((float) $plan->precio, 2, ',', '.') }}</span>
                                        @endif
                                        <span class="z-rate-now">{{ number_format($plan->precioFinal(), 2, ',', '.') }}</span>
                                    </p>
                                    <p class="z-rate-period">{{ $plan->esAnual() ? 'por año' : 'por mes' }}</p>

                                    @if ($plan->tienePrueba())
                                        <p class="z-rate-promo">
                                            <x-z.icon name="spark" class="size-3.5" />
                                            <span>{{ $plan->trial_dias }} días de prueba</span>
                                        </p>
                                    @endif

                                    @foreach ($plan->beneficiosLista() as $beneficio)
                                        <p class="z-hint">{{ $beneficio }}</p>
                                    @endforeach
                                </div>

                                <footer class="z-rate-card-foot">
                                    @auth
                                        <a href="{{ route('planes.index') }}" wire:navigate class="btn btn-secondary z-rate-cta">
                                            Elegir este plan
                                            <x-z.icon name="arrow-right" class="size-4" />
                                        </a>
                                    @else
                                        <a href="{{ route('register') }}" wire:navigate class="btn btn-primary z-rate-cta">
                                            Suscribirme
                                            <x-z.icon name="arrow-right" class="size-4" />
                                        </a>
                                    @endauth
                                </footer>
                            </article>
                        @empty
                            <p class="z-rate-empty">Todavía no hay planes publicados. Vuelve pronto para conocer las opciones de suscripción.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <footer class="z-public-footer">
                <div class="z-public-footer-inner">
                    <div>
                        <span class="z-wordmark">ZORZEL</span>
                        <span class="z-public-footer-note">Biblioteca virtual de suscripción</span>
                    </div>
                    <p>Catálogo · Licencias · Lectura PDF</p>
                </div>
            </footer>
        </div>
    </body>
</html>
