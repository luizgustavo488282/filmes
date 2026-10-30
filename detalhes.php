<?php
require_once 'config.php';

// Sanitiza os parâmetros (só números no id; só 'movie' ou 'tv' no tipo)
$id   = preg_replace('/[^0-9]/', '', $_GET['id'] ?? '');
$tipo = ($_GET['tipo'] ?? 'movie') === 'tv' ? 'tv' : 'movie';

if ($id === '') { header("Location: index.php"); exit; }

$detalhes = fetch_tmdb("/{$tipo}/{$id}");

// Se deu erro ao buscar
if (empty($detalhes) || isset($detalhes['success']) || empty($detalhes['id'])) {
    die('<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" href="style.css"></head>
    <body><div class="erro-page">
        <h2>Conteúdo não encontrado ou erro na conexão.</h2>
        <a href="index.php">← Voltar ao Início</a>
    </div></body></html>');
}

$titulo  = htmlspecialchars($tipo === 'movie' ? ($detalhes['title'] ?? '') : ($detalhes['name'] ?? ''));
$sinopse = $detalhes['overview'] ?? '';
if (empty($sinopse)) $sinopse = 'A sinopse deste título ainda não foi traduzida para o Português.';

$backdrop = get_image_url($detalhes['backdrop_path'] ?? $detalhes['poster_path'] ?? '', 'w780');
$poster   = get_image_url($detalhes['poster_path'] ?? '');
$seasons  = max(1, (int)($detalhes['number_of_seasons'] ?? 1));

$ano  = substr($detalhes['release_date'] ?? $detalhes['first_air_date'] ?? '', 0, 4);
$nota = number_format($detalhes['vote_average'] ?? 0, 1);
$generosArray = array_map(function($g) { return $g['name']; }, $detalhes['genres'] ?? []);
$generos = implode(' • ', array_slice($generosArray, 0, 3));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0a0a0a">
    <title><?= $titulo ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon-192.png">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>
    <div class="details-hero" style="background-image: url('<?= $backdrop ?>');">
        <a href="index.php" class="btn-back" aria-label="Voltar"
           onclick="if (history.length > 1) { history.back(); return false; }">‹</a>
    </div>

    <main class="details-body">
        <img src="<?= $poster ?>" class="details-poster" alt="Capa de <?= $titulo ?>">

        <h1 class="details-title"><?= $titulo ?></h1>

        <div class="details-meta">
            <?php if ($nota > 0): ?>
                <span class="meta-badge meta-rating">★ <?= $nota ?></span>
            <?php endif; ?>
            <?php if (!empty($ano)): ?>
                <span class="meta-badge"><?= $ano ?></span>
            <?php endif; ?>
            <?php if (!empty($generos)): ?>
                <span class="meta-badge meta-plain"><?= htmlspecialchars($generos) ?></span>
            <?php endif; ?>
        </div>

        <p class="details-synopsis"><?= htmlspecialchars($sinopse) ?></p>

        <div class="play-container">
            <?php if ($tipo === 'movie'): ?>
                <a href="player.php?id=<?= $id ?>&type=movie" class="btn-play-action">
                    <span>▶</span> Assistir Filme
                </a>
            <?php else: ?>
                <form action="player.php" method="GET">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="type" value="tv">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sel-s">Temporada</label>
                            <select id="sel-s" name="s" class="custom-select">
                                <?php for ($i = 1; $i <= $seasons; $i++): ?>
                                    <option value="<?= $i ?>">Temp. <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sel-e">Episódio</label>
                            <select id="sel-e" name="e" class="custom-select">
                                <?php for ($ep = 1; $ep <= 40; $ep++): ?>
                                    <option value="<?= $ep ?>">Ep. <?= $ep ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-play-action">
                        <span>▶</span> Assistir Episódio
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item"><span>🏠</span><label>Início</label></a>
        <a href="animes.php" class="nav-item-center" title="Animes"><span>⛩️</span></a>
        <a href="buscar.php" class="nav-item"><span>🔍</span><label>Buscar</label></a>
    </nav>
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</body>
</html>
