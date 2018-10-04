<?php

namespace Ssess;

/*
 * TODO Use DockBlocks
 * TODO Respect session.use_strict_mode (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.non-adaptive-session)
 * TODO Implement timestamp based session (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-data-deletion)
 * TODO Implement session locking (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-locking)
 * TODO Allow user to specify hash algorithm
 * TODO Allow user to specify encryption algorithm
 * TODO Allow user to specify APP name so that session files from different applications never gets mixed
 * TODO Allow user to specify APP key so that even if a hacker get the session files and knows a session_id, it wouldn't be able to decrypt
 * TODO Make tests
 * TODO Show what a session file looks like with and without encryption in README
 * TODO Option to lock session to IP
 * TODO Option to lock session to User Agent
 * TODO Option to lock session to Host
 * TODO Option to create session cookie with mutating random name
 * TODO Create way to regenerate the session_id
 * TODO Create a way to change encryption/hash algorithms over time without loosing previous sessions, to allow incremental security upgrades
 */
class Ssess implements \SessionHandlerInterface
{
    private $savePath;
    private $encryptionAlgorithm = 'aes128';
    private $hashAlgorithm = 'sha256';

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
        $encrypted_data = openssl_encrypt($session_data, $this->encryptionAlgorithm, $session_id, 0, $iv);

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

        return openssl_decrypt($encrypted_data->data, $this->encryptionAlgorithm, $session_id, 0, $iv);
    }

    private function getFileName($session_id)
    {
        return 'ssess_'.openssl_digest($session_id, $this->hashAlgorithm);
    }
}