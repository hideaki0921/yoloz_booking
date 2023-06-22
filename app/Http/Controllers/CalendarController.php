<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\CalendarService;

class CalendarController extends Controller
{
    protected $service;

    public function __construct(CalendarService $service)
    {
        $this->service = $service;
    }

    //TOP画面表示
    public function index(Request $request)
    {
        if ($request['selected_month']) {
            $current_date = new Carbon($request['selected_month']);
        } else {
            $current_date = Carbon::now();
        }

        $title_year = $current_date->copy()->format('Y年');
        $title_month = $current_date->copy()->format('n月');
        $days_in_month = $current_date->copy()->daysInMonth;
        $start_of_month = $current_date->copy()->startOfMonth();
        $end_of_month = $current_date->copy()->endOfMonth();
        $input_year_month = $current_date->copy()->format('Y/m/');

        //予約情報取得
        $bookings_data = $this->service->getBooking();

        //日付フォーマット変更
        foreach ($bookings_data as $booking) {
            $book = new Carbon($booking->booking_date);
            $bookings[] = $book->copy()->format('Y/m/d H:i:s');
        }

        //予約可能時間仮置き
        $times = [
            '10:00',
            '11:00',
            '12:00',
            '13:00',
            '14:00',
            '15:00',
            '16:00',
            '17:00',
            '18:00',
        ];

        //画面表示用、inputのvalue用に整頓
        $time_list[] = [
            'view_time'   => '',
            'hidden_time' => '',
        ];

        for ($i = 0; $i < count($times); $i++) {
            $time_list[$i]['view_time'] = $times[$i];
            $time_list[$i]['hidden_time'] = $times[$i] . ':00';
        }

        return view('user.calendar', [
            'current_date'     => $current_date,
            'title_year'       => $title_year,
            'title_month'      => $title_month,
            'start_of_month'   => $start_of_month,
            'end_of_month'     => $end_of_month,
            'days_in_month'    => $days_in_month,
            'time_list'        => $time_list,
            'input_year_month' => $input_year_month,
            'bookings'         => $bookings,

        ]);
    }

    //ユーザー情報入力画面表示
    public function inputUserInfo(Request $request)
    {
        //日付フォーマット変更
        $booking_date_time = new Carbon($request['booking_date_time']);
        $booking_date_time->format('Y/m/d H:i:s');
        $booking_date_time_title = $booking_date_time->copy()->format('Y年n月d日 H:i');

        //セッションからLINE IDを取得
        $line_id =  session('line_id');

        //LINE IDをもとにユーザー情報取得
        $user_data = $this->service->getUserWithLineId($line_id);

        //都道府県マスタ取得
        $prefecture_data = $this->service->getPrefecture();

        return view('user.inputUserInfo', [
            'booking_date_time'       => $booking_date_time,
            'booking_date_time_title' => $booking_date_time_title,
            'prefecture_data'         => $prefecture_data,
            'user_data'               => $user_data,
        ]);
    }

    //予約確定
    public function registerBooking(Request $request)
    {
        //セッションからLINE IDを取得
        $line_id =  session('line_id');

        //LINE IDをもとにユーザー情報取得
        $user_data = $this->service->getUserWithLineId($line_id);

        if ($user_data) {  //予約情報が存在する場合
            $user_id = $user_data->user_id;
        } else {           //存在しない場合は新規登録
            $user_id = $this->service->insertUser($request, $line_id);
        }

        //予約テーブルにインサート
        $this->service->insertBooking($request, $user_id);

        //ユーザーIDをもとにユーザー情報取得
        $user_data = $this->service->getUserWithUserId($user_id);

        $this->service->registerGoogleCalendarEvent($request, $user_data);

        //Outlookカレンダーに予約情報登録
        $this->service->registerOutlookCalendarEvent($request, $user_data);

        return redirect('/calendar');
    }

    //Googleカレンダー連携情報登録
    public function registerGoogleCalendar(Request $request)
    {
        // ファイル名を取得する
        $file_name = $request['google_key_file']->getClientOriginalName();

        // jsonファイルを取得する
        $uploaded_file = $request->file('google_key_file');
        $file_path     = $request->file('google_key_file')->path($uploaded_file);
        $file_content   = trim(file_get_contents($file_path));
        $file_content   = mb_convert_encoding($file_content, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');

        // 登録する情報を配列に格納する
        $organize_set_info = [
            'file_name'    => $file_name,    // ファイル名
            'file_content' => $file_content, // ファイル内容
        ];

        // Googleカレンダー連携設定情報を登録する
        $this->service->insertGoogleCalendar($organize_set_info);

        return redirect('/calendar');
    }

    //Outlookカレンダー連携情報登録
    public function registerOutlookCalendar(Request $request)
    {
        // カレンダー連携設定情報の整理
        $organize_set_info = [
            'client_id'     => $request['calendar_client_id'],
            'client_secret' => $request['calendar_secret'],
            'tenant_id'     => $request['calendar_tenant_id'],
        ];

        // Outlookカレンダー連携設定情報を登録する
        $this->service->insertOutlookCalendar($organize_set_info);

        return redirect('/calendar');
    }
}
