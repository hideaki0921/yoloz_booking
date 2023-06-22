@extends('layouts.app_yoloz')

@section('content')

<div class="px-3">
    <div class="row text-center mb-3">
        <div class="col text-end">
            <form action="/calendar" method="post">
                @csrf
                <input type="hidden" name="selected_month" value="{{ $current_date->copy()->subMonthNoOverflow() }}">
                <button type="submit" class="btn p-0 calendar_arrow_btn">
                    <i class="fa-solid fa-circle-chevron-left"></i>
                </button>
            </form>
        </div>
        <div class="col fw-bold calendar_title_month">{{ $title_month }}</div>
        <div class="col text-start">
            <form action="/calendar" method="post">
                @csrf
                <input type="hidden" name="selected_month" value="{{ $current_date->copy()->addMonthNoOverflow() }}">
                <button type="submit" class="btn p-0 calendar_arrow_btn">
                    <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="calendar_wrap">
        <table class="calendar_table">
            <thead>
                <tr class="text-center calendar_tr">
                    <td class="py-2 color_red">Sun</td>
                    <td class="py-2">Mon</td>
                    <td class="py-2">Tue</td>
                    <td class="py-2">Wed</td>
                    <td class="py-2">thu</td>
                    <td class="py-2">Fri</td>
                    <td class="py-2 color_blue">Sat</td>
                </tr>
            </thead>
            <tbody class="text-center">
                @for ($day = 1; $day <= $days_in_month; $day++) @if ($day==1 || $start_of_month->dayOfWeek == 0)
                    <tr>
                        @endif

                        @if ($day == 1)
                        @for ($i = 0; $i < $start_of_month->dayOfWeek; $i++)
                            <td class="border-end border-bottom"></td>
                            @endfor
                            @endif

                            <td class="border-end border-bottom">
                                <button type="button" class="btn calendar_date_btn" data-bs-toggle="modal" data-bs-target="#timeSelectModal{{ $day }}">
                                    <span>{{ $day }}</span><br>
                                    <span><i class="fa-regular fa-circle calendar_circle_icon"></i></span>
                                </button>
                            </td>

                            <!-- Modal -->
                            <div class="modal fade" id="timeSelectModal{{ $day }}" tabindex="-1" aria-labelledby="" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $title_month }}{{ $day }}日</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @foreach($time_list as $time)
                                            <form action="/calendar/inputUserInfo" method="post">
                                                @csrf
                                                <input type="hidden" name="booking_date_time" value="{{ $input_year_month . $day . ' ' . $time['view_time'] }}">
                                                @if(in_array ($input_year_month . $day . ' ' . $time['hidden_time'] , $bookings))
                                                <button type="submit" class="btn time_select_btn_disabled" disabled>{{ $time['view_time'] }}</button>
                                                @else
                                                <button type="submit" class="btn time_select_btn">{{ $time['view_time'] }}</button>
                                                @endif
                                            </form>
                                            @endforeach
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($day == $days_in_month || $end_of_month->dayOfWeek == 6)
                    </tr>
                    @endif

                    @php
                    $start_of_month->addDay();
                    @endphp
                    @endfor
            </tbody>
        </table>
    </div>

    <div class="mt-5 text-center">------↓↓↓-----テスト用------↓↓↓-----</div>
    <div class="text-center">
        <div class="mt-3">Googleカレンダー</div>
        <form action="/calendar/registerGoogleCalendar" method="post" enctype="multipart/form-data">
            @csrf
            <input type="file" accept=".json" class="form-control" name="google_key_file">
            <button type="submit" class="btn btn_green btn_lg mt-3">登録</button>
        </form>
    </div>

    <div class="text-center mb-5">
        <div class="mt-5">Outlookカレンダー</div>
        <form action="/calendar/registerOutlookCalendar" method="post">
            @csrf
            <div class="mt-3">クライアントID</div>
            <input class="form-control" name="calendar_client_id">
            <div class="mt-3">テナントID</div>
            <input class="form-control" name="calendar_tenant_id">
            <div class="mt-3">クライアントSecret</div>
            <input class="form-control" name="calendar_secret">
            <button type="submit" class="btn btn_green btn_lg mt-3">登録</button>
        </form>
    </div>
</div>

@endsection