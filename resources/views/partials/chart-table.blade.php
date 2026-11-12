{{--
    The values behind a chart, as a table a screen reader can walk, visually
    hidden. A chart's text alternative is a summary; this is the data, for a
    reader who wants the numbers rather than the shape. Opt-in on each chart
    through its `table` prop.

    $caption  what the table is (the chart's label or summary)
    $rows     list of [label, value], both already formatted strings
    $headers  the two column headers
--}}
<table class="sr-only">
    <caption>{{ $caption }}</caption>
    <thead>
        <tr>
            <th scope="col">{{ $headers[0] }}</th>
            <th scope="col">{{ $headers[1] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as [$name, $value])
            <tr>
                <th scope="row">{{ $name }}</th>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
