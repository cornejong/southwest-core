<?php

/*
 * Created on Sat Apr 06 2019
 *
 * Copyright (c) 2019 SouthCoast Technologies
 */

namespace SouthCoast\SouthWest\Component\Cryptographic;

/**
 * SouthCoast\Cryptographic KeyExchange
 *
 * This object helps to perform a Diffie Hellman based key exchange
 * that will result in a hashed and base64 encoded cryptographic key that can
 * be used for end to end encryption
 *
 * @property int        $alpha      The first public value
 * @property int        $beta       The second public value
 * @property int        $delta      The private value
 * @property int|float  $zeta       The shared public value based on Alpha & Beta
 * @property int|float  $sigma      The correspondents Sigma value
 * @property string     $key        The resulted cryptographic key
 *
 * Usage:
 *
 * $a = new KeyExchange;
 * $b = new KeyExchange($a->getAlpha(), $a->getBeta());
 *
 * $a->setSigma($b->getZeta());
 * $b->setSigma($a->getZeta());
 *
 * $key_a = $a->calculateKey();
 * $key_b = $b->calculateKey();
 *
 */
class KeyExchange
{
    const MIN_INT_VAL = 10;

    const MAX_ONT_VAL = 999;

    /**
     * The first public calculation int value
     *
     * @var int
     */
    private $alpha;

    /**
     * The second public calculation int value
     *
     * @var int
     */
    private $beta;

    /**
     * The private calculation value
     *
     * @var int
     */
    private $delta;

    /**
     * The to be shared shared public value based on Alpha & Delta
     *
     * @var int|float
     */
    private $zeta;

    /**
     * The Zeta value from your correspondent
     *
     * @var int|float
     */
    private $sigma;

    /**
     * The resulted cryptographic key
     *
     * @var string
     */
    private $key;

    /**
     * This setup function initializes the KeyExchange
     * It sets the Alpha, Beta, Delta & Zeta values.
     *
     * If you are the initializing party in the exchange (Client),
     *      you won't have to declare the Alpha & Beta values.
     *      These will be generated for you.
     *
     * If you are the receiving party in the exchange (Server),
     *      you need to provide the Alpha & Beta values that were provided to you.
     *
     * @param integer $alpha    The first public calculation int value
     * @param integer $beta     The second public calculation int value
     */
    public function __construct(int $alpha = null, int $beta = null)
    {
        /* Set the Alpha value if provided, else generate it */
        $this->setAlpha($alpha ?? $this->generateRandomPrime());
        /* Set the Beta Value if provided, else generate it */
        $this->setBeta($beta ?? rand(KeyExchange::MIN_INT_VAL, KeyExchange::MAX_ONT_VAL));
        /* Set the Delta Value if provided, else generate it */
        $this->setDelta($this->generateRandomPrime());
        /* Calculate the Zeta value on the values set above */
        $this->setZeta($this->calculateZeta());
    }

    /**
     * This method calculates the Zeta value based on Alpha & Delta
     *
     * @return int
     */
    public function calculateZeta(): int
    {
        return pow($this->alpha / 100, $this->delta / 100);
    }

    /**
     * @param $sigma
     * @return mixed
     */
    public function calculateKey($sigma = null)
    {
        if (!is_null($sigma)) {
            $this->setSigma($sigma);
        }

        $this->key = base64_encode(hash('ripemd320', pow($this->sigma, $this->delta)));
        return $this->key;
    }

    /**
     * This method returns a random prime number between $start = 10 & $end = 400
     *
     * @param integer $minimum
     * @param integer $maximum
     * @return integer
     */
    public function generateRandomPrime(int $minimum = 10, int $maximum = 400): int
    {
        /* Create the options array */
        $options = [];
        /* Loop until we reached the $end */
        for ($number = $minimum; $number <= $maximum; $number++) {
            /* If the current number is a prime number */
            if ($this->isPrime($number)) {
                /* Add it to the options */
                $options[] = $number;
            }
        }
        /* Return a random value from the possible options */
        return array_rand($options);
    }

    /**
     * Checks if the $number is a prime number
     *
     * @param int|float $number
     * @return boolean
     *
     * @todo Better document this method
     */
    public function isPrime($number): bool
    {
        /* One and non numeric values are never prime numbers */
        if (!is_numeric($number) || $number === 1) {
            return false;
        }
        /* Two is the only even prime number */
        if ($number === 2) {
            return true;
        }
        // square root algorithm speeds up testing of bigger prime numbers
        $x = sqrt($number);
        $x = floor($x);

        for ($i = 2; $i <= $x; ++$i) {
            if ($number % $i == 0) {
                break;
            }
        }

        if ($x == $i - 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Getters & Setters
     */

    /**
     * returns alpha and beta as array
     *
     * @return array
     */
    public function getAlphaBeta(): array
    {
        return [
            'alpha' => $this->alpha,
            'beta' => $this->beta,
        ];
    }

    /**
     * Get the value of alpha
     *
     * @return int
     */
    public function getAlpha(): int
    {
        return $this->alpha;
    }

    /**
     * Set the value of alpha
     *
     * @param int $alpha
     * @return  self
     * @throws \Exception
     */
    public function setAlpha(int $alpha)
    {
        if (!is_numeric($alpha)) {
            throw new \Exception('The value provided for $alpha is not a numeric value! Provided: ' . $alpha, 1);
        }

        $this->alpha = $alpha;

        return $this;
    }

    /**
     * Get the value of beta
     *
     * @return int
     */
    public function getBeta(): int
    {
        return $this->beta;
    }

    /**
     * Set the value of beta
     *
     * @param int $beta
     * @return  self
     * @throws \Exception
     */
    public function setBeta(int $beta)
    {
        if (!is_numeric($beta)) {
            throw new \Exception('The value provided for $beta is not a numeric value! Provided: ' . $beta, 1);
        }

        $this->beta = $beta;

        return $this;
    }

    /**
     * Get the value of delta
     *
     * @return int
     */
    public function getDelta(): int
    {
        return $this->delta;
    }

    /**
     * Set the value of delta
     *
     * @param int $delta
     * @return  self
     * @throws \Exception
     */
    public function setDelta(int $delta)
    {
        if (!is_numeric($delta)) {
            throw new \Exception('The value provided for $beta is not a numeric value! Provided: ' . $delta, 1);
        }

        $this->delta = $delta;

        return $this;
    }

    /**
     * Get the value of key
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get the value of zeta
     *
     * @return int|float
     */
    public function getZeta()
    {
        return $this->zeta;
    }

    /**
     * Set the value of zeta
     *
     * @param int|float $zeta
     * @return  self
     * @throws \Exception
     */
    private function setZeta(int $zeta)
    {
        if (!is_numeric($zeta)) {
            throw new \Exception('The value provided for $zeta is not a numeric value! Provided: ' . $zeta, 1);
        }

        $this->zeta = $zeta;

        return $this;
    }

    /**
     * Get the value of sigma
     *
     * @return int|float
     */
    public function getSigma()
    {
        return $this->sigma;
    }

    /**
     * Set the value of sigma
     *
     * @param int|float $sigma
     * @return  self
     * @throws \Exception
     */
    public function setSigma(int $sigma)
    {
        if (!is_numeric($sigma)) {
            throw new \Exception('The value provided for $sigma is not a numeric value! Provided: ' . $sigma, 1);
        }

        $this->sigma = $sigma;

        return $this;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->key ?? $this->zeta;
    }

}
