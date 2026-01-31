<?php

class Captcha {
    public static function generate() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $width = 150;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $bg = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $lineColor = imagecolorallocate($image, 64, 64, 64);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        // Add some noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, 
                rand(0, $width), rand(0, $height), 
                rand(0, $width), rand(0, $height), 
                $lineColor
            );
        }

        // Generate Code
        $code = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I, 1, O, 0
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }

        $_SESSION['captcha'] = $code;

        // Add text using built-in font (to avoid needing .ttf files)
        // Using imagestring for simplicity and portability
        $fontSize = 5;
        $x = 30;
        $y = 15;
        
        // Spread characters out
        for ($i = 0; $i < 5; $i++) {
            imagestring($image, $fontSize, $x + ($i * 20), $y + rand(-5, 5), $code[$i], $textColor);
        }

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        imagepng($image);
        imagedestroy($image);
    }

    public static function verify($input) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['captcha'])) return false;
        
        return strtoupper(trim($input)) === $_SESSION['captcha'];
    }
}
