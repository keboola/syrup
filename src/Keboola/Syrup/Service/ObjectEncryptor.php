<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service;

use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Exception\UserException;

class ObjectEncryptor
{
    /** @var \Keboola\Syrup\Encryption\CryptoWrapper */
    protected $encryptor;

    const PREFIX = 'KBC::Encrypted==';

    /**
     * @param $encryptor
     */
    public function __construct($encryptor)
    {
        $this->setEncryptor($encryptor);
    }

    /**
     * @return \Keboola\Syrup\Encryption\CryptoWrapper
     */
    protected function getEncryptor()
    {
        return $this->encryptor;
    }

    /**
     * @param \Keboola\Syrup\Encryption\CryptoWrapper $encryptor
     * @return $this
     */
    protected function setEncryptor($encryptor)
    {
        $this->encryptor = $encryptor;

        return $this;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function encrypt($data)
    {
        if (is_scalar($data)) {
            return $this->encryptValue($data);
        }
        if (is_array($data)) {
            return $this->encryptArray($data);
        }
        throw new ApplicationException("Only arrays and strings are supported for encryption.");
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function decrypt($data)
    {
        if (is_scalar($data)) {
            return $this->decryptValue($data);
        }
        if (is_array($data)) {
            return $this->decryptArray($data);
        }
        throw new ApplicationException("Only arrays and strings are supported for decryption.");
    }

    /**
     * @param $value
     * @return string
     */
    protected function decryptValue($value)
    {
        if (substr($value, 0, 16) != self::PREFIX) {
            throw new UserException("'{$value}' is not an encrypted value.");
        }
        try {
            return $this->encryptor->decrypt(substr($value, 15));
        } catch (\Exception $e) {
            throw new ApplicationException("Decryption failed: " . $e->getMessage(), $e, ["value" => $value]);
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function encryptValue($value)
    {
        // return self if already encrypted
        if (substr($value, 0, 16) == self::PREFIX) {
            return $value;
        }

        try {
            return self::PREFIX . $this->encryptor->encrypt($value);
        } catch (\Exception $e) {
            throw new ApplicationException("Encryption failed: " . $e->getMessage(), $e, ["value" => $value]);
        }
    }

    /**
     * @param $data
     * @return array
     */
    protected function encryptArray($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (substr($key, 0, 1) == '#') {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $this->encryptValue($value);
                } elseif (is_array($value)) {
                    $result[$key] = $this->encryptArray($value);
                } else {
                    throw new ApplicationException("Only arrays and scalars are supported for encryption.");
                }
            } else {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $value;
                } elseif (is_array($value)) {
                    $result[$key] = $this->encryptArray($value);
                } else {
                    throw new ApplicationException("Only arrays and scalars are supported for encryption.");
                }
            }
        }
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    protected function decryptArray($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (substr($key, 0, 1) == '#') {
                if (is_scalar($value)) {
                    $result[$key] = $this->decryptValue($value);
                } elseif (is_array($value)) {
                    $result[$key] = $this->decryptArray($value);
                } else {
                    throw new ApplicationException("Only arrays and scalars are supported for decryption.");
                }
            } else {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $value;
                } elseif (is_array($value)) {
                    $result[$key] = $this->decryptArray($value);
                } else {
                    throw new ApplicationException("Only arrays and scalars are supported for decryption.");
                }
            }
        }
        return $result;
    }
}
