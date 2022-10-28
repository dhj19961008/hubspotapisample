<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateHubSpotList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createcontactlist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $endpoint = 'https://api.hubapi.com/contacts/v1/lists';


        $header = array(
            "Content-Type: application/json",
            "Authorization: Bearer " . env('HUBSPOT_APP_TOKEN')
        );
        $data = json_encode(array(
            'name' => time(),
            "dynamic" => false, //true=動的リストの作成,false=静的リストの作成
        ));
        $options = array(
            'http' => array(
                'protocol_version' => '1.1',
                'method' => 'POST',
                'header' => $header,
                'content' => $data
            )
        );


        $context = stream_context_create($options);
        $response = json_decode(file_get_contents($endpoint, true, $context));

        dd($response->listId); //レスポンスからリストIDを取得可能
    }
}
