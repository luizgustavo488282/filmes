<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$envKey = getenv('TMDB_API_KEY');
define('TMDB_API_KEY', $envKey !== false ? $envKey : 'b9d9728c29d4df6031f85d3fae654f21');

define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/');
define('TMDB_LANGUAGE', 'pt-BR');
define('EMBEDMOVIES_BASE', 'https://myembed.biz');
define('APP_NAME', 'CineWave');

// URLs do Player EmbedMovies
function embedmovies_movie_url($id) {
    $id = preg_replace('/[^a-zA-Z0-9]/', '', (string) $id);
    return EMBEDMOVIES_BASE . '/filme/' . $id;
}
function embedmovies_series_url($id) {
    $id = preg_replace('/[^a-zA-Z0-9]/', '', (string) $id);
    return EMBEDMOVIES_BASE . '/serie/' . $id;
}
function embedmovies_episode_url($id, $season, $episode) {
    $id = preg_replace('/[^a-zA-Z0-9]/', '', (string) $id);
    return EMBEDMOVIES_BASE . '/serie/' . $id . '/' . (int)$season . '/' . (int)$episode;
}

// BLOCKLIST RIGOROSA: REMOÇÃO EXPLICITA DE JIMIHEN, ECCHI, NUDEZ E ANIME +18
define('TITLE_BLOCKLIST', [
    // Título específico solicitado e suas variações
    'jimihen', 'jimiko', 'kaechau', 'jun isei', 'kouyuu', 'pure heterosexuality',
    
    // Animes Ecchi, Nudez, Peitos e Conteúdo Adulto
    'overflow', 'hentai', 'porn', 'sexo', 'erotic', 'ecchi', 'sensual', 
    'playboy', 'kamasutra', 'hot', 'strip', 'prostituta', 'orgia', 
    'xxx', 'adult', 'eroti', 'yuri', 'yaoi', 'seikatsu', 'fap', 'slut', 
    'naked', 'nudity', 'pelada', 'pelado', 'peito', 'peitos', 'pênis', 'penis',
    'bunda', 'vagina', 'ass', 'dick', 'cock', 'pussy', 'nude', 'boobs',
    'tits', 'breast', 'seduction', 'seducao', 'incest', 'incesto', 'cleavage',
    
    // Títulos de animes ecchi/maduros conhecidos por capas e cenas sugestivas
    'harem', 'ishuzoku', 'interspecies', 'valkyrie', 'gakuen', 'kiss',
    'sora no otoshimono', 'to love-ru', 'high school dxd', 'shinmai maou',
    'yosuga no sora', 'redo of healer', 'kaifuku', 'peter grill', 'monster musume',
    'boarders', 'domestic na kanojo', 'prison school', 'shokugeki no soma',
    'to love ru', 'motto to love ru', 'kiss x sis', 'masou gakuen', 'isekai maou',
    'maken-ki', 'seikon no qwaser', 'hybrid x heart', 'dq18', 'softcore'
]);

// Função de verificação que inspeciona Título, Nome Original e Sinopse
function is_title_blocked($title = '', $originalTitle = '', $overview = '') {
    $haystack = mb_strtolower($title . ' ' . $originalTitle . ' ' . $overview, 'UTF-8');
    foreach (TITLE_BLOCKLIST as $term) {
        if ($term !== '' && mb_strpos($haystack, mb_strtolower($term, 'UTF-8')) !== false) {
            return true;
        }
    }
    return false;
}

// Formatação de Imagens
function get_image_url($path, $size = 'w300') {
    if (empty($path)) return 'https://placehold.co/300x450/161616/e50914?text=CineWave';
    return TMDB_IMAGE_BASE . $size . $path;
}

// Requisição cURL/Stream Context com filtro de conteúdo adulto forçado
function fetch_tmdb($endpoint, $params = []) {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = TMDB_LANGUAGE;
    $params['include_adult'] = 'false'; // Desativa conteúdo estritamente adulto na fonte TMDB
    
    $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
    $response = false;
    $erro = "";

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        if($response === false) $erro = curl_error($ch);
        curl_close($ch);
    }

    if (!$response && ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            "http" => ["method" => "GET", "header" => "User-Agent: Mozilla/5.0\r\n"],
            "ssl"  => ["verify_peer" => false, "verify_peer_name" => false]
        ]);
        $response = @file_get_contents($url, false, $context);
        if($response === false) $erro = "file_get_contents bloqueado pelo XAMPP.";
    }

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success'] === false) {
            return ['results' => [], 'erro_api' => $data['status_message']];
        }
        if (isset($data['results']) || isset($data['id'])) {
            return $data;
        }
    }
    
    return ['results' => [], 'erro_api' => $erro];
}
?>