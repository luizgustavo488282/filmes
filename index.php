<?php
require_once 'config.php';

// Puxa os dados da API
$dataFilmes = fetch_tmdb('/movie/popular');
$filmesPopulares = $dataFilmes['results'] ?? [];
$erroFilmes = $dataFilmes['erro_api'] ?? null;

$dataSeries = fetch_tmdb('/tv/popular');
$seriesPopulares = $dataSeries['results'] ?? [];
$erroSeries = $dataSeries['erro_api'] ?? null;

$dataAnimes = fetch_tmdb('/discover/tv', [
    'with_genres' => 16,
    'with_original_language' => 'ja',
    'sort_by' => 'popularity.desc'
]);
$animesPopulares = $dataAnimes['results'] ?? [];
$erroAnimes = $dataAnimes['erro_api'] ?? null;

$destaque = $filmesPopulares[0] ?? null;

// Desenha um carrossel de cards
function render_carousel($itens, $tipo, $erro, $vazio) {
    echo '<div class="carousel-wrap">';
    echo '<button type="button" class="carousel-btn prev" aria-label="Anterior" hidden>‹</button>';
    echo '<div class="carousel">';
    if (empty($itens) && $erro) {
        echo '<p class="error-msg"><b>ERRO:</b> ' . htmlspecialchars($erro) . '</p>';
    } elseif (empty($itens)) {
        echo '<p class="empty-msg">' . htmlspecialchars($vazio) . '</p>';
    } else {
        foreach ($itens as $item) {
            $nome = $item['title'] ?? $item['name'] ?? '';
            $orig = $item['original_title'] ?? $item['original_name'] ?? '';
            if (is_title_blocked($nome, $orig)) continue;
            echo '<a href="detalhes.php?id=' . (int)$item['id'] . '&tipo=' . $tipo . '" class="card">';
            echo '<img src="' . get_image_url($item['poster_path'] ?? '') . '" alt="Capa" loading="lazy">';
            echo '<p>' . htmlspecialchars($nome) . '</p>';
            echo '</a>';
        }
    }
    echo '</div>';
    echo '<button type="button" class="carousel-btn next" aria-label="Próximo">›</button>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0a0a0a">
    <title>Início - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon-192.png">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>
    <header class="top-bar">
        <h1>Cine<b>Wave</b></h1>
    </header>

    <?php if ($destaque): ?>
    <section class="banner-destaque" style="background-image: url('<?= get_image_url($destaque['poster_path'] ?? $destaque['backdrop_path'] ?? '', 'w780') ?>');">
        <div class="banner-overlay">
            <h2><?= htmlspecialchars($destaque['title'] ?? $destaque['name'] ?? '') ?></h2>
            <a href="detalhes.php?id=<?= (int)$destaque['id'] ?>&tipo=movie" class="btn-play">▶ Assistir Agora</a>
        </div>
    </section>
    <?php endif; ?>

    <main>
        <h2 class="section-title">Filmes em Alta</h2>
        <?php render_carousel($filmesPopulares, 'movie', $erroFilmes, 'Nenhum filme disponível no momento.'); ?>

        <h2 class="section-title">Séries em Alta</h2>
        <?php render_carousel($seriesPopulares, 'tv', $erroSeries, 'Nenhuma série disponível no momento.'); ?>

        <h2 class="section-title">Animes em Destaque</h2>
        <?php render_carousel($animesPopulares, 'tv', $erroAnimes, 'Nenhum anime disponível no momento.'); ?>
    </main>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active"><span>🏠</span><label>Início</label></a>
        <a href="filmes.php" class="nav-item"><span>🎬</span><label>Filmes</label></a>
        <a href="animes.php" class="nav-item-center" title="Animes"><span>⛩️</span></a>
        <a href="series.php" class="nav-item"><span>📺</span><label>Séries</label></a>
        <a href="buscar.php" class="nav-item"><span>🔍</span><label>Buscar</label></a>
    </nav>
    <script>
    // Setas dos carrosséis: passam os títulos para o lado
    document.querySelectorAll('.carousel-wrap').forEach(function (wrap) {
        var track = wrap.querySelector('.carousel');
        var prev  = wrap.querySelector('.carousel-btn.prev');
        var next  = wrap.querySelector('.carousel-btn.next');

        function update() {
            prev.hidden = track.scrollLeft <= 4;
            next.hidden = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
        }
        function go(dir) {
            track.scrollBy({ left: dir * track.clientWidth * 0.85, behavior: 'smooth' });
        }

        prev.addEventListener('click', function () { go(-1); });
        next.addEventListener('click', function () { go(1); });
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        window.addEventListener('load', update);
        update();
    });
    </script>
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</body>
</html>
