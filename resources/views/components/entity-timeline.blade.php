@foreach($timeline as $entry)
    <article class="media">
        <figure class="media-left">
            {{ $entry->emoji }}
        </figure>
        <div class="media-content">
            <div class="content">
                @if($entry->actor)
                    <strong>{{ $entry->actor->full_name }}</strong>
                    <br />
                @endif
                <span>
                    @if($entry->link)
                        <a href="{{ $entry->link }}">{{ $entry->description }}</a>
                    @else
                        {{ $entry->description }}
                    @endif
                    • <small title="{{ $entry->time->format('M jS Y h:ia') }}">{{ $entry->time->diffForHumans() }}</small></span>
            </div>
        </div>
    </article>
@endforeach
