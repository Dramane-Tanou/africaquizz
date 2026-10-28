<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $regionsData = [
            'afrique-du-nord' => [
                'name' => 'Afrique du Nord',
                'countries' => [
                    ['iso2'=>'DZ','iso3'=>'DZA','name'=>'Algérie','capital'=>'Alger'],
                    ['iso2'=>'EG','iso3'=>'EGY','name'=>'Égypte','capital'=>'Le Caire'],
                    ['iso2'=>'LY','iso3'=>'LBY','name'=>'Libye','capital'=>'Tripoli'],
                    ['iso2'=>'MA','iso3'=>'MAR','name'=>'Maroc','capital'=>'Rabat'],
                    ['iso2'=>'SD','iso3'=>'SDN','name'=>'Soudan','capital'=>'Khartoum'],
                    ['iso2'=>'TN','iso3'=>'TUN','name'=>'Tunisie','capital'=>'Tunis'],
                ],
            ],
            'afrique-de-louest' => [
                'name' => 'Afrique de l\'Ouest',
                'countries' => [
                    ['iso2'=>'BJ','iso3'=>'BEN','name'=>'Bénin','capital'=>'Porto-Novo'],
                    ['iso2'=>'BF','iso3'=>'BFA','name'=>'Burkina Faso','capital'=>'Ouagadougou'],
                    ['iso2'=>'CV','iso3'=>'CPV','name'=>'Cap-Vert','capital'=>'Praia'],
                    ['iso2'=>'CI','iso3'=>'CIV','name'=>'Côte d\'Ivoire','capital'=>'Yamoussoukro'],
                    ['iso2'=>'GM','iso3'=>'GMB','name'=>'Gambie','capital'=>'Banjul'],
                    ['iso2'=>'GH','iso3'=>'GHA','name'=>'Ghana','capital'=>'Accra'],
                    ['iso2'=>'GN','iso3'=>'GIN','name'=>'Guinée','capital'=>'Conakry'],
                    ['iso2'=>'GW','iso3'=>'GNB','name'=>'Guinée-Bissau','capital'=>'Bissau'],
                    ['iso2'=>'LR','iso3'=>'LBR','name'=>'Liberia','capital'=>'Monrovia'],
                    ['iso2'=>'ML','iso3'=>'MLI','name'=>'Mali','capital'=>'Bamako'],
                    ['iso2'=>'MR','iso3'=>'MRT','name'=>'Mauritanie','capital'=>'Nouakchott'],
                    ['iso2'=>'NE','iso3'=>'NER','name'=>'Niger','capital'=>'Niamey'],
                    ['iso2'=>'NG','iso3'=>'NGA','name'=>'Nigeria','capital'=>'Abuja'],
                    ['iso2'=>'SN','iso3'=>'SEN','name'=>'Sénégal','capital'=>'Dakar'],
                    ['iso2'=>'SL','iso3'=>'SLE','name'=>'Sierra Leone','capital'=>'Freetown'],
                    ['iso2'=>'TG','iso3'=>'TGO','name'=>'Togo','capital'=>'Lomé'],
                ],
            ],
            'afrique-centrale' => [
                'name' => 'Afrique Centrale',
                'countries' => [
                    ['iso2'=>'AO','iso3'=>'AGO','name'=>'Angola','capital'=>'Luanda'],
                    ['iso2'=>'CM','iso3'=>'CMR','name'=>'Cameroun','capital'=>'Yaoundé'],
                    ['iso2'=>'CF','iso3'=>'CAF','name'=>'République centrafricaine','capital'=>'Bangui'],
                    ['iso2'=>'CG','iso3'=>'COG','name'=>'République du Congo','capital'=>'Brazzaville'],
                    ['iso2'=>'CD','iso3'=>'COD','name'=>'République démocratique du Congo','capital'=>'Kinshasa'],
                    ['iso2'=>'GA','iso3'=>'GAB','name'=>'Gabon','capital'=>'Libreville'],
                    ['iso2'=>'GQ','iso3'=>'GNQ','name'=>'Guinée équatoriale','capital'=>'Malabo'],
                    ['iso2'=>'ST','iso3'=>'STP','name'=>'São Tomé-et-Príncipe','capital'=>'São Tomé'],
                    ['iso2'=>'TD','iso3'=>'TCD','name'=>'Tchad','capital'=>'N\'Djaména'],
                ],
            ],
            'afrique-de-lest' => [
                'name' => 'Afrique de l\'Est',
                'countries' => [
                    ['iso2'=>'BI','iso3'=>'BDI','name'=>'Burundi','capital'=>'Gitega'],
                    ['iso2'=>'KM','iso3'=>'COM','name'=>'Comores','capital'=>'Moroni'],
                    ['iso2'=>'DJ','iso3'=>'DJI','name'=>'Djibouti','capital'=>'Djibouti'],
                    ['iso2'=>'ER','iso3'=>'ERI','name'=>'Érythrée','capital'=>'Asmara'],
                    ['iso2'=>'ET','iso3'=>'ETH','name'=>'Éthiopie','capital'=>'Addis-Abeba'],
                    ['iso2'=>'KE','iso3'=>'KEN','name'=>'Kenya','capital'=>'Nairobi'],
                    ['iso2'=>'MG','iso3'=>'MDG','name'=>'Madagascar','capital'=>'Antananarivo'],
                    ['iso2'=>'MW','iso3'=>'MWI','name'=>'Malawi','capital'=>'Lilongwe'],
                    ['iso2'=>'MU','iso3'=>'MUS','name'=>'Maurice','capital'=>'Port-Louis'],
                    ['iso2'=>'MZ','iso3'=>'MOZ','name'=>'Mozambique','capital'=>'Maputo'],
                    ['iso2'=>'UG','iso3'=>'UGA','name'=>'Ouganda','capital'=>'Kampala'],
                    ['iso2'=>'RW','iso3'=>'RWA','name'=>'Rwanda','capital'=>'Kigali'],
                    ['iso2'=>'SC','iso3'=>'SYC','name'=>'Seychelles','capital'=>'Victoria'],
                    ['iso2'=>'SO','iso3'=>'SOM','name'=>'Somalie','capital'=>'Mogadiscio'],
                    ['iso2'=>'SS','iso3'=>'SSD','name'=>'Soudan du Sud','capital'=>'Djouba'],
                    ['iso2'=>'TZ','iso3'=>'TZA','name'=>'Tanzanie','capital'=>'Dodoma'],
                    ['iso2'=>'ZM','iso3'=>'ZMB','name'=>'Zambie','capital'=>'Lusaka'],
                    ['iso2'=>'ZW','iso3'=>'ZWE','name'=>'Zimbabwe','capital'=>'Harare'],
                ],
            ],
            'afrique-australe' => [
                'name' => 'Afrique Australe',
                'countries' => [
                    ['iso2'=>'ZA','iso3'=>'ZAF','name'=>'Afrique du Sud','capital'=>'Pretoria'],
                    ['iso2'=>'BW','iso3'=>'BWA','name'=>'Botswana','capital'=>'Gaborone'],
                    ['iso2'=>'SZ','iso3'=>'SWZ','name'=>'Eswatini','capital'=>'Mbabane'],
                    ['iso2'=>'LS','iso3'=>'LSO','name'=>'Lesotho','capital'=>'Maseru'],
                    ['iso2'=>'NA','iso3'=>'NAM','name'=>'Namibie','capital'=>'Windhoek'],
                ],
            ],
        ];

        $totalCountries = 0;
        foreach ($regionsData as $slug => $data) {
            $region = Region::updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name']]
            );

            foreach ($data['countries'] as $c) {
                Country::updateOrCreate(
                    ['iso2' => $c['iso2']],
                    [
                        'region_id' => $region->id,
                        'iso3' => $c['iso3'],
                        'slug' => Str::slug($c['name']),
                        'capital' => $c['capital'],
                        'status' => 'member',
                    ]
                );
                $totalCountries++;
            }
        }

        $worldRegion = Region::updateOrCreate(
            ['slug' => 'reste-du-monde'],
            ['name' => 'Reste du monde']
        );
        $isoCountries = json_decode(
            file_get_contents(database_path('data/iso-countries.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $worldCount = 0;
        foreach ($isoCountries['countries'] as [$iso2, $iso3]) {
            if (Country::where('iso2', $iso2)->exists()) {
                continue;
            }

            Country::create([
                'region_id' => $worldRegion->id,
                'iso2' => $iso2,
                'iso3' => $iso3,
                'slug' => strtolower($iso2),
                'capital' => null,
                'status' => 'member',
            ]);
            $worldCount++;
        }

        $this->command->info("CountrySeeder terminé : $totalCountries pays africains et $worldCount autres pays/territoires ajoutés.");
        $this->command->info('Catalogue ISO : ' . Country::count() . ' pays et territoires disponibles.');
    }
}