<?php

namespace EdituraEDU\ANAF;

use Throwable;

class ANAFBinHelper extends ANAFAPIClient {
    public function __construct(string $configFilePath) {
        if ( ! file_exists($configFilePath)) {
            throw new \Exception("Config file does not exist");
        }
        $config = json_decode(file_get_contents($configFilePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Config file is not valid JSON");
        }
        $this->VerifyConfig($config);
        parent::__construct(
                $config['oauth'],
                $config['production'],
                [$this, 'LogOutput'],
                $config['token_file_path']
        );
    }

    public function LogOutput(string $message, Throwable|null $ex = null): void {
        echo "[ANAF] ".$message.PHP_EOL;
        if ($ex !== null) {
            echo "[ANAF] Exception: ".$ex->getMessage().PHP_EOL;
            echo $ex->getTraceAsString().PHP_EOL;
        }
    }

    public function VerifyConfig(array $config): void {
        if ( ! isset($config['oauth']) || ! is_array($config['oauth'])) {
            throw new \InvalidArgumentException("Config must contain an 'oauth' object");
        }

        $requiredOAuthKeys = [
                'clientId',
                'clientSecret',
                'redirectUri',
                'urlAuthorize',
                'urlAccessToken',
                'urlResourceOwnerDetails',
        ];

        foreach ($requiredOAuthKeys as $key) {
            if ( ! isset($config['oauth'][$key]) || ! is_string($config['oauth'][$key]) || trim($config['oauth'][$key]) === '') {
                throw new \InvalidArgumentException("Config OAuth field '$key' must be a non-empty string");
            }
        }

        if ( ! array_key_exists('production', $config) || ! is_bool($config['production'])) {
            throw new \InvalidArgumentException("Config field 'production' must be a boolean");
        }

        if ( ! isset($config['token_file_path']) || ! is_string($config['token_file_path']) || trim($config['token_file_path']) === '') {
            throw new \InvalidArgumentException("Config field 'token_file_path' must be a non-empty string");
        }

    }
}
