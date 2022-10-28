
## BaseURL
- https://api.hubapi.com

## Search the CRM

CRM内のレコードを取得するために使用


- エンドポイント
```
POST /crm/v3/objects/{オブジェクトの種類}/search
```

基本的にはコンタクト情報のみ扱えれば問題ないため、その際のエンドポイントは下記
```
POST /crm/v3/objects/contacts/search
```

- ドキュメント
https://developers.hubspot.com/docs/api/crm/search

## CRM API / Object / Contacts

HubSpot内のコンタクトをIDで指定し、そのコンタクトのプロパティを更新するAPI

- エンドポイント
```
PATCH /crm/v3/objects/contacts/{contactId}
```

- ドキュメント
https://developers.hubspot.com/docs/api/crm/contacts

## Marketing API / List

HubSpot内のリストを操作するAPI。
※リストとはHubSpot内のコンタクトを任意の条件で絞り込み、名前付きのリストで管理できる機能。
※例えば特定の地域の顧客に対してコンテンツ配信を行いたい場合に、予め配信先のリストを作って運用する用途がある。

先述したAPIはv3が提供されているが、こちらの機能の提供は現時点でv1のみ。
v1のベースURLは下記の通り。
```
https://api.hubapi.com
```

### Get all contact lists

HubSpotのリストを全て取得する。

- エンドポイント
```
GET /contacts/v1/lists
```

- ドキュメント
https://legacydocs.hubspot.com/docs/methods/lists/get_lists

- レスポンス

結果の数（count）=1でリクエストした場合
```
{
  +"offset": 1
  +"lists": array:1 [
    0 => {#626
      +"portalId": 7493517
      +"listId": 999
      +"createdAt": 1615191430026
      +"updatedAt": 1615191619358
      +"name": "リスト名"
      +"listType": "DYNAMIC" //※(1)
      +"authorId": 11111111
      +"filters": []
      +"metaData": {#616
        +"size": 12
        +"lastSizeChangeAt": 1615541954258
        +"processing": "DONE"
        +"lastProcessingStateChangeAt": 1615191620201
        +"error": ""
        +"listReferencesCount": null
        +"parentFolderId": null
      }
      +"archived": false
      +"teamIds": []
      +"ilsFilterBranch": "{"filterBranchOperator":"OR","filters":[],"filterBranches":[{"filterBranchOperator":"AND","filters":[...省略],"filterBranches":[],"filterBranchType":"AND"}],"filterBranchType":"OR"}"
      +"limitExempt": false
      +"internal": false
      +"dynamic": true
      +"readOnly": false
    }
  ]
  +"has-more": true //※(2)
}

- has-moreがtrueの場合、offsetの値を渡して続けてリクエストすると結果の2ページ目以降を取得可能。

```


### Create a new contact list

HubSpot上に新規でリストを作成する。

- このAPIでID指定でのコンタクトを追加はできないが、一旦空のリストを作成して後から下記APIで追加することはできる。

- エンドポイント
```
POST /contacts/v1/lists
```

- ドキュメント
https://legacydocs.hubspot.com/docs/methods/lists/create_list


### Add existing contacts to a list

HubSpot上に存在するコンタクト情報をID指定でリストに追加する。
- IDは配列形式で最大500件まで指定可能

- エンドポイント
```
POST /contacts/v1/lists/{list_id}/add
```

- ドキュメント
https://legacydocs.hubspot.com/docs/methods/lists/add_contact_to_list


## サンプル

### PushStationAddLineToHubSpotContact

- HubSpotのCRM検索APIを使用し、ユーザータイプのプロパティにクリニックが入ったコンタクトを全て取得
- 取得したコンタクトのうち、予めクリニックIDプロパティに値を持たせているものだけ抽出
- for〜each...で取得した各コンタクトの最寄駅、路線を取得（予めローカルにSc_CLinicsテーブルとStationsテーブルを取り込み済み）
- コンタクト情報を更新するAPIを使用し、歯科医院のコンタクト情報の最寄駅・路線情報を更新

### ReadHubSpotList

- HubSpotの当アカウントで作成したリストを全て取得

### CreateHubSpotList

- HubSpot上のリスト作成をAPIを用いて行う。

### AddContactToList

- HubSpot上のコンタクトIDを指定し、そのコンタクトを任意のリストに追加する。




