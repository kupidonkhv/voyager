@extends('voyager::master')

@section('page_title', __('voyager::activity.event_details'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="voyager-list"></i> {{ __('voyager::activity.event_details') }}
        </h1>
        <a href="{{ route('voyager.activity.index') }}" class="btn btn-warning">
            <i class="glyphicon glyphicon-list"></i> {{ __('voyager::activity.back_to_list') }}
        </a>
    </div>
@stop

@section('content')
    <div class="page-content container-fluid">
        @include('voyager::alerts')

        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('voyager::activity.events.'.$log->event) }}</h3>
                    </div>
                    <div class="panel-body">
                        <dl class="dl-horizontal">
                            <dt>{{ __('voyager::activity.time') }}</dt>
                            <dd>{{ $log->occurredAtInAppTimezone()?->format('Y-m-d H:i:s T') }} <small>({{ __('voyager::activity.timezone_note', ['timezone' => config('app.timezone', 'UTC')]) }})</small></dd>

                            <dt>{{ __('voyager::activity.category') }}</dt>
                            <dd>{{ __('voyager::activity.categories.'.$log->category) }}</dd>

                            <dt>{{ __('voyager::activity.result') }}</dt>
                            <dd>{{ __('voyager::activity.results.'.$log->result) }}</dd>

                            <dt>{{ __('voyager::activity.actor') }}</dt>
                            <dd>{{ $log->actor_type ? $log->actor_type.' #'.$log->actor_id : __('voyager::activity.system') }}</dd>

                            <dt>{{ __('voyager::activity.subject') }}</dt>
                            <dd>
                                @if($log->subject_type)
                                    {{ $log->subject_type }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}
                                    @if($subjectLabel)
                                        <br><small>{{ $subjectLabel }}</small>
                                    @elseif($log->subject_id)
                                        <br><small>{{ __('voyager::activity.deleted_subject', ['type' => $log->subject_type, 'id' => $log->subject_id]) }}</small>
                                    @endif
                                @else
                                    &mdash;
                                @endif
                            </dd>

                            <dt>{{ __('voyager::activity.resource') }}</dt>
                            <dd>{{ $log->resource ?: '—' }}</dd>
                        </dl>

                        <h4>{{ __('voyager::activity.changed_fields') }}</h4>
                        @if(!empty($log->changed_fields))
                            <ul class="list-unstyled">
                                @foreach($log->changed_fields as $field)
                                    <li><code>{{ $field }}</code></li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted">{{ __('voyager::activity.no_changed_fields') }}</p>
                        @endif

                        <h4>{{ __('voyager::activity.metadata') }}</h4>
                        @if(!empty($log->metadata))
                            <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            <p class="text-muted">{{ __('voyager::activity.no_metadata') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('voyager::activity.technical_context') }}</h3>
                    </div>
                    <div class="panel-body">
                        @if($canViewContext)
                            <dl class="dl-horizontal">
                                <dt>{{ __('voyager::activity.source') }}</dt>
                                <dd>{{ $log->source }}</dd>
                                <dt>{{ __('voyager::activity.ip_address') }}</dt>
                                <dd>{{ $log->ip_address ?: '—' }}</dd>
                                <dt>{{ __('voyager::activity.route_name') }}</dt>
                                <dd>{{ $log->route_name ?: '—' }}</dd>
                                <dt>{{ __('voyager::activity.request_id') }}</dt>
                                <dd><code>{{ $log->request_id ?: '—' }}</code></dd>
                                <dt>{{ __('voyager::activity.operation_id') }}</dt>
                                <dd>
                                    @if($log->operation_id)
                                        <a href="{{ route('voyager.activity.index', ['operation_id' => $log->operation_id]) }}"><code>{{ $log->operation_id }}</code></a>
                                    @else
                                        —
                                    @endif
                                </dd>
                                <dt>{{ __('voyager::activity.event_uuid') }}</dt>
                                <dd><code>{{ $log->event_uuid }}</code></dd>
                                <dt>{{ __('voyager::activity.recorded_at') }}</dt>
                                <dd>{{ $log->recordedAtInAppTimezone()?->format('Y-m-d H:i:s T') }}</dd>
                                <dt>{{ __('voyager::activity.schema_version') }}</dt>
                                <dd>{{ $log->schema_version }}</dd>
                            </dl>
                        @else
                            <p class="text-muted">{{ __('voyager::activity.context_forbidden') }}</p>
                        @endif
                    </div>
                </div>

                @if($log->operation_id)
                    <a href="{{ route('voyager.activity.index', ['operation_id' => $log->operation_id]) }}" class="btn btn-default btn-block">
                        {{ __('voyager::activity.open_operation') }}
                    </a>
                @endif

                @if($log->subject_type && $log->subject_id)
                    <a href="{{ route('voyager.activity.index', ['subject_type' => $log->subject_type, 'subject_id' => $log->subject_id]) }}" class="btn btn-default btn-block">
                        {{ __('voyager::activity.open_subject') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
@stop
