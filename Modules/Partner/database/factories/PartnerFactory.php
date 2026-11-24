<?php

namespace Modules\Partner\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'country_id' => Country::factory(),
            'name' => ['fr' => $name, 'en' => $name],
            'description' => ['fr' => 'Déménagement clés en main.', 'en' => 'Turnkey moving.'],
            'website' => ['fr' => 'https://example.test/fr', 'en' => 'https://example.test/en'],
            'cta_label' => ['fr' => 'Demander un devis', 'en' => 'Get a quote'],
            'status' => PartnerStatusEnum::PUBLISHED,
        ];
    }
}
