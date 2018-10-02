<?php

namespace Ssess;

class Ssess implements \SessionHandlerInterface
{
    private $savePath;
    private $cipher = 'aes128';

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
        $iv_length = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted_data = openssl_encrypt($session_data, $this->cipher, $session_id, 0, $iv);

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

        return openssl_decrypt($encrypted_data->data, $this->cipher, $session_id, 0, $iv);
    }

    private function getFileName($session_id)
    {
        return 'ssess_'.sha1($session_id);
    }
}