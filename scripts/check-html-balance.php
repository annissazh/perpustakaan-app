<?php

/**
 * Memeriksa keseimbangan dan urutan nesting tag HTML pada file Blade.
 *
 * Tag di dalam ekspresi Blade (@if, @foreach, {{ }}, {!! !!}) diabaikan
 * agar tidak mengganggu pemeriksaan struktur.
 */
/**
 * Menghapus direktif Blade tanpa membuang isi HTML di dalamnya,
 * sehingga struktur tag tetap bisa diperiksa.
 */
function stripBladeDirectives(string $content): string
{
    // Blok PHP inline dan ekspresi output dibuang seluruhnya.
    $content = preg_replace('/@php\b.*?@endphp/s', '', $content);
    $content = preg_replace('/\{!!.*?!!\}/s', '', $content);
    $content = preg_replace('/\{\{.*?\}\}/s', '', $content);
    $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);

    // Direktif dengan argumen: hapus beserta tanda kurungympics secara seimbang.
    $content = preg_replace_callback(
        '/@(include|extends|yield|method|checked|selected|disabled|readonly|required|section|if|elseif|foreach|forelse|while|props|class|error|break|continue)\b\s*\(/s',
        function (array $match): string {
            $start = $match[0];
            $offset = strlen($start) - 1;
            $depth = 0;
            $length = strlen($start);

            while ($offset < $length) {
                if ($start[$offset] === '(') {
                    $depth++;
                } elseif ($start[$offset] === ')') {
                    $depth--;
                    if ($depth === 0) {
                        return '';
                    }
                }
                $offset++;
            }

            return '';
        },
        $content
    );

    // Direktif tanpa argumen.
    $content = preg_replace('/@endif\b|@endforeach\b|@endforelse\b|@endwhile\b|@endsection\b|@endclass\b|@endphp\b|@csrf\b|@else\b|@empty\b|@break\b|@continue\b|@default\b/s', '', $content);

    return $content;
}

$files = $argv;
array_shift($files);

$voidTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

$problems = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);

    $stripped = stripBladeDirectives($content);
    $stripped = preg_replace('/<script\b.*?<\/script>/si', '', $stripped);
    $stripped = preg_replace('/<style\b.*?<\/style>/si', '', $stripped);
    $stripped = preg_replace('/<!--.*?-->/s', '', $stripped);

    // Ambil seluruh tag beserta posisinya.
    preg_match_all('/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)(\/?)>/s', $stripped, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

    $stack = [];

    foreach ($matches as $match) {
        $isClosing = $match[1][0] === '/';
        $tag = strtolower($match[2][0]);
        $selfClosing = $match[4][0] === '/';
        $position = $match[0][1];
        $line = substr_count(substr($stripped, 0, $position), "\n") + 1;

        if (in_array($tag, $voidTags, true) || $selfClosing) {
            continue;
        }

        if (! $isClosing) {
            $stack[] = ['tag' => $tag, 'line' => $line];

            continue;
        }

        // Cari pasangan di stack.
        $found = null;
        for ($i = count($stack) - 1; $i >= 0; $i--) {
            if ($stack[$i]['tag'] === $tag) {
                $found = $i;
                break;
            }
        }

        if ($found === null) {
            printf("%s:%d  </%s> tanpa tag pembuka yang cocok\n", $file, $line, $tag);
            $problems++;

            continue;
        }

        // Ada tag lain yang belum ditutup sebelum tag ini.
        for ($i = count($stack) - 1; $i > $found; $i--) {
            printf("%s:%d  <%s> (dibuka baris %d) belum ditutup sebelum </%s>\n", $file, $stack[$i]['line'], $stack[$i]['tag'], $stack[$i]['line'], $tag);
            $problems++;
        }

        array_splice($stack, $found);
    }

    foreach ($stack as $unclosed) {
        printf("%s:%d  <%s> tidak pernah ditutup\n", $file, $unclosed['line'], $unclosed['tag']);
        $problems++;
    }
}

echo $problems === 0 ? "OK: semua tag HTML seimbang dan berurutan benar.\n" : "\nTotal masalah: $problems\n";
