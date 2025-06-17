<?php
// Créer une image par défaut simple
$width = 800;
$height = 500;
$image = imagecreatetruecolor($width, $height);

// Définir les couleurs
$bg_color = imagecolorallocate($image, 230, 240, 250); // Bleu très clair
$text_color = imagecolorallocate($image, 6, 19, 116);  // Bleu foncé (couleur principale du site)
$border_color = imagecolorallocate($image, 100, 150, 200); // Bleu moyen

// Remplir le fond
imagefill($image, 0, 0, $bg_color);

// Dessiner un cadre
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
imagerectangle($image, 10, 10, $width-11, $height-11, $border_color);

// Ajouter du texte
$text = "SAF VOYAGE";
$font_size = 5; // Taille de police GD (1-5)
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$text_x = ($width - $text_width) / 2;
$text_y = ($height - $text_height) / 2;

imagestring($image, $font_size, $text_x, $text_y, $text, $text_color);
imagestring($image, 3, $text_x, $text_y + 30, "Image par defaut", $text_color);

// Vérifier si le dossier existe, sinon le créer
$directory = '../assets/images/';
if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
}

// Sauvegarder l'image
imagejpeg($image, $directory . 'default.jpg', 90);
imagedestroy($image);

echo "Image par défaut créée avec succès!";
?>
