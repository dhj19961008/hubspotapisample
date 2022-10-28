<?php

namespace App\Console\Commands;

use App\Models\Sc_clinic;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PushStationAndLineToHubSpotContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pushstationandline';

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
        $endpoint = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
        //HubSpotのコンタクト一覧を検索するエンドポイントを使用。
        //今回はV3のAPIを使用。V1ではプロパティでの絞り込みが出来なそうなため。
        //下記にエンドポイントの詳細について記述あり。
        //https://developers.hubspot.com/docs/api/crm/search

        $after = 0;
        //検索結果のページングを初期化

        $i = 0;
        //ループ回数をカウント

        while (isset($after)) {

            $i = $i + 1;
            $this->info('Loop Count is [' . $i . ']');

            $data = json_encode(array(
                'properties'    => ['clinic_id', 'firstname', 'usertype'],
                'filterGroups' => [array('filters' => [array('propertyName' => 'usertype', 'operator' => 'EQ', 'value' => 'クリニック')])],
                //①
                //HubSpot内でユーザータイプ（物理名：usertype、論理名：ユーザータイプ）が"クリニック"であるものだけ抽出。

                //②
                //{"properties":"filterGroups":{"filters":{"propertyName":"username","operator":"EQ","value":""}}} ←この形式では400エラーが返される。
                //{"properties":"filterGroups":[{"filters":[{"propertyName":"username","operator":"EQ","value":""}]}]} ←この形式なら通る。

                //③
                //operatorは演算子を表す。詳細は下記ドキュメントを参照。
                //https://developers.hubspot.com/docs/cms/hubl/operators-and-expression-tests

                'limit' => 100,
                'after' => $after,
                //1回のリクエストで取得する検索結果数。デフォルトは10で最大値は100。
            ));
            //ここまででリクエストにつけるJSONデータを作成。

            $header = array(
                "Content-Type: application/json",
                "Authorization: Bearer " . env('HUBSPOT_APP_TOKEN')
                //パラメータはJSONで飛ばす必要あり。URLにAPI_KEYをつける認証方法は近々廃止予定なのでトークンを使用。
            );
            $options = array(
                'http' => array(
                    'protocol_version' => '1.1',
                    'method' => 'POST',
                    //POSTメソッドのみ可
                    'header' => $header,
                    'content' => $data,
                )
            );

            $context = stream_context_create($options);
            $response = json_decode(file_get_contents($endpoint, true, $context));
            //コンタクト検索のエンドポイントにリクエスト送信

            unset($after);

            $span = 0.01;
            //歯科医院から最寄駅と呼べるまでの距離

            foreach ($response->results as $key => $result) {
                $st_concat = '';
                //この後駅IDを文字列で連結
                $ln_concat = '';
                //この後路線IDを文字列で連結
                if (isset($result->properties->clinic_id)) {
                    //HubSpotの結果が（弊社で手動入力した）クリニックIDを持っているか判定
                    //持っている場合に処理

                    $clinic = Sc_clinic::where('id', $result->properties->clinic_id)->first();
                    if (!empty($clinic) && !empty($clinic->lng) && ($clinic->pref_id == 11 || $clinic->pref_id == 12 || $clinic->pref_id == 13 || $clinic->pref_id == 14)) {
                        //今回は1都3県のクリニックのみ処理
                        $stations = DB::table('stations')->whereRaw("MBRContains(GeomFromText('LineString(" . ($clinic->lng - $span) . " " . ($clinic->lat - $span) . "," . ($clinic->lng + $span) . " " . ($clinic->lat + $span) . ")'), `latlng`)")->get();
                    } else {
                        $stations = [];
                    }
                    //一致するクリニックが存在した場合に最寄駅を抽出
                    //存在しない場合は最終的に空の配列を飛ばす。

                    if (count($stations) !== 0) {
                        foreach ($stations as $station) {
                            //最寄駅が1件以上見つかった場合に処理

                            $h_station = Station::where('id', $station->id)->where('is_hubspot', 1)->first();
                            //下準備時にStationテーブルにis_hubspotカラムを追加。
                            //HubSpotに登録した駅情報と一致するものを抽出

                            if (!empty($h_station)) {
                                $st_concat = $st_concat . $h_station->id . ';';
                            }
                            //駅情報を「;」で連結
                            //"11111;11112;11113;11114"

                            $ln_concat = $ln_concat . $station->line_id . ';';
                            //路線情報を「;」で連結
                            //"100000;100001;100002;100003"
                        }
                    } else {
                        $this->info('[' . $key . ']' . $clinic->id . ' 最寄り駅が見つかりませんでした。');
                        Log::info('[' . $key . ']' . $clinic->id . ' 最寄り駅が見つかりませんでした。');
                    }


                    $up_endpoint = 'https://api.hubapi.com/crm/v3/objects/contacts/' . $result->id;
                    //HubSpot内のコンタクト情報をアップデートするエンドポイントを使用
                    $up_header = array(
                        "Content-Type: application/json",
                        "Authorization: Bearer " . env('HUBSPOT_APP_TOKEN')
                    );
                    $up_data = json_encode(array(
                        'properties'    => array('station_selection' => $st_concat, 'rosen' => $ln_concat),

                    ));
                    $up_options = array(
                        'http' => array(
                            'protocol_version' => '1.1',
                            'method' => 'PATCH',
                            //情報のアップデート時はPATCHメソッドを使用
                            'header' => $up_header,
                            'content' => $up_data,
                        )
                    );
                    $up_context = stream_context_create($up_options);
                    file_get_contents($up_endpoint, true, $up_context);
                    $this->info('[' . $key . ']' . $clinic->id . ' done');
                }
            }

            if (property_exists($response, 'paging') == false) {
                break;
            } else {
                $after = $response->paging->next->after;
            }
        }
    }
}
