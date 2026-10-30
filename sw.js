// Service worker do CineWave: deixa o app instalável e guarda só o visual.
// Páginas e vídeos sempre vêm da rede (conteúdo sempre atualizado).
const CACHE = 'cinewave-v1';
const ASSETS = ['style.css', 'icon-192.png', 'icon-512.png', 'manifest.json'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== location.origin) return;          // TMDB, embed etc. direto da rede

    if (req.mode === 'navigate') {                        // páginas: rede, com aviso se estiver offline
        e.respondWith(fetch(req).catch(() => new Response(
            '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
            '<body style="background:#141414;color:#fff;font-family:sans-serif;text-align:center;padding:90px 24px">' +
            '<h2>Sem conexão</h2><p style="color:#8c8c8c;margin:12px 0 24px">Conecte-se à internet para continuar.</p>' +
            '<a href="index.php" style="color:#fff;border-bottom:2px solid #e50914;text-decoration:none;font-weight:700">Tentar novamente</a>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
        return;
    }
    if (ASSETS.some((a) => url.pathname.endsWith(a))) {   // visual: cache primeiro, atualiza em segundo plano
        e.respondWith(caches.match(req).then((hit) => {
            const net = fetch(req).then((r) => { caches.open(CACHE).then((c) => c.put(req, r.clone())); return r; }).catch(() => hit);
            return hit || net;
        }));
    }
});
