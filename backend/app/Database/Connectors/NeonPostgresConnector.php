<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;
use PDO;

class NeonPostgresConnector extends PostgresConnector
{
    public function getOptions(array $config)
    {
        // DB_OPTIONS is a libpq connection option (for example Neon's
        // endpoint=...), not a numeric PDO attribute. Keep it in the DSN and
        // pass only actual PDO attributes to PDO::__construct().
        if (is_string($config['options'] ?? null)) {
            $config['options'] = [];
        }

        return parent::getOptions($config);
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
