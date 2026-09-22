<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;
use PDO;

class NeonPostgresConnector extends PostgresConnector
{
    public function getOptions(array $config)
    {
        $options = $config['options'] ?? [];
        if (is_string($options)) {
            $options = [$options => true];
        }
        return array_diff_key($this->options, $options) + $options;
    }

    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        if (!empty($config['options'])) {
            $opt = $config['options'];
            if (is_string($opt)) {
                $dsn .= ";options='{$opt}'";
            }
        }

        return $dsn;
    }
}