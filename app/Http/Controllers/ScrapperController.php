<?php

namespace App\Http\Controllers;

use App\Events\ScrapingLog;
use DeepCopy\Filter\Filter;
use Exception;
use HTMLDomParser\DomFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ScrapperController extends Controller
{
    //
    public function showScrapingLog()
    {
        return view('scraping');
    }


    public function scrapEbay(){
        
        $client = new Client();
        $response = $client->request('GET', 'https://www.ebay.com/sch/i.html?_nkw=1997%20tops%20Derek%20jeter');

        $html = $response->getBody()->getContents();

        dd($html);
    }
    public function scrapSportCard(){
        $results = [];
        
        $client = new Client();
    
        $response = $client->request('GET', 'https://www.sportscardchecklist.com/sport-baseball/vintage-and-new-release-trading-card-checklists');
        $html = DomFactory::load($response->getBody()->getContents());
        
        
        $years = $html->find('.col-sm-3 ul li a');
        
        foreach($years as $year){
            
            $client_year = new Client();

            $response_year = $client_year->request('GET', $year->getAttribute('href'))->getBody()->getContents();

            $year_html = DomFactory::load($response_year);

            $brands = $year_html->find('.col-sm-4 ul li a');
            
            
            $results[$year->text()] = [];


            foreach($brands as $brand){
                 
                $client_brand = new Client();

                $response_brand = $client_brand->request('GET', $brand->getAttribute('href'))->getBody()->getContents();
                
                $brand_html = DomFactory::load($response_brand);

                $results[$year->text()][$brand->text()] = [];
                
                $cards = $brand_html->find('li.li-underline a');

                
                

                foreach($cards as $card){

                    $client_cards = new Client();

                    $response_cards = $client_cards->request('GET', $card->getAttribute('href'))->getBody()->getContents();

                    $cards_html = DomFactory::load($response_cards);

                    $results[$year->text()][$brand->text()][] = $cards_html->findOne('h5.h4')->text();
                    dd($results);
                }
            }        
        }

        
        
        return response()->json($response);
      
    }

    public function improveScrapping()
    {
        $results = [];

        $client = new Client();
        $imageDir = 'public/images/baseball';
        
        if (!Storage::exists($imageDir)) {
            Storage::makeDirectory($imageDir);
        }

        $response = $client->request('GET', 'https://www.sportscardchecklist.com/sport-basketball/vintage-and-new-release-trading-card-checklists');
        $html = DomFactory::load($response->getBody()->getContents());

        $years = $html->find('.col-sm-3 li.li-underline a');
        
        foreach($years as $year){
            $year_response = $client->request('GET', $year->getAttribute('href'));
            $year_html = DomFactory::load($year_response->getBody()->getContents());
    
            $brands = $year_html->find('li.li-underline a');
    
            $results[$year->text()] = [];
    
            foreach ($brands as $brand) {
                try {
                    $response = $client->request('GET', $brand->getAttribute('href'));
                    $url = $brand->getAttribute('href');
                    echo "Fetching Brand-URL: $url";
                    
                    $clusters_html = DomFactory::load($client->request('GET', $url)->getBody()->getContents());
                    
                    $clusters = $clusters_html->find('li.li-underline a[rel=bookmark]');
    
                    $results[$year->text()][$brand->text()] = [];
                    foreach($clusters as $key => $cluster){
    
                        $url = $cluster->getAttribute('href');
    
                        $card_response = $client->request('GET', $cluster->getAttribute('href'));
    
                        echo "Fetching Brand-Cluster: $url";
                        
                        $cards = DomFactory::load($card_response->getBody()->getContents())->find('div.smart-container div.panel.panel-primary');
                        
    
                        $results[$year->text()][$brand->text()][trim($cluster->text())] = [];
    
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
    
    
                            $results[$year->text()][$brand->text()][$cluster->text()][] = $cardinfo;
                            
                        }    
    
                    }
        
                } catch (\Exception $e) {
                    Log::error("Error fetching URL: " . $e->getMessage());
                    event(new ScrapingLog("Error fetching URL: " . $e->getMessage()));
                }
            }
        }


        File::put('baseball.json', json_encode($results));

        echo "Scraping complete! Check logs for details.";
    }

}
