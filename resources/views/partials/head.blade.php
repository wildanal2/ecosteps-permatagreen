<!-- BASIC META -->
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<!-- SEO META -->
<meta name="description" content="Hanya dengan menghitung dan mencatat langkah harian, PermataBankers dapat berkontribusi dalam menyelamatkan gajah Sumatra di Bukit Tigapuluh." />
<meta name="author" content="PermataBank CSR" />
<meta name="keywords" content="permata bank, csr, penghijauan, langkah hijau, emisi karbon, lingkungan, sustainability, pohon" />
<meta name="robots" content="index, follow" />
<meta name="language" content="id" />
<meta name="theme-color" content="#0061FE" />

<title>{{ $title ?? 'Move for Elephants' }}</title>

<!-- FAVICONS -->
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#0061FE">
<meta name="msapplication-TileColor" content="#0061FE" />
<meta name="msapplication-TileImage" content="{{ asset('mstile-144x144.png') }}" />

<!-- OPEN GRAPH / FACEBOOK -->
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:title" content='Ikuti Gerakan "Move for Elephants"' />
<meta property="og:description" content="Hanya dengan menghitung dan mencatat langkah harian, PermataBankers dapat berkontribusi dalam menyelamatkan gajah Sumatra di Bukit Tigapuluh." />
<meta property="og:image" content="{{ url('og-image.jpg') }}" />
<meta property="og:image:width" content="300" />
<meta property="og:image:height" content="300" />
<meta property="og:site_name" content="Move for Elephants" />

<!-- TWITTER CARD -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content='Ikuti Gerakan "Move for Elephants"' />
<meta name="twitter:description" content="Hanya dengan menghitung dan mencatat langkah harian, PermataBankers dapat berkontribusi dalam menyelamatkan gajah Sumatra di Bukit Tigapuluh." />
<meta name="twitter:image" content="{{ url('og-image.jpg') }}" />

<!-- FONTS -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link
    rel="stylesheet"
    type="text/css"
    href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css"
  />
<link
    rel="stylesheet"
    type="text/css"
    href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"
  />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
