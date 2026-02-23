<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
</head>

<body>
    <div id="root"></div>
    <script>
        <?php $tenant = [
            'company_name' => isset($tenant) ? $tenant->company_name : null,
        ]; ?>
        window.tenant = @json($tenant);
    </script>
    @viteReactRefresh
    @vite('resources/js/react/main.tsx')
</body>

</html>