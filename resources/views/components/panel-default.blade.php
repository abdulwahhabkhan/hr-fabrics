<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title ?? 'Default Panel' }}</h4>
        @if($top_nav ?? '')
            <div class="pull-right">
                {{$top_nav}}
            </div>
        @endif
    </div>
    <div class="panel-body">
        {{ $slot }}
    </div>
</div>
