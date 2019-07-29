<?php

declare(strict_types=1);

namespace PTLS;

use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;
use Mdanter\Ecc\Serializer\Signature\DerSignatureSerializer;

class EcDSA
{
    private $adapter;
    private $pemSerializer;
    private $gen;
    private $privateKey;

    public static function isValidPrivateKey($privateKeyPem)
    {
        return is_string($privateKeyPem) &&
                   strpos($privateKeyPem, '-----BEGIN EC PRIVATE KEY-----') !== false
               ? true : false;
    }

    public function __construct($privateKeyPem)
    {
        $this->adapter = EccFactory::getAdapter();
        $this->pemSerializer = new PemPrivateKeySerializer(new DerPrivateKeySerializer($this->adapter));
        $this->privateKey = $this->pemSerializer->parse($privateKeyPem);
        $this->gen = $this->privateKey->getPoint();
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function getGenerator()
    {
        return $this->gen;
    }

    public function getSignature($dataSign, $hashAlgo)
    {
        $signer = new Signer($this->adapter);
        $privateKey = $this->getPrivateKey();

        $hash = $signer->hashData($this->gen, $hashAlgo, $dataSign);

//        $random = RandomGeneratorFactory::getRandomGenerator();
        $random = RandomGeneratorFactory::getHmacRandomGenerator($privateKey, $hash, $hashAlgo);

        $randomK = $random->generate($this->gen->getOrder());

        $signature = $signer->sign($privateKey, $hash, $randomK);

        $serializer = new DerSignatureSerializer();
        $serializedSig = $serializer->serialize($signature);

        return $serializedSig;
    }
}
