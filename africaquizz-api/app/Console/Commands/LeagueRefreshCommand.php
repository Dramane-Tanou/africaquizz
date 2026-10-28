<?php
namespace App\Console\Commands;

use App\Services\LeagueService;
use Illuminate\Console\Command;

class LeagueRefreshCommand extends Command
{
    protected $signature = 'league:refresh';
    protected $description = 'Crée les ligues de la semaine et met à jour l\'XP';

    public function handle(LeagueService $service)
    {
        $created = $service->createCurrentWeekLeagues();
        $this->info('Ligues : ' . json_encode($created));
        $updated = $service->refreshCurrentWeekXp();
        $this->info("$updated membres mis à jour.");
    }
}