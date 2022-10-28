<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReadHubSpotList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:readhubspotlist';

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
        $has_more = true;
        $offset = null;

        while ($has_more == true) {

            if (empty($offset)) {
                $query = http_build_query(array(
                    'count' => '250'
                    //一度のリクエストで取得できる最大件数は250
                ), '');
            } else {
                $query = http_build_query(array(
                    'offset'  => $offset,
                    'count' => '250'
                ));
            }

            $header = array(
                "Authorization: Bearer " . env('HUBSPOT_APP_TOKEN')
            );
            $options = array(
                'http' => array(
                    'protocol_version' => '1.1',
                    'method' => 'GET',
                    'header' => $header,
                )
            );

            $requestUrl = $endpoint . '?' . $query;
            $context = stream_context_create($options);
            $response = json_decode(file_get_contents($requestUrl, true, $context));

            $offset = $response->offset;
            $has_more = $response->{'has-more'};

            foreach ($response->lists as $list) {
                $this->info($list->name);
            }
        }
    }
}
