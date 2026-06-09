<?php
// Vars esperadas: $seo_title, $seo_desc, $seo_image (opcionais)
defined('ABSPATH') || exit;
$_title = isset($seo_title) ? esc_html($seo_title) . ' | Tarly Pinheiro Imóveis' : 'Tarly Pinheiro Imóveis | CRECI 105224 | Goiânia';
$_desc  = isset($seo_desc) ? esc_attr($seo_desc) : 'Os melhores imóveis em Goiânia e Aparecida de Goiânia. CRECI 105224.';
$_img   = isset($seo_image) ? esc_url($seo_image) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $_title ?></title>
<meta name="description" content="<?= $_desc ?>">
<meta property="og:title"       content="<?= $_title ?>">
<meta property="og:description" content="<?= $_desc ?>">
<meta property="og:type"        content="website">
<?php if ($_img): ?><meta property="og:image" content="<?= $_img ?>"><?php endif; ?>
<link rel="canonical" href="<?= esc_url(get_permalink()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= TI_URL ?>assets/css/public.css?v=<?= TI_VER ?>">
<?php wp_head(); ?>
</head>
<body>
