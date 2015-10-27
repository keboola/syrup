<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service;

use Guzzle\Service\Exception\ServiceNotFoundException;
use Keboola\Syrup\Encryption\BaseWrapper;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Exception\UserException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ObjectEncryptor
{
    protected $container;

    const PREFIX = 'KBC::Encrypted==';

    /**
     * @param ContainerInterface $container DI container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string|array $data Data to encrypt
     * @param string $wrapperName Service name of encryptor wrapper
     * @return mixed
     */
    public function encrypt($data, $wrapperName = 'syrup.encryption.base_wrapper')
    {
        /** @var BaseWrapper $wrapper */
        try {
            $wrapper = $this->container->get($wrapperName);
        } catch (ServiceNotFoundException $e) {
            throw new ApplicationException("Invalid crypto wrapper " . $wrapperName, $e);
        }
        if (is_scalar($data)) {
            return $this->encryptValue($data, $wrapper);
        }
        if (is_array($data)) {
            return $this->encryptArray($data, $wrapper);
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
     * Find a wrapper to decrypt a given cipher.
     * @param string $value Cipher text
     * @return BaseWrapper
     */
    protected function findWrapper($value)
    {
        if (substr($value, 0, 16) != self::PREFIX) {
            throw new UserException("'{$value}' is not an encrypted value.");
        } else {
            $wrapper = $this->container->get('syrup.encryption.base_wrapper');
        }
        return $wrapper;
    }

    /**
     * @param $value
     * @return string
     */
    protected function decryptValue($value)
    {
        $wrapper = $this->findWrapper($value);
        try {
            return $wrapper->decrypt(substr($value, 16));
        } catch (\InvalidCiphertextException $e) {
            // the key or cipher text is wrong - return the original one
            return $value;
        } catch (\Exception $e) {
            // decryption failed for more serious reasons
            throw new ApplicationException("Decryption failed: " . $e->getMessage(), $e, ["value" => $value]);
        }
    }

    /**
     * @param string $value
     * @param BaseWrapper $wrapper
     * @return string
     */
    protected function encryptValue($value, BaseWrapper $wrapper)
    {
        // return self if already encrypted
        if (substr($value, 0, 16) == self::PREFIX) {
            return $value;
        }

        try {
            return self::PREFIX . $wrapper->encrypt($value);
        } catch (\Exception $e) {
            throw new ApplicationException("Encryption failed: " . $e->getMessage(), $e, ["value" => $value]);
        }
    }

    /**
     * @param array $data
     * @param BaseWrapper $wrapper
     * @return array
     */
    protected function encryptArray(array $data, BaseWrapper $wrapper)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (substr($key, 0, 1) == '#') {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $this->encryptValue($value, $wrapper);
                } elseif (is_array($value)) {
                    $result[$key] = $this->encryptArray($value, $wrapper);
                } else {
                    throw new ApplicationException("Only arrays and scalars are supported for encryption.");
                }
            } else {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $value;
                } elseif (is_array($value)) {
                    $result[$key] = $this->encryptArray($value, $wrapper);
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
