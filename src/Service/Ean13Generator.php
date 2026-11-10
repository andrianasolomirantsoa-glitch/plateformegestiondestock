<?php

namespace App\Service;

use App\Repository\ProduitRepository;

class Ean13Generator
{
    /**
     * Génère un code EAN-13 unique pour un produit.
     *
     * Préfixe 20 : usage interne.
     */
    public function generate(ProduitRepository $repository): string
    {
        do {
            // 2 chiffres de préfixe + 10 chiffres aléatoires.
            $base = '20';

            for ($i = 0; $i < 10; $i++) {
                $base .= (string) random_int(0, 9);
            }

            $code = $base . self::calculateCheckDigit($base);

        } while ($repository->findOneBy(['codeBarres' => $code]) !== null);

        return $code;
    }

    /**
     * Calcule le 13e chiffre de contrôle EAN-13.
     */
    public static function calculateCheckDigit(string $twelveDigits): int
    {
        if (!preg_match('/^\d{12}$/', $twelveDigits)) {
            throw new \InvalidArgumentException(
                'Le code doit contenir exactement 12 chiffres.'
            );
        }

        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $twelveDigits[$i];

            // Positions impaires : poids 1.
            // Positions paires : poids 3.
            $sum += $digit * (($i % 2 === 0) ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Vérifie la validité d'un code EAN-13.
     */
    public static function isValid(string $code): bool
    {
        if (!preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        $base = substr($code, 0, 12);
        $checkDigit = self::calculateCheckDigit($base);

        return (int) $code[12] === $checkDigit;
    }
}