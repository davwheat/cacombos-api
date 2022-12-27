<x-mail::message>
# Combos submitting

Someone ({{$fromUser ?? 'no email provided'}}) has submitted combos to mobilecombos.com.

<x-mail::table>
| Info | Data |
| --- | --- |
| Device name | {{ $deviceName }} |
| Device model | {{ $deviceModel }} |
| Firmware info | {{ $deviceFirmware }} |
| Comment | {{ $comment }} |
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
