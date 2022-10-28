<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddContactToList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:addcontacttolist';

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
        $list_id = '1309';
        $endpoint = 'https://api.hubapi.com/contacts/v1/lists/' . $list_id . '/add';

        $header = array(
            "Content-Type: application/json",
            "Authorization: Bearer " . env('HUBSPOT_APP_TOKEN')
        );
        $data = json_encode(array(
            'vids' => [11254101,7457451,8872501] //HubSpot上のコンタクトIDを配列で指定
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

        dd($response);
    }
}
