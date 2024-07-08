<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class ebayScrapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebay-scrapping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ebay Scrapping';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        $crawler = $client->request('GET', 'https://www.symfony.com/blog/');


    }
}
