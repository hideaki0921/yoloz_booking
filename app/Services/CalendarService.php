<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\GoogleCalendar\Event;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Microsoft\Graph\Graph;
use Illuminate\Support\Facades\Http;

class CalendarService
{
    //ユーザー情報登録
    public function insertUser($request, $line_id)
    {
        $user_id = DB::table('trn_users')
            ->insertGetId([
                'user_name'     => $request['user_name'],
                'email'         => $request['email'],
                'tel'           => $request['tel'],
                'age'           => $request['age'],
                'gender'        => $request['gender'],
                'zip_code'      => $request['zip_code'],
                'prefecture_id' => $request['prefecture'],
                'address'       => $request['address'],
                'line_id'       => $line_id,
            ]);

        return $user_id;
    }

    //予約情報登録
    public function insertBooking($request, $user_id)
    {
        DB::table('trn_bookings')
            ->insert([
                'user_id'      => $user_id,
                'booking_date' => $request['booking_date_time'],
            ]);
        return;
    }

    //LINE IDをもとにユーザー情報取得
    public function getUserWithLineId($line_id)
    {
        $user_data = DB::table('trn_users')
            ->where('line_id', $line_id)
            ->where('is_deleted', config('const.NOT_DELETED'))
            ->first();

        return $user_data;
    }

    //ユーザーIDをもとにユーザー情報取得
    public function getUserWithUserId($user_id)
    {
        $user_data = DB::table('trn_users')
            ->where('user_id', $user_id)
            ->where('is_deleted', config('const.NOT_DELETED'))
            ->first();

        return $user_data;
    }

    //都道府県マスタ取得
    public function getPrefecture()
    {
        $prefecture_data = DB::table('mst_prefecture')
            ->get();

        return $prefecture_data;
    }

    //予約情報取得
    public function getBooking()
    {
        $bookings_data = DB::table('trn_bookings')
            ->where('is_deleted', config('const.NOT_DELETED'))
            ->get();
        return $bookings_data;
    }

    // Googleカレンダー連携設定情報を登録する
    public function insertGoogleCalendar($organize_set_info)
    {
        DB::table('trn_google_calendar_setting')
            ->insert([
                'file_name'    => $organize_set_info['file_name'],
                'file_content' => $organize_set_info['file_content'],
            ]);

        return;
    }

    // Googleカレンダー連携設定情報を成形
    public function getGoogleCalendarConfigInfo()
    {
        // // セッションから情報を取得する
        // $sessionInfo = session()->all();
        // $companyId   = $sessionInfo['company_id'];   // 契約会社ID
        // $personId    = session()->pull('person_id'); // 担当者ID

        // // 配列に格納する
        // $sessionInfo = [
        //     'company_id' => $companyId,
        //     'person_id'  => $personId
        // ];

        // Googleカレンダー連携設定情報を取得する
        $google_calendar_set_info = $this->getGoogleCalendarSetInfo();
        $file_name = $google_calendar_set_info->file_name;

        // Google IDを取得する（従業員TBLから）!!!!!!自分のやつ仮置き!!!!!!!!
        $google_id = 'suwa.inaou@gmail.com';

        // Googleカレンダー連携情報を設定する（ファイルパスとGoogleユーザーIDのみの設定）
        $calendar_config_info =
            [
                'default_auth_profile' => env('GOOGLE_CALENDAR_AUTH_PROFILE', 'service_account'),
                'auth_profiles' => [
                    /*
                 * Authenticate using a service account.
                 */
                    'service_account' => [
                        /*
                     * Path to the json file containing the credentials.
                     */
                        'credentials_json' => storage_path('app/json/' . $file_name),
                    ],
                    /*
                 * Authenticate with actual google user account.
                 */
                    'oauth' => [
                        /*
                     * Path to the json file containing the oauth2 credentials.
                     */
                        'credentials_json' => storage_path('app/google-calendar/oauth-credentials.json'),
                        /*
                     * Path to the json file containing the oauth2 token.
                     */
                        'token_json' => storage_path('app/google-calendar/oauth-token.json'),
                    ],
                ],
                /*
             *  The id of the Google Calendar that will be used by default.
             */
                'calendar_id' => $google_id, // GoogleのユーザーID（例：---@gmail.com）
                /*
             *  The email address of the user account to impersonate.
             */
                'user_to_impersonate' => env('GOOGLE_CALENDAR_IMPERSONATE'),
            ];

        // Googleカレンダー設定情報を返す
        return $calendar_config_info;
    }

    // Googleカレンダー連携設定情報を取得する
    public function getGoogleCalendarSetInfo()
    {
        $calendar_set_info = DB::table('trn_google_calendar_setting')
            ->where('is_deleted', config('const.NOT_DELETED'))
            ->first();

        return $calendar_set_info;
    }

