<?php

use Illuminate\Contracts\Console\Kernel;

/**
 * Audit mendasar aplikasi Laravel:
 *  1. Semua route() yang dipakai di Blade harus terdaftar.
 *  2. Semua view yang dirender controller harus ada di resources/views.
 *  3. Semua partial yang di-@include dan layout yang di-@extends harus ada.
 */
$base = __DIR__;

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// --- Kumpulkan nama route yang terdaftar ---
$routeNames = [];
foreach (app('router')->getRoutes() as $route) {
    if ($route->getName()) {
        $routeNames[] = $route->getName();
    }
}

$problems = 0;

$bladeFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base.'/resources/views'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $bladeFiles[] = $file->getPathname();
    }
}

// --- 1. route() di Blade ---
foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    preg_match_all('/route\(\s*[\'"]([a-zA-Z0-9_.-]+)[\'"]/', $content, $matches);

    foreach (array_unique($matches[1]) as $routeName) {
        if (! in_array($routeName, $routeNames, true)) {
            printf("ROUTE TIDAK ADA: %s memakai route('%s')\n", str_replace($base.'/', '', $file), $routeName);
            $problems++;
        }
    }
}

// --- 2. View yang dirender controller ---
$controllerFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base.'/app/Http/Controllers'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $controllerFiles[] = $file->getPathname();
    }
}

foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    preg_match_all('/view\(\s*[\'"]([a-zA-Z0-9_.-]+)[\'"]/', $content, $matches);

    foreach (array_unique($matches[1]) as $viewName) {
        $path = $base.'/resources/views/'.str_replace('.', '/', $viewName).'.blade.php';
        if (! file_exists($path)) {
            printf("VIEW TIDAK ADA: %s merender view('%s')\n", str_replace($base.'/', '', $file), $viewName);
            $problems++;
        }
    }
}

// --- 3. Partial yang di-@include harus ada ---
foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    preg_match_all('/@include\(\s*[\'"]([a-zA-Z0-9_.-]+)[\'"]/', $content, $matches);

    foreach (array_unique($matches[1]) as $partial) {
        $path = $base.'/resources/views/'.str_replace('.', '/', $partial).'.blade.php';
        if (! file_exists($path)) {
            printf("PARTIAL TIDAK ADA: %s meng-include '%s'\n", str_replace($base.'/', '', $file), $partial);
            $problems++;
        }
    }
}

// --- 4. Layout yang di-@extends harus ada ---
foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    preg_match_all('/@extends\(\s*[\'"]([a-zA-Z0-9_.-]+)[\'"]/', $content, $matches);

    foreach (array_unique($matches[1]) as $layout) {
        $path = $base.'/resources/views/'.str_replace('.', '/', $layout).'.blade.php';
        if (! file_exists($path)) {
            printf("LAYOUT TIDAK ADA: %s meng-extends '%s'\n", str_replace($base.'/', '', $file), $layout);
            $problems++;
        }
    }
}

// --- 5. Variabel yang dipakai di view tapi tidak dikirim controller ---
// Kumpulkan nama variabel dari controller (compact, view data, dengan key => value).
$controllerVars = [];

foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    $class = basename($file, '.php');
    preg_match_all('/compact\(\s*([^)]*)\)/', $content, $matches);

    foreach ($matches[1] as $group) {
        foreach (explode(',', $group) as $var) {
            $var = trim($var, " \t\n\r'\"");
            if ($var !== '' && ! str_contains($var, '$')) {
                $controllerVars[$class][$var] = true;
            }
        }
    }

    preg_match_all('/view\(\s*[\'"]([a-zA-Z0-9_.-]+)[\'"]\s*,\s*\[(.*?)\]\s*\)/s', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        preg_match_all('/[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]\s*=>/', $match[2], $vars);
        foreach ($vars[1] as $var) {
            $controllerVars[$class][$var] = true;
        }
    }

    // Variabel yang di-set langsung: $x = ...; lalu view(..., compact($x))
    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=/', $content, $matches);
    foreach ($matches[1] as $var) {
        $controllerVars[$class][$var] = true;
    }
}

// Peta view -> controller yang merendernya.
$viewControllers = [];
foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    $class = basename($file, '.php');
    preg_match_all('/view\(\s*[\'"]([a-zA-Z0-9_.-]+)[\'"]/', $content, $matches);
    foreach (array_unique($matches[1]) as $viewName) {
        $viewControllers[$viewName][] = $class;
    }
}

// Variabel global yang selalu tersedia.
$alwaysAvailable = [
    'errors', 'app', 'auth', 'request', 'session', 'old', 'currentUser',
    '__env', '__data', '__path', '__error', 'errors', 'loop', 'value', 'key',
];

foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    $relative = str_replace($base.'/resources/views/', '', $file);
    $viewName = str_replace('/', '.', preg_replace('/\.blade\.php$/', '', $relative));

    $controllers = $viewControllers[$viewName] ?? [];

    // Kumpulkan variabel yang dipakai: {{ $x }}, @if($x), $x->, $x['
    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $content, $matches);
    $used = array_unique($matches[1]);

    $known = [];
    foreach ($controllers as $controller) {
        $known += $controllerVars[$controller] ?? [];
    }

    foreach ($used as $var) {
        if (isset($known[$var]) || in_array($var, $alwaysAvailable, true)) {
            continue;
        }

        // Variabel yang berasal dari loop (@foreach as $x) atau @php lokal.
        if (preg_match('/@foreach\s*\([^)]*\$'.$var.'\b/', $content)
            || preg_match('/@forelse\s*\([^)]*\$'.$var.'\b/', $content)
            || preg_match('/@php\b[^@]*\$'.$var.'\s*=/s', $content)
            || preg_match('/@php\b.*?@endphp/s', $content)
            || preg_match('/@isset\s*\(\s*\$'.$var.'\b/', $content)
            || preg_match('/\$'.$var.'\s*=/', $content)) {
            continue;
        }

        printf("VARIABEL BERUBAH: %s memakai \${$var} tetapi tidak dikirim controller (%s)\n",
            $relative, $controllers ? implode(', ', $controllers) : 'tanpa controller');
        $problems++;
    }
}

echo $problems === 0 ? "OK: tidak ada referensi rusak.\n" : "\nTotal masalah: $problems\n";
