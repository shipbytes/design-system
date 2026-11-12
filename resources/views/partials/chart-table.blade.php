{{--
    The values behind a chart, as a table a screen reader can walk, visually
    hidden. A chart's text alternative is a summary; this is the data, for a
    reader who wants the numbers rather than the shape. Opt-in on each chart
    through its `table` prop.

    $caption  what the table is (the chart's label or summary)
    $headers  the column headers: what a row is, then one per series
    $rows     a list per row: its name, then a value per series, every one
              already a formatted string
--}}
<table class="sr-only">
    <caption>{{ $caption }}</caption>
    <thead>
        <tr>
            @foreach ($headers as $header)
                <th scope="col">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <th scope="row">{{ $row[0] }}</th>
                @foreach (array_slice($row, 1) as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
