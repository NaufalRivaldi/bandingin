{{-- Rendered into article bodies via RichContentRenderer. Values come from live catalogue data. --}}
<table class="phone-comparison">
    <thead>
        <tr>
            <th></th>
            <th>{{ $left->name }}</th>
            <th>{{ $right->name }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <th>{{ $row['label'] }}</th>
                <td>{{ $row['left'] }}</td>
                <td>{{ $row['right'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
