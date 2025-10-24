@extends('layouts.pdf')

@section('title', 'สรุปผลการประเมินตนเอง')

@section('content')
    <h3>รายงานสรุปหน่วยบริการสุขภาพผู้เดินทาง ปี {{ $filterYear }} รอบ {{ $filterRound }}</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>ชื่อหน่วยบริการ</th>
                <th>จังหวัด</th>
                <th>ระดับล่าสุด</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($serviceUnits as $i => $su)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $su->org_name }}</td>
                    <td>{{ optional($su->province)->title }}</td>
                    <td>
                        {{ optional($su->assessmentLevels->last())->level ?? '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
