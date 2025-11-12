@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{ config('app.url') }}/assets/images/permata-logo.png" class="logo" alt="EcoSteps PermataGreen" style="height: 50px; max-height: 50px;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
