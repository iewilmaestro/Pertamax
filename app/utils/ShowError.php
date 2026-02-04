<?php

class ShowError {

    private $logFile;
    private $lastErrorMsg = null;

    public function __construct(string $logFile = 'error.log') {
        date_default_timezone_set('Asia/Jakarta');
        $this->logFile = $logFile;
        $this->_start();
    }

    public function _start() {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        Display::info("all errors are displayed and logged in {$this->logFile}");
        Display::line();
        sleep(2);

        ini_set('display_errors', '0');
        ini_set('log_errors', '0');

        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError($errno, $errstr, $errfile, $errline) {
        $errfile = basename($errfile);
        $msg = "$errstr | $errfile | $errline";

        $this->log($msg);
        return true;
    }

    public function handleShutdown() {
        $error = error_get_last();
        if ($error !== null) {

            $message = explode(' in ', $error['message'])[0];
            $errfile = basename($error['file']);
            $msg = "$message | $errfile | {$error['line']}";

            if ($this->lastErrorMsg !== $msg) {
                $this->log($msg);
            }
        }
    }

    private function log(string $message) {
        $this->lastErrorMsg = $message;

        $date = date('Y-m-d H:i:s');
        Display::debug($message);

        file_put_contents(
            $this->logFile, 
            "[$date] $message\n",
            FILE_APPEND
        );
    }
}