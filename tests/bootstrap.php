<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Keboola\Syrup\Debug\Debug;

defined('SYRUP_APP_NAME') || define('SYRUP_APP_NAME', getenv('SYRUP_APP_NAME')? getenv('SYRUP_APP_NAME') : 'syrup-test');
defined('DATABASE_PORT') || define('DATABASE_PORT', getenv('DATABASE_PORT')? getenv('DATABASE_PORT') : null);
defined('DATABASE_HOST') || define('DATABASE_HOST', getenv('DATABASE_HOST')? getenv('DATABASE_HOST') : '127.0.0.1');
defined('DATABASE_USER') || define('DATABASE_USER', getenv('DATABASE_USER')? getenv('DATABASE_USER') : 'syrup');
defined('DATABASE_PASSWORD') || define('DATABASE_PASSWORD', getenv('DATABASE_PASSWORD')? getenv('DATABASE_PASSWORD') : null);
defined('DATABASE_NAME') || define('DATABASE_NAME', getenv('DATABASE_NAME')? getenv('DATABASE_NAME') : 'syrup');
defined('AWS_ACCESS_KEY_ID') || define('AWS_ACCESS_KEY_ID', getenv('AWS_ACCESS_KEY_ID')? getenv('AWS_ACCESS_KEY_ID') : 'AKIAJMFLGHTG2BI3WNEQ');
defined('AWS_SECRET_ACCESS_KEY') || define('AWS_SECRET_ACCESS_KEY', getenv('AWS_SECRET_ACCESS_KEY')? getenv('AWS_SECRET_ACCESS_KEY') : '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');
defined('AWS_REGION') || define('AWS_REGION', getenv('AWS_REGION')? getenv('AWS_REGION') : 'us-east-1');
defined('SAPI_URL') || define('SAPI_URL', getenv('SAPI_URL')? getenv('SAPI_URL') : null);
defined('SAPI_TOKEN') || define('SAPI_TOKEN', getenv('SAPI_TOKEN')? getenv('SAPI_TOKEN') : '572-30505-7c2f2a3a53ff2b96b6c1aaa12d128e0b4c3d53c7');
defined('ELASTICSEARCH_HOST') || define('ELASTICSEARCH_HOST', getenv('ELASTICSEARCH_HOST')? getenv('ELASTICSEARCH_HOST') : 'http://127.0.0.1:9200');
defined('AWS_SQS_DEFAULT_QUEUE') || define('AWS_SQS_DEFAULT_QUEUE', getenv('AWS_SQS_DEFAULT_QUEUE')? getenv('AWS_SQS_DEFAULT_QUEUE') : 'https://sqs.us-east-1.amazonaws.com/[id]/[name]');
defined('AWS_S3_BUCKET') || define('AWS_S3_BUCKET', getenv('AWS_S3_BUCKET')? getenv('AWS_S3_BUCKET') : 'keboola-logs');
defined('AWS_S3_BUCKET_LOGS_PATH') || define('AWS_S3_BUCKET_LOGS_PATH', getenv('AWS_S3_BUCKET_LOGS_PATH')? getenv('AWS_S3_BUCKET_LOGS_PATH') : '/debug-files');
defined('AWS_SQS_TEST_QUEUE_NAME') || define('AWS_SQS_TEST_QUEUE_NAME', getenv('AWS_SQS_TEST_QUEUE_NAME')? getenv('AWS_SQS_TEST_QUEUE_NAME') : '');
$paramsYaml = \Symfony\Component\Yaml\Yaml::dump([
    'parameters' => [
        'app_name' => SYRUP_APP_NAME,
        'secret' => md5(uniqid()),
        'encryption_key' => md5(uniqid()),
        'database_driver' => 'pdo_mysql',
        'database_port' => DATABASE_PORT,
        'database_host' => DATABASE_HOST,
        'database_user' => DATABASE_USER,
        'database_password' => DATABASE_PASSWORD,
        'database_name' => DATABASE_NAME,
        'syrup.driver' => 'pdo_mysql',
        'syrup.port' => DATABASE_PORT,
        'syrup.host' => DATABASE_HOST,
        'syrup.user' => DATABASE_USER,
        'syrup.password' => DATABASE_PASSWORD,
        'syrup.name' => DATABASE_NAME,
        'locks_db.driver' => 'pdo_mysql',
        'locks_db.port' => DATABASE_PORT,
        'locks_db.host' => DATABASE_HOST,
        'locks_db.user' => DATABASE_USER,
        'locks_db.password' => DATABASE_PASSWORD,
        'locks_db.name' => DATABASE_NAME,
        'doctrine_migrations_dir' => __DIR__  . "/../DoctrineMigrations",
        'uploader' => [
            'aws-access-key' => AWS_ACCESS_KEY_ID,
            'aws-secret-key' => AWS_SECRET_ACCESS_KEY,
            's3-upload-path' => AWS_S3_BUCKET . AWS_S3_BUCKET_LOGS_PATH,
            'aws-region' => AWS_REGION,
            'url-prefix' => 'https://connection.keboola.com/admin/utils/logs?file=',
        ],
        'storage_api.url' => SAPI_URL,
        'storage_api.test.url' => SAPI_URL,
        'storage_api.test.token' => SAPI_TOKEN,
        'elasticsearch' => [
            'hosts' => [ELASTICSEARCH_HOST]
        ],
        'elasticsearch.index_prefix' => 'devel',
        'queue' => [
            'url' => null,
            'db_table' => 'queues'
        ],
        'components' => [

        ]
    ]
]);
file_put_contents(__DIR__ . '/../app/config/parameters.yml', $paramsYaml);
touch(__DIR__ . '/../app/config/parameters_shared.yml');

$db = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => DATABASE_HOST,
    'dbname' => DATABASE_NAME,
    'user' => DATABASE_USER,
    'password' => DATABASE_PASSWORD,
    'port' => DATABASE_PORT
]);

$stmt = $db->prepare(file_get_contents(__DIR__ . '/db.sql'));
$stmt->execute();
$stmt->closeCursor();

$db->insert('queues', [
    'id' => 'default',
    'access_key' => AWS_ACCESS_KEY_ID,
    'secret_key' => AWS_SECRET_ACCESS_KEY,
    'region' => AWS_REGION,
    'url' => AWS_SQS_DEFAULT_QUEUE
]);

passthru('php vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php '
    . 'app vendor');
passthru(sprintf('php "%s/../app/console" cache:clear --env=test', __DIR__));
passthru(sprintf('php "%s/../app/console" syrup:create-index -d  --env=test', __DIR__));

Debug::enable(null, true, 'test');
