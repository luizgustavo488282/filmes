<?php
require_once 'config.php';

$page = max(1, (int)($_GET['page'] ?? 1));

// Busca filmes populares na API
$data = fetch_tmdb('/discover/movie', [
    'sort_by' => 'popularity.desc',
    'page'    => $page
]);

$brutos     = $data['results'] ?? [];
$erro       = $data['erro_api'] ?? null;
$totalPages = min($data['total_pages'] ?? 1, 500);

// Filtra apenas conteúdos adequados
$itens = array_values(array_filter($brutos, function($item) {
    $nome    = $item['title'] ?? '';
    $orig    = $item['original_title'] ?? '';
    $sinopse = $item['overview'] ?? '';
    if (!empty($item['adult']) && $item['adult'] === true) return false;
    return !is_title_blocked($nome, $orig, $sinopse);
}));

$destaque = $itens[0] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0a0a0a">
    <title>Filmes - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon-192.png">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>
    <header class="top-bar">
        <h1>Catálogo de <b>Filmes</b></h1>
    </header>

    <?php if ($destaque && $page === 1): ?>
    <section class="banner-destaque" style="background-image: url('<?= get_image_url($destaque['poster_path'] ?? $destaque['backdrop_path'] ?? '', 'w780') ?>');">
        <div class="banner-overlay">
            <h2><?= htmlspecialchars($destaque['title'] ?? '') ?></h2>
            <a href="detalhes.php?id=<?= (int)$destaque['id'] ?>&tipo=movie" class="btn-play red">▶ Assistir Filme</a>
        </div>
    </section>
    <?php endif; ?>

    <main style="<?= ($page === 1 && $destaque) ? '' : 'padding-top: calc(60px + env(safe-area-inset-top, 0px));' ?>">
        <h2 class="grid-title">Filmes Populares</h2>

        <?php if (empty($itens) && $erro): ?>
            <p class="error-msg"><b>ERRO:</b> <?= htmlspecialchars($erro) ?></p>
        <?php elseif (empty($itens)): ?>
            <p class="empty-msg">Nenhum filme encontrado nesta página.</p>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($itens as $item): ?>
                    <a href="detalhes.php?id=<?= (int)$item['id'] ?>&tipo=movie" class="card">
                        <img src="<?= get_image_url($item['poster_path'] ?? '') ?>" alt="Capa" loading="lazy">
                        <p><?= htmlspecialchars($item['title'] ?? '') ?></p>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="filmes.php?page=<?= $page - 1 ?>">‹ Anterior</a>
                <?php endif; ?>
                <span><?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="filmes.php?page=<?= $page + 1 ?>">Próxima ›</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item"><span>🏠</span><label>Início</label></a>
        <a href="filmes.php" class="nav-item active"><span>🎬</span><label>Filmes</label></a>
        <a href="animes.php" class="nav-item-center" title="Animes"><span>⛩️</span></a>
        <a href="series.php" class="nav-item"><span>📺</span><label>Séries</label></a>
        <a href="buscar.php" class="nav-item"><span>🔍</span><label>Buscar</label></a>
    </nav>
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</body>
</html>
