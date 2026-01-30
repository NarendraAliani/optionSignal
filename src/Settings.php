<?php

require_once __DIR__ . '/Database.php';

class Settings {
    private $db;
    private $encryptionKey;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->encryptionKey = ENCRYPTION_KEY;
    }

    public function get($key) {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        
        if ($row) {
            return $this->decrypt($row['setting_value']);
        }
        return null;
    }

    public function set($key, $value) {
        $encrypted = $this->encrypt($value);
        $stmt = $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $encrypted, $encrypted]);
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $this->decrypt($row['setting_value']);
        }
        return $settings;
    }

    // Simple AES encryption for storage security
    private function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    // Decryption
    private function decrypt($data) {
        $decoded = base64_decode($data, true);
        if ($decoded === false || strpos($decoded, '::') === false) {
            return $data; // Return raw if decoding fails or format is wrong
        }
        
        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
    }
}
