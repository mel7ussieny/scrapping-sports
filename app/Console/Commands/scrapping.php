<?php

namespace App\Console\Commands;

use Exception;
use HTMLDomParser\DomFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Str;


class scrapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrapping {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapping';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = $this->option('url');
        
        preg_match("/com(.*)\/year/", $url, $matches);
        $sport = Str::slug($matches[1]);
        preg_match("/year-(\d+)/", $url, $matches);
        $dir_year = $matches[1];

        $results = [];

        $client = new Client();
        $imageDir = "public/images/$sport";
        
        if (!Storage::exists($imageDir)) {
            Storage::makeDirectory($imageDir);
        }
    
        $year_response = $client->request('GET', $url);
        $year_html = DomFactory::load($year_response->getBody()->getContents());

        $brands = $year_html->find('li.li-underline a');

        $results[$dir_year] = [];

        foreach ($brands as $brand) {
            try {
                $url = $brand->getAttribute('href');
                echo "Fetching Brand-URL: $url \n";
                
                $clusters_html = DomFactory::load($client->request('GET', $url)->getBody()->getContents());
                
                $clusters = $clusters_html->find('li.li-underline a[rel=bookmark]');

                $results[$dir_year][$brand->text()] = [];
                foreach($clusters as $key => $cluster){

                    $url = $cluster->getAttribute('href');

                    $card_response = $client->request('GET', $cluster->getAttribute('href'));

                    echo "Fetching Brand-Cluster: $url \n";
                    
                    $cards = DomFactory::load($card_response->getBody()->getContents())->find('div.smart-container div.panel.panel-primary');
                    

                    $results[$dir_year][$brand->text()][trim($cluster->text())] = [];

                    foreach($cards as $card){
                        
                        $cardinfo = [];

                        
                        foreach($card->find('img') as $img){
                            $cardinfo['imgs'][] = basename($img->getAttribute('src'));

                            $imgPath = $imageDir . '/' . basename($img->getAttribute('src'));
                            $imgData = $client->request('GET', $img->getAttribute('src'))->getBody()->getContents();
                            Storage::put($imgPath, $imgData);    
                        }
                        
                        
                        try{
                            $cardinfo['name'] = $card->findOne('h5.h4')->text();
                        }catch(Exception $e){
                            $cardinfo['name'] = null;
                        }

                        if($details = $card->findOne('p.view-sales-head'))
                            $cardinfo['details'] = str_replace(["\r\n", "\r", "\n"], "", $details->text());


                        $results[$dir_year][$brand->text()][$cluster->text()][] = $cardinfo;
                        
                    }    

                }
    
            } catch (\Exception $e) {
                Log::error("Error fetching URL: " . $e->getMessage());
            }
        }


        Storage::put("$sport/$dir_year.json", json_encode($results));

        echo "Scraping complete! Check logs for details.";

    }
}
