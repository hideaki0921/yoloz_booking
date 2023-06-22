@extends('layouts.app_yoloz')

@section('content')

<div class="px-5">

    <form action="/calendar/registerBooking" method="post">
        @csrf

        <input type="hidden" name="booking_date_time" value="{{ $booking_date_time }}">

        <div class="text-center fw-bold">ご予約日:{{ $booking_date_time_title }}</div>

        <div class="fw-bold mb-1 mt-3">氏名</div>
        <input type="text" name="user_name" class="form-control" @if($user_data) value="{{ $user_data->user_name }}" @endif>

        <div class="fw-bold mb-1 mt-3">メールアドレス</div>
        <input type="email" name="email" class="form-control" @if($user_data) value="{{ $user_data->email }}" @endif>

        <div class="fw-bold mb-1 mt-3">電話番号</div>
        <input type="tel" name="tel" class="form-control" @if($user_data) value="{{ $user_data->tel }}" @endif>

        <div class="fw-bold mb-1 mt-3">年齢</div>
        <input type="number" name="age" class="form-control" @if($user_data) value="{{ $user_data->age }}" @endif>

        <div class="fw-bold mb-1 mt-3">性別</div>
        <select name="gender" class="form-select">
            @if($user_data)
            <option value="" selected>選択してください</option>
            @endif
            <option value="男性" @if($user_data && $user_data->gender == "男性") selected @endif>男性</option>
            <option value="女性" @if($user_data && $user_data->gender == "女性") selected @endif>女性</option>
            <option value="その他" @if($user_data && $user_data->gender == "その他") selected @endif>その他</option>
        </select>

        <div class="fw-bold mb-1 mt-3">郵便番号(ハイフンなし)</div>
        <input type="number" name="zip_code" class="form-control" @if($user_data) value="{{ $user_data->zip_code }}" @endif>

        <div class="fw-bold mb-1 mt-3">都道府県</div>
        <select name="prefecture" class="form-select">
            @if($user_data)
            <option value="" selected>選択してください</option>
            @endif
            @foreach($prefecture_data as $prefecture)
            <option value="{{ $prefecture->prefecture_id }}" @if($user_data && $user_data->prefecture_id == $prefecture->prefecture_id) selected @endif>{{ $prefecture->prefecture_name }}</option>
            @endforeach
        </select>

        <div class="fw-bold mb-1 mt-3">市区町村、番地等</div>
        <input type="text" name="address" class="form-control" @if($user_data) value="{{ $user_data->address }}" @endif>

        <div class="text-center mt-5">
            <button type="submit" class="btn btn_green btn_lg">予約確定</button>
        </div>
        <div class="text-center mt-4 mb-5">
            <a href="/calendar" class="btn btn-secondary btn_lg">戻る</a>
        </div>

    </form>

</div>

@endsection