<?php
require __DIR__ . '/config.php';

$type = ($_GET['type'] ?? 'movie') === 'tv' ? 'tv' : 'movie';
$id   = preg_replace('/[^0-9]/', '', $_GET['id'] ?? '0');
$s    = max(1, (int) ($_GET['s'] ?? 1));
$ep   = max(1, (int) ($_GET['e'] ?? 1));

// Usa as funções do config.php para gerar o link do player
if ($type === 'tv') {
    $current_src = embedmovies_episode_url($id, $s, $ep);
} else {
    $current_src = embedmovies_movie_url($id);
}

$back = 'detalhes.php?id=' . $id . '&tipo=' . $type;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title>Assistir - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon-192.png">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <style>
        html, body.player-page { margin: 0; background: #000; }

        .player-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            background: #000;
            overflow: hidden;
        }
        .player-wrapper:fullscreen,
        .player-wrapper:-webkit-full-screen {
            width: 100vw;
            height: 100vh;
            background: #000;
        }
        .player-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }

        /* Em tela cheia: corta as faixas pretas do próprio player
           (topo e rodapé). Ajuste os valores se sobrar/faltar borda. */
        .player-wrapper.is-fs iframe {
            --crop-top: 14px;
            --crop-bottom: 24px;
            top: calc(-1 * var(--crop-top));
            height: calc(100% + var(--crop-top) + var(--crop-bottom));
        }

        /* Botões próprios: ficam abaixo dos botões do embed e somem
           sozinhos depois de alguns segundos sem mexer o mouse */
        .btn-voltar,
        .btn-fs {
            position: absolute;
            top: calc(64px + env(safe-area-inset-top, 0px));
            z-index: 20;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0,0,0,.6);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            cursor: pointer;
            padding: 0 0 3px;
            transition: opacity .3s;
        }
        .btn-voltar { left: calc(10px + env(safe-area-inset-left, 0px)); font-size: 1.7rem; line-height: 1; }
        .btn-fs     { right: calc(10px + env(safe-area-inset-right, 0px)); padding: 0; }
        .btn-fs svg { width: 20px; height: 20px; fill: none; stroke: #fff; stroke-width: 2; }
        .btn-voltar:active, .btn-fs:active { background: rgba(229,9,20,.9); transform: scale(.92); }

        .player-wrapper.idle .btn-voltar,
        .player-wrapper.idle .btn-fs { opacity: 0; pointer-events: none; }

        /* Área invisível sobre o botão "Fullscreen" nativo do embed
           (canto inferior direito), para ele chamar a tela cheia da página. */
        .fs-hit {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 64px;
            height: 56px;
            z-index: 15;
            background: transparent;
            border: 0;
            cursor: pointer;
        }
    </style>
</head>
<body class="player-page">
    <div class="player-wrapper" id="playerWrapper">

        <iframe
            id="playerFrame"
            src="<?= htmlspecialchars($current_src) ?>"
            allow="autoplay *; encrypted-media *; picture-in-picture *; fullscreen *; screen-wake-lock *"
            allowfullscreen
            webkitallowfullscreen
            mozallowfullscreen>
        </iframe>

        <a href="<?= htmlspecialchars($back) ?>" class="btn-voltar" aria-label="Voltar">‹</a>

        <button type="button" class="btn-fs" id="btnFs" aria-label="Tela cheia">
            <svg viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
        </button>

        <button type="button" class="fs-hit" id="fsHit" aria-label="Tela cheia"></button>
    </div>

    <script>
    (function () {
        var wrapper = document.getElementById('playerWrapper');
        var frame   = document.getElementById('playerFrame');

        function fsElement() {
            return document.fullscreenElement || document.webkitFullscreenElement || null;
        }

        function enter() {
            var req = wrapper.requestFullscreen ||
                      wrapper.webkitRequestFullscreen ||
                      wrapper.msRequestFullscreen;
            if (req) {
                var p = req.call(wrapper);
                if (p && p.then) {
                    p.then(function () {
                        if (screen.orientation && screen.orientation.lock) {
                            screen.orientation.lock('landscape').catch(function () {});
                        }
                    }).catch(function () {});
                }
            } else {
                var r2 = frame.requestFullscreen || frame.webkitRequestFullscreen;
                if (r2) r2.call(frame);
            }
        }

        function exit() {
            var ex = document.exitFullscreen ||
                     document.webkitExitFullscreen ||
                     document.msExitFullscreen;
            if (ex) ex.call(document);
            if (screen.orientation && screen.orientation.unlock) {
                try { screen.orientation.unlock(); } catch (e) {}
            }
        }

        function toggle() {
            if (fsElement()) exit(); else enter();
        }

        function onChange() {
            wrapper.classList.toggle('is-fs', !!fsElement());
        }

        // Esconde os botões próprios quando o mouse/toque fica parado
        var idleTimer;
        function wake() {
            wrapper.classList.remove('idle');
            clearTimeout(idleTimer);
            idleTimer = setTimeout(function () { wrapper.classList.add('idle'); }, 3000);
        }
        ['mousemove', 'touchstart', 'keydown'].forEach(function (ev) {
            document.addEventListener(ev, wake, { passive: true });
        });
        wake();

        document.getElementById('btnFs').addEventListener('click', toggle);
        document.getElementById('fsHit').addEventListener('click', toggle);
        document.addEventListener('fullscreenchange', onChange);
        document.addEventListener('webkitfullscreenchange', onChange);

        // Tecla F alterna tela cheia no computador
        document.addEventListener('keydown', function (e) {
            if ((e.key === 'f' || e.key === 'F') && !e.ctrlKey && !e.metaKey && !e.altKey) {
                toggle();
            }
        });
    })();
    </script>
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); }); }</script>
</body>
</html>