    // Googleカレンダーイベント情報取得
    public function getGoogleCalendarEvent()
    {
        // Googleカレンダー連携情報を取得する（Googleカレンダー設定テーブル）
        $google_calendar_set_info = $this->getGoogleCalendarSetInfo();

        // jsonファイル名、内容を取得する
        $file_content = $google_calendar_set_info->file_content;
        $file_content = str_replace("\n", '', $file_content);
        $file_name    = $google_calendar_set_info->file_name;

        // JSONファイルをストレージ（storage\app\json\）に追加する
        Storage::put('json\\' . $file_name, $file_content);

        // 日時を設定する
        $now_date_time   = Carbon::now();                                           // 現在日時を取得する
        $date_time_end   = $now_date_time->copy()->endOfDay()->toDateTimeString();    // 当日の23時59分59秒
        $date_time_start = $now_date_time->copy()->subMinute(30)->toDateTimeString(); // 現時刻の30分前を取得
        $start_dt      = new Carbon($date_time_start);                              // 開始日時
        $end_dt        = new Carbon($date_time_end);                                // 終了日時

        $query_parameters = [];

        // イベント情報を取得する（引数： 開始日時、終了日時、オプション-文字列検索等、ユーザーID）
        $events = Event::get($start_dt, $end_dt, $query_parameters, null);
        $event_array = [];

        // 各イベント情報を配列に格納する
        foreach ($events as $event) {

            // 日時の文字形式を整理する
            $start_time = substr($event->start->dateTime, 0, -6);
            $end_time   = substr($event->end->dateTime, 0, -6);

            $event_array[] = [
                'id'           => $event->id,          // カレンダーID
                'subject'      => $event->summary,     // タイトル
                'body_preview' => $event->description, // 説明文
                'start_time'   => $start_time,          // 開始日時
                'end_time'     => $end_time             // 終了日時
            ];
        }

        // JSONファイルをストレージから削除する
        Storage::delete('json\\' . $file_name);

        // 担当者のイベント情報を返す
        return $event_array;
    }

    //Googleカレンダーに予約情報登録
    public function registerGoogleCalendarEvent($request, $user_data)
    {
        // Googleカレンダー連携情報を取得する（Googleカレンダー設定テーブル）
        $google_calendar_set_info = $this->getGoogleCalendarSetInfo();

        // jsonファイル名、内容を取得する
        $file_content = $google_calendar_set_info->file_content;
        $file_content = str_replace("\n", '', $file_content);
        $file_name    = $google_calendar_set_info->file_name;

        // JSONファイルをストレージ（storage\app\json\）に追加する
        Storage::put('json\\' . $file_name, $file_content);

        $dt = new Carbon($request['booking_date_time']);
        $event = new Event;
        $event->name = $user_data->user_name . ' 様';
        $event->startDateTime = $dt;
        $event->endDateTime = $dt->addHour();   // 1時間後
        $event->description = "氏名 : " . $user_data->user_name . "\nメールアドレス : " . $user_data->email . "\n電話番号 : " . $user_data->tel;
        $new_event = $event->save();

        // JSONファイルをストレージから削除する
        Storage::delete('json\\' . $file_name);
    }

    // Outlookカレンダー連携設定情報を登録する
    public function insertOutlookCalendar($organize_set_info)
    {
        DB::table('trn_outlook_calendar_setting')
            ->insert([
                'client_id'     => $organize_set_info['client_id'],
                'client_secret' => $organize_set_info['client_secret'],
                'tenant_id'     => $organize_set_info['tenant_id'],
            ]);

        return;
    }

    //Outlookカレンダーイベント情報取得
    public function getOutlookCalendarEvent()
    {
        // Outlookカレンダー連携情報を取得する
        $calendar_set_info = $this->getOutlookCalendarSetInfo();

        // Outlookユーザー情報を取得する
        // $outlookUserInfo = $this->getOutlookUserInfo($params);

        // アクセストークンを取得する
        $access_token = $this->getOutlookCalendarAccessToken($calendar_set_info);

        // 取得したデータを整理する
        $events        = '';
        $tenant_id     = (isset($calendar_set_info->tenant_id)) ? $calendar_set_info->tenant_id : '';
        $client_id     = (isset($calendar_set_info->client_id)) ? $calendar_set_info->client_id : '';
        $client_secret = (isset($calendar_set_info->client_secret)) ? $calendar_set_info->client_secret : '';
        // $user_id       = (isset($outlookUserInfo['teams_user_id'])) ? $outlookUserInfo['teams_user_id'] : '';
        //!!!!!!自分のやつ仮置き!!!!!!!!
        $user_id       = 'suwa.h-test@2dy2nf.onmicrosoft.com';
        $event_array    = [];
        $now_date_time   = Carbon::now();                                        // 現在日時を取得する
        // $date_time_start = Carbon::today()->toDateTimeString();                  // 当日の0時0分0秒
        $date_time_end   = $now_date_time->copy()->endOfDay()->toDateTimeString(); // 当日の23時59分59秒

        // $now_date_time = Carbon::now(); // 現在日時を取得する
        // $date_time_end = $now_date_time->copy()->addMinute(30)->toDateTimeString(); // 現時刻の30分後を取得
        $date_time_start = $now_date_time->copy()->subMinute(30)->toDateTimeString();   // 現時刻の30分前を取得

        // 日時情報を編集する
        $date_time_start = str_replace(' ', 'T', $date_time_start);
        $date_time_end   = str_replace(' ', 'T', $date_time_end);
        $date_time_start = "'" . $date_time_start . "'";
        $date_time_end   = "'" . $date_time_end . "'";

        // // 取得した情報が一つでも存在しない時
        if ($user_id == '' || $tenant_id == '' || $client_id == '' || $client_secret == '') {
            return false;
        }

        // Graphインスタンスを生成する
        $graph = new Graph();
        $graph->setAccessToken($access_token);

        // URIを作成する
        $url_time_filter = "?" . '$filter' . "=start/dateTime+ge+" . "$date_time_start" . "+and+end/dateTime+le+" . $date_time_end;
        $url_select      = '$select' . "=subject,id,bodyPreview,start,end";
        $url_calendar    = "/users/" . $user_id . "/events" . $url_time_filter . "&" . $url_select;

        // イベント情報を取得する
        $event  = $graph->createRequest("GET", $url_calendar)->addHeaders(["Prefer" => 'outlook.timezone="Tokyo Standard Time"'])->execute();
        $events = $event->getBody();

        // 取得したイベント情報を整理する（配列に格納する）
        foreach ($events['value'] as $key => $value) {
            $event_array[$key]['id']          = $value['id'];
            $event_array[$key]['subject']     = $value['subject'];
            $event_array[$key]['body_preview'] = $value['body_preview'];
            $event_array[$key]['start_time']   = $value['start']['dateTime'];
            $event_array[$key]['end_time']     = $value['end']['dateTime'];
        }

        // 担当者のイベント情報を返す
        return $event_array;
    }

