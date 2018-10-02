<?php

class Ssess implements SessionHandlerInterface
{
    private $savePath;
    private $cipher = 'aes128';
    private $initializationVectorLength;

    public function open($save_path, $name)
    {
        $this->initializationVectorLength = openssl_cipher_iv_length($this->cipher);

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
        $file_name = 'ssess_'.sha1($session_id);
        return (string)@file_get_contents("$this->savePath/$file_name");
    }

    public function write($session_id, $session_data)
    {
        $file_name = 'ssess_'.sha1($session_id);
        $text_data = json_encode($session_data);

        $iv = openssl_random_pseudo_bytes($this->initializationVectorLength);
        $encrypted_data = openssl_encrypt($text_data, $this->cipher, $session_id, 0, $iv);

        return file_put_contents("$this->savePath/$file_name", $encrypted_data) !== false;
    }

    public function destroy($session_id)
    {
        $file_name = 'ssess_'.sha1($session_id);

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
}