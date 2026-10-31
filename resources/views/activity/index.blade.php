@extends('voyager::master')

@section('page_title', __('voyager::activity.title'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="voyager-list"></i> {{ __('voyager::activity.title') }}
        </h1>
    </div>
@stop

@section('content')
    <div class="page-content container-fluid">
        @include('voyager::alerts')

        <div class="panel panel-bordered">
            <div class="panel-body">
                <form method="GET" action="{{ route('voyager.activity.index') }}" class="form-inline activity-filters">
                    <div class="form-group">
                        <label for="from">{{ __('voyager::activity.from') }}</label>
                        <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}">
                    </div>
                    <div class="form-group">
                        <label for="to">{{ __('voyager::activity.to') }}</label>
                        <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}">
                    </div>
                    <div class="form-group">
                        <label for="actor_id">{{ __('voyager::activity.actor_id') }}</label>
                        <input type="text" class="form-control" id="actor_id" name="actor_id" value="{{ $filters['actor_id'] }}">
                    </div>
                    <div class="form-group">
                        <label for="category">{{ __('voyager::activity.category') }}</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">{{ __('voyager::activity.all') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ __('voyager::activity.categories.'.$category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event">{{ __('voyager::activity.event') }}</label>
                        <select class="form-control" id="event" name="event">
                            <option value="">{{ __('voyager::activity.all') }}</option>
                            @foreach($events as $event)
                                <option value="{{ $event }}" @selected($filters['event'] === $event)>{{ __('voyager::activity.events.'.$event) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="result">{{ __('voyager::activity.result') }}</label>
                        <select class="form-control" id="result" name="result">
                            <option value="">{{ __('voyager::activity.all') }}</option>
                            @foreach($results as $result)
                                <option value="{{ $result }}" @selected($filters['result'] === $result)>{{ __('voyager::activity.results.'.$result) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="resource">{{ __('voyager::activity.resource') }}</label>
                        <input type="text" class="form-control" id="resource" name="resource" value="{{ $filters['resource'] }}">
                    </div>
                    <div class="form-group">
                        <label for="subject_type">{{ __('voyager::activity.subject_type') }}</label>
                        <input type="text" class="form-control" id="subject_type" name="subject_type" value="{{ $filters['subject_type'] }}">
                    </div>
                    <div class="form-group">
                        <label for="subject_id">{{ __('voyager::activity.subject_id') }}</label>
                        <input type="text" class="form-control" id="subject_id" name="subject_id" value="{{ $filters['subject_id'] }}">
                    </div>
                    <div class="form-group">
                        <label for="operation_id">{{ __('voyager::activity.operation_id') }}</label>
                        <input type="text" class="form-control" id="operation_id" name="operation_id" value="{{ $filters['operation_id'] }}">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('voyager::activity.apply') }}</button>
                    <a href="{{ route('voyager.activity.index') }}" class="btn btn-default">{{ __('voyager::activity.reset') }}</a>
                </form>
            </div>
        </div>

        <div class="panel panel-bordered">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>{{ __('voyager::activity.time') }}</th>
                            <th>{{ __('voyager::activity.actor') }}</th>
                            <th>{{ __('voyager::activity.action') }}</th>
                            <th>{{ __('voyager::activity.subject') }}</th>
                            <th>{{ __('voyager::activity.result') }}</th>
                            <th>{{ __('voyager::activity.resource') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            @php
                                $badge = ['success' => 'label-success', 'failure' => 'label-danger', 'denied' => 'label-warning', 'partial' => 'label-info'][$log->result] ?? 'label-default';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('voyager.activity.show', $log->id) }}">
                                        {{ $log->occurredAtInAppTimezone()?->format('Y-m-d H:i:s T') }}
                                    </a>
                                </td>
                                <td>
                                    {{ $log->actor_type ? $log->actor_type.' #'.$log->actor_id : __('voyager::activity.system') }}
                                </td>
                                <td>{{ __('voyager::activity.events.'.$log->event) }}</td>
                                <td>
                                    @if($log->subject_type)
                                        {{ $log->subject_type }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td><span class="label {{ $badge }}">{{ __('voyager::activity.results.'.$log->result) }}</span></td>
                                <td>{{ $log->resource ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">{{ __('voyager::activity.no_records') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="activity-pagination">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .activity-filters .form-group { margin-right: 8px; margin-bottom: 8px; }
        .activity-filters label { display: block; font-size: 12px; }
        .activity-pagination { display: flex; justify-content: flex-end; }
        @media (max-width: 767px) {
            .activity-filters .form-group, .activity-filters .form-control { width: 100%; }
        }
    </style>
@stop
