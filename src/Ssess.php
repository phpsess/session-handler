<?php

namespace Ssess;

/*
 * TODO Use DockBlocks
 * TODO Implement timestamp based session (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-data-deletion)
 * TODO Implement session locking (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-locking)
 * TODO Allow user to specify hash algorithm
 * TODO Allow user to specify encryption algorithm
 * TODO Allow user to specify APP name so that session files from different applications never gets mixed
 * TODO Make tests
 * TODO Instruct users to set the session.use_strict_mode ini config
 * TODO Show what a session file looks like with and without encryption in README
 * TODO Option to lock session to IP
 * TODO Option to lock session to User Agent
 * TODO Option to lock session to Host
 * TODO Option to create session cookie with mutating random name
 * TODO Create a way to change encryption/hash algorithms over time without loosing previous sessions, to allow incremental security upgrades
 */
class Ssess implements \SessionHandlerInterface
{
    private $savePath;
    private $appKey;
    private $encryptionAlgorithm = 'aes128';
    private $hashAlgorithm = 'sha512';

    public function __construct($app_key)
    {
        $this->appKey = openssl_digest($app_key, $this->hashAlgorithm);
        $this->handleStrict();
    }

    private function handleStrict()
    {
        if (!ini_get('session.use_strict_mode') || headers_sent()) {
            return;
        }

        $cookie_name = session_name();
        if (empty($_COOKIE[$cookie_name])) {
            return;
        }

        $session_id = $_COOKIE[$cookie_name];

        $save_path = session_save_path();
        if (!is_dir($save_path)) {
            return;
        }

        $file_name = $this->getFileName($session_id);
        if (file_exists("$save_path/$file_name")) {
            return;
        }

        session_start();
        session_regenerate_id();
        session_write_close();
    }

    public function open($save_path, $name)
    {
        $this->savePath = $save_path;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777);
        }

        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($session_id)
    {
        $file_name = $this->getFileName($session_id);

        $content = @file_get_contents("$this->savePath/$file_name");

        if (!$content) {
            return '';
        }

        return $this->decode($session_id, $content);
    }

    public function write($session_id, $session_data)
    {
        $file_name = $this->getFileName($session_id);

        $content = $this->encode($session_id, $session_data);

        return file_put_contents("$this->savePath/$file_name", $content) !== false;
    }

    public function destroy($session_id)
    {
        $file_name = $this->getFileName($session_id);

        $file = "$this->savePath/$file_name";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime)
    {
        foreach (glob("$this->savePath/ssess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }

    private function encode($session_id, $session_data)
    {
        $iv_length = openssl_cipher_iv_length($this->encryptionAlgorithm);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encryption_key = $this->getEncryptionKey($session_id);
        $encrypted_data = openssl_encrypt($session_data, $this->encryptionAlgorithm, $encryption_key, 0, $iv);

        return json_encode([
            'data' => $encrypted_data,
            'iv' => base64_encode($iv)
        ]);
    }

    private function decode($session_id, $content)
    {
        $encrypted_data = json_decode($content);

        if (!$encrypted_data) {
            return '';
        }

        $iv = base64_decode($encrypted_data->iv);
        $encryption_key = $this->getEncryptionKey($session_id);

        return openssl_decrypt($encrypted_data->data, $this->encryptionAlgorithm, $encryption_key, 0, $iv);
    }

    private function getFileName($session_id)
    {
        return 'ssess_'.openssl_digest($session_id, $this->hashAlgorithm);
    }

    private function getEncryptionKey($session_id)
    {
        return $this->appKey . openssl_digest($session_id, $this->hashAlgorithm);
    }
}