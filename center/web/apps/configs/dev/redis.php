<?php
$redis['master'] = array(
    'host' => "127.0.0.1",
    'port' => 6379,
    'database' => 15,
);

/**
 * Codis集群服务器
 */
$redis['cluster'] = array(
    'host' => "127.0.0.1",
    'port' => 6379,
    'database' => 15,
);

$redis['platform'] = array(
    'host' => '127.0.0.1',
    'port' => 6379,
);

return $redis;