    //Outlookカレンダー連携情報を取得
    public function getOutlookCalendarSetInfo()
    {
        $calendar_set_info = DB::table('trn_outlook_calendar_setting')
            ->where('is_deleted', config('const.NOT_DELETED'))
            ->first();

        return $calendar_set_info;
    }

    // Outlookアクセストークンを取得する
    public function getOutlookCalendarAccessToken($calendar_set_info)
    {
        // 取得したデータを整理する
        $access_token   = '';
        $tenant_id     = (isset($calendar_set_info->tenant_id)) ? $calendar_set_info->tenant_id : '';
        $client_id     = (isset($calendar_set_info->client_id)) ? $calendar_set_info->client_id : '';
        $client_secret = (isset($calendar_set_info->client_secret)) ? $calendar_set_info->client_secret : '';

        // 取得した情報が一つでも存在しない時
        if ($tenant_id == '' || $client_id == '' || $client_secret == '') {
            return false;
        }

        // guzzleインスタンス生成
        $guzzle = new \GuzzleHttp\Client();

        // URIを作成する
        $url_access_token = 'https://login.microsoftonline.com/' . $tenant_id . '/oauth2/v2.0/token';

        // アクセストークンを取得する
        $token = json_decode($guzzle->post($url_access_token, [
            'form_params' => [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'scope'         => 'https://graph.microsoft.com/.default',
                'grant_type'    => 'client_credentials',
            ],
        ])->getBody()->getContents());
        $access_token = $token->access_token;

        // アクセストークンを返す
        return $access_token;
    }

    //Outlookカレンダーに予約情報登録
    public function registerOutlookCalendarEvent($request, $user_data)
    {
        // Outlookカレンダー連携情報を取得する（Outlookカレンダー設定テーブル）
        $calendar_set_info = $this->getOutlookCalendarSetInfo();

        // アクセストークンを取得する
        $access_token = $this->getOutlookCalendarAccessToken($calendar_set_info);

        // Outlookユーザー情報を取得する
        // $outlookUserInfo = $this->getOutlookUserInfo($params);
        // $user_id       = (isset($outlookUserInfo['teams_user_id'])) ? $outlookUserInfo['teams_user_id'] : '';
        //!!!!!!自分のやつ仮置き!!!!!!!!
        $user_id       = 'suwa.h-test@2dy2nf.onmicrosoft.com';

        $booking_date_time = new Carbon($request['booking_date_time']);

        $start_date = $booking_date_time->copy()->format('Y-m-d\TH:i:s');
        $end_date = $booking_date_time->copy()->addHour()->format('Y-m-d\TH:i:s');

        // イベントの情報を設定する
        $eventData = [
            'subject' => $user_data->user_name . ' 様',
            'body' => [
                'contentType' => 'HTML',
                'content' => "氏名 : " . $user_data->user_name . "\nメールアドレス : " . $user_data->email . "\n電話番号 : " . $user_data->tel
            ],
            'start' => [
                'dateTime' => $start_date,
                'timeZone' => 'Tokyo Standard Time'
            ],
            'end' => [
                'dateTime' => $end_date,
                'timeZone' => 'Tokyo Standard Time'
            ]
        ];

        // イベントを作成する
        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $url = "/users/{$user_id}/events";
        $created_event = $graph->createRequest("POST", $url)
            ->attachBody($eventData)
            ->addHeaders(["Prefer" => 'outlook.timezone="Tokyo Standard Time"'])
            ->execute();

        // $created_event_data = $created_event->getBody();

        return;
    }
}
