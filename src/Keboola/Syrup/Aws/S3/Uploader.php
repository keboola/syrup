<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Aws\S3;

class Uploader
{
    /**
     * @var \Aws\S3\S3Client
     */
    private $client;
    private $awsKey;
    private $awsSecret;
    private $awsRegion;
    protected $s3Bucket;
    protected $s3KeyPrefix;


    public function __construct($config)
    {
        if (!isset($config['aws-access-key'])) {
            throw new \Exception('Parameter \'aws-access-key\' is missing from config');
        }
        $this->awsKey = $config['aws-access-key'];

        if (!isset($config['aws-secret-key'])) {
            throw new \Exception('Parameter \'aws-secret-key\' is missing from config');
        }
        $this->awsSecret = $config['aws-secret-key'];

        if (!isset($config['aws-region'])) {
            throw new \Exception('Parameter \'aws-region\' is missing from config');
        }
        $this->awsRegion = $config['aws-region'];

        if (!isset($config['s3-upload-path'])) {
            throw new \Exception('Parameter \'s3-upload-path\' is missing from config');
        }
        $dashPos = strpos($config['s3-upload-path'], '/');
        if ($dashPos === false) {
            $this->s3Bucket = $config['s3-upload-path'];
            $this->s3KeyPrefix = null;
        } else {
            $this->s3Bucket = substr($config['s3-upload-path'], 0, $dashPos);
            $this->s3KeyPrefix = substr($config['s3-upload-path'], $dashPos+1);
        }
    }

    protected function getClient()
    {
        if (!$this->client) {
            $this->client = new \Aws\S3\S3Client([
                'version' => '2006-03-01',
                'region' => $this->awsRegion,
                'retries' => 40,
                'credentials' => [
                    'key' => $this->awsKey,
                    'secret' => $this->awsSecret
                ]
            ]);
        }
        return $this->client;
    }

    /**
     * @param string $filePath Path to File
     * @param string $contentType Content Type
     * @return string
     * @throws \Exception
     */
    public function uploadFile($filePath, $contentType = 'text/plain')
    {
        $name = basename($filePath);
        $fp = fopen($filePath, 'r');
        if (!$fp) {
            throw new \Exception("File '$filePath' not found");
        }

        $result = $this->uploadString($name, $fp, $contentType);
        if (is_resource($fp)) {
            fclose($fp);
        }

        return $result;
    }

    /**
     * @param string $name File Name
     * @param string $content File Content
     * @param string $contentType Content Type
     * @return string
     */
    public function uploadString($name, $content, $contentType = 'text/plain')
    {
        $s3FileName = sprintf('%s-%s-%s', date('Y/m/d/Y-m-d-H-i-s'), uniqid(), $name);

        $this->getClient()->putObject(array(
            'Bucket' => $this->s3Bucket,
            'Key' => "$this->s3KeyPrefix/$s3FileName",
            'Body' => $content,
            'ACL' => 'private',
            'ContentType' => $contentType
        ));

        return 'https://connection.keboola.com/admin/utils/logs?file=' . $s3FileName;
    }
}
