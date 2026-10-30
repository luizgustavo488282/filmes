<?php
require_once 'config.php';

$query = trim($_GET['q'] ?? '');
$resultados = [];

// Se o usuário digitou algo, faz a busca na API (filmes, séries e animes juntos)
if ($query !== '') {
    $dados = fetch_tmdb('/search/multi', ['query' => $query]);
    $resultados = $dados['results'] ?? [];
}

$placeholder = 'https://placehold.co/300x450/161616/e50914?text=Sem+Imagem';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0a0a0a">
    <title>Buscar - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon-192.png">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>
    <header class="top-bar">
        <h1>Buscar no Cine<b>Wave</b></h1>
    </header>

    <main class="search-container">
        <form action="buscar.php" method="GET" class="search-form">
            <input type="search" name="q" inputmode="search" enterkeyhint="search"
                   placeholder="Filme, série ou anime..."
                   value="<?= htmlspecialchars($query) ?>" required
                   <?= $query === '' ? 'autofocus' : '' ?>>
            <button type="submit">Buscar</button>
        </form>

        <?php if ($query !== ''): ?>
            <h2 class="result-title">Resultados para "<?= htmlspecialchars($query) ?>"</h2>

            <?php
            // Mantém só filmes e séries (ignora perfis de atores) e aplica o filtro de conteúdo
            $lista = [];
            foreach ($resultados as $item) {
                $tipo = $item['media_type'] ?? 'movie';
                if ($tipo !== 'movie' && $tipo !== 'tv') continue;
                $nome = $item['title'] ?? $item['name'] ?? '';
                $orig = $item['original_title'] ?? $item['original_name'] ?? '';
                if (is_title_blocked($nome, $orig)) continue;
                $lista[] = ['id' => (int)$item['id'], 'tipo' => $tipo, 'nome' => $nome, 'poster' => $item['poster_path'] ?? ''];
            }
            ?>

            <?php if (empty($lista)): ?>
                <div class="no-results"><p>Nenhum título encontrado.</p></div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($lista as $it): ?>
                        <a href="detalhes.php?id=<?= $it['id'] ?>&tipo=<?= $it['tipo'] ?>" class="card">
                            <img src="<?= get_image_url($it['poster']) ?>"
                                 alt="<?= htmlspecialchars($it['nome']) ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null;this.src='<?= $placeholder ?>';">
                            <p><?= htmlspecialchars($it['nome']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item"><span>🏠</span><label>Início</label></a>
        <a href="filmes.php" class="nav-item"><span>🎬</span><label>Filmes</label></a>
        <a href="animes.php" class="nav-item-center" title="Animes"><span>⛩️</span></a>
        <a href="series.php" class="nav-item"><span>📺</span><label>Séries</label></a>
        <a href="buscar.php" class="nav-item active"><span>🔍</span><label>Buscar</label></a>
    </nav>
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</body>
</html>
