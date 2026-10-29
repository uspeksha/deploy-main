<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }"
      x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Followme') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ config('app.csp_nonce') }}">
        /* Token warna: sama dengan layout admin & navbar */
        :root {
            --sb-top: #0a3a31;
            --sb-bottom: #05201b;
            --accent: #34d399;
            --surface: #f4f7f6;
            --glow-a: rgba(52, 211, 153, .35);
            --glow-b: rgba(34, 211, 238, .22);
            --glow-c: rgba(20, 184, 166, .28);
            --dot: rgba(100, 116, 139, .16);
            --spot: rgba(16, 185, 129, .14);
        }
        .dark {
            --sb-top: #082a24;
            --sb-bottom: #031612;
            --surface: #0b1412;
            --glow-a: rgba(16, 185, 129, .22);
            --glow-b: rgba(34, 211, 238, .10);
            --glow-c: rgba(20, 184, 166, .16);
            --dot: rgba(255, 255, 255, .06);
            --spot: rgba(52, 211, 153, .10);
        }

        html { scroll-behavior: smooth; }
        body { background-color: var(--surface); transition: background-color .4s ease; }
        ::selection { background: rgba(52, 211, 153, .35); }

        /* Latar: aurora + pola titik */
        .bg-stage { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .bg-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(var(--dot) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse at 50% 30%, #000 30%, transparent 80%);
                    mask-image: radial-gradient(ellipse at 50% 30%, #000 30%, transparent 80%);
        }
        .blob { position: absolute; border-radius: 9999px; filter: blur(70px); will-change: transform; }
        .blob-a { width: 34rem; height: 34rem; top: -12rem; left: -9rem; background: var(--glow-a); animation: drift 22s ease-in-out infinite alternate; }
        .blob-b { width: 28rem; height: 28rem; top: 18%; right: -10rem; background: var(--glow-b); animation: drift 28s ease-in-out -6s infinite alternate-reverse; }
        .blob-c { width: 30rem; height: 30rem; bottom: -14rem; left: 30%; background: var(--glow-c); animation: drift 25s ease-in-out -12s infinite alternate; }
        @keyframes drift {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to   { transform: translate3d(60px, 40px, 0) scale(1.12); }
        }

        /* Spotlight yang mengikuti kursor (hanya di perangkat dengan mouse) */
        .spotlight {
            position: absolute; inset: 0; opacity: 0; transition: opacity .6s ease;
            background: radial-gradient(420px circle at var(--mx, 50%) var(--my, 30%), var(--spot), transparent 70%);
        }
        @media (hover: hover) and (pointer: fine) { .has-pointer .spotlight { opacity: 1; } }

        /* Konten tampil sekali saat halaman dibuka */
        .page-enter { position: relative; z-index: 10; animation: pageIn .6s cubic-bezier(.2, .8, .2, 1) both; }
        @keyframes pageIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        /* Fokus keyboard selalu terlihat */
        :focus-visible { outline-color: var(--accent); }

        @media (prefers-reduced-motion: reduce) {
            .blob, .page-enter { animation: none !important; }
            .spotlight { display: none; }
            html { scroll-behavior: auto; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100"
      x-data="{ pointer: false }"
      :class="{ 'has-pointer': pointer }"
      @pointermove.window.passive="
          if ($event.pointerType === 'mouse') {
              pointer = true;
              $el.style.setProperty('--mx', $event.clientX + 'px');
              $el.style.setProperty('--my', $event.clientY + 'px');
          }">

    <!-- Latar dekoratif -->
    <div class="bg-stage" aria-hidden="true">
        <div class="blob blob-a"></div>
        <div class="blob blob-b"></div>
        <div class="blob blob-c"></div>
        <div class="bg-dots"></div>
        <div class="spotlight"></div>
    </div>

    <div class="page-enter">
        {{ $slot }}
    </div>

    @stack('scripts')
</body>
</html>