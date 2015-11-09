<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service;

use Keboola\Syrup\Encryption\BaseWrapper;
use Keboola\Syrup\Encryption\CryptoWrapperInterface;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Exception\UserException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ObjectEncryptor
{
    /**
     * DI service container.
     * @var ContainerInterface
     */
    protected $container;

    /**
     * List of known wrappers.
     * @var CryptoWrapperInterface[]
     */
    private $wrappers = [];

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
            try {
                return $this->decryptValue($data);
            } catch (\InvalidCiphertextException $e) {
                throw new UserException($e->getMessage(), $e);
            }
        }
        if (is_array($data)) {
            return $this->decryptArray($data);
        }
        throw new ApplicationException("Only arrays and strings are supported for decryption.");
    }

    /**
     * Manually add a known crypto wrapper. Generally, wrappers should be added to services.yml with tag
     * 'syrup.encryption.wrapper' - that way, they will be added automatically.
     * @param CryptoWrapperInterface $wrapper
     */
    public function pushWrapper(CryptoWrapperInterface $wrapper)
    {
        if (isset($this->wrappers[$wrapper->getPrefix()])) {
            throw new ApplicationException("Cryptowrapper prefix " . $wrapper->getPrefix() . " is not unique.");
        }
        $this->wrappers[$wrapper->getPrefix()] = $wrapper;
    }

    /**
     * Find a wrapper to decrypt a given cipher.
     * @param string $value Cipher text
     * @return CryptoWrapperInterface|null
     */
    protected function findWrapper($value)
    {
        $selectedWrapper = null;
        if (empty($this->wrappers)) {
            throw new ApplicationException("There are no wrappers registered for the encryptor.");
        }
        foreach ($this->wrappers as $wrapper) {
            if (substr($value, 0, mb_strlen($wrapper->getPrefix())) == $wrapper->getPrefix()) {
                $selectedWrapper = $wrapper;
            }
        }
        return $selectedWrapper;
    }

    /**
     * @param $value
     * @return string
     * @throws \InvalidCiphertextException
     */
    protected function decryptValue($value)
    {
        $wrapper = $this->findWrapper($value);
        if (!$wrapper) {
            throw new \InvalidCiphertextException("Value is not an encrypted value.");
        }
        try {
            return $wrapper->decrypt(substr($value, mb_strlen($wrapper->getPrefix())));
        } catch (\InvalidCiphertextException $e) {
            throw new \InvalidCiphertextException("Value $value is not an encrypted value.");
        } catch (\Exception $e) {
            // decryption failed for more serious reasons
            throw new ApplicationException("Decryption failed: " . $e->getMessage(), $e, ["value" => $value]);
        }
    }

    /**
     * @param string $value
     * @param CryptoWrapperInterface $wrapper
     * @return string
     */
    protected function encryptValue($value, CryptoWrapperInterface $wrapper)
    {
        // return self if already encrypted with any wrapper
        if ($this->findWrapper($value)) {
            return $value;
        }

        try {
            return $wrapper->getPrefix() . $wrapper->encrypt($value);
        } catch (\Exception $e) {
            throw new ApplicationException("Encryption failed: " . $e->getMessage(), $e, ["value" => $value]);
        }
    }

    /**
     * @param array $data
     * @param CryptoWrapperInterface $wrapper
     * @return array
     */
    protected function encryptArray(array $data, CryptoWrapperInterface $wrapper)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (substr($key, 0, 1) == '#') {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $this->encryptValue($value, $wrapper);
                } elseif (is_array($value)) {
                    $result[$key] = $this->encryptArray($value, $wrapper);
                } else {
                    throw new ApplicationException(
                        "Invalid item $key - only arrays and scalars are supported for encryption."
                    );
                }
            } else {
                if (is_scalar($value) || is_null($value)) {
                    $result[$key] = $value;
                } elseif (is_array($value)) {
                    $result[$key] = $this->encryptArray($value, $wrapper);
                } else {
                    throw new ApplicationException(
                        "Invalid item $key - only arrays and scalars are supported for encryption."
                    );
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
            try {
                if (substr($key, 0, 1) == '#') {
                    if (is_scalar($value)) {
                        $result[$key] = $this->decryptValue($value);
                    } elseif (is_array($value)) {
                        $result[$key] = $this->decryptArray($value);
                    } else {
                        throw new ApplicationException(
                            "Invalid item $key - only arrays and scalars are supported for decryption."
                        );
                    }
                } else {
                    if (is_scalar($value) || is_null($value)) {
                        $result[$key] = $value;
                    } elseif (is_array($value)) {
                        $result[$key] = $this->decryptArray($value);
                    } else {
                        throw new ApplicationException(
                            "Invalid item $key - only arrays and scalars are supported for decryption."
                        );
                    }
                }
            } catch (\InvalidCiphertextException $e) {
                throw new UserException("Invalid cipher text for key $key " . $e->getMessage(), $e);
            }
        }
        return $result;
    }
}
