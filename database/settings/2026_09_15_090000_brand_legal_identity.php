<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * L'identité de l'éditeur, enfin renseignée (T-242).
 *
 * `legal_entity` et `legal_address` existaient depuis le bloc 01 mais étaient
 * **vides** : `config/brand.php` les lisait dans `BRAND_LEGAL_ENTITY`, ligne
 * présente et vide dans le `.env`. La substitution du LegalController faisait
 * alors exactement son travail — remplacer le gabarit par la valeur — et la
 * page affichait « Le représentant légal de . », sans raison sociale, sans
 * numéro, sans adresse. Non-conformité LCEN, et signal de confiance inversé :
 * un site marchand dont personne ne sait qui le tient.
 *
 * Six réglages s'ajoutent, tous exigés par l'article 6 III-1 de la LCEN et
 * par l'article 19 pour la vente en ligne : la forme juridique, le numéro
 * d'immatriculation, le SIRET du siège, le numéro de TVA, le directeur de la
 * publication et l'hébergeur.
 *
 * Les valeurs viennent de `config/brand.php`, comme à la création des
 * réglages. Les deux réglages qui existaient déjà ne sont écrasés **que s'ils
 * sont vides** : une valeur saisie dans l'administration n'est jamais
 * remplacée par une migration.
 */
return new class extends SettingsMigration
{
    /** @var list<string> Les réglages créés par cette migration. */
    private const NOUVEAUX = [
        'legal_form',
        'legal_siren',
        'legal_siret',
        'legal_vat',
        'legal_publication_director',
        'legal_host',
        'legal_host_media',
        'legal_host_location',
    ];

    /** @var list<string> Ceux qui existaient, vides depuis le bloc 01. */
    private const EXISTANTS = ['legal_entity', 'legal_address'];

    public function up(): void
    {
        foreach (self::NOUVEAUX as $property) {
            $this->migrator->add("brand.{$property}", $this->config($property));
        }

        foreach (self::EXISTANTS as $property) {
            $this->migrator->update(
                "brand.{$property}",
                fn (string $current): string => trim($current) === '' ? $this->config($property) : $current,
            );
        }
    }

    public function down(): void
    {
        foreach (self::NOUVEAUX as $property) {
            $this->migrator->delete("brand.{$property}");
        }

        foreach (self::EXISTANTS as $property) {
            $this->migrator->update(
                "brand.{$property}",
                fn (string $current): string => $current === $this->config($property) ? '' : $current,
            );
        }
    }

    private function config(string $property): string
    {
        return (string) config("brand.{$property}");
    }
};
