<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Salary') ?></title>
    <meta name="keterangan" content="Salary – Sistem Penggajian Karyawan Modern">
    <meta name="robots" content="noindex, nofollow">

    <!-- Google Fonts: Inter (body) + Poppins (heading) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- PWA & Mobile Optimization -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="theme-color" content="#0078d4">
    <!-- iOS Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Salary">
    <link rel="apple-touch-icon" href="<?= assetUrl('favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= assetUrl('favicon/favicon-96x96.png') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= assetUrl('favicon/favicon.svg') ?>">
    <link rel="shortcut icon" href="<?= assetUrl('favicon/favicon.ico') ?>">

    <!-- App CSS -->
    <link rel="stylesheet" href="<?= assetUrl('css/app.css') ?>">
</head>
<body class="auth-body">

    <?= $content ?>

    <script src="<?= assetUrl('js/app.js') ?>"></script>
</body>
</html>
